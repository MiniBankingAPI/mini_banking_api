<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SaldoController
{
    private function get_data() {
        return @new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
    }

    // GET /accounts/{idAccount}/balance
    public function get_balance(Request $request, Response $response, $args){
        $mysqli = $this->get_data();
        $accountId = (int)$args['idAccount']; 

        $check = $mysqli->query("SELECT id FROM accounts WHERE id = $accountId");
        if (!$check || $check->num_rows === 0) {
            $response->getBody()->write(json_encode(['error' => 'Account not found']));
            return $response->withHeader("Content-type", "application/json")->withStatus(404);
        }

        $result = $mysqli->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = $accountId");
        $row = $result->fetch_assoc();
        $balance = (float)($row['balance'] ?? 0.00);

        $response->getBody()->write(json_encode([
            'account_id' => $accountId,
            'balance' => $balance
        ]));
        return $response->withHeader("Content-type", "application/json")->withStatus(200);
    }


    // GET /accounts/{idAccount}/balance/convert/fiat?to=USD
    public function convert_to_fiat(Request $request, Response $response, $args){
        $accountId = (int)$args['idAccount'];
        $mysqli = $this->get_data(); 
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            $response->getBody()->write(json_encode([
                'error' => 'Missing target currency'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $stmt = $mysqli->prepare('SELECT id, currency FROM accounts WHERE id = ?');
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $account = $result->fetch_assoc();

        if (!$account) {
            $response->getBody()->write(json_encode([
                'error' => 'Account not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $from = strtoupper($account['currency']);

        $stmt = $mysqli->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS balance
            FROM transactions
            WHERE account_id = ?
        ");
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $balance = (float)($row['balance'] ?? 0);

        $url = "https://api.frankfurter.dev/v1/latest?base={$from}&symbols={$to}";
        $json = @file_get_contents($url);

        if ($json === false) {
            $response->getBody()->write(json_encode([
                'error' => 'External exchange API unavailable'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(502);
        }

        $data = json_decode($json, true);

        if (!isset($data['rates'][$to])) {
            $response->getBody()->write(json_encode([
                'error' => 'Target currency not supported'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $rate = (float)$data['rates'][$to];
        $converted = round($balance * $rate, 2);

        $response->getBody()->write(json_encode([
            'account_id' => $accountId,
            'provider' => 'Frankfurter',
            'conversion_type' => 'fiat',
            'from_currency' => $from,
            'to_currency' => $to,
            'original_balance' => $balance,
            'converted_balance' => $converted,
            'rate' => $rate,
            'date' => $data['date'] ?? null
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    // GET /accounts/{idAccount}/balance/convert/crypto?to=BTC
    public function convert_to_crypto(Request $request, Response $response, $args) {
        $mysqli = $this->get_data();
        $accountId = (int)($args['idAccount'] ?? 0);
        
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            $response->getBody()->write(json_encode(['error' => 'Missing target crypto']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $mysqli->prepare('SELECT currency FROM accounts WHERE id = ?');
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if (!$account) {
            $response->getBody()->write(json_encode(['error' => 'Account not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $from = strtoupper($account['currency']);

        $stmt = $mysqli->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS balance 
            FROM transactions 
            WHERE account_id = ?
        ");
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $balance = (float)($stmt->get_result()->fetch_assoc()['balance'] ?? 0);

        $marketSymbol = $to . $from; 
        $url = "https://binance.com/api/v3/ticker/price?symbol=" . $marketSymbol;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $json = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);


            $response->getBody()->write(json_encode([
                'error' => "Market pair {$marketSymbol} not supported on Binance",
                'url_tested' => $url 
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
        

        $data = json_decode($json, true);
        $price = (float)($data['price'] ?? 0);

        if ($price <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid price from Binance']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
        }

        $converted = round($balance / $price, 8);

        $response->getBody()->write(json_encode([
            'account_id' => $accountId,
            'provider' => 'Binance',
            'conversion_type' => 'crypto',
            'from_currency' => $from,
            'to_crypto' => $to,
            'market_symbol' => $marketSymbol,
            'original_balance' => $balance,
            'price' => $price,
            'converted_amount' => $converted
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }


}
