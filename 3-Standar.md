# STANDAR

## STANDAR MODEL
1. Install uuid via composer `composer require ramsey/uuid` <sub>2024-06</sub>
2. Install carbon via composer `composer require nesbot/carbon:2.72.5` <sub>2024-06</sub>
3. Install to-raw-sql via composer `composer require pyaesoneaung/to-raw-sql` <sub>2024-06</sub>
4. BUAT HELPER ModelHelper
   1. Buat direktori / folder baru dengan nama `Helper` di dalam `app`, sehingga menjadi `app/Helper`
   2. Buat file baru dengan nama `ModelHelper.php` di dalam `app/Helper`, sehingga menjadi `app/Helper/ModelHelper.php`
   3. Buat *method* orderSql
   ```php
   <?php

    namespace App\Helper;

    class ModelHelper{

        public static function orderSql(string $column, array $val) : string {
            $conn = env('DB_CONNECTION','mysql');
            if($conn === 'mysql') {
                $str = implode("','", $val);
                return "FIELD(`".$column."`,'".$str."')";
            } else {
                $str = implode("','", $val);
                return "array_position(array['".$str."'], ".$column.");";
            }
        }
    }
   ``` 
5. Buat model `UserModel` di `app/Models`, sehingga menjadi `app/Models/UserModel.php`
6. Import
    1. `Illuminate\Support\Facades\DB` dengan manambahkan baris `use Illuminate\Support\Facades\DB;`
    2. `Ramsey\Uuid\Uuid` dengan menambahkan baris `use Ramsey\Uuid\Uuid;`
    3. `Carbon\Carbon` dengan manambahkan baris `use Carbon\Carbon;`
    4. `Helper\ModelHelper` dengan menambahkan baris `use Helper\ModelHelper;`
7. Tambakan property/attribute di dalam *class*
    1. `protected $table = 'users';`
    2. `static $tabel           = 'users';`
    3. `static $columns         = ['id','nama_user','email_user','username_user','password_user','created_at','updated_at','deleted_at'];`
    4. `static $write_columns   = ['nama_user','email_user','username_user','password_user'];`
    5. `static $read_columns    = ['id','nama_user','email_user','username_user','password_user'];`
    6. `static $order           = [['created_at', 'desc'], ['id', 'desc']];`
    7. `static $default 	= [];`
8. Buat *method* di dalam *UserModel class*
    1. create()
    ```php
    // ['foo'=>'bar'] >> INSERT INTO `table`(`foo`) VALUES ('bar');
    // IF INTERSECTION return empty array, throw Exception
    public static function create($obj = []) {
        $filtered = array_filter($obj, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);

        if(empty($filtered)) throw new \Exception("Data is NULL or none columns macth data");

        $id = Uuid::uuid4();
        $created_at = $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
        $obj = array_merge(['id'=>$id,'created_at'=>$created_at,'updated_at'=>$updated_at], self::$default, $filtered);

        DB::table(self::$tabel)->insert($obj);

        return DB::table(self::$tabel)->find($obj['id']);
    }
    ```

    2. bulkCreate()
    ```php
    // [
    //     [ 'foo'=>'bar'],
    //     [ 'foo'=>'baz'] 
    // ]
    // >> INSERT INTO `table`(`foo`) VALUES ('bar'),('baz');
    // [
    //     [ 'foo'=>'bar'],
    //     [ 'foo'=>'baz'] 
    // ]
    // additional : [ 'lorem'=> 'ipsum' ]
    // >> INSERT INTO `table`(`foo`,`lorem`) VALUES ('bar','ipsum'),('baz','ipsum');
    // IF INTERSECTION return empty obj, throw Exception
    // bulkCreate return Array same order with arrObj input
    public static function bulkCreate($arrObj, $additional = []){
        $mapped = array_map(function($value) {
        	$new_value = array_merge($value,$additional);
            $filtered = array_filter($new_value, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);

            if(empty($filtered)) throw new \Exception("Data is NULL or none columns macth data");

            $id = Uuid::uuid1();
            $created_at = $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
            $obj = array_merge(['id'=>$id,'created_at'=>$created_at,'updated_at'=>$updated_at], self::$default, $filtered);

            return $obj;
        }, $arrObj);

        $arrId = array_map(fn ($value) => $value['id'], $mapped);

        DB::table(self::$tabel)->insert($mapped);

        return DB::table(self::$tabel)->whereIn('id',$arrId)->orderBy('id')->get()->all();
    }
    ```
    3. bulkSync()
    ```php
    // [
    //     ['id'=>null,'foo'=>'bar','deleted'=>false],
    //     ['id'=>'key1','foo'=>'baz','deleted'=>false],
    //     ['id'=>'key2','foo'=>'bzz','deleted'=>true]
    // ]
    // >> START TRANSACTION
    // >> IF (id NULL and deleted false)        INSERT INTO `table`(`foo`) VALUES ('bar');
    // >> IF (id NOT NULL and deleted false)    UPDATE `table` SET `foo`='baz', `updated_at`=NOW() WHERE `id`='key1';
    // >> IF (id NOT NULL and deleted true)     UPDATE `table` SET `foo`='bzz', `deleted_at`=NOW() WHERE `id`='key2';
    // >> COMMIT
    // [
    //     ['id'=>null  ,'foo'=>'bar','deleted': false },
    //     ['id'=>'key1','foo'=>'baz','deleted': false },
    //     ['id'=>'key2','foo'=>'bzz','deleted': true }
    // ]
    // additional : { lorem: 'ipsum'}
    // >> START TRANSACTION
    // >> IF (id NULL and deleted false)        INSERT INTO `table`(`foo`,`lorem`) VALUES ('bar','ipsum');
    // >> IF (id NOT NULL and deleted false)    UPDATE `table` SET `foo`='baz', `lorem` = 'ipsum', `updated_at`=NOW() WHERE `id`='key1';
    // >> IF (id NOT NULL and deleted true)     UPDATE `table` SET `foo`='bzz', `lorem` = 'ipsum', `deleted_at`=NOW() WHERE `id`='key2';
    // >> COMMIT
    // id and deleted are required for every element, if not exists throw Exception
    // IF INTERSECTION return empty array, throw Exception
    // bulkSync return Array same order with arrObj input, rows with (id NULL & deleted TRUE) will be omit
    public static function bulkSync($arrObj, $additional = []){
        if(!(is_array($arrObj) && array_is_list($arrObj) && !empty($arrObj)))
            throw new \Exception("Data is not array or Data is not array list or Data is empty array");

        $created_at = $updated_at = $deleted_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
        $arrId = [];
        DB::beginTransaction();
        for ($i=0; $i < count($arrObj); $i++) {
            $value = $arrObj[$i];
            if(!empty($additional)) {
                $added = array_merge($value,$additional);
            } else {
                $added = $value;
            }
            $filtered = array_filter($added, function($key) {
                if($key=='id') return TRUE;
                if($key=='deleted') return TRUE;
                return in_array($key, self::$write_columns);
            }, ARRAY_FILTER_USE_KEY);

            if(empty($filtered)) throw new \Exception("Data is NULL or none columns macth data");
            if(!array_key_exists("id",$filtered)) throw new \Exception("ID not found");
            if(!array_key_exists("deleted",$filtered)) throw new \Exception("deleted not found");

            if($filtered['id']) {
                $id = $filtered['id'];
                unset($filtered['id']);
                if($filtered['deleted']) {
                    $obj = array_merge(['deleted_at'=>$deleted_at],$filtered);
                    unset($obj['deleted']);
                    DB::table(self::$tabel)->where('id',$id)->update($obj);
                } else {
                    $obj = array_merge(['updated_at'=>$updated_at],$filtered);
                    unset($obj['deleted']);
                    DB::table(self::$tabel)->where('id',$id)->update($obj);
                }
                array_push($arrId, $id);
            } else {
                $id = Uuid::uuid1();
                $obj = array_merge(self::$default, $filtered, ['id'=>$id,'created_at'=>$created_at,'updated_at'=>$updated_at]);
                unset($obj['deleted']);
                DB::table(self::$tabel)->insert($obj);
                array_push($arrId, $id);
            }
        }
        DB::commit();

        return DB::table(self::$tabel)->whereIn('id',$arrId)->orderByRaw(ModelHelper::orderSql('id',$arrId))->get()->all();
    }
    ``` 

    4. \_update()
    ```php
    // obj_set : ['foo'=>'new bar'] => SET `bar` = 'new bar'
    // where : ['foo'=>'bar'] >> WHERE `foo` = 'bar' AND deleted_at IS NULL
    // where : [['foo','<>','bar']] >> WHERE `foo` <> 'bar' AND deleted_at IS NULL
    // where : 'foo = ?' + where_values : ['bar'] >> `foo` = 'bar' AND deleted_at IS NULL
    public static function _update($obj_set, $where, $where_values = []) {
    	$sql = DB::table(self::$tabel)->whereNull('deleted_at');

        if (is_array($where)) {
            if (array_is_list($where)) {
                $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
            } else {
                $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
            }
            if(empty($filtered_where)) throw new \Exception("WHERE empty after filtered, it will update all rows in the table");
            $sql->where($filtered_where);
        } else {
            $sql->whereRaw($where,$where_values);
        }
        
        $filtered_set   = array_filter($obj_set, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);
        $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
        $obj = array_merge(['updated_at'=>$updated_at],$filtered_set);

		$arrId = $sql->pluck('id');

        $sql->update($obj);

        return DB::table(self::$tabel)->whereIn('id',$arrId)->get()->all();
    }
    ```

    5. destroy()
    ```php
    // where : ['foo'=>'bar'] >> WHERE `foo` = 'bar'
    // where : [['foo','<>','bar']] >> WHERE `foo` <> 'bar'
    // where : 'foo = ?' + where_values : ['bar'] >> `foo` = 'bar'
    public static function destroy($where, $where_values = [], $force=false) {
    	if($force) {
            $sql = DB::table(self::$tabel);
        } else {
            $sql = DB::table(self::$tabel)->whereNull('deleted_at');
        }

        if (is_array($where)) {
            if (array_is_list($where)) {
                $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
            } else {
                $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
            }
            if(empty($filtered_where)) throw new \Exception("WHERE empty after filtered, it will delete all rows in the table");
            $sql->where($filtered_where);
        } else {
            $sql->whereRaw($where,$where_values);
        }

        if($force){
            $data = $sql->get()->all();
            $sql->delete();
        } else {
            $arrId = $sql->pluck('id');
            $sql->update(['deleted_at'=>Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')))]);

            $data = DB::table(self::$tabel)->whereIn('id', $arrId)->get()->all();
        }
        return $data;
    }
    ```

    6. findAll()
    ```php
    // attributes DEFAULT self::$read_columns
    // attributes : ['foo',['bar','baz']] >> SELECT `foo`, `bar` AS `baz`
    // attributes : 'foo, bar AS baz' >> RAW 'foo, bar as baz'
    // attributes : ['foo','bar AS baz'] SELECT `foo`, `bar` AS `baz`
    // where DEFAULT "void"
    // where : ['foo'=>'bar'] >> WHERE `foo` = 'bar'
    // where : [['foo','<>','bar']] >> WHERE `foo` <> 'bar'
    // where : 'foo = ?' + where_values : ['bar'] >> `foo` = 'bar'
    // order DEFAULT self::$order
    // order : [['foo','asc'],['bar','desc']] >> ORDER BY foo ASC, bar DESC
    // order : ['foo','asc'] >> ORDER BY foo ASC
    // order : 'foo asc' >> RAW 'foo asc'
    // INFO : whatever form order is, order will apply to query as string by orderByRaw
    // limit DEFAULT "void"
    // limit : 1 >> LIMIT 1
    // offset DEFAULT "void"
    // offset : 1 >> OFFSET 1
    // if offset without limit : LIMIT 9007199254740991 OFFSET 1
    public static function findAll($input_option = ['attributes'=> [], 'where' => [], 'where_values' => [], 'order' => [], 'limit' => null, 'offset' => null, 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(empty($input_option['attributes']))      $input_option['attributes']     = self::$read_columns;
        if(empty($input_option['where']))           $input_option['where']          = false;
        if(empty($input_option['where_values']))    $input_option['where_values']   = [];
        if(empty($input_option['order']))           $input_option['order']          = self::$order;
        if(empty($input_option['limit']))           $input_option['limit']          = false;
        if(empty($input_option['offset']))          $input_option['offset']         = false;
        if(empty($input_option['paranoid']))        $input_option['paranoid']       = false;

        if(empty($output_option['sql']))            $output_option['sql']           = false;

        // INITIAL
        $sql = DB::table(self::$tabel);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
        }

        if($input_option['where'] !== false) {
            $where          = $input_option['where'];
            $where_values   = $input_option['where_values'];
            if (is_array($where)) {
                if (array_is_list($where)) {
                    $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
                    $sql->where($filtered_where);
                } else {
                    $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
                    $sql->where($filtered_where);
                }
            } else if(is_string($where)) {
                $sql->whereRaw($where,$where_values);
            } else {
                throw new \Exception("\$where is not array nor string");
            }
        }
        // END WHERE
            
        // ATTRIBUTES
        if($input_option['attributes'] !== false) {
            if(is_array($input_option['attributes'])) {
                $attributes = array_map(function($value) {
                    if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                    return $value;
                },$input_option['attributes']);
                $sql->select($attributes);
            } elseif(is_string($input_option['attributes'])) {
                $attributes = DB::raw($input_option['attributes']);
                $sql->select($attributes);
            } else {
                throw new \Exception("\$attributes is not array nor string");
            }
        }
        // END ATTRIBUTES


        // ORDER
        if($input_option['order'] !== false) {
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
                        $reduced_order = false;
                    }
                }
                if($reduced_order) $sql->orderByRaw($reduced_order);
            } elseif(is_string($input_option['order'])) {
                $reduced_order = $input_option['order'];
                $sql->orderByRaw($reduced_order);
            } else {
                throw new \Exception("\$order is not array nor string");
            }
        }
        // END ORDER

        // LIMIT & OFFSET
        if($input_option['limit'] !== false) {
            if($input_option['offset'] !== false) {
                $sql->limit($input_option['limit'])->offset($input_option['offset']);
            } else {
                $sql->limit($input_option['limit']);
            }
        } else {
            if($input_option['offset'] !== false) {
                $sql->limit('9007199254740991')->offset($input_option['offset']);
            }
        }
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

    7. findByPk()
    ```php
    public static function findByPk($id, $input_option = ['attributes'=> [], 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(empty($input_option['attributes']))      $input_option['attributes']     = self::$columns;
        if(empty($input_option['paranoid']))        $input_option['paranoid']       = false;

        if(empty($output_option['sql']))            $output_option['sql']           = false;

        // INITIAL
        $sql = DB::table(self::$tabel)->where('id',$id);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
        }
        // END WHERE
            
        // ATTRIBUTES
        if($input_option['attributes'] !== false) {
            if(is_array($input_option['attributes'])) {
                $attributes = array_map(function($value) {
                    if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                    return $value;
                },$input_option['attributes']);
                $sql->select($attributes);
            } elseif(is_string($input_option['attributes'])) {
                $attributes = DB::raw($input_option['attributes']);
                $sql->select($attributes);
            } else {
                throw new \Exception("\$attributes is not array nor string");
            }
        }
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

    8. findOne()
    ```php
    public static function findOne($input_option = ['attributes'=> [], 'where' => [], 'where_values' => [], 'order' => [], 'offset' => null, 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(empty($input_option['attributes']))      $input_option['attributes']     = self::$read_columns;
        if(empty($input_option['where']))           $input_option['where']          = false;
        if(empty($input_option['where_values']))    $input_option['where_values']   = [];
        if(empty($input_option['order']))           $input_option['order']          = self::$order;
        if(empty($input_option['offset']))          $input_option['offset']         = false;
        if(empty($input_option['paranoid']))        $input_option['paranoid']       = false;

        if(empty($output_option['sql']))            $output_option['sql']           = false;

        // INITIAL
        $sql = DB::table(self::$tabel);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
        }

        if($input_option['where'] !== false) {
            $where          = $input_option['where'];
            $where_values   = $input_option['where_values'];
            if (is_array($where)) {
                if (array_is_list($where)) {
                    $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
                    $sql->where($filtered_where);
                } else {
                    $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
                    $sql->where($filtered_where);
                }
            } else if(is_string($where)) {
                $sql->whereRaw($where,$where_values);
            } else {
                throw new \Exception("\$where is not array nor string");
            }
        }
        // END WHERE
            
        // ATTRIBUTES
        if($input_option['attributes'] !== false) {
            if(is_array($input_option['attributes'])) {
                $attributes = array_map(function($value) {
                    if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                    return $value;
                },$input_option['attributes']);
                $sql->select($attributes);
            } elseif(is_string($input_option['attributes'])) {
                $attributes = DB::raw($input_option['attributes']);
                $sql->select($attributes);
            } else {
                throw new \Exception("\$attributes is not array nor string");
            }
        }
        // END ATTRIBUTES


        // ORDER
        if($input_option['order'] !== false) {
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
                        $reduced_order = false;
                    }
                }
                if($reduced_order) $sql->orderByRaw($reduced_order);
            } elseif(is_string($input_option['order'])) {
                $reduced_order = $input_option['order'];
                $sql->orderByRaw($reduced_order);
            } else {
                throw new \Exception("\$order is not array nor string");
            }
        }
        // END ORDER

        // LIMIT & OFFSET
        $sql->limit(1);
        if($input_option['offset'] !== false)    $sql->offset($input_option['offset']);
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

    9. findOrCreate()
    ```php
    // IF INTERSECTION return empty array, throw Exception
    public static function findOrCreate($where, $default = []) {
        // INITIAL
        $sql = DB::table(self::$tabel)->select(self::$read_columns)->whereNull('deleted_at');

        // WHERE
        if (is_array($where)) {
            if (array_is_list($where)) {
                $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
                $sql->where($filtered_where);
            } else {
                $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
                $sql->where($filtered_where);
            }
        } else {
            throw new \Exception("\$where is not array");
        }
        // END WHERE
            
        // LIMIT
        $sql->limit(1);
        // END LIMIT

        // EXECUTE data
        $data = $sql->get();
        
        if($data->isEmpty()){
            $arr_assoc = array_merge(self::$default,$where,$default);

            $filtered = array_filter($arr_assoc, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);

            if(empty($filtered)) throw new \Exception("Data is NULL or none columns macth data");

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

    10. findAndCountAll()
    ```php
    public static function findAndCountAll($input_option = ['attributes'=> [], 'where' => [], 'where_values' => [], 'order' => null, 'limit' => null, 'offset' => null, 'paranoid' => false], $output_option = ['sql'=> false]) {
        if(empty($input_option['attributes']))      $input_option['attributes']     = self::$read_columns;
        if(empty($input_option['where']))           $input_option['where']          = false;
        if(empty($input_option['where_values']))    $input_option['where_values']   = [];
        if(empty($input_option['order']))           $input_option['order']          = self::$order;
        if(empty($input_option['limit']))           $input_option['limit']          = false;
        if(empty($input_option['offset']))          $input_option['offset']         = false;
        if(empty($input_option['paranoid']))        $input_option['paranoid']       = false;

        if(empty($output_option['sql']))            $output_option['sql']           = false;

        // INITIAL
        $sql = DB::table(self::$tabel);
        $num_rows = DB::table(self::$tabel);

        // WHERE
        if(!$input_option['paranoid']) {
            $sql->whereNull('deleted_at');
            $num_rows->whereNull('deleted_at');
        }

        if($input_option['where'] !== false) {
            $where          = $input_option['where'];
            $where_values   = $input_option['where_values'];
            if (is_array($where)) {
                if (array_is_list($where)) {
                    $filtered_where = array_filter($where, fn($value) => in_array($value[0], self::$columns));
                    $sql->where($filtered_where);
                    $num_rows->where($filtered_where);
                } else {
                    $filtered_where = array_filter($where, fn($key) => in_array($key, self::$columns), ARRAY_FILTER_USE_KEY);
                    $sql->where($filtered_where);
                    $num_rows->where($filtered_where);
                }
            } elseif(is_string($where)) {
                $sql->whereRaw($where,$where_values);
                $num_rows->whereRaw($where,$where_values);
            } else {
                throw new \Exception("\$where is not array nor string");
            }
        }
        // END WHERE

        // EXECUTE num_rows
        $count = $num_rows->count();
            
        // ATTRIBUTES
        if($input_option['attributes'] !== false) {
            if(is_array($input_option['attributes'])) {
                $attributes = array_map(function($value) {
                    if(is_array($value)) return "{$value[0]} AS {$value[1]}";
                    return $value;
                },$input_option['attributes']);
                $sql->select($attributes);
            } elseif(is_string($input_option['attributes'])) {
                $attributes = DB::raw($input_option['attributes']);
                $sql->select($attributes);
            } else {
                throw new \Exception("\$attributes is not array nor string");
            }
        }
        // END ATTRIBUTES


        // ORDER
        if($input_option['order'] !== false) {
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
                        $reduced_order = false;
                    }
                }
                if($reduced_order) $sql->orderByRaw($reduced_order);
            } elseif(is_string($input_option['order'])) {
                $reduced_order = $input_option['order'];
                $sql->orderByRaw($reduced_order);
            } else {
                throw new \Exception("\$order is not array nor string");
            }
        }
        // END ORDER

        // LIMIT & OFFSET
        if($input_option['limit'] !== false) {
            if($input_option['offset'] !== false) {
                $sql->limit($input_option['limit'])->offset($input_option['offset']);
            } else {
                $sql->limit($input_option['limit']);
            }
        } else {
            if($input_option['offset'] !== false) {
                $sql->limit('9007199254740991')->offset($input_option['offset']);
            }
        }
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

    11. empty()
    ```php
   public static function empty($attributes = []){
        if(empty($attributes)) {
            $obj = array_fill_keys(self::$columns, null);
        } else {
            $obj = array_fill_keys($attributes, null);
        }
        return (object) $obj;
    }
    ```
    12. check()
    ```php
    public static function check($id) {
    	$data = DB::table(self::$tabel)->select('id')->where('id',$id)->whereNull('deleted_at')->limit(1)->get()->all();

    	if(empty($data)) return FALSE;
    	return TRUE;
    }
    ``` 
9. Jika ingin membuat standar model lainnya, ulangi langkah 5 - 8. Jangan lupa penyesuaian pada langkah 7.
10. Untuk type data id (primary key) yang berbeda, akan ada perbedaan pada *method* create(), bulkCreate(), bulkSync(), findOrCreate()

## STANDAR CONTROLLER
1. Install validation via composer `composer require respect/validation` <sub>2024-06</sub>
2. Buat controller `User` di `app/Http/Controllers`, sehingga menjadi `app/Http/Controllers/User.php`
3. Import model `UserModel` dengan syntax `use App\Models\UserModel as Model;` pada `User`. Karena `UserModel` di `User` Controller harus bernama `Model`
4. Buat *method* di dalam *User class*
    1. register()
    ```php
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
    ```
    2. registerBulk()
    ```php
    public function registerBulk(Request $request) {
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
	        $data = Model::bulkCreate($arrObj,$additional);
	
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
    ```
    3. sync()
    ```php
    public function sync(Request $request) {
	    $body = $request->post();
	
	    if(!(array_key_exists('bulk_data', $body) && is_array($body['bulk_data']) && array_is_list($body['bulk_data']) && !empty($body['bulk_data']))) {
	        $res = new \stdClass();
	        $res->error_code = 400;
	        $res->error_desc = 'bulk_data not exists or bulk_data is not array or bulk_data is nor array list or bulk_data is empty array';
	        $res->data = [];
	
	        return response()->json($res,200);
	    }
	
		$arrObj = $request->only(['bulk_data']);
		$additional = $request->except(['bulk_data']);
	    
	    try {
	        $data = Model::bulkSync($arrObj,$additional);
	
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
    ```  
    4. update()
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
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }
    ```
    5. delete()
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
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }
    ```

    6. list()
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
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }
    ```

    7. detailById()
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
            $res->data = env('APP_DEBUG')?$e->getMessage():[];
            return response()->json($res,200);
        }
    }
    ```
5. Jika ingin membuat standar controller lainnya, ulangi langkah 2 - 4. Jangan lupa penyesuaian pada langkah 3.

## STANDAR ROUTING

```php
<?php
$controller = 'User';
$router->group(['prefix'=>'user','middleware'=>'auth'], function () use ($router,$controller) {
    $router->post('register',         $controller.'@'.'register');
$router->post('register-bulk',         $controller.'@'.'registerBulk');
$router->post('sync',         $controller.'@'.'sync');
    $router->post('update/{id}',      $controller.'@'.'update');
    $router->get('delete/{id}',       $controller.'@'.'delete');
    $router->post('list',              $controller.'@'.'list');
$router->get('list',              $controller.'@'.'list');
    $router->get('detail-by-id/{id}', $controller.'@'.'detailById');
});
```
