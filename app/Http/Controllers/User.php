<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel as Model;

class User extends Controller {
    
    public function register(Request $request) {
        $body = $request->post();

        try {
            if(array_key_exists('bulk_data', $body) && is_array($body['bulk_data']) && array_is_list($body['bulk_data'])) {
                $data = Model::bulkCreate($body['bulk_data']);
            } else {
                $data = Model::create($body);
            }

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data;

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = $e->getMessage();
            return response()->json($res,200);
        }
    }

    public function update(Request $request, $id) {
        $body = $request->post();
        try {
            $data = Model::_update($body,['id'=>$id]);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data;

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = $e->getMessage();
            return response()->json($res,200);
        }
    }

    public function delete(Request $request, $id) {
        try {
            $data = Model::destroy(['id'=>$id]);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data;

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = $e->getMessage();
            return response()->json($res,200);
        }
    }

    public function list(Request $request) {
        $input = $request->all();

        $input_option   = [];
        $count          = false;

        if(array_key_exists('page',$input) && array_key_exists('limit',$input)) {
            $input_option = array_merge($input_option, ['limit'     => $input['limit']]);
            $input_option = array_merge($input_option, ['offset'    => $input['limit'] * ($input['page'] - 1)]);
            $count = true;
        }

        if(array_key_exists('sort_by',$input) && array_key_exists('sort_type',$input)) {
            $input_option = array_merge($input_option, ['order'     => [$input['sort_by'],$input['sort_type']]]);
        }

        $input_option = array_merge($input_option,['where'=>$input]);

        try {
            if ($count) {
                $data = Model::findAndCountAll($input_option);
            } else {
                $data = Model::findAll($input_option);
            }

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            if($count){
                $res->data      = $data['data'];
                $res->num_rows  = $data['num_rows'];
            } else {
                $res->data = $data;
            }

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = $e->getMessage();
            return response()->json($res,200);
        }
    }

    public function detailById(Request $request, $id) {
        try {
            $data = Model::findByPk($id);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data;

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
