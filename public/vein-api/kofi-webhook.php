<?php
// Ko-fi Webhook — VEIN FR
// Enregistrer ce token dans Ko-fi : Settings → API → Webhook URL
$VERIFICATION_TOKEN = '0685cca0-fc93-4a31-90fb-fce71e11ab3b';

$raw = $_POST['data'] ?? '';
if (!$raw) { http_response_code(400); exit('No data'); }

$data = json_decode($raw, true);
if (!$data) { http_response_code(400); exit('Invalid JSON'); }

if (($data['verification_token'] ?? '') !== $VERIFICATION_TOKEN) {
    http_response_code(403);
    exit('Forbidden');
}

$file = '/var/www/vein-fr/kofi_dons.json';

$dons = [];
if (file_exists($file)) {
    $dons = json_decode(file_get_contents($file), true) ?: [];
}

$don = [
    'id'       => $data['kofi_transaction_id'] ?? uniqid(),
    'from'     => $data['from_name'] ?? 'Anonyme',
    'amount'   => floatval($data['amount'] ?? 0),
    'currency' => $data['currency'] ?? 'EUR',
    'message'  => substr($data['message'] ?? '', 0, 200),
    'type'     => $data['type'] ?? 'Donation',
    'at'       => date('c'),
];

// Evite les doublons
foreach ($dons as $d) {
    if ($d['id'] === $don['id']) {
        http_response_code(200);
        exit('Already saved');
    }
}

array_unshift($dons, $don);
$dons = array_slice($dons, 0, 200);

file_put_contents($file, json_encode($dons, JSON_PRETTY_PRINT), LOCK_EX);
http_response_code(200);
echo 'OK';
