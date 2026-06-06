<?php

namespace App\Observers;

use App\Models\Server;
use App\Models\ServerDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServerDatabaseObserver
{
    public function created(Server $server): void
    {
        $dbName = 's' . $server->id . '_' . Str::slug($server->name, '_');
        $dbUser = 'srv_' . $server->id . '_' . Str::random(6);
        $dbPassword = Str::random(24);

        $dbName = substr($dbName, 0, 64);
        $dbUser = substr($dbUser, 0, 32);

        DB::connection('mysql_admin')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
        DB::connection('mysql_admin')->statement("CREATE USER '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}'");
        DB::connection('mysql_admin')->statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%'");
        DB::connection('mysql_admin')->statement("FLUSH PRIVILEGES");

        ServerDatabase::create([
            'server_id'   => $server->id,
            'db_name'     => $dbName,
            'db_user'     => $dbUser,
            'db_password' => $dbPassword,
        ]);
    }

    public function deleted(Server $server): void
    {
        $db = ServerDatabase::where('server_id', $server->id)->first();
        if (!$db) return;

        DB::connection('mysql_admin')->statement("DROP DATABASE IF EXISTS `{$db->db_name}`");
        DB::connection('mysql_admin')->statement("DROP USER IF EXISTS '{$db->db_user}'@'%'");
        DB::connection('mysql_admin')->statement("FLUSH PRIVILEGES");
    }
}
