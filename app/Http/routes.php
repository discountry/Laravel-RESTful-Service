<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});

/**
 * OAuth
 */
Route::post('oauth/access_token', function() {
 return Response::json(Authorizer::issueAccessToken());
});

Route::get('/register',function(){$user = new App\User();
 $user->name="tester";
 $user->email="test@test.com";
 $user->password = \Illuminate\Support\Facades\Hash::make("password");
 $user->save();
});

/**
 * Api
 */
$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['namespace' => 'App\Http\Controllers'], function ($api) {
    $api->get('users', 'UsersController@index');
    $api->get('users/{id}', 'UsersController@show');
});

$api->version('v1', ['middleware' => 'api.auth'] , function ($api) {
    $api->get('time', function () {
        return ['now' => microtime(), 'date' => date('Y-M-D',time())];
    });
});


