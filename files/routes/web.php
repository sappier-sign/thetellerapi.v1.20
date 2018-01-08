<?php

$router->get('/', function () use ($router) {
//    return $router->app->version();
    return 'PaySwitch Company Ltd. TheTellerAPI version 1.20';
});

$router->post('', function () use ($router) {
    return 'PaySwitch Company Ltd. TheTellerAPI version 1.20';
});

$router->post('purchase.do', 'TransactionController@purchase');
$router->post('transfer.do', 'TransactionController@transfer');

$router->get('transactions/{transaction_id}', 'TransactionController@getTransactionById');
$router->get('transactions/{date}/{start?}/{end?}/{r_switches?}', 'TransactionsController@getTransactions');
$router->post('transaction/process', 'TransactionController@create');
