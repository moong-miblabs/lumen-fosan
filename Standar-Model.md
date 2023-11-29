# STANDAR MODEL (POV CONTROLLER)


## PENAMAAN MODEL
1. Nama model selalu `PascalCase`
2. Jika nama tabel dalam bentuk `plural`, maka nama model tetap `singular`
3. Nama tabel adalah kata benda dan kata dasar

tabel `user` :point_right: `UserModel`

tabel `ref_provinsi` :point_right: `ProvinsiModel`

tabel `ref_kab_kota` :point_right: `KabKotaModel`

tabel `ms_mahasiswa` :point_right: `MahasiswaModel`

## STUDY CASE
Tabel users

|id|nama_user|username_user|password_user|created_at|updated_at|deleted_at|
|--|--|--|--|--|--|--|

## create
Standar Model menyediakan *method* `create` untuk pembuatan `record`, dengan menerima suatu `array associative`
```php
use App\Models\UserModel;

$data = Model::create(['nama_user'=>'Barbossa']);
```
akan mengeksekusi SQL
```sql
INSERT INTO users (id,nama_user,created_at,updated_at) VALUES ('0279c5be-aa94-4065-8faf-6f97d3be3d04', 'Barbossa', '1993-06-10 17:32', '1993-06-10 17:32')
```
dimana :
1. `id` merupakan UUID random yang di*generate* otomatis oleh model (dengan bantuan `ramsey/uuid`)
2. `created_at` dan `updated_at` merupakan waktu sekarang dengan format `yyyy-mm-dd hh:ii:ss` di*generate* otomatis oleh model (dengan bantuan `nesbot/carbon`)

*method* `create` akan mengembalikan `array object` berisi semua kolom dari tabel `users`
```php
use App\Models\UserModel;

$data = Model::create(['nama_user'=>'Barbossa']);

$data->id;		// '0279c5be-aa94-4065-8faf-6f97d3be3d04'
$data->nama_user;	// 'Barbossa'
$data->username_user;	// NULL
$data->password_user;	// NULL
$data->created_at;	// '1993-06-10 17:32'
$data->updated_at;	// '1993-06-10 17:32'
$data->deleted_at;	// NULL
```

>
> walaupun `$data = Model::create(['nama_user'=>'Barbossa','querty'=>'some foo']);`, maka `querty` akan diabaikan, karena `create` menggunakan `static write_column` sebagai intersection
>

## bulkCreate
Standar Model menyediakan *method* `bulkCreate` untuk memungkinkan pembuatan beberapa `record` sekaligus, hanya dengan satu SQL.
Penggunaan `bulkCreate` sangat mirip dengan `create`, dengan menerima `array dari array associative`, ***bukan*** satu `array associative`.
```php
use App\Models\UserModel;

$data = Model::create([
	[ 'nama_user' => 'Jack Sparrow' ],
	[ 'nama_user' => 'Davy Jones' ]
]);
```
akan mengeksekusi SQL
```sql
INSERT INTO users (id,nama_user,created_at,updated_at) VALUES
	('72c7511c-888a-11ee-b9d1-0242ac120002', 'Jack Sparrow', '1993-06-10 17:32', '1993-06-10 17:32'),
	('72c75478-888a-11ee-b9d1-0242ac120002', 'Davy Jones', '1993-06-10 17:32', '1993-06-10 17:32'),
```
*method* `bulkCreate` akan mengembalikan `array dari array object` berisi semua kolom dari tabel `users`
```php
use App\Models\UserModel;

$data = Model::create(['nama_user'=>'Barbossa']);

$data[0]->id;			// '72c7511c-888a-11ee-b9d1-0242ac120002'
$data[0]->nama_user;		// 'Jack Sparro'
$data[0]->username_user;	// NULL
$data[0]->password_user;	// NULL
$data[0]->created_at;		// '1993-06-10 17:32'
$data[0]->updated_at;		// '1993-06-10 17:32'
$data[0]->deleted_at;		// NULL

$data[1]->id;			// '72c75478-888a-11ee-b9d1-0242ac120002'
$data[1]->nama_user;		// 'Davy Jones'
$data[1]->username_user;	// NULL
$data[1]->password_user;	// NULL
$data[1]->created_at;		// '1993-06-10 17:32'
$data[1]->updated_at;		// '1993-06-10 17:32'
$data[1]->deleted_at;		// NULL
```

## \_update
Standar Model menyediakan *method* `_update` untuk mengubah `record`, dengan menerima suatu `array associative` dan `where expression`
```php
use App\Models\UserModel;

$data = Model::_update(['nama_user'=>'Joshamee Gibbs'],['nama_user'=>'Jack Sparrow']);
```
akan mengeksekusi SQL
```sql
UPDATE users SET nama_user = 'Joshamee Gibbs', updated_at = '1993-06-10 17:32' WHERE deleted_at IS NULL AND (nama_user = 'Jack Sparrow')
```
dimana :
1. `updated_at` merupakan waktu sekarang dengan format `yyyy-mm-dd hh:ii:ss` di*generate* otomatis oleh model (dengan bantuan `nesbot/carbon`)

*method* `\update` akan mengembalikan `array dari array object` berisi semua kolom dari record yang berubah

>
>	`where expression` akan dipelajari lebih lanjut
>

## destroy
Standar Model menyediakan *method* `destroy` untuk menghapus `record`, dengan menerima suatu `where expression`
Secara Default, destroy akan melakukan `soft delete`, yaitu menandai record dengan mengisi kolom `deleted_at`
```php
use App\Models\UserModel;

$data = Model::destroy(['nama_user'=>'Joshamee Gibbs']);
```
akan mengeksekusi SQL
```sql
UPDATE users SET deleted_at = '1993-06-10 17:32' WHERE deleted_at IS NULL AND (nama_user = 'Joshamee Gibbs')
```
dimana :
1. `deleted_at` merupakan waktu sekarang dengan format `yyyy-mm-dd hh:ii:ss` di*generate* otomatis oleh model (dengan bantuan `nesbot/carbon`)

Untuk menghapus dari `record` dari tabel maka masukan parameter boolean `true` di parameter ketiga
```php
use App\Models\UserModel;

$data = Model::destroy(['nama_user'=>'Joshamee Gibbs'],[],true);
```
akan mengeksekusi SQL
```sql
DELETE FROM users WHERE nama_user = 'Joshamee Gibbs'
```

*method* `destroy` akan mengembalikan `array dari array object` berisi semua kolom dari `record` yang terhapus

## where expression
where expression adalah ekspresi untuk mengisi where di SQL. where expression belum dituliskan dalam beberapa opsi

1. Array Associate

```php
['foo'=>'bar']
```
menjadi
```sql
WHERE foo = 'bar'
```

```php
['foo'=>'bar','foz'=>'baz']
```
menjadi
```sql
WHERE foo = 'bar' AND foz = 'baz'
```

2. Array dari Array List

```php
[
	['foo','>',45]
]
```
menjadi
```sql
WHERE foo > 45
```

```php
[
	['foo','>',45],
	['foz','<>','baz']
]
```
menjadi
```sql
WHERE foo = 'bar' AND foz <> 'baz'
```

3. String dengan binding
```php
$where 			= "foo = ? AND foz <> ?";
$where_values 	= ['bar','baz'];
```

menjadi
```sql
WHERE foo = 'bar' AND foz <> 'baz'
```