<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ProcessController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $sort = $request->input('sort','cpu');
        $search = $request->input('search','');
        $raw = shell_exec('ps aux --sort=-pcpu 2>/dev/null');
        $processes = [];
        $lines = explode("\n", trim($raw??''));
        array_shift($lines);
        foreach ($lines as $line) {
            if (!trim($line)) continue;
            $parts = preg_split('/\s+/', trim($line), 11);
            if (count($parts) < 11) continue;
            $cmd = $parts[10];
            if ($search && !str_contains(strtolower($cmd),strtolower($search)) && !str_contains($parts[1],$search)) continue;
            $processes[] = ['user'=>$parts[0],'pid'=>(int)$parts[1],'cpu'=>(float)$parts[2],'mem'=>(float)$parts[3],'vsz'=>$this->fmtKb((int)$parts[4]),'stat'=>$parts[7],'command'=>strlen($cmd)>80?substr($cmd,0,80).'…':$cmd];
        }
        usort($processes, fn($a,$b) => $sort==='mem'?$b['mem']<=>$a['mem']:($sort==='pid'?$a['pid']<=>$b['pid']:$b['cpu']<=>$a['cpu']));
        return response()->json(array_slice($processes,0,100));
    }
    public function kill(Request $request): JsonResponse
    {
        $pid = (int)$request->input('pid');
        $signal = $request->input('signal','TERM');
        if (!in_array($signal,['TERM','KILL','HUP','INT'])) return response()->json(['error'=>'Signal non autorisé'],403);
        if ($pid<=1||$pid>4194304) return response()->json(['error'=>'PID invalide'],400);
        $out = shell_exec("kill -".escapeshellarg($signal)." ".$pid." 2>&1");
        return response()->json(['output'=>trim($out??'Signal envoyé')]);
    }
    private function fmtKb(int $kb): string { if($kb>=1048576) return round($kb/1048576,1).' GB'; if($kb>=1024) return round($kb/1024,1).' MB'; return $kb.' KB'; }
}
