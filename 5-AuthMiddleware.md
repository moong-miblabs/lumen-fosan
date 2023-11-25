# AuthMiddleware

## Membuat AuthMiddleware
1. Buat Middleware dengan nama `AuthMiddleware`, file `AuthMiddleware.php` menjadi `App/Http/Middleware/AuthMiddleware.php`
2. Import `BcryptHelper` dengan menambahkan baris `use App\Helper\BcryptHelper;`
3. Buatlah script untuk cek `Authorization` pada header, jika tidak punya. ceklah `token` pada `body` jika request method `POST`, atau di `query` jika request method `GET`
4. Jika `Authorization` atau `token` tidak dikirimkan, maka response dengan status 401
5. Jika dikirimkan, maka coba untuk 'buka' token tersebut
6. Jika tidak berhasil, maka response dengan status 401
7. Jika berhasil, masukan data token pada request, kemudian lanjutkan *(next)* request
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Helper\JsonwebtokenHelper;

class AuthMiddleware {
    
    public function handle(Request $request, Closure $next): Response {
        $token = $request->header('Authorization');
        if(!$token) {
            if ($request->isMethod('post')) {
                $token = $request->input('token');
            } else {
                $token = $request->query('token');
            }
            if(!$token){
                $res = new \stdClass();
                $res->error_code = 401;
                $res->error_desc = 'Unauthorized';
                $res->data = [];
                return response()->json($res,200);
            }
        }

        $decoded = JsonwebtokenHelper::verify($token);
        if($decoded){
            $request->data_token = $decoded;
            return $next($request);
        } else {
            $res = new \stdClass();
            $res->error_code = 401;
            $res->error_desc = 'Unauthorized';
            $res->data = [];
            return response()->json($res,200);
        }
    }
}

```

## DAFTARKAN AuthMiddleware SEBAGAI ROUTE MIDDLEWARE

1. Daftarkan AuthMiddleware sebagai route middleware in `bootstrap/app.php`
```php
/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    // App\Http\Middleware\ExampleMiddleware::class
    App\Http\Middleware\CorsMiddleware::class
]);

$app->routeMiddleware([
//     'auth' => App\Http\Middleware\Authenticate::class,
    'auth' => App\Http\Middleware\AuthMiddleware::class,
]);
```

## Pasang Middleware di route group
```php
$controller = 'User';
$router->group(['prefix'=>'user'],function () use ($router,$controller) {
    Route::post('register',$controller.'@'.'register');
    Route::post('update/{id}',$controller.'@'.'update');
    Route::get('delete/{id}',$controller.'@'.'delete');
    Route::get('list',$controller.'@'.'list');
    Route::get('detail-by-id/{id}',$controller.'@'.'detailById');
});
```
menjadi
```php
$controller = 'User';
$router->group(['prefix'=>'user','middleware'=>'auth'],function () use ($router,$controller) {
    Route::post('register',$controller.'@'.'register');
    Route::post('update/{id}',$controller.'@'.'update');
    Route::get('delete/{id}',$controller.'@'.'delete');
    Route::get('list',$controller.'@'.'list');
    Route::get('detail-by-id/{id}',$controller.'@'.'detailById');
});
```