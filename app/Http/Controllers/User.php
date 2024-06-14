<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel as Model;

class User extends Controller {
    
    public function register(Request $request) {
        $body = $request->post();

        try {
            $data = Model::create($body);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data;

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }

    public function registerBulk(Request $request) {
        $body = $request->post();

        if(!(array_key_exists('bulk_data', $body) && is_array($body['bulk_data']) && array_is_list($body['bulk_data']) && !empty($body['bulk_data']))) {
            $res = new \stdClass();
            $res->error_code = 400;
            $res->error_desc = 'bulk_data not exists or bulk_data is not array or bulk_data is not array list or bulk_data is empty array';
            $res->data = [];

            return response()->json($res,200);
        }

        $arrObj     = $request->only(['bulk_data']);
        $additional = $request->except(['bulk_data']);

        try {
            $data = Model::bulkCreate($arrObj['bulk_data'],$additional);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data;

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }

    public function sync(Request $request) {
        $body = $request->post();

        if(!(array_key_exists('bulk_data', $body) && is_array($body['bulk_data']) && array_is_list($body['bulk_data']) && !empty($body['bulk_data']))) {
            $res = new \stdClass();
            $res->error_code = 400;
            $res->error_desc = 'bulk_data not exists or bulk_data is not array or bulk_data is nor array list or bulk_data is empty array';
            $res->data = [];

            return response()->json($res,200);
        }

        $arrObj     = $request->only(['bulk_data']);
        $additional = $request->except(['bulk_data']);

        try {
            $data = Model::bulkSync($arrObj['bulk_data'],$additional);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data;

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }

    public function update(Request $request, $id = null) {
        if(is_null($id)) $id = $request->post('id');

        $body = $request->except('id');
        
        try {
            $data = Model::_update($body,['id'=>$id]);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data[0];

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }

    public function delete(Request $request, $id = null) {
        if(is_null($id)) $id = $request->post('id');
        try {
            $data = Model::destroy(['id'=>$id]);

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            $res->data = $data[0];

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }

    public function list(Request $request) {
        $input_option = [];

        $page   = $request->input('page');
        $limit  = $request->input('limit');

        if ($page && $limit) {
            $input_option['limit']  = $limit;
            $input_option['offset'] = $limit * ($page-1);
        }

        $sort_by    = $request->input('sort_by');
        $sort_type  = $request->input('sort_type');

        if ($sort_by) {
            if($sort_type == 'desc' || $sort_type == 'DESC') {
                $input_option['order'] = $sort_by . " DESC";
            } else {
                $input_option['order'] = $sort_by . " ASC";
            }
        }

        try {
            if ($page && $limit) {
                $data = Model::findAndCountAll($input_option);
            } else {
                $data = Model::findAll($input_option);
            }

            $res = new \stdClass();
            $res->error_code = 0;
            $res->error_desc = '';
            if($page && $limit){
                $res->data      = $data['data'];
                $res->num_rows  = $data['num_rows'];
                $res->page      = intval($page);
                $res->limit     = intval($limit);
            } else {
                $res->data = $data;
            }

            return response()->json($res,200);
        } catch(\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
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
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }

    public function test(Request $request){
        try {
            $data = Model::check('00000000-0000-0000-0000-000000000000');
            return response()->json($data,200);
        } catch (\Exception $e) {
            $res = new \stdClass();
            $res->error_code = 500;
            $res->error_desc = 'Internal Server Error';
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }
}
