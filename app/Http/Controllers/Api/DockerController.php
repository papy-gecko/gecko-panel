<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class DockerController extends Controller
{
    public function containers(): JsonResponse
    {
        $raw = shell_exec('docker ps -a --format "{{json .}}" 2>/dev/null');
        $containers = [];
        foreach (explode("\n", trim($raw ?? '')) as $line) {
            if (!$line) continue;
            $c = json_decode($line, true);
            if ($c) $containers[] = [
                'id'     => substr($c['ID']??'',0,12),
                'name'   => ltrim($c['Names']??'','/'),
                'image'  => $c['Image']??'',
                'status' => $c['Status']??'',
                'state'  => str_contains(strtolower($c['Status']??''),'up')?'running':'stopped',
                'ports'  => $c['Ports']??'',
            ];
        }
        return response()->json($containers);
    }
    public function containerAction(Request $request, string $id): JsonResponse
    {
        $action = $request->input('action');
        if (!in_array($action,['start','stop','restart','pause','unpause'])) return response()->json(['error'=>'Action non autorisée'],403);
        $out = shell_exec("docker ".escapeshellarg($action)." ".escapeshellarg($id)." 2>&1");
        return response()->json(['output'=>trim($out)]);
    }
    public function containerRemove(string $id): JsonResponse
    {
        $out = shell_exec("docker rm -f ".escapeshellarg($id)." 2>&1");
        return response()->json(['output'=>trim($out)]);
    }
    public function containerLogs(string $id): JsonResponse
    {
        $out = shell_exec("docker logs --tail=100 ".escapeshellarg($id)." 2>&1");
        return response()->json(['logs'=>$out]);
    }
    public function images(): JsonResponse
    {
        $raw = shell_exec('docker images --format "{{json .}}" 2>/dev/null');
        $images = [];
        foreach (explode("\n", trim($raw ?? '')) as $line) {
            if (!$line) continue;
            $img = json_decode($line, true);
            if ($img) $images[] = ['id'=>substr($img['ID']??'',0,12),'repository'=>$img['Repository']??'','tag'=>$img['Tag']??'','size'=>$img['Size']??'','created'=>$img['CreatedSince']??''];
        }
        return response()->json($images);
    }
    public function imagePull(Request $request): JsonResponse
    {
        $image = $request->input('image');
        if (!$image || !preg_match('/^[\w.\-\/:\@]+$/',$image)) return response()->json(['error'=>'Image invalide'],400);
        $out = shell_exec("docker pull ".escapeshellarg($image)." 2>&1");
        return response()->json(['output'=>$out]);
    }
    public function imageRemove(string $id): JsonResponse
    {
        $out = shell_exec("docker rmi ".escapeshellarg($id)." 2>&1");
        return response()->json(['output'=>trim($out)]);
    }
    public function volumes(): JsonResponse
    {
        $raw = shell_exec('docker volume ls --format "{{json .}}" 2>/dev/null');
        $volumes = [];
        foreach (explode("\n", trim($raw ?? '')) as $line) {
            if (!$line) continue;
            $v = json_decode($line, true);
            if ($v) $volumes[] = $v;
        }
        return response()->json($volumes);
    }
    public function networks(): JsonResponse
    {
        $raw = shell_exec('docker network ls --format "{{json .}}" 2>/dev/null');
        $networks = [];
        foreach (explode("\n", trim($raw ?? '')) as $line) {
            if (!$line) continue;
            $n = json_decode($line, true);
            if ($n) $networks[] = $n;
        }
        return response()->json($networks);
    }
}
