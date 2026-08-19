<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$conn  = db();
$since = $_GET['since'] ?? null;

$sql    = "SELECT t.id, t.trainee_id_number, t.first_name, t.last_name, t.middle_name,
                  t.course, t.required_hours, t.status, s.school_name, t.updated_at
           FROM trainees t
           LEFT JOIN schools s ON t.school_id = s.id
           WHERE t.status = 'active'";
$params = [];
$types  = '';

if ($since) {
    $sql     .= " AND t.updated_at >= ?";
    $params[] = $since;
    $types   .= 's';
}
$sql .= " ORDER BY t.last_name, t.first_name";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success'   => true,
    'count'     => count($rows),
    'trainees'  => $rows,
    'timestamp' => date('Y-m-d\TH:i:s'),
]);
