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
