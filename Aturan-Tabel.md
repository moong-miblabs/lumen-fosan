# Aturan Tabel

## TIPE DATA

### String

|Tipe Data|Mysql|
|--|--|
|STRING|VARCHAR(255)|
|TEXT|TEXT|
|CHAR|CHAR(255)|

### Boolean

|Tipe Data|Mysql|
|--|--|
|BOOLEAN|BOOLEAN|

### Integer

|Tipe Data|Mysql|
|--|--|
|TINYINT|TINYINT|
|SMALLINT|SMALLINT|
|INTEGER|INTEGER|
|BIGINT|BIGINT|

### Float (desimal)

|Tipe Data|Mysql|
|--|--|
|FLOAT|FLOAT|

### Tanggal

|Tipe Data|Mysql|
|--|--|
|DATE|DATETIME|
|DATE ONLY|DATE|
|TIME ONLY|TIME|

## PENAMAAN TABEL dan KOLOM

setiap tabel dan kolom harus dinamai dengan ikut aturan `snake_case`

## PRIMARY KEY (id)

setiap tabel harus punya kolom `id` dengan tipe data `CHAR(36)` sebagai `PRIMARY KEY`

## PARAMETER WAKTU 

setiap tabel harus punya parameter waktu
1. `created_at` dengan tipe data `DATETIME` dan tidak boleh null `NOT NULL` 
2. `updated_at` dengan tipe data `DATETIME` dan tidak boleh null `NOT NULL` 
3. `deleted_at` dengan tipe data `DATETIME` dan default null `DEFAULT NULL` 

>
> Tabel yang mengikuti 'aturan tabel' ini, dapat dibaca dengan mudah oleh 'standar model'
>