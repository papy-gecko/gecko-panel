<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class SystemdController extends Controller
{
    public function services(): JsonResponse
    {
        $raw = shell_exec('systemctl list-units --type=service --all --no-pager --no-legend 2>/dev/null');
        $services = [];
        foreach (explode("\n", trim($raw ?? '')) as $line) {
            if (!trim($line)) continue;
            preg_match('/^\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.*)$/', $line, $m);
            if (!$m || !str_ends_with($m[1],'.service')) continue;
            $services[] = ['unit'=>$m[1],'name'=>str_replace('.service','',$m[1]),'load'=>$m[2],'active'=>$m[3],'sub'=>$m[4],'description'=>trim($m[5]),'state'=>$m[3]==='active'?($m[4]==='running'?'running':'active'):$m[3]];
        }
        return response()->json($services);
    }
    public function serviceAction(Request $request, string $service): JsonResponse
    {
        if (!preg_match('/^[\w.\-]+$/',$service)) return response()->json(['error'=>'Nom invalide'],400);
        $action = $request->input('action');
        if (!in_array($action,['start','stop','restart','reload','enable','disable'])) return response()->json(['error'=>'Action non autorisée'],403);
        $unit = str_ends_with($service,'.service')?$service:$service.'.service';
        $out = shell_exec("sudo systemctl {$action} ".escapeshellarg($unit)." 2>&1");
        $status = trim(shell_exec("systemctl is-active ".escapeshellarg($unit)." 2>&1")?:'');
        return response()->json(['output'=>trim($out??''),'status'=>$status]);
    }
    public function serviceLogs(string $service): JsonResponse
    {
        if (!preg_match('/^[\w.\-]+$/',$service)) return response()->json(['error'=>'Nom invalide'],400);
        $unit = str_ends_with($service,'.service')?$service:$service.'.service';
        $out = shell_exec("journalctl -u ".escapeshellarg($unit)." -n 100 --no-pager 2>&1");
        return response()->json(['logs'=>$out]);
    }
}
