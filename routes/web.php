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
    //return $router->app->version();
    $response = [
        'status' => 1,
        'data' => "SBN accounts RESTful API-- production v1.0",
        'message'=> 'success'
    ];

    return response()->json($response, 200, [], JSON_PRETTY_PRINT);

});


$router->group(['prefix' => 'v1'], function ($app) use ($router) {


    $app->post('authorize','UserController@auth');
    $app->post('accesstoken','UserController@accesstoken');
    $app->get('me','UserController@me');
    $app->post('ping','UserController@ping');
    $app->get('logout','UserController@logout');
    $app->post('change_password','UserController@change_own_password');

    /************************* USER RELATED ROUTER (USER & ACCESS)*************************************/

    $router->group( ['prefix' => 'customers', 'middleware' => 'auth' ], function($app)
    {
        $app->post('/','CustomerController@create');
        $app->put('/{id}','CustomerController@update');
        $app->get('/{id}','CustomerController@view');
        $app->delete('/{id}','CustomerController@delete');
        $app->get('/','CustomerController@index');
    });

    $router->group( ['prefix' => 'users' ], function($app)
    {
        $app->post('/','UserController@create');
        $app->put('/{id}','UserController@update');
        $app->get('/{id}','UserController@view');
        $app->delete('/{id}','UserController@delete');
        $app->get('/','UserController@index');
    });
    $router->group( ['prefix' => 'useraccess'], function ($app)
    {
        $app->get('/', 'UserAccessController@index');
        $app->get('/{id}', 'UserAccessController@view');
        $app->post('/', 'UserAccessController@create');
        $app->put('/{id}', 'UserAccessController@update');
        $app->delete('/{id}','UserAccessController@delete');
        /* get default json of access*/
        $app->post('/default_format', 'UserAccessController@defaultUserAccessJson');
    });


});
