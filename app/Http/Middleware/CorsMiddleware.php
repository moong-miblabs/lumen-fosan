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