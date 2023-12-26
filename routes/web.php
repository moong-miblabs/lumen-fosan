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
    return env('APP_NAME',$router->app->version());
});

// $controller = 'Setup';
// $router->group(['prefix'=>'setup'],function () use ($router,$controller) {
//     Route::get('dbsync',$controller.'@'.'dbsync');
//     Route::get('seed',$controller.'@'.'seed');
//     Route::get('drop',$controller.'@'.'drop');
// });

$router->post('raw', [
    'middleware'    => 'auth',
    'uses'          => 'Raw@index'
]);

$controller = 'User';
$router->group(['prefix'=>'user','middleware'=>'auth'],function () use ($router,$controller) {
    Route::post('register',$controller.'@'.'register');
    Route::post('update/{id}',$controller.'@'.'update');
    Route::get('delete/{id}',$controller.'@'.'delete');
    Route::get('list',$controller.'@'.'list');
    Route::get('detail-by-id/{id}',$controller.'@'.'detailById');
});

$controller = 'Login';
$router->group(['prefix'=>'login'],function () use ($router,$controller) {
    Route::post('/',$controller.'@'.'Login');
    Route::post('verify',$controller.'@'.'verify');
});