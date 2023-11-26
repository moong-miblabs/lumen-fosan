<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

class UserModel extends Model {
    use HasFactory;

    protected $table = 'users';
    static $tabel           = 'users';
    static $columns         = ['id','nama_user','username_user','password_user','created_at','updated_at','deleted_at'];
    static $write_columns   = ['nama_user','username_user','password_user'];
    static $read_columns    = ['id','nama_user','username_user','password_user'];
    static $order           = [['created_at', 'desc'], ['id', 'desc']];

    public static function create($arr_assoc = []) {
        $filtered = array_filter($arr_assoc, fn($key) => in_array($key, self::$write_columns), ARRAY_FILTER_USE_KEY);

        $id = Uuid::uuid4();
        $created_at = $updated_at = Carbon::now(new \DateTimeZone(env('APP_TIMEZONE','Asia/Jakarta')));
        $obj = array_merge(['id'=>$id,'created_at'=>$created_at,'updated_at'=>$updated_at],$filtered);

        DB::table(self::$tabel)->insert($obj);

        return DB::table(self::$tabel)->find($obj['id']);
    }

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

    public static function empty(){
        return array_fill_keys(self::$columns, null);
    }
}
