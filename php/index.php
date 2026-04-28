<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/MovimentiController.php';
require __DIR__ . '/controllers/SaldoController.php';


$app = AppFactory::create();

// Movimenti
$app->get('/accounts/{idAccount}/transactions', "MovimentiController:index");
$app->get('/accounts/{idAccount}/transactions/{idTransaction}', "MovimentiController:show");
$app->post('/accounts/{idAccount}/deposits', "MovimentiController:create");
$app->post('/accounts/{idAccount}/withdrawals', "MovimentiController:remove");
$app->put('/accounts/{idAccount}/transactions/{idTransaction}', "MovimentiController:update");
$app->delete('/accounts/{idAccount}/transactions/{idTransaction}', "MovimentiController:destroy");

// Saldo
$app->get('/accounts/{idAccount}/balance', "SaldoController:index");

// Conversione del saldo
$app->get('/accounts/{idAccount}/balance/convert/fiat', "SaldoController:convert_to_fiat");
$app->get('/accounts/{idAccount}/balance/convert/crypto', "SaldoController:convert_to_crypto");


$app->run();
