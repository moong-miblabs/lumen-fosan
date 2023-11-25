# SETUP
1. *uncomment* `// $app->withFacades();` menjadi `$app->withFacades();` di file `bootstrap.php/app.php`
2. Buat Model `SetupModel.php` di `app/Models`, sehingga menjadi `app/Models/SetupModel.php`
3. Import facdes DB dengan syntax `use Illuminate\Support\Facades\DB;` pada `SetupModel`
4. buat 3 *method* dengan nama : dbsync, seed, drop pada *SetupModel class*. Isi dengan tabel [**baca aturan tabel**]
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class SetupModel extends Model {
    use HasFactory;

    public static function dbsync(){
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS users(
                id CHAR(36) PRIMARY KEY, /* UUID length */
                nama_user VARCHAR(100),
                username_user VARCHAR(100),
                password_user VARCHAR(60), /* Bcrypt length */
                created_at TIMESTAMP WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP WITH TIME ZONE NOT NULL,
                deleted_at TIMESTAMP WITH TIME ZONE DEFAULT NULL
            );
        ");
    }

    public static function seed(){
        DB::unprepared("
            INSERT INTO users(id,nama_user,username_user,password_user,created_at,updated_at) VALUES ('00000000-0000-0000-0000-000000000000','ADMIN','admin','\$2a\$10\$YNvqg2vig8tZpqdz/l2SruQk1On0seDza0UF.OaN2gAroTAObmw/G',NOW(),NOW()) ON CONFLICT (id) DO NOTHING;
        ");
    }

    public static function drop(){
        DB::unprepared("
            DROP TABLE IF EXISTS users;
        ");
    }
}

```

5. Buat Controller `Setup` di `app/Http/Controllers`, sehingga menjadi `app/Http/Controllers/Setup`
6. Import `SetupModel` dengan syntax `use App\Models\SetupModel as Model;` pada `Setup`. Karena `SetupModel` di `Setup` Controller harus bernama `Model`
7. Buat 3 *method* dengan nama : dbsync, seed di dalam *Setup class*
```php
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

```
9. Buat router gruop di `routes/web.php`
```php
$controller = 'Setup';
$router->group(['prefix'=>'setup'],function () use ($router,$controller) {
    Route::get('dbsync',$controller.'@'.'dbsync');
    Route::get('seed',$controller.'@'.'seed');
    Route::get('drop',$controller.'@'.'drop');
});
```
10. Akses `http://book-be.local/api/setup/dbsync`, kemudian cek apakah tabel sudah terbuat
11. Akses `http://book-be.local/api/setup/seed`, kemudian cek apakah tabel sudah terisi
12. Akses `http://book-be.local/api/setup/seed`, untuk menghapus tabel apabila diperlukan
13. Jika tabel sudah terbuat maka, **WAJIB** comment router group
```php
// $controller = 'Setup';
// $router->group(['prefix'=>'setup'],function () use ($router,$controller) {
//     Route::get('dbsync',$controller.'@'.'dbsync');
//     Route::get('seed',$controller.'@'.'seed');
//     Route::get('drop',$controller.'@'.'drop');
// });
```