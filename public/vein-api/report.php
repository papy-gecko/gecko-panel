<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$original = trim($input['original'] ?? '');
$bad      = trim($input['bad']      ?? '');
$suggest  = trim($input['suggest']  ?? '');
$context  = trim($input['context']  ?? '');

if (empty($original) || empty($bad)) {
    http_response_code(400);
    echo json_encode(['error' => 'Champs requis manquants']);
    exit;
}

// Limite longueurs
$original = mb_substr($original, 0, 300);
$bad      = mb_substr($bad,      0, 300);
$suggest  = mb_substr($suggest,  0, 300);
$context  = mb_substr($context,  0, 500);

$reports_file = __DIR__ . '/reports.json';
$reports = [];
if (file_exists($reports_file)) {
    $reports = json_decode(file_get_contents($reports_file), true) ?? [];
}

$reports[] = [
    'id'       => uniqid(),
    'date'     => date('Y-m-d H:i:s'),
    'original' => $original,
    'bad'      => $bad,
    'suggest'  => $suggest,
    'context'  => $context,
    'ip'       => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
];

file_put_contents($reports_file, json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

echo json_encode(['success' => true, 'total' => count($reports)]);
