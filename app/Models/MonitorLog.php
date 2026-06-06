<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MonitorLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['monitor_id', 'status', 'latency', 'message', 'checked_at'];
    protected $casts = ['checked_at' => 'datetime'];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
