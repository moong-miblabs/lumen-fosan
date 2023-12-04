# LOGIN & AUTHENTICATION & HELPER

## BUAT HELPER BcryptHelper
1. Buat direktori / folder baru dengan nama `Helper` di dalam `app`, sehingga menjadi `app/Helper`
2. Buat file baru dengan nama `BcryptHelper.php` di dalam `app/Helper`, sehingga menjadi `app/Helper/BcryptHelper.php`
3. Buat *method* untuk `hash` dan `compare`
```php
<?php

namespace App\Helper;

class BcryptHelper{

    public static function hash($str){
        return password_hash($str, PASSWORD_BCRYPT, ["cost" => 10]);
    }

    public static function compare($password,$hash){
        return password_verify($password,$hash);
    }
}
```

## BUAT HELPER JsonwebtokenHelper
1. Install `php-jwt` via composer `composer require firebase/php-jwt` <sub>2023-11</sub>
2. Buat direktori / folder baru dengan nama `Helper` di dalam `app`, sehingga menjadi `app/Helper` (jika belum ada)
3. Buat file baru dengan nama `JsonwebtokenHelper.php` di dalam `app/Helper`, sehingga menjadi `app/Helper/JsonwebtokenHelper.php`
4. Import `php-jwt`
	1. dengan `php-jwt` menambahkan baris `use Firebase\JWT\JWT;`
	2. dengan `php-jwt` menambahkan baris `use Firebase\JWT\Key;`

5. Buat *method* untuk `sign` dan `verify`
```php
<?php

namespace App\Helper;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JsonwebtokenHelper{

    public static function sign($arr_assoc){
        $key = env('APP_KEY');
        $arr_assoc = array_merge($arr_assoc,['iat'=>strtotime("now")]);
        // JWT::$leeway = 60; // $leeway in seconds
        return JWT::encode($arr_assoc, $key, 'HS256');
    }

    public static function verify($token){
        $key = env('APP_KEY');
        try {
            $decode = JWT::decode($token, new Key($key, 'HS256'));
            return $decode;
        } catch(\Exception $e) {
            return false;
        }
    }
}
```

## BUAT Controller Login
1. Buat controller `Login` di `app/Http/Controllers`, sehingga menjadi `app/Http/Controllers/Login`
2. Import
	1. `UserModel` dengan menambahkan baris `use App\Models\UserModel;`
	2. `BcryptHelper` dengan menambahkan baris `use App\Helper\BcryptHelper;`
	3. `JsonwebtokenHelper` dengan menambahkan baris `use App\Helper\JsonwebtokenHelper;`
3. Buat *method* login
```php
public function login(Request $request){
    $body = $request->input();
    try {
        $data = UserModel::findOne([
            'where' => [
                'username_user'=>$body['username']
            ]
        ]);
        if(!empty((array) $data)) {
            if(BcryptHelper::compare($body['password'],$data->password_user)) {
                $res = new \stdClass();
                $res->error_code    = 0;
                $res->error_desc    = '';
                $res->data          = $data;
                $res->token         = JsonwebtokenHelper::sign(['id'=>$data->id]);
                return response()->json($res,200);
            } else {
                $res = new \stdClass();
                $res->error_code    = 400;
                $res->error_desc    = 'Password salah.';
                $res->data          = [];
                return response()->json($res,200);
            }
        } else {
            $res = new \stdClass();
            $res->error_code    = 400;
            $res->error_desc    = 'Username tidak ditemukan.';
            $res->data          = [];
            return response()->json($res,200);
        }
    } catch(\Execption $e) {
        $res = new \stdClass();
        $res->error_code    = 500;
        $res->error_desc    = 'Internal Server Error';
        $res->data          = $e->getMessage();
        return response()->json($res,200);
    }
}
```

4. Buat *method* verify
```php
public function verify(Request $request){
    $token = $request->input('token');
    if(JsonwebtokenHelper::verify($token)){
        return response(true,200);
    } else {
        return response(false,200);
    }
}
```

## BUAT ROUTE login dan verify
```php
$controller = 'Login';
$router->group(['prefix'=>'login'],function () use ($router,$controller) {
    Route::post('/',        $controller.'@'.'Login');
    Route::post('verify',   $controller.'@'.'verify');
});
```