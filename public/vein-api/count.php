<?php
// Retourne le compteur en JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$counter_file = __DIR__ . '/count.json';
$data = ['count' => 0];
if (file_exists($counter_file)) {
    $data = json_decode(file_get_contents($counter_file), true) ?? ['count' => 0];
}
echo json_encode($data);
