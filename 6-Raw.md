# STANDAR

## Controller
1. Buat controller `Raw` di `app/Http/Controllers`, sehingga menjadi `app/Http/Controllers/Raw.php`
2. Import `Illuminate\Support\Facades\DB` dengan manambahkan baris `use Illuminate\Support\Facades\DB;`
3. Buat *method* index di dalam *Raw class*
```php
<?php 
public function index(Request $request){
	$text 	= $request->post('text');
	$values = $request->post('values');

	if(strtoupper(substr($text,0,6)) !== "SELECT") {
		$res = new \stdClass();
        $res->error_code = 405;
        $res->error_desc = 'Method Not Allowed';
        $res->data = [];
        return response()->json($res,200);
	}
    try {
        $data  = $users = DB::select($text, $values);

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

## Router
```php
<?php
$router->post('raw', [
	'middleware'	=> 'auth',
	'uses' 			=> 'Raw@index'
]);
```