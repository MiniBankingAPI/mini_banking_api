<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MovimentiController
{
  private $dataBase;
  private function get_data() {
        return new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
    }

  // GET /accounts/{idAccount}/transactions
  public function list_movements(Request $request, Response $response, $args){

    $mysqli = $this->get_data();

    $id = $args['idAccount'];
    $stmt = $mysqli->prepare("SELECT * FROM transactions WHERE account_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  // GET /accounts/{idAccount}/transactions/{idTransaction}
  public function details_movement(Request $request, Response $response, $args){

    $mysqli = $this->get_data();

    $id = $args['idAccount'];
    $idTrans = $args['idTransaction'];

    $stmt = $mysqli->prepare("SELECT * FROM transactions WHERE id = ? AND account_id = ?");
    $stmt->bind_param("ii", $idTrans, $id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_assoc();

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);

  }

  // POST /accounts/{idAccount}/deposits
  public function register_deposit(Request $request, Response $response, $args){

    $mysqli = $this->get_data();

    $id = $args['idAccount'];
    $body = json_decode($request->getBody(), true);
    $importo = $body['amount'] ?? 0;
    $descrizione = $body['descriprion'] ?? 'nessuna descrizione sul deposito';

    if($importo <= 0){
      $response->getBody()->write(json_encode(["ERRORE!" => "L'importo deve essere maggiore di zero"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(400);
    }

    $stmt = $mysqli->prepare("INSERT INTO transictions (account_id, type, amount, description) VALUES (?, 'deposito', ?, ?)");
    $stmt->bind_param("ids", $id, $importo, $descrizione);
    $stmt->execute();

    $response->getBody()->write(json_encode(["message" => "Deposito registrato"]));
    return $response->withHeader("Content-type", "application/json")->withStatus(201);
  }

}
