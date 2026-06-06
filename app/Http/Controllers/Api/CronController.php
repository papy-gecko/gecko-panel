<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class CronController extends Controller
{
    public function list(): JsonResponse
    {
        $raw = shell_exec('crontab -l 2>/dev/null') ?? '';
        $lines = explode("\n", trim($raw));
        $crons = [];
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (!$line || str_starts_with($line, '#')) continue;
            $parts = preg_split('/\s+/', $line, 6);
            $crons[] = [
                'id'      => $i,
                'raw'     => $line,
                'schedule'=> count($parts) >= 5 ? implode(' ', array_slice($parts, 0, 5)) : '?',
                'command' => count($parts) >= 6 ? $parts[5] : ($parts[count($parts)-1] ?? ''),
            ];
        }
        return response()->json($crons);
    }

    public function add(Request $request): JsonResponse
    {
        $schedule = $request->input('schedule');
        $command  = $request->input('command');
        if (!$schedule || !$command) return response()->json(['error' => 'Champs manquants'], 400);

        $current = shell_exec('crontab -l 2>/dev/null') ?? '';
        $new = rtrim($current)."\n{$schedule} {$command}\n";
        $tmp = tempnam('/tmp', 'cron');
        file_put_contents($tmp, $new);
        shell_exec("crontab {$tmp} 2>&1");
        unlink($tmp);
        return response()->json(['ok' => true]);
    }

    public function delete(Request $request): JsonResponse
    {
        $raw = $request->input('raw');
        $current = shell_exec('crontab -l 2>/dev/null') ?? '';
        $lines = array_filter(explode("\n", $current), fn($l) => trim($l) !== trim($raw));
        $new = implode("\n", $lines)."\n";
        $tmp = tempnam('/tmp', 'cron');
        file_put_contents($tmp, $new);
        shell_exec("crontab {$tmp} 2>&1");
        unlink($tmp);
        return response()->json(['ok' => true]);
    }
}
