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

$controller = 'Setup';
$router->group(['prefix'=>'setup'],function () use ($router,$controller) {
    $router->get('dbsync',$controller.'@'.'dbsync');
    $router->get('seed',$controller.'@'.'seed');
    $router->get('drop',$controller.'@'.'drop');
});

$router->post('raw', [
    'middleware'    => 'auth',
    'uses'          => 'Raw@index'
]);

$controller = 'User';
$router->group(['prefix'=>'user'], function () use ($router,$controller) {
    $router->post('register',       $controller.'@'.'register');
    $router->post('register-bulk',  $controller.'@'.'registerBulk');
    $router->post('sync',           $controller.'@'.'sync');
    $router->post('update[/{id}]',  $controller.'@'.'update');
    $router->get('delete/{id}',     $controller.'@'.'delete');
    $router->post('delete',         $controller.'@'.'delete');
    $router->post('list',           $controller.'@'.'list');
    $router->get('list',            $controller.'@'.'list');
    $router->get('detail-by-id/{id}', $controller.'@'.'detailById');
});

$controller = 'Login';
$router->group(['prefix'=>'login'],function () use ($router,$controller) {
    Route::post('/',$controller.'@'.'Login');
    Route::post('verify',$controller.'@'.'verify');
});
