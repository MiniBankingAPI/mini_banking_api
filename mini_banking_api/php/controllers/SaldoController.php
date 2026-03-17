<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SaldoController
{
    public function get_balance(Request $request, Response $response, $args){

        $id = $args['idAccount'];
      // Saldo = Sommadepositi - Sommaprelievi =  sum(amount)
        $mysqli_connection = new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
        $result = $mysqli_connection->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as balance FROM transactions WHERE account_id = '$id'");

        $row = $result->fetch_assoc();
        $balance = $row['balance'] ?? 0.00;

        $response->getBody()->write(json_encode([
            'account_id' => $id,
            'balance' => $balance
        ]));
        return $response->withHeader("Content-type", "application/json")->withStatus(200);
    }


    
}
