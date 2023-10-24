<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// $router->group(['prefix' => 'api', 'middleware' => 'auth:api'], function () use ($router) {
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('get/key', 'KeyController@index');
    $router->group(['prefix' => 'wallets'], function () use ($router) {
        $router->post('create', 'WalletController@createWallet');
        $router->post('debit', 'WalletController@debitWallet');
        $router->post('credit', 'WalletController@creditWallet');
        $router->get('{id}', 'WalletController@show');
        $router->get('user/{user_id}', 'WalletController@showByUser');


        $router->group(['prefix' => '{wallet_id}/transactions/'], function () use ($router) {
            $router->get('/', 'TransactionController@index');
            $router->post('/histories', 'TransactionController@histories');
        });

        $router->group(['prefix' => 'transactions/'], function () use ($router) {
            $router->get('{id}', 'TransactionController@show');
        });

        $router->group(['prefix' => 'topup'], function () use ($router) {
            $router->post('/', 'TopUpController@initializePayment');
            $router->post('/verify-transaction', 'TopUpController@verifyTransaction');
        });


        $router->group(['prefix' => 'withdrawals'], function () use ($router) {
            $router->post('/', 'WithdrawalController@initializeWithdraw');
            $router->post('/verify', 'WithdrawalController@verifyTransaction');
        });

        $router->group(['prefix' => 'revenues'], function () use ($router) {
            $router->get('campaign/{campaign_id}/daily-stats', 'RevenueController@getDailyRevenueByCampaign');
            $router->get('campaign/{campaign_id}/monthly-stats', 'RevenueController@getMonthlyRevenueByCampaign');
            $router->post('summary', 'RevenueController@revenueSummary');
        });

        $router->group(['prefix' => 'disburse/'], function () use ($router){
            $router->post('revenue', 'RevenueDisbursementController@index');
        });

        $router->group(['prefix' => 'rewards'], function () use ($router){
            $router->get('{audience_id}', 'WalletRewardController@rewards');
        });

    });

    $router->group(['prefix' => 'channels/'], function () use ($router) {
        $router->get('/', 'TransactionChannelController@index');
        $router->post('/', 'TransactionChannelController@create');
        $router->get('{id}', 'TransactionChannelController@show');
    });



});
