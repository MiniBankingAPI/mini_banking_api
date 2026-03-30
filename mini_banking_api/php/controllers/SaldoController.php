<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SaldoController
{
    private function get_data() {
        return new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
    }

    // GET /accounts/{idAccount}/balance
    public function get_balance(Request $request, Response $response, $args){
        $mysqli = $this->get_data();
        $id = (int)$args['idAccount'];

        // controllo esistenza conto
        $check = $mysqli->query("SELECT id FROM accounts WHERE id = $id");
        if ($check->num_rows === 0) {
            $response->getBody()->write(json_encode(['error' => 'Account not found']));
            return $response->withHeader("Content-type", "application/json")->withStatus(404);
        }

        $result = $mysqli->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = $id");
        $row = $result->fetch_assoc();
        $balance = (float)($row['balance'] ?? 0.00);

        $response->getBody()->write(json_encode([
            'account_id' => $id,
            'balance' => $balance
        ]));
        return $response->withHeader("Content-type", "application/json")->withStatus(200);
    }

    // GET /accounts/{idAccount}/balance/convert/fiat?to=USD
    public function convert_to_fiat(Request $request, Response $response, $args){
        $id = (int)$args['idAccount'];
        $mysqli = $this->get_data();
        
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            $response->getBody()->write(json_encode(['error' => 'Missing target currency']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // recupero valuta base del conto
        $stmt = $mysqli->prepare('SELECT currency FROM accounts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $acc = $stmt->get_result()->fetch_assoc();

        if (!$acc) {
            $response->getBody()->write(json_encode(['error' => 'Account not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $from = strtoupper($acc['currency']);

        // calcolo saldo
        $res = $mysqli->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = $id");
        $balance = (float)($res->fetch_assoc()['balance'] ?? 0);

        // chiamata reale a Frankfurter
        $url = "https://api.frankfurter.dev{$from}&symbols={$to}";
        $json = @file_get_contents($url);

        if ($json === false) {
            $response->getBody()->write(json_encode(['error' => 'Exchange API unavailable or invalid currency']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
        }

        $data = json_decode($json, true);
        $rate = (float)($data['rates'][$to] ?? 0);

        if (!$rate) {
            $response->getBody()->write(json_encode(['error' => 'Currency not supported']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $response->getBody()->write(json_encode([
            'account_id' => $id,
            'provider' => 'Frankfurter',
            'conversion_type' => 'fiat',
            'from_currency' => $from,
            'to_currency' => $to,
            'original_balance' => $balance,
            'rate' => $rate,
            'converted_balance' => round($balance * $rate, 2),
            'date' => $data['date']
        ]));
        return $response->withHeader("Content-type", "application/json")->withStatus(200);
    }

    // GET /accounts/{idAccount}/balance/convert/crypto?to=BTC
    public function convert_to_crypto(Request $request, Response $response, $args) {
        $id = (int)$args['idAccount'];
        $mysqli = $this->get_data();
        
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            $response->getBody()->write(json_encode(['error' => 'Missing target crypto']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $mysqli->prepare('SELECT currency FROM accounts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $acc = $stmt->get_result()->fetch_assoc();

        if (!$acc) {
            $response->getBody()->write(json_encode(['error' => 'Account not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $from = strtoupper($acc['currency']);

        $res = $mysqli->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = $id");
        $balance = (float)($res->fetch_assoc()['balance'] ?? 0);

        // costruzione Market Symbol Binance: CRYPTO + FIAT (es. BTCEUR)
        $marketSymbol = $to . $from;
        $url = "https://api.binance.com" . $marketSymbol;
        $json = @file_get_contents($url);

        if ($json === false) {
            $response->getBody()->write(json_encode(['error' => "Market pair {$marketSymbol} not supported on Binance"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
        }

        $data = json_decode($json, true);
        $price = (float)($data['price'] ?? 0);

        $response->getBody()->write(json_encode([
            'account_id' => $id,
            'provider' => 'Binance',
            'conversion_type' => 'crypto',
            'from_currency' => $from,
            'to_crypto' => $to,
            'market_symbol' => $marketSymbol,
            'original_balance' => $balance,
            'price' => $price,
            'converted_amount' => ($price > 0) ? round($balance / $price, 8) : 0
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
