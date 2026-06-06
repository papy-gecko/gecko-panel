<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class LogController extends Controller
{
    private function getLogs(): array
    {
        $logs = [
            'journalctl' => 'journalctl',
            'nginx'      => '/var/log/nginx/access.log',
            'nginx_err'  => '/var/log/nginx/error.log',
            'laravel'    => '/var/www/pelican/storage/logs/laravel-'.date('Y-m-d').'.log',
            'dpkg'       => '/var/log/dpkg.log',
            'auth'       => '/var/log/auth.log',
            'syslog'     => '/var/log/syslog',
        ];
        return $logs;
    }

    public function list(): JsonResponse
    {
        $available = [];
        foreach ($this->getLogs() as $name => $path) {
            if ($path === 'journalctl') {
                $available[] = ['name' => $name, 'path' => $path, 'size' => '—', 'modified' => date('Y-m-d H:i:s')];
            } elseif (file_exists($path) && is_readable($path)) {
                $available[] = ['name' => $name, 'path' => $path, 'size' => $this->fmtSize(filesize($path)), 'modified' => date('Y-m-d H:i:s', filemtime($path))];
            }
        }
        return response()->json($available);
    }

    public function tail(Request $request): JsonResponse
    {
        $log   = $request->input('log', 'journalctl');
        $lines = min((int)$request->input('lines', 100), 500);

        if ($log === 'journalctl' || !isset($this->getLogs()[$log])) {
            $output = shell_exec("journalctl -n {$lines} --no-pager 2>&1");
            return response()->json(['content' => $output]);
        }

        $path = $this->getLogs()[$log];
        if (!file_exists($path) || !is_readable($path)) {
            return response()->json(['content' => "Fichier non disponible: {$path}"]);
        }

        $output = shell_exec("tail -n {$lines} ".escapeshellarg($path)." 2>&1");
        return response()->json(['content' => $output]);
    }

    public function search(Request $request): JsonResponse
    {
        $log   = $request->input('log', 'journalctl');
        $query = $request->input('query', '');
        if (!$query) return response()->json(['content' => '']);

        if ($log === 'journalctl') {
            $output = shell_exec("journalctl --no-pager | grep -i ".escapeshellarg($query)." | tail -200 2>&1");
            return response()->json(['content' => $output ?: 'Aucun résultat']);
        }

        $path = $this->getLogs()[$log] ?? null;
        if (!$path || !file_exists($path)) return response()->json(['content' => 'Log non disponible']);

        $output = shell_exec("grep -i ".escapeshellarg($query)." ".escapeshellarg($path)." | tail -200 2>&1");
        return response()->json(['content' => $output ?: 'Aucun résultat']);
    }

    private function fmtSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes/1048576,1).' MB';
        if ($bytes >= 1024)   return round($bytes/1024,1).' KB';
        return $bytes.' B';
    }
}
