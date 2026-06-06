<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class Fail2banController extends Controller
{
    public function status(): JsonResponse
    {
        $status = shell_exec('sudo fail2ban-client status 2>&1');
        return response()->json(['status' => $status, 'active' => !str_contains($status ?? '', 'not running')]);
    }

    public function jail(string $name): JsonResponse
    {
        if (!preg_match('/^[\w\-]+$/', $name)) return response()->json(['error' => 'Nom invalide'], 400);
        $out = shell_exec("sudo fail2ban-client status ".escapeshellarg($name)." 2>&1");
        // Parse les IPs bannies
        preg_match('/Banned IP list:\s*(.*)/', $out ?? '', $m);
        $banned = isset($m[1]) ? array_filter(explode(' ', trim($m[1]))) : [];
        return response()->json(['raw' => $out, 'banned' => array_values($banned)]);
    }

    public function unban(Request $request): JsonResponse
    {
        $ip   = $request->input('ip');
        $jail = $request->input('jail', '--all');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return response()->json(['error' => 'IP invalide'], 400);
        $jailArg = $jail === '--all' ? '--all' : escapeshellarg($jail);
        $out = shell_exec("sudo fail2ban-client unban {$jailArg} ".escapeshellarg($ip)." 2>&1");
        return response()->json(['output' => trim($out)]);
    }

    public function ban(Request $request): JsonResponse
    {
        $ip   = $request->input('ip');
        $jail = $request->input('jail', 'sshd');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return response()->json(['error' => 'IP invalide'], 400);
        if (!preg_match('/^[\w\-]+$/', $jail)) return response()->json(['error' => 'Jail invalide'], 400);
        $out = shell_exec("sudo fail2ban-client set ".escapeshellarg($jail)." banip ".escapeshellarg($ip)." 2>&1");
        return response()->json(['output' => trim($out)]);
    }
}
