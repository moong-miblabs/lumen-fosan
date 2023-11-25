# GENERAL

1. Buat sub-domain `example.mibplus.id`, kosongkan folder
2. Instal Laravel via composer `composer create-project --prefer-dist laravel/lumen example.mibplus.id` <sub>2023-11</sub>
3. Buat Database PostgreSQL, mibplusi_example
4. sesuaikan env

```text
APP_NAME=Lumen
```
menjadi
```text
APP_NAME="Back-End Posyandu Lansia (Polandia) by Mr Munji (UNDIP) at Nov 2023 for Postgraduate Thesis"
```
---
```text
APP_KEY=
```
menjadi
```text
APP_KEY=000000000000MIB-Plus000000000000
```
---
```text
APP_URL=http://localhost
```
menjadi
```text
APP_URL=https://example.mibplus.id
```
---
```text
APP_TIMEZONE=UTC
```
menjadi
```text
APP_TIMEZONE=Asia/Jakarta
```
---
```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret
```
menjadi
```text
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=mibplusi_example
DB_USERNAME=foo
DB_PASSWORD=bar
```

>
> `APP_TIMEZONE` diisi dengan timezoneID, `Asia/Jakarta` untuk `WIB`, `Asia/Makassar` untuk `WITA`, `Asia/Jayapura` untuk `WIT`
>

>
> Variabel dalam .env harus diapit *double quote* (") jika mengandung *space*
>

5. Copy index.php dan .htaccess dari folder `public`, paste ke `ROOT`. dalam file `index.php`, ganti path menjadi `bootstrap/app.php`
```php
$app = require __DIR__.'/../bootstrap/app.php';
```
menjadi
```php
$app = require __DIR__.'/bootstrap/app.php';
```
6. Ubah `web.php` di `routes/web.php`
```php
<?php
$router->get('/', function () use ($router) {
    return $router->app->version();
});
```
menjadi
```php
<?php
$router->get('/', function () use ($router) {
    return env('APP_NAME',$router->app->version());
});
```
7. Akses `https://example.mibplus.id/` apakah sudah muncul `APP_NAME` yang ada di env?
8. CorsMiddleware
	1. Buat file `CorsMiddleware.php` in `app/Http/Middleware/CorsMiddleware.php`
	```php
	<?php

    namespace App\Http\Middleware;

    use Closure;

    class CorsMiddleware{
        public function handle($request, Closure $next){
            $origin = '*';
            // $origin = $request->server->get('HTTP_ORIGIN');

            $allowedOrigins = [
                NULL,
                '',
                ''
            ];

            if(in_array($origin, $allowedOrigins) or $origin =='*'){    
                $headers = [
                    'Access-Control-Allow-Origin'      => $origin,
                    'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age'           => '86400',
                    'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
                ];

                if ($request->isMethod('OPTIONS')){
                    return response()->json('{"method":"OPTIONS"}', 200, $headers);
                }

                $response = $next($request);
                $IlluminateResponse = 'Illuminate\Http\Response';
                $SymfonyResopnse = 'Symfony\Component\HttpFoundation\Response';
                if($response instanceof $IlluminateResponse) {
                    foreach ($headers as $key => $value) {
                        $response->header($key, $value);
                    }
                    return $response;
                }

                if($response instanceof $SymfonyResopnse) {
                    foreach ($headers as $key => $value) {
                        $response->headers->set($key, $value);
                    }
                    return $response;
                }
            }
        }
    }
	```
	2. Daftarkan CorsMiddleware sebagai global middleware in `bootstrap/app.php`
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

	// $app->routeMiddleware([
	//     'auth' => App\Http\Middleware\Authenticate::class,
	// ]);
	```
9. Handle `404 Not Found` pada `app/Exceptions/Handler.php`
Tambahkan baris
```php
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
```
dan tambahkan if dalam *method* render
```php
public function render($request, Throwable $exception)
{
    if($exception instanceof NotFoundHttpException){
        $res = new \stdClass();
        $res->error_code = 404;
        $res->error_desc = 'Not Found';
        $res->data = [];
        return response()->json($res,200);
    }
    return parent::render($request, $exception);
}
```
10. Akses URL `https://example.mibplus.id/qwerty` apakah sudah memberikan response dibawah?
```json
{
    "error_code"    : 404,
    "error_desc"    : "Not Found",
    "data"          : []
}
```