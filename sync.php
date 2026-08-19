<?php
/**
 * POST /sync.php
 * Receives scan records from Flutter and writes them to InfinityFree's MySQL.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!isset($body['scans']) || !is_array($body['scans'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid "scans" array.']);
    exit;
}

$conn         = db();
$results      = [];
$synced       = 0;
$skipped      = 0;
$errors       = 0;
$validPunches = ['am_in', 'am_out', 'pm_in', 'pm_out'];
$columnMap    = [
    'am_in'  => 'am_time_in',
    'am_out' => 'am_time_out',
    'pm_in'  => 'pm_time_in',
    'pm_out' => 'pm_time_out',
];

foreach ($body['scans'] as $scan) {
    $localId   = $scan['local_id']          ?? '';
    $idNumber  = strtoupper(trim($scan['trainee_id_number'] ?? ''));
    $scannedAt = $scan['scanned_at']        ?? '';
    $punchType = $scan['punch_type']        ?? '';

    // Validate
    if ($idNumber === '' || $scannedAt === '' || !in_array($punchType, $validPunches)) {
        $results[] = ['local_id' => $localId, 'status' => 'error', 'message' => 'Invalid scan data.'];
        $errors++;
        continue;
    }

    // Parse timestamp
    $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $scannedAt)
       ?: DateTime::createFromFormat('Y-m-d\TH:i', $scannedAt);
    if (!$dt) {
        $results[] = ['local_id' => $localId, 'status' => 'error', 'message' => "Invalid timestamp: $scannedAt"];
        $errors++;
        continue;
    }

    $datetimeStr = $dt->format('Y-m-d H:i:s');
    $dateStr     = $dt->format('Y-m-d');

    // Look up trainee
    $tStmt = $conn->prepare(
        "SELECT id, status FROM trainees WHERE trainee_id_number = ? LIMIT 1"
    );
    $tStmt->bind_param('s', $idNumber);
    $tStmt->execute();
    $trainee = $tStmt->get_result()->fetch_assoc();

    if (!$trainee) {
        $results[] = ['local_id' => $localId, 'status' => 'error', 'message' => "Trainee not found: $idNumber"];
        $errors++;
        continue;
    }
    if ($trainee['status'] !== 'active') {
        $results[] = ['local_id' => $localId, 'status' => 'skipped', 'message' => "Trainee is {$trainee['status']}."];
        $skipped++;
        continue;
    }

    $traineeId = (int)$trainee['id'];
    $col       = $columnMap[$punchType];

    // Get existing record for that date
    $rStmt = $conn->prepare("SELECT * FROM dtr_records WHERE trainee_id = ? AND date = ?");
    $rStmt->bind_param('is', $traineeId, $dateStr);
    $rStmt->execute();
    $record = $rStmt->get_result()->fetch_assoc();

    // Skip if punch already filled
    if ($record && !empty($record[$col])) {
        $results[] = ['local_id' => $localId, 'status' => 'skipped', 'message' => "$punchType already recorded for $dateStr."];
        $skipped++;
        continue;
    }

    // Insert or update
    if (!$record) {
        if ($punchType === 'am_in') {
            $ins = $conn->prepare("INSERT INTO dtr_records (trainee_id, date, am_time_in) VALUES (?,?,?)");
        } elseif ($punchType === 'pm_in') {
            $ins = $conn->prepare("INSERT INTO dtr_records (trainee_id, date, pm_time_in) VALUES (?,?,?)");
        } else {
            $results[] = ['local_id' => $localId, 'status' => 'error', 'message' => "Cannot apply $punchType without existing record for $dateStr."];
            $errors++;
            continue;
        }
        $ins->bind_param('iss', $traineeId, $dateStr, $datetimeStr);
        $ins->execute();
        $recordId = $conn->insert_id;
    } else {
        $upd = $conn->prepare("UPDATE dtr_records SET $col = ? WHERE id = ?");
        $upd->bind_param('si', $datetimeStr, $record['id']);
        $upd->execute();
        $recordId = $record['id'];
    }

    // Recalculate hours
    $hStmt = $conn->prepare(
        "SELECT am_time_in, am_time_out, pm_time_in, pm_time_out FROM dtr_records WHERE id = ?"
    );
    $hStmt->bind_param('i', $recordId);
    $hStmt->execute();
    $rec = $hStmt->get_result()->fetch_assoc();

    $calc = function(?string $in, ?string $out): float {
        if (!$in || !$out) return 0.0;
        return round(max(0, strtotime($out) - strtotime($in)) / 3600, 2);
    };
    $hours = round(
        $calc($rec['am_time_in'], $rec['am_time_out']) +
        $calc($rec['pm_time_in'], $rec['pm_time_out']),
        2
    );

    $hUpd = $conn->prepare("UPDATE dtr_records SET hours_worked = ? WHERE id = ?");
    $hUpd->bind_param('di', $hours, $recordId);
    $hUpd->execute();

    $results[] = [
        'local_id'     => $localId,
        'status'       => 'ok',
        'message'      => ucfirst(str_replace('_', ' ', $punchType)) . ' recorded.',
        'hours_worked' => $hours,
        'record_id'    => $recordId,
    ];
    $synced++;
}

echo json_encode([
    'success' => true,
    'results' => $results,
    'synced'  => $synced,
    'skipped' => $skipped,
    'errors'  => $errors,
]);
