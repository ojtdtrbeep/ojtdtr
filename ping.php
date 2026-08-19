<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test DB connection too
require_once __DIR__ . '/db.php';
$conn = db();
$dbOk = $conn->ping();

echo json_encode([
    'status'   => 'ok',
    'message'  => 'Railway API reachable',
    'db'       => $dbOk ? 'connected' : 'error',
    'time'     => date('Y-m-d H:i:s'),
    'php'      => PHP_VERSION,
]);
