<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class TerminalController extends Controller
{
    private array $blocked = ['rm -rf /','mkfs','dd if=',':(){:|:&};:'];
    public function execute(Request $request): JsonResponse
    {
        $command = $request->input('command','');
        $cwd = $request->input('cwd','/var/www/gecko');
        if (!$command) return response()->json(['output'=>'','cwd'=>$cwd]);
        foreach ($this->blocked as $d) {
            if(str_contains($command,$d)) return response()->json(['output'=>'Commande bloquée.','cwd'=>$cwd,'error'=>true]);
        }
        if (preg_match('/^cd\s+(.+)$/',trim($command),$m)) {
            $target = trim($m[1]);
            $newCwd = realpath($cwd.'/'.$target)?:realpath($target)?:$cwd;
            return response()->json(['output'=>'','cwd'=>$newCwd]);
        }
        $tmp = tempnam('/tmp','cmd_');
        file_put_contents($tmp, "cd ".escapeshellarg($cwd)."\n".$command."\n");
        $out = shell_exec('sudo -n bash '.$tmp.' 2>&1');
        unlink($tmp);
        return response()->json(['output'=>$out??'','cwd'=>$cwd]);
    }
}
