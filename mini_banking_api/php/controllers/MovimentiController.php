<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MovimentiController
{

  private function get_data() {
        return new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
    }

  // ? GET /accounts/{idAccount}/transactions
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

  // ? GET /accounts/{idAccount}/transactions/{idTransaction}
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

  // ? POST /accounts/{idAccount}/deposits
  public function register_deposit(Request $request, Response $response, $args){

    $mysqli = $this->get_data();

    $id = $args['idAccount'];
    $body = json_decode($request->getBody(), true);
    $importo = $body['amount'] ?? 0;
    $descrizione = $body['descriprion'] ?? 'nessuna descrizione sul deposito';

    if($importo <= 0){
      $response->getBody()->write(json_encode(["ERRORE!" => "L'importo deve essere maggiore di zero"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(422);
    }

    $stmt = $mysqli->prepare("INSERT INTO transactions (`account_id`, `type`, `amount`, `description`) VALUES (?, 'deposit', ?, ?)");
    $stmt->bind_param("ids", $id, $importo, $descrizione);

     if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["message" => "Prelievo effettuato"]));
        return $response->withHeader("Content-type", "application/json")->withStatus(201);
    } else {
          var_dump($stmt);

        $response->getBody()->write(json_encode(["error" => "Errore durante l'operazione"]));
        return $response->withStatus(502);
    }
  }

  // ? POST /accounts/{idAccount}/withdrawals
  public function register_withdrawal(Request $request, Response $response, $args){

    $mysqli = $this->get_data();

    $id = $args['idAccount'];
    $body = json_decode($request->getBody(), true);
    $importo = $body['amount'] ?? 0;
    $descrizione = $body['descriprion'] ?? 'nessuna descrizione sul prelievo';

    if($importo <= 0){
      $response->getBody()->write(json_encode(["ERRORE!" => "L'importo deve essere maggiore di zero"]));
      return $response->withHeader("Content-type", "application/json")->withStatus(422);
    }

    $stmt = $mysqli->prepare("SELECT amount FROM transactions WHERE account_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $saldo_attuale = $row['saldo'] ?? 0;

    if($saldo_attuale > $importo){

      $response->getBody()->write(json_encode(["ERRORE!" => "L'importo richiesto non può essere superiore del saldo disponibile"]));
      return $response->withHeader("Content-type", "Application/json")->withStatus(422);
    }

    $stmt = $mysqli->prepare("INSERT INTO transactions ('account_id', 'type', 'amount', 'description') VALUES (?, 'withdrawal', ?, ?)");
    $importoDaRimuovere = -$importo;
    $stmt->bind_param("ids", $id, $importoDaRimuovere, $descrizione);
    
     if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["message" => "Prelievo effettuato"]));
        return $response->withHeader("Content-type", "application/json")->withStatus(201);
    } else {
          var_dump($stmt);

        $response->getBody()->write(json_encode(["error" => "Errore durante l'operazione"]));
        return $response->withStatus(502);
    }
    
  }

  // ? PUT /accounts/{idAccount}/transactions/{idTransaction}
  public function modify_movement_description(Request $request, Response $response, array $args){

    $mysqli = $this->get_data();
    $idAccount = $args["idAccount"];
    $idTransaction = $args["idTransaction"];

    $body = json_decode($request->getBody(), true);
    $newDescrizione= $body["description"] ?? null;

    if(!$newDescrizione){
      $response->getBody()->write(json_encode(["ERRORE:"=> "Descrizione da aggiornare mancante alla richiesta"]));
      return $response->withHeader("Content-type, ", "application/json")->withStatus(400);
    }

    $stmt = $mysqli->prepare("UPDATE transactions SET description = ? WHERE id = ? AND account_id = ?");
    $stmt->bind_param("sii", $newDescrizione, $idTransaction, $idAccount);
    
  }

}


