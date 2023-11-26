<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel;
use App\Helper\BcryptHelper;
use App\Helper\JsonwebtokenHelper;

class Login extends Controller {
    
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

    public function verify(Request $request){
        $token = $request->input('token');
        if(JsonwebtokenHelper::verify($token)){
            return response(true,200);
        } else {
            return response(false,200);
        }
    }
}
