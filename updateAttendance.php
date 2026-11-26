<?php
include("dbconn.php");
date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');

$data    = json_decode(file_get_contents('php://input'), true);
$records = $data['records'] ?? [];
$date    = date('Y-m-d');

if (!is_array($records) || count($records) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$find   = mysqli_prepare($dbconn, "SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ?");
$update = mysqli_prepare($dbconn, "UPDATE attendance SET status = ? WHERE attendance_id = ?");
$insert = mysqli_prepare($dbconn, "INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");

mysqli_begin_transaction($dbconn);
try {
    foreach ($records as $r) {
        $sid    = (int)($r['student_id'] ?? 0);
        $status = $r['status'] ?? '';
        if ($sid <= 0 || !in_array($status, ['Present', 'Absent'], true)) {
            throw new Exception('bad record');
        }

        mysqli_stmt_bind_param($find, "is", $sid, $date);
        mysqli_stmt_execute($find);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($find));

        if ($row) {
            mysqli_stmt_bind_param($update, "si", $status, $row['attendance_id']);
            mysqli_stmt_execute($update);
        } else {
            mysqli_stmt_bind_param($insert, "iss", $sid, $date, $status);
            mysqli_stmt_execute($insert);
        }
    }
    mysqli_commit($dbconn);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    mysqli_rollback($dbconn);
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
