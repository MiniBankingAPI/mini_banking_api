<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MovimentiController
{

  public function list_movements(Request $request, Response $response, $args){

    $id = $args['idAccount'];

    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
    $result = $mysqli_connection->query("SELECT * FROM transactions WHERE account_id = '$id'");
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function details_movement(Request $request, Response $response, $args){
  
    $id = $args['idAccount'];
    $idTrans = $args['idTransaction'];

    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'hotpeppers', 'bank');
    $result = $mysqli_connection->query("SELECT * FROM transactions WHERE account_id = '$id' AND id = '$idTrans'");
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
    }

}
