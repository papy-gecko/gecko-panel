<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

$file = '/var/www/vein-fr/kofi_dons.json';
$dons = [];
if (file_exists($file)) {
    $dons = json_decode(file_get_contents($file), true) ?: [];
}

$total  = round(array_sum(array_column($dons, 'amount')), 2);
$count  = count($dons);
$last   = $dons[0] ?? null;

echo json_encode([
    'count'  => $count,
    'total'  => $total,
    'last'   => $last ? [
        'from'     => $last['from'],
        'amount'   => $last['amount'],
        'currency' => $last['currency'],
    ] : null,
]);
