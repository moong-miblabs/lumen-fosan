<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SetupModel as Model;

class Setup extends Controller {
    
    public function dbsync(){
        try {
            Model::dbsync();

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = 'DBSYNC sukses';

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = $e->getMessage();
            return response()->json($res,200);
        }
    }

    public function seed(){
        try {
            Model::seed();

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = 'SEEDING sukses';

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = $e->getMessage();
            return response()->json($res,200);
        }
    }

    public function drop(){
        try {
            Model::drop();

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = 'DROP sukses';

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = $e->getMessage();
            return response()->json($res,200);
        }
    }
}
