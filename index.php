<?php
header('Content-Type: application/json');
echo json_encode([
    'status'  => 'ok',
    'message' => 'OJT DTR API (Railway)',
    'time'    => date('Y-m-d H:i:s'),
]);
