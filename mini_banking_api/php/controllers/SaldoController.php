<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SaldoController
{
    private function get_data() {
        return new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
    }

    //GET /accounts/1/balance per ottenere il saldo attuale
    public function get_balance(Request $request, Response $response, $args){

        $mysqli_connection = $this->get_data();

        $id = $args['idAccount'];
        // Saldo = Sommadepositi - Sommaprelievi =  sum(amount)
        $result = $mysqli_connection->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = '$id'");

        $row = $result->fetch_assoc();
        $balance = $row['balance'] ?? 0.00;

        $response->getBody()->write(json_encode([
            'account_id' => $id,
            'balance' => $balance
        ]));
        return $response->withHeader("Content-type", "application/json")->withStatus(200);
    }


    // GET /accounts/1/balance/convert/fiat?to=USD per convertire il saldo in una valuta fiat - /accounts/{idAccount}/balance/convert/fiat
    public function convert_to_fiat(Request $request, Response $response, $args){

        $id = $args['idAccount'];
        // Saldo = Sommadepositi - Sommaprelievi =  sum(amount)
        $mysqli_connection = $this->get_data();
        
        // per recuperare il parametro to dalla query string (?to=USD)
        $params = $request->getQueryParams();
        $to_currency = strtoupper($params['to'] ?? 'EUR');

        // per calcoalre il saldo
        $result =  $mysqli_connection->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = '$id'");
        $row = $result->fetch_assoc();
        $balance = (float)($row['balance'] ?? 0.0);

        // logica di conversione
        $rates = [
            'USD' => 1.08,
            'GBP' => 0.85,
            'JPY' => 163.50
        ];

        $rate = $rates[$to_currency] ?? 1.0; //default 1:1 se la valuta non è in lista
        $converted_balance = $balance * $rate;

        $data = [
            'account_id' => (int)$id,
            'original_balance' => $balance,
            'currency' => 'EUR',
            'converted_balance' => round($converted_balance, 2),
            'target_currency' => $to_currency
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader("Content-type", "application/json")->withStatus(200);
    }
    // GET /accounts/1/balance/convert/crypto?to=BTC per convertire il saldo in una criptovaluta - /accounts/{idAccount}/balance/convert/crypto
    public function convert_to_crypto(Request $request, Response $response, $args){
        $id = $args['idAccount'];
        $mysqli_connection = $this->get_data();

        // per recuperare la crypto di destinazione (?to=BTC)
        $params = $request->getQueryParams();
        $to_crypto = strtoupper($params['to'] ?? 'BTC');

        // calcolo del saldo
        $query = "SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = '$id'";
        $result = $mysqli_connection->query($query);
        $row = $result->fetch_assoc();
        $balance = (float)($row['balance'] ?? 0.0);

        //tassi di cambio delle crypto per 1 EUR

        $crypto_rates = [
            'BTC' => 0.000016,  
            'ETH' => 0.00032,   
            'SOL' => 0.0075     
        ];

        $rate = $crypto_rates[$to_crypto] ?? 0.0;
        $converted_balance = $balance * $rate;

        $data = [
            'account_id' => (int)$id,
            'original_balance_eur' => $balance,
            'converted_balance' => $converted_balance,
            'crypto_currency' => $to_crypto,
            'rate_applied' => $rate
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader("Content-type", "application/json")->withStatus(200);

    }
}
