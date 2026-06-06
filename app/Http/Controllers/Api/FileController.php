<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ZipArchive;

class FileController extends Controller
{
    private array $allowedRoots = ['/', '/var', '/etc', '/home', '/tmp', '/root', '/srv', '/opt'];

    public function browse(Request $request): JsonResponse
    {
        $path = $request->input('path', '/var/www/pelican');
        if (!$this->isAllowed($path)) return response()->json(['error' => 'Chemin non autorise'], 403);
        if (!is_dir($path)) return response()->json(['error' => 'Dossier introuvable'], 404);

        $items = [];
        $entries = @scandir($path);
        if ($entries === false) return response()->json(['error' => 'Permission refusee'], 403);

        foreach ($entries as $item) {
            if ($item === '.') continue;
            $full = $path.'/'.$item;
            $isDir = is_dir($full);
            $items[] = [
                'name'     => $item,
                'path'     => $full,
                'type'     => $isDir ? 'dir' : 'file',
                'size'     => $isDir ? '-' : $this->fmtSize(@filesize($full) ?: 0),
                'modified' => date('Y-m-d H:i', @filemtime($full) ?: 0),
                'perms'    => substr(sprintf('%o', @fileperms($full) ?: 0), -4),
            ];
        }
        usort($items, fn($a,$b) => $a['type']==='dir' && $b['type']!=='dir' ? -1 : ($b['type']==='dir' && $a['type']!=='dir' ? 1 : strcmp($a['name'],$b['name'])));
        return response()->json(['path' => $path, 'items' => $items, 'parent' => dirname($path)]);
    }

    public function read(Request $request): JsonResponse
    {
        $path = $request->input('path');
        if (!$this->isAllowed($path)) return response()->json(['error' => 'Chemin non autorise'], 403);
        if (!is_file($path)) return response()->json(['error' => 'Fichier introuvable'], 404);
        if (filesize($path) > 512000) return response()->json(['error' => 'Fichier trop grand (> 512 KB)'], 413);

        $content = file_get_contents($path);
        if ($content === false) return response()->json(['error' => 'Lecture impossible'], 500);
        return response()->json(['content' => $content, 'path' => $path]);
    }

    public function write(Request $request): JsonResponse
    {
        $path    = $request->input('path');
        $content = $request->input('content', '');
        if (!$this->isAllowed($path)) return response()->json(['error' => 'Chemin non autorise'], 403);

        if (file_exists($path)) copy($path, $path.'.bak');
        $result = file_put_contents($path, $content);
        if ($result === false) return response()->json(['error' => 'Ecriture impossible'], 500);
        return response()->json(['ok' => true, 'bytes' => $result]);
    }

    public function delete(Request $request): JsonResponse
    {
        $path = $request->input('path');
        if (!$this->isAllowed($path)) return response()->json(['error' => 'Chemin non autorise'], 403);
        if (!file_exists($path)) return response()->json(['error' => 'Introuvable'], 404);

        $ok = is_dir($path) ? $this->deleteRecursive($path) : unlink($path);
        return response()->json(['ok' => $ok]);
    }

    public function download(Request $request)
    {
        $path = $request->input('path');
        if (!$this->isAllowed($path)) abort(403);
        if (!file_exists($path)) abort(404);

        $filename = basename($path);

        if (is_file($path)) {
            // Augmenter les limites pour les gros fichiers
            set_time_limit(300);
            ini_set('memory_limit', '256M');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-cache');
            ob_end_clean();
            readfile($path);
            exit;
        }

        // Dossier : tar.gz en streaming (envoie les donnees immediatement, evite timeout Cloudflare)
        set_time_limit(0);
        $tarName   = $filename . '.tar.gz';
        $parentDir = dirname($path);
        $dirName   = basename($path);

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . addslashes($tarName) . '"');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        ob_end_clean();
        flush();

        $cmd  = 'tar -czf - -C ' . escapeshellarg($parentDir) . ' ' . escapeshellarg($dirName) . ' 2>/dev/null';
        $proc = popen($cmd, 'r');
        while (!feof($proc)) {
            echo fread($proc, 65536);
            flush();
        }
        pclose($proc);
        exit;
    }

    public function rename(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');
        if (!$this->isAllowed($from) || !$this->isAllowed($to)) return response()->json(['error' => 'Chemin non autorise'], 403);
        if (!file_exists($from)) return response()->json(['error' => 'Source introuvable'], 404);
        if (file_exists($to)) return response()->json(['error' => 'Destination existe deja'], 409);

        $ok = rename($from, $to);
        return response()->json(['ok' => $ok]);
    }

    public function mkdir(Request $request): JsonResponse
    {
        $path = $request->input('path');
        if (!$this->isAllowed($path)) return response()->json(['error' => 'Chemin non autorise'], 403);
        if (file_exists($path)) return response()->json(['error' => 'Existe deja'], 409);

        $ok = mkdir($path, 0755, true);
        return response()->json(['ok' => $ok]);
    }

    public function upload(Request $request): JsonResponse
    {
        $dir = preg_replace('#/+#', '/', $request->input('dir', '/tmp'));
        $dir = rtrim($dir, '/') ?: '/';
        if (!$this->isAllowed($dir)) return response()->json(['error' => 'Chemin non autorise'], 403);
        if (!is_dir($dir)) return response()->json(['error' => 'Dossier cible introuvable'], 404);

        $uploaded = [];
        $files = $request->file('files') ?? [];
        if (!is_array($files)) $files = [$files];
        foreach ($files as $file) {
            if (!$file) continue;
            $originalName = $file->getClientOriginalName();
            $dest = $dir . '/' . $originalName;
            $tmpName = 'pelican_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $file->move('/tmp', $tmpName);
            $tmpFull = '/tmp/' . $tmpName;
            exec('sudo /usr/local/bin/pelican-move ' . escapeshellarg($tmpFull) . ' ' . escapeshellarg($dest), $out, $code);
            if ($code !== 0) {
                @unlink($tmpFull);
                return response()->json(['error' => "Impossible d ecrire dans $dir"], 500);
            }
            $uploaded[] = $dest;
        }
        return response()->json(['ok' => true, 'files' => $uploaded]);
    }

    private function addFolderToZip(ZipArchive $zip, string $folder, string $zipPath): void
    {
        $zip->addEmptyDir($zipPath);
        $entries = @scandir($folder);
        if ($entries === false) return; // dossier inaccessible, on saute

        foreach ($entries as $item) {
            if ($item === '.' || $item === '..') continue;
            $full  = $folder . '/' . $item;
            $entry = $zipPath . '/' . $item;
            if (is_dir($full)) {
                $this->addFolderToZip($zip, $full, $entry);
            } elseif (is_readable($full)) {
                $zip->addFile($full, $entry);
            }
        }
    }

    private function deleteRecursive(string $path): bool
    {
        $entries = @scandir($path);
        if ($entries !== false) {
            foreach ($entries as $item) {
                if ($item === '.' || $item === '..') continue;
                $full = $path . '/' . $item;
                is_dir($full) ? $this->deleteRecursive($full) : unlink($full);
            }
        }
        return rmdir($path);
    }

    private function isAllowed(string $path): bool
    {
        $real = realpath($path) ?: $path;
        foreach ($this->allowedRoots as $root) {
            if (str_starts_with($real, $root)) return true;
        }
        return false;
    }

    private function fmtSize(int $b): string
    {
        if ($b >= 1048576) return round($b/1048576,1).' MB';
        if ($b >= 1024)   return round($b/1024,1).' KB';
        return $b.' B';
    }
}
