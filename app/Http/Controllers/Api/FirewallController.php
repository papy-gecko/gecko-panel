<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class FirewallController extends Controller
{
    public function status(): JsonResponse
    {
        $status = shell_exec('sudo ufw status verbose 2>&1');
        $numbered = shell_exec('sudo ufw status numbered 2>&1');
        return response()->json(['status' => $status, 'numbered' => $numbered, 'active' => str_contains($status ?? '', 'active')]);
    }

    public function allow(Request $request): JsonResponse
    {
        $port  = $request->input('port');
        $proto = $request->input('proto', 'tcp');
        if (!preg_match('/^\d{1,5}(:\d{1,5})?$/', $port)) return response()->json(['error' => 'Port invalide'], 400);
        if (!in_array($proto, ['tcp','udp','any'])) return response()->json(['error' => 'Protocole invalide'], 400);
        $out = shell_exec("sudo ufw allow {$port}/{$proto} 2>&1");
        return response()->json(['output' => trim($out)]);
    }

    public function deny(Request $request): JsonResponse
    {
        $port  = $request->input('port');
        $proto = $request->input('proto', 'tcp');
        if (!preg_match('/^\d{1,5}(:\d{1,5})?$/', $port)) return response()->json(['error' => 'Port invalide'], 400);
        $out = shell_exec("sudo ufw deny {$port}/{$proto} 2>&1");
        return response()->json(['output' => trim($out)]);
    }

    public function deleteRule(Request $request): JsonResponse
    {
        $num = (int)$request->input('num');
        if ($num < 1 || $num > 100) return response()->json(['error' => 'Numéro invalide'], 400);
        $out = shell_exec("echo y | sudo ufw delete {$num} 2>&1");
        return response()->json(['output' => trim($out)]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $action = $request->input('action');
        if (!in_array($action, ['enable','disable'])) return response()->json(['error' => 'Action invalide'], 400);
        $out = shell_exec("echo y | sudo ufw {$action} 2>&1");
        return response()->json(['output' => trim($out)]);
    }
}
