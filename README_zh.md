**原文请看我的博客：** [用Laravel搭建带Oauth2验证的RESTful服务](http://discountry.github.io/tutorial/2016/02/20/Laravel-RESTful-Oauth2/)


> 参考了 [Laravel 5 token based Authentication (OAuth 2.0)](https://medium.com/@mshanak/laravel-5-token-based-authentication-ae258c12cfea#.5bzflbkp9) & [Dingo Wiki](https://github.com/dingo/api/wiki)，但是原文中有一些bug，而且不适用最新的5.2版本，我的教程里解决了这些问题。

### 1.全新安装Laravel并配置好你的数据库，我在这里用的是mysql.

``` bash
composer global require "laravel/installer"
laravel new restful
```

### 2.修改 `composer.json` 并在命令行运行 `composer update` 添加两个包.

``` json
    "require": {
        "php": ">=5.5.9",
        "laravel/framework": "5.2.*",
        //下面这两个是新添加的，注意版本号
        "dingo/api": "1.0.*dev",
        "lucadegasperi/oauth2-server-laravel": "5.1.*"
    }
```

### 3.在 `config/app.php` 文件里添加新的 `providers`.

``` php
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

#### 在 `aliases` 里添加下面的内容:

``` php
<?php
    'aliases' => [

        //Add bottom lines to your aliases array.
        'Authorizer' => LucaDegasperi\OAuth2Server\Facades\Authorizer::class,

    ],
```

### 4.在你的 `app/Http/Kernel.php` 文件里添加新的 `$middleware` & `$routeMiddleware`.

``` php
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

### 5.在你的项目里运行 `php artisan vendor:publish` & `php artisan migrate` .

#### 添加这些配置项在 `.env` 文件里，具体含义可以查看[Dingo Wiki](https://github.com/dingo/api/wiki):

``` php
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

#### 配置你的 `app\config\oauth2.php` 文件:

``` php
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

### 6.在 `routes.php` 文件添加我们需要的路由.

``` php

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

### 7.在数据库的 `oauth_client` 表里添加一条 client 数据用来测试. ***例如phphub就是github API的一个client***

``` sql
INSERT INTO `oauth_clients` (`id`, `secret`, `name`, `created_at`, `updated_at`) VALUES
(‘f3d259ddd3ed8ff3843839b’, ‘4c7f6f8fa93d59c45502c0ae8c4a95b’, ‘Main website’, ‘2015–05–12 21:00:00’, ‘0000–00–00 00:00:00’);
```

### 8.编写你的 API Controller.

随便叫什么 Book,Post,User 都好,这是一个举例:

``` php
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

### 9.开始测试吧!

其实已经初步完工了，接下来我们要测试刚才配置好的服务，一般会用到一个Chrome应用 [PostMan](https://chrome.google.com/webstore/detail/postman-rest-client-packa/fhbjgbiflinjbdggehcddcbncdddomop) 来模拟请求你的服务器，当然你也可以用自己的办法.

![GET from Server](http://discountry.github.io/images/get.png)
![Oauth2](http://discountry.github.io/images/oauth.png)
![Token test](http://discountry.github.io/images/test.png)
