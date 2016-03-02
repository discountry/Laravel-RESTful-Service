# Laravel-RESTful-Service
**Using Laravel to set up a RESTful Service with Oauth2 Server**

> This tutorial is based on [Laravel 5 token based Authentication (OAuth 2.0)](https://medium.com/@mshanak/laravel-5-token-based-authentication-ae258c12cfea#.5bzflbkp9) & [Dingo Wiki](https://github.com/dingo/api/wiki).They both have some bugs and I fixed them.

### 1.Install a new Laravel Project and of coures you have to set up your database.

```bash
composer global require "laravel/installer"
laravel new restful
```

### 2.Modify `composer.json` and run `composer update` to include extra packages.

```json

    "require": {
        "php": ">=5.5.9",
        "laravel/framework": "5.2.*",
        "dingo/api": "1.0.*dev",
        "lucadegasperi/oauth2-server-laravel": "5.1.*"
    }

```

### 3.Add new providers in your `config/app.php` file.

```php

<?php
    'providers' => [

        //Add bottom lines to your providers array.
        /**
         * Customized Service Providers...
         */
        Dingo\Api\Provider\LaravelServiceProvider::class,
        LucaDegasperi\OAuth2Server\Storage\FluentStorageServiceProvider::class,
        LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider::class,

    ],

```

#### And this lines to the aliases array:

```php

<?php
    'aliases' => [

        //Add bottom lines to your aliases array.
        'Authorizer' => LucaDegasperi\OAuth2Server\Facades\Authorizer::class,

    ],

```

### 4.Add new `$middleware` & `$routeMiddleware` in your `app/Http/Kernel.php` file.

```php

<?php
    protected $middleware = [
        //Add bottom lines to your $middleware array.
        \LucaDegasperi\OAuth2Server\Middleware\OAuthExceptionHandlerMiddleware::class,
    ];
    //
    protected $routeMiddleware = [
        //Add bottom lines to your $routeMiddleware array.
        'oauth' => \LucaDegasperi\OAuth2Server\Middleware\OAuthMiddleware::class,
        'oauth-user' => \LucaDegasperi\OAuth2Server\Middleware\OAuthUserOwnerMiddleware::class,
        'oauth-client' => \LucaDegasperi\OAuth2Server\Middleware\OAuthClientOwnerMiddleware::class,
        'check-authorization-params' => \LucaDegasperi\OAuth2Server\Middleware\CheckAuthCodeRequestMiddleware::class,
    ];

```

### 5.Run `php artisan vendor:publish` and `php artisan migrate` in your project folder.

#### Add the following settings in you `.env` file:

```php

API_STANDARDS_TREE=x
API_SUBTYPE=rest
API_NAME=REST
API_PREFIX=api
API_VERSION=v1
API_CONDITIONAL_REQUEST=true
API_STRICT=false
API_DEBUG=true
API_DEFAULT_FORMAT=json

```

#### Configure your `app\config\oauth2.php` like this:

```php

<?php
    //Modify the $grant_types as follow.
    'grant_types' => [
            'password' => [
             'class' => 'League\OAuth2\Server\Grant\PasswordGrant',
             'access_token_ttl' => 604800,
             
             // the code to run in order to verify the user's identity
             'callback' => 'App\Http\Controllers\VerifyController@verify',
             ],
        ],

```

### 6.Now is your `routes.php` file.

```php

<?php

//Add the following lines to your routes.php

/**
 * OAuth
 */

//Get access_token
Route::post('oauth/access_token', function() {
 return Response::json(Authorizer::issueAccessToken());
});

//Create a test user, you don't need this if you already have.
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

//Show user info via restful service.
$api->version('v1', ['namespace' => 'App\Http\Controllers'], function ($api) {
    $api->get('users', 'UsersController@index');
    $api->get('users/{id}', 'UsersController@show');
});

//Just a test with auth check.
$api->version('v1', ['middleware' => 'api.auth'] , function ($api) {
    $api->get('time', function () {
        return ['now' => microtime(), 'date' => date('Y-M-D',time())];
    });
});

```

### 7.You'll need a client to make your oauth2 server runs.

In the database find the `oauth_client` s Table , insert new record to it ,or you can use the following SQL code in phpMyAdmin:

```sql

INSERT INTO `oauth_clients` (`id`, `secret`, `name`, `created_at`, `updated_at`) VALUES
(‘f3d259ddd3ed8ff3843839b’, ‘4c7f6f8fa93d59c45502c0ae8c4a95b’, ‘Main website’, ‘2015–05–12 21:00:00’, ‘0000–00–00 00:00:00’);

```

### 8.Edit your Api Controllers.

You can add models named Book,Post,User as you like,here is an example:

```php

<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{

    public function index()
    {
        return User::all();
    }

    public function show($id)
    {
        return User::findOrFail($id);
    }
}

```

### 9.Test your server now!

We are almost done.Now you need to test the server you've just set up.We can use tools like [PostMan](https://chrome.google.com/webstore/detail/postman-rest-client-packa/fhbjgbiflinjbdggehcddcbncdddomop) to emulate requests to your server.