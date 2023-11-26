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