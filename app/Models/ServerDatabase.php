<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerDatabase extends Model
{
    protected $fillable = ['server_id', 'db_name', 'db_user', 'db_password'];

    protected $casts = [];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
