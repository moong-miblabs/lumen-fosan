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