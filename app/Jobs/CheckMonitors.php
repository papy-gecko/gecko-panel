<?php
namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class CheckMonitors 
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Monitor::where('active', true)->each(function (Monitor $monitor) {
            $start = microtime(true);
            $status = 'down';
            $message = null;

            try {
                if ($monitor->type === 'http') {
                    $response = Http::timeout($monitor->timeout)->get($monitor->target);
                    $status = $response->status() < 500 ? 'up' : 'down';
                    $message = 'HTTP ' . $response->status();
                } elseif ($monitor->type === 'tcp') {
                    $fp = @fsockopen($monitor->target, $monitor->port, $errno, $errstr, $monitor->timeout);
                    if ($fp) { fclose($fp); $status = 'up'; } else { $message = $errstr; }
                } elseif ($monitor->type === 'ping') {
                    exec("ping -c 1 -W {$monitor->timeout} {$monitor->target}", $out, $code);
                    $status = $code === 0 ? 'up' : 'down';
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }

            $latency = round((microtime(true) - $start) * 1000);
            $wasDown = $monitor->status === 'down';

            $monitor->update(['status' => $status, 'latency' => $latency, 'last_checked_at' => now()]);

            MonitorLog::create(['monitor_id' => $monitor->id, 'status' => $status, 'latency' => $latency, 'message' => $message, 'checked_at' => now()]);

            // Alerte Discord si changement d'état
            if ($monitor->discord_webhook && $wasDown !== ($status === 'down')) {
                $emoji = $status === 'up' ? '✅' : '❌';
                Http::post($monitor->discord_webhook, [
                    'content' => "{$emoji} **{$monitor->name}** est maintenant **" . strtoupper($status) . "** (latence: {$latency}ms)"
                ]);
            }
        });
    }
}
