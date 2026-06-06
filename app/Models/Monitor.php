<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Monitor extends Model
{
    protected $fillable = ['name', 'type', 'target', 'port', 'interval', 'timeout', 'active', 'status', 'latency', 'last_checked_at', 'discord_webhook'];

    protected $casts = ['active' => 'boolean', 'last_checked_at' => 'datetime'];

    public function logs()
    {
        return $this->hasMany(MonitorLog::class);
    }

    public function uptimePercent(): float
    {
        $total = $this->logs()->where('checked_at', '>=', now()->subHours(24))->count();
        if ($total === 0) return 0;
        $up = $this->logs()->where('checked_at', '>=', now()->subHours(24))->where('status', 'up')->count();
        return round(($up / $total) * 100, 2);
    }
}
