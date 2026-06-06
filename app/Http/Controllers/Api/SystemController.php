<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
class SystemController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'cpu'    => $this->getCpu(),
            'ram'    => $this->getRam(),
            'disk'   => $this->getDisk(),
            'net'    => $this->getNet(),
            'uptime' => $this->getUptime(),
            'load'   => $this->getLoad(),
        ]);
    }
    private function getCpu(): array
    {
        $s1 = $this->readCpuLine(); usleep(200000); $s2 = $this->readCpuLine();
        $delta = array_map(fn($a,$b) => $b-$a, $s1, $s2);
        $total = array_sum($delta); $idle = $delta[3];
        return ['percent' => $total > 0 ? round((1-$idle/$total)*100,1) : 0, 'cores' => (int)trim(shell_exec('nproc')?:'1')];
    }
    private function readCpuLine(): array
    {
        $line = explode(' ', preg_replace('/\s+/',' ',trim(explode("\n",file_get_contents('/proc/stat'))[0])));
        array_shift($line); return array_map('intval',$line);
    }
    private function getRam(): array
    {
        $info = [];
        foreach (explode("\n",file_get_contents('/proc/meminfo')) as $line)
            if (preg_match('/^(\w+):\s+(\d+)/',$line,$m)) $info[$m[1]]=(int)$m[2];
        $total=$info['MemTotal']??0; $free=$info['MemAvailable']??0; $used=$total-$free;
        return ['total'=>$this->fmt($total*1024),'used'=>$this->fmt($used*1024),'free'=>$this->fmt($free*1024),'percent'=>$total>0?round($used/$total*100,1):0];
    }
    private function getDisk(): array
    {
        $total=disk_total_space('/'); $free=disk_free_space('/'); $used=$total-$free;
        return ['total'=>$this->fmt($total),'used'=>$this->fmt($used),'free'=>$this->fmt($free),'percent'=>$total>0?round($used/$total*100,1):0];
    }
    private function getNet(): array
    {
        $iface=trim(shell_exec("ip route | grep default | awk '{print $5}' | head -1")?:'eth0');
        $path="/sys/class/net/{$iface}/statistics/";
        $rx=(int)@file_get_contents($path.'rx_bytes'); $tx=(int)@file_get_contents($path.'tx_bytes');
        return ['iface'=>$iface,'rx'=>$this->fmt($rx),'tx'=>$this->fmt($tx),'rx_rate'=>'—','tx_rate'=>'—'];
    }
    private function getUptime(): string
    {
        $s=(int)explode(' ',file_get_contents('/proc/uptime'))[0];
        $d=intdiv($s,86400); $h=intdiv($s%86400,3600); $m=intdiv($s%3600,60);
        return $d>0?"{$d}j {$h}h {$m}m":($h>0?"{$h}h {$m}m":"{$m}m");
    }
    private function getLoad(): array { $l=sys_getloadavg(); return ['1'=>round($l[0],2),'5'=>round($l[1],2),'15'=>round($l[2],2)]; }
    private function fmt(int $b): string
    {
        if($b>=1073741824) return round($b/1073741824,1).' GB';
        if($b>=1048576) return round($b/1048576,1).' MB';
        if($b>=1024) return round($b/1024,1).' KB';
        return $b.' B';
    }
}
