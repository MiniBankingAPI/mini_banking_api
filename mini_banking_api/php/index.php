<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/AlunniController.php';

$app = AppFactory::create();

//Movimenti
$app->get('/accounts/{idAccount}/transactions', "MovimentiController:list_movemets");
$app->get('/accounts/1/transactions/5', "MovimentiController:details_movement");
$app->post('/accounts/1/deposits', "MovimentiController:register_deposit");
$app->post('/accounts/1/withdrawals', "MovimentiController:register_");




$app->get('/test', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Test page");
    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->get('/alunni', "AlunniController:index");

$app->run();
