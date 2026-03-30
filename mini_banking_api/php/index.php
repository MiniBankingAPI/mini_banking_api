<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/MovimentiController.php';
require __DIR__ . '/controllers/SaldoController.php';


$app = AppFactory::create();

// Movimenti
$app->get('/accounts/{idAccount}/transactions', "MovimentiController:list_movements");
$app->get('/accounts/{idAccount}/transactions/{idTransaction}', "MovimentiController:details_movement");
$app->post('/accounts/{idAccount}/deposits', "MovimentiController:register_deposit");
$app->post('/accounts/{idAccount}/withdrawals', "MovimentiController:register_withdrawal");
$app->put('/accounts/{idAccount}/transactions/{idTransaction}', "MovimentiController:modify_movement_description");
$app->delete('/accounts/{idAccount}/transactions/{idTransaction}', "MovimentiController:eliminate_movement");

// Saldo
$app->get('/accounts/{idAccount}/balance', "SaldoController:get_balance");

// Conversione del saldo
$app->get('/accounts/{idAccount}/balance/convert/fiat', "SaldoController:convert_to_fiat");
$app->get('/accounts/{idAccount}/balance/convert/crypto', "SaldoController:convert_to_crypto");


$app->run();
