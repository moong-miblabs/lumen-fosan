# STANDAR

## STANDAR MODEL
1. Install uuid via composer `composer require ramsey/uuid` <sub>2023-11</sub>
2. Install carbon via composer `composer require nesbot/carbon` <sub>2023-11</sub>
3. Install to-raw-sql via composer `composer require pyaesoneaung/to-raw-sql` <sub>2023-11</sub>
4. Buat model `UserModel` di `app/Models`, sehingga menjadi `app/Models/UserModel.php`
5. Import
    1. `Illuminate\Support\Facades\DB` dengan manambahkan baris `use Illuminate\Support\Facades\DB;`
    2. `Ramsey\Uuid\Uuid` dengan menambahkan baris `use Ramsey\Uuid\Uuid;`
    3. `Carbon\Carbon` dengan manambahkan baris `use Carbon\Carbon;`
6. Tambakan property/attribute di dalam *class*
    1. `protected $table = 'users';`
    2. `static $tabel           = 'users';`
    3. `static $columns         = ['id','nama_user','username_user','password_user','created_at','updated_at','deleted_at'];`
    4. `static $write_columns   = ['nama_user','username_user','password_user'];`
    5. `static $read_columns    = ['id','nama_user','username_user','password_user'];`
    6. `static $order           = [['created_at', 'desc'], ['id', 'desc']];`
7. Buat *method* di dalam *UserModel class*
    1. create()
    ```php
    public static function create($arr_assoc = []) {
        $filtered = array_filter($arr_assoc, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);

        $id = Uuid::uuid4();
        $created_at = $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
        $obj = array_merge(['id'=>$id,'created_at'=>$created_at,'updated_at'=>$updated_at],$filtered);

        DB::table(self::$tabel)->insert($obj);

        return DB::table(self::$tabel)->find($obj['id']);
    }
    ```

    2. bulkCreate()
    ```php
    public static function bulkCreate($arr_list = []){
        $mapped = array_map(function($value) {
            $filtered = array_filter($value, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);

            $id = Uuid::uuid1();
            $created_at = $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
            $obj = array_merge(['id'=>$id,'created_at'=>$created_at,'updated_at'=>$updated_at],$filtered);

            return $obj;
        }, $arr_list);

        $arrId = array_map(fn ($value) => $value['id'], $mapped);

        DB::table(self::$tabel)->insert($mapped);

        return DB::table(self::$tabel)->whereIn('id',$arrId)->get()->all();
    }
    ```

    3. \_update()
    ```php
    public static function _update($arr_assoc_set, $where, $where_values = []) {
        if (is_array($where)) {
            if (array_is_list($where)) {
                $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
            } else {
                $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
            }
        } else {
            $filtered_where = DB::raw($where,$where_values);
        }
        
        $filtered_set   = array_filter($arr_assoc_set, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);
        $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
        $obj = array_merge(['updated_at'=>$updated_at],$filtered_set);

        $arrId = DB::table(self::$tabel)->whereNull('deleted_at')->where($filtered_where)->pluck('id');

        DB::table(self::$tabel)->whereNull('deleted_at')->where($filtered_where)->update($obj);

        return DB::table(self::$tabel)->whereIn('id',$arrId)->get()->all();
    }
    ```

    4. destroy()
    ```php
    public static function destroy($where, $where_values = [], $force=false) {
        if (is_array($where)) {
            if (array_is_list($where)) {
                $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
            } else {
                $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
            }
        } else {
            $filtered_where = DB::raw($where,$where_values);
        }

        if($force){
            $data = DB::table(self::$tabel)->where($filtered_where)->get();
            DB::table(self::$tabel)->where($filtered_where)->delete();
        } else {
            $arrId = DB::table(self::$tabel)->whereNull('deleted_at')->where($filtered_where)->pluck('id');
            DB::table(self::$tabel)->whereNull('deleted_at')->where($filtered_where)->update(['deleted_at'=>Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')))]);

            $data = DB::table(self::$tabel)->whereIn('id', $arrId)->get();
        }
        return $data;
    }
    ```

    5. findAll()
    ```php
    public static function findAll($input_option = ['attributes'=> null, 'where' => [], 'where_values' => [], 'order' => [], 'limit' => null, 'offset' => null, 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(!isset($input_option['attributes']))                 $input_option['attributes']     = self::$read_columns;;
        if(!array_key_exists('where', $input_option))           $input_option['where']          = [];
        if(!array_key_exists('where_values', $input_option))    $input_option['where_values']   = [];
        if(!array_key_exists('order', $input_option))           $input_option['order']          = self::$order;
        if(!array_key_exists('limit', $input_option))           $input_option['limit']          = null;
        if(!array_key_exists('offset', $input_option))          $input_option['offset']         = null;
        if(!array_key_exists('paranoid', $input_option))        $input_option['paranoid']       = false;

        if(!array_key_exists('sql', $output_option)) $output_option['sql'] = false;

        // INITIAL
        $sql = DB::table(self::$tabel);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
        }

        if($input_option['where']) {
            $where          = $input_option['where'];
            $where_values   = $input_option['where_values'];
            if (is_array($where)) {
                if (array_is_list($where)) {
                    $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
                } else {
                    $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
                }
            } else {
                $filtered_where = DB::raw($where,$where_values);
            }
            $sql->where($filtered_where);
        }
        // END WHERE
            
        // ATTRIBUTES
        if(is_array($input_option['attributes'])) {
            $attributes = array_map(function($value) {
                if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                return $value;
            },$input_option['attributes']);
        } elseif(is_string($input_option['attributes'])) {
            $attributes = DB::raw($input_option['attributes']);
        } else {
            $attributes = $input_option['attributes'];
        }
        $sql->select($attributes);
        // END ATTRIBUTES


        // ORDER
        if (is_array($input_option['order'])) {
            if (is_array($input_option['order'][0])) {
                $filtered_order = array_filter($input_option['order'], fn($value) => in_array($value[0], self::$columns));
                $reduced_order  = array_reduce($filtered_order, function($total,$value) {
                    $separator = $total?', ':'';
                    return $total.$separator."{$value[0]} {$value[1]}";
                },'');
            } else {
                if(in_array($input_option['order'][0],self::$columns)) {
                    $reduced_order = "{$input_option['order'][0]} {$input_option['order'][1]}";
                } else {
                    $reduced_order = '';
                }
            }
        } else {
            $reduced_order = $input_option['order'];
        }
        if($reduced_order) $sql->orderByRaw($reduced_order);
        // END ORDER

        // LIMIT & OFFSET
        if($input_option['limit'] !== null)     $sql->limit($input_option['limit']);
        if($input_option['offset'] !== null)    $sql->offset($input_option['offset']);
        // END LIMIT & OFFSET

        // EXECUTE data
        $data = $sql->get()->all();
        // EXECUTE sql
        if($output_option['sql'])   $raw_sql = $sql->toRawSql();

        
        if(!$output_option['sql']){
            return $data;
        } else {
            $return = [];
            $return['data'] = $data;
            $return['sql'] = $raw_sql;

            return $return;
        }
    }
    ```

    6. findByPk()
    ```php
    public static function findByPk($id,$input_option = ['attributes'=> null, 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(!isset($input_option['attributes']))                 $input_option['attributes']     = self::$read_columns;;
        if(!array_key_exists('paranoid', $input_option))        $input_option['paranoid']       = false;

        if(!array_key_exists('sql', $output_option)) $output_option['sql'] = false;

        // INITIAL
        $sql = DB::table(self::$tabel)->where('id',$id);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
        }
        // END WHERE
            
        // ATTRIBUTES
        if(is_array($input_option['attributes'])) {
            $attributes = array_map(function($value) {
                if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                return $value;
            },$input_option['attributes']);
        } elseif(is_string($input_option['attributes'])) {
            $attributes = DB::raw($input_option['attributes']);
        } else {
            $attributes = $input_option['attributes'];
        }
        $sql->select($attributes);
        // END ATTRIBUTES

        // EXECUTE data
        $data = $sql->get();
        // EXECUTE sql
        if($output_option['sql'])   $raw_sql = $sql->toRawSql();

        
        if(!$output_option['sql']){
            if($data->isNotEmpty()) {
                return $data[0];
            } else {
                return new \stdClass();
            }
        } else {
            $return = [];
            if($data->isNotEmpty()) {
                $return['data'] = $data[0];
            } else {
                $return['data'] = new \stdClass();
            }
            $return['sql'] = $raw_sql;

            return $return;
        }
    }
    ```

    7. findOne()
    ```php
    public static function findOne($input_option = ['attributes'=> null, 'where' => [], 'where_values' => [], 'order' => [], 'offset' => null, 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(!isset($input_option['attributes']))                 $input_option['attributes']     = self::$read_columns;;
        if(!array_key_exists('where', $input_option))           $input_option['where']          = [];
        if(!array_key_exists('where_values', $input_option))    $input_option['where_values']   = [];
        if(!array_key_exists('order', $input_option))           $input_option['order']          = self::$order;
        if(!array_key_exists('offset', $input_option))          $input_option['offset']         = null;
        if(!array_key_exists('paranoid', $input_option))        $input_option['paranoid']       = false;

        if(!array_key_exists('sql', $output_option)) $output_option['sql'] = false;

        // INITIAL
        $sql = DB::table(self::$tabel);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
        }

        if($input_option['where']) {
            $where          = $input_option['where'];
            $where_values   = $input_option['where_values'];
            if (is_array($where)) {
                if (array_is_list($where)) {
                    $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
                } else {
                    $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
                }
            } else {
                $filtered_where = DB::raw($where,$where_values);
            }
            $sql->where($filtered_where);
        }
        // END WHERE
            
        // ATTRIBUTES
        if(is_array($input_option['attributes'])) {
            $attributes = array_map(function($value) {
                if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                return $value;
            },$input_option['attributes']);
        } elseif(is_string($input_option['attributes'])) {
            $attributes = DB::raw($input_option['attributes']);
        } else {
            $attributes = $input_option['attributes'];
        }
        $sql->select($attributes);
        // END ATTRIBUTES


        // ORDER
        if (is_array($input_option['order'])) {
            if (is_array($input_option['order'][0])) {
                $filtered_order = array_filter($input_option['order'], fn($value) => in_array($value[0], self::$columns));
                $reduced_order  = array_reduce($filtered_order, function($total,$value) {
                    $separator = $total?', ':'';
                    return $total.$separator."{$value[0]} {$value[1]}";
                },'');
            } else {
                if(in_array($input_option['order'][0],self::$columns)) {
                    $reduced_order = "{$input_option['order'][0]} {$input_option['order'][1]}";
                } else {
                    $reduced_order = '';
                }
            }
        } else {
            $reduced_order = $input_option['order'];
        }
        if($reduced_order) $sql->orderByRaw($reduced_order);
        // END ORDER

        // LIMIT & OFFSET
        $sql->limit(1);
        if($input_option['offset'] !== null)    $sql->offset($input_option['offset']);
        // END LIMIT & OFFSET

        // EXECUTE data
        $data = $sql->get();
        // EXECUTE sql
        if($output_option['sql'])   $raw_sql = $sql->toRawSql();

        
        if(!$output_option['sql']){
            if($data->isNotEmpty()) {
                return $data[0];
            } else {
                return new \stdClass();
            }
        } else {
            $return = [];
            if($data->isNotEmpty()) {
                $return['data'] = $data[0];
            } else {
                $return['data'] = new \stdClass();
            }
            $return['sql'] = $raw_sql;

            return $return;
        }
    }
    ```

    8. findOrCreate()
    ```php
    public static function findOrCreate($where, $default = []) {
        // INITIAL
        $sql = DB::table(self::$tabel)->select(self::$read_columns);

        // WHERE
        if(true) {
            $sql->whereNull('deleted_at');
        }
        $sql->where($where);
            
        // LIMIT
        $sql->limit(1);
        // END LIMIT

        // EXECUTE data
        $data = $sql->get();
        
        if($data->isEmpty()){
            $arr_assoc = array_merge($where,$default);

            $filtered = array_filter($arr_assoc, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);

            $id = Uuid::uuid4();
            $created_at = $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
            $obj = array_merge(['id'=>$id,'created_at'=>$created_at,'updated_at'=>$updated_at],$filtered);

            DB::table(self::$tabel)->insert($obj);

            $return = [];
            $return['data']     = DB::table(self::$tabel)->find($obj['id']);
            $return['created']  = true;

            return $return;
        } else {
            $return = [];
            $return['data']     = $data[0];
            $return['created']  = false;

            return $return;
        }
    }
    ```

    8. findAndCountAll()
    ```php
    public static function findAndCountAll($input_option = ['attributes'=> null, 'where' => [], 'where_values' => [], 'order' => [], 'limit' => null, 'offset' => null, 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(!isset($input_option['attributes']))                 $input_option['attributes']     = self::$read_columns;
        if(!array_key_exists('where', $input_option))           $input_option['where']          = [];
        if(!array_key_exists('where_values', $input_option))    $input_option['where_values']   = [];
        if(!array_key_exists('order', $input_option))           $input_option['order']          = self::$order;
        if(!array_key_exists('limit', $input_option))           $input_option['limit']          = null;
        if(!array_key_exists('offset', $input_option))          $input_option['offset']         = null;
        if(!array_key_exists('paranoid', $input_option))        $input_option['paranoid']       = false;

        if(!array_key_exists('sql', $output_option)) $output_option['sql'] = false;

        // INITIAL
        $sql = DB::table(self::$tabel);
        $num_rows = DB::table(self::$tabel);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
            $num_rows->whereNull('deleted_at');
        }

        if($input_option['where']) {
            $where          = $input_option['where'];
            $where_values   = $input_option['where_values'];
            if (is_array($where)) {
                if (array_is_list($where)) {
                    $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
                } else {
                    $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
                }
            } else {
                $filtered_where = DB::raw($where,$where_values);
            }
            $sql->where($filtered_where);
            $num_rows->where($filtered_where);
        }
        // END WHERE

        // EXECUTE num_rows
        $count = $num_rows->count();
            
        // ATTRIBUTES
        if(is_array($input_option['attributes'])) {
            $attributes = array_map(function($value) {
                if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                return $value;
            },$input_option['attributes']);
        } elseif(is_string($input_option['attributes'])) {
            $attributes = DB::raw($input_option['attributes']);
        } else {
            $attributes = $input_option['attributes'];
        }
        $sql->select($attributes);
        // END ATTRIBUTES


        // ORDER
        if (is_array($input_option['order'])) {
            if (is_array($input_option['order'][0])) {
                $filtered_order = array_filter($input_option['order'], fn($value) => in_array($value[0], self::$columns));
                $reduced_order  = array_reduce($filtered_order, function($total,$value) {
                    $separator = $total?', ':'';
                    return $total.$separator."{$value[0]} {$value[1]}";
                },'');
            } else {
                if(in_array($input_option['order'][0],self::$columns)) {
                    $reduced_order = "{$input_option['order'][0]} {$input_option['order'][1]}";
                } else {
                    $reduced_order = '';
                }
            }
        } else {
            $reduced_order = $input_option['order'];
        }
        if($reduced_order) $sql->orderByRaw($reduced_order);
        // END ORDER

        // LIMIT & OFFSET
        if($input_option['limit'] !== null)     $sql->limit($input_option['limit']);
        if($input_option['offset'] !== null)    $sql->offset($input_option['offset']);
        // END LIMIT & OFFSET

        // EXECUTE data
        $data = $sql->get()->all();
        // EXECUTE sql
        if($output_option['sql'])   $raw_sql = $sql->toRawSql();

        $return = [];
        $return['data']     = $data;
        $return['num_rows'] = $count;
        if($output_option['sql'])
            $return['sql'] = $raw_sql;

        return $return;
    }
    ```

    9. empty()
    ```php
    public static function empty(){
        return array_fill_keys(self::$columns, null);
    }
    ```
8. Jika ingin membuat standar model lainnya, ulangi langkah 4 - 7. Jangan lupa penyesuaian pada langkah 6.

## STANDAR CONTROLLER
1. Buat controller `User` di `app/Http/Controllers`, sehingga menjadi `app/Http/Controllers/User.php`
2. Import model `UserModel` dengan syntax `use App\Models\UserModel as Model;` pada `User`. Karena `UserModel` di `User` Controller harus bernama `Model`
3. Buat *method* di dalam *User class*
    1. register()
    ```php
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
    ```
    2. update()
    ```php
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
    ```
    3. delete()
    ```php
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
    ```

    4. list()
    ```php
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

        // CUSTOM OPERATOR
            $mapped_input = array_map( function($value,$key) {
                if($key=='nama_user') return [$key,'ilike','%'.$value.'%'];
                return [$key,'=',$value];
            }, array_values($input), array_keys($input));
            $input = $mapped_input;
        // END CUSTOM OPERATOR

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
    ```

    5. detailById()
    ```php
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
    ```
4. Jika ingin membuat standar controller lainnya, ulangi langkah 1 - 3. Jangan lupa penyesuaian pada langkah 2.

## STANDAR ROUTING

```php
<?php
$controller = 'User';
$router->group(['prefix'=>'user','middleware'=>'auth'], function () use ($router,$controller) {
    Route::post('register',         $controller.'@'.'register');
    Route::post('update/{id}',      $controller.'@'.'update');
    Route::get('delete/{id}',       $controller.'@'.'delete');
    Route::get('list',              $controller.'@'.'list');
    Route::get('detail-by-id/{id}', $controller.'@'.'detailById');
});
```