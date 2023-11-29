# Aturan Tabel

## TIPE DATA

### String

|Tipe Data|Mysql|PostgreSQL|
|--|--|--|
|STRING|VARCHAR(255)|VARCHAR(255)|
|TEXT|TEXT|TEXT|
|CHAR|CHAR(255)|CHAR(255)|

### Boolean

|Tipe Data|Mysql|PostgreSQL|
|--|--|--|
|BOOLEAN|BOOLEAN|BOOLEAN|

### Integer

|Tipe Data|Mysql|PostgreSQL
|--|--|--|
|TINY INTEGER|TINYINT|SMALLINT|
|SMALL INTEGER|SMALLINT|SMALLINT|
|MEDIUM INTEGER|INT|INT|
|INTEGER|INT|INT|
|BIG INTIGER|BIGINT|BIGINT|

### Float (desimal)

|Tipe Data|Mysql|PostgreSQL|
|--|--|--|
|FLOAT|FLOAT|REAL|

### Tanggal

|Tipe Data|Mysql|PostgreSQL|
|--|--|--|
|DATE|DATETIME|TIMESTAMP WITH TIME ZONE|
|DATE ONLY|DATE|DATE|
|TIME ONLY|TIME|TIME|

## PENAMAAN TABEL dan KOLOM

setiap tabel dan kolom harus dinamai dengan ikut aturan `snake_case`

## PRIMARY KEY (id)

setiap tabel harus punya kolom `id` dengan tipe data `CHAR(36)` sebagai `PRIMARY KEY`

## PARAMETER WAKTU 

setiap tabel harus punya parameter waktu
1. `created_at` dengan tipe data `DATETIME` / `TIMESTAMP WITH TIME ZONE` dan tidak boleh null `NOT NULL` 
2. `updated_at` dengan tipe data `DATETIME` / `TIMESTAMP WITH TIME ZONE` dan tidak boleh null `NOT NULL` 
3. `deleted_at` dengan tipe data `DATETIME` / `TIMESTAMP WITH TIME ZONE` dan default null `DEFAULT NULL` 

>
> Tabel yang mengikuti 'aturan tabel' ini, dapat dibaca dengan mudah oleh 'standar model'
>