<?php
// Incrémente le compteur et redirige vers l'exe
$counter_file = __DIR__ . '/count.json';

$data = ['count' => 0];
if (file_exists($counter_file)) {
    $data = json_decode(file_get_contents($counter_file), true) ?? ['count' => 0];
}
$data['count']++;
file_put_contents($counter_file, json_encode($data), LOCK_EX);

header('Location: /vein-fr/VeinFR_Installer.exe');
exit;
