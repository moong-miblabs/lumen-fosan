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