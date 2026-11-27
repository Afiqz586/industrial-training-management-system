<?php
session_start();
require_once __DIR__ . '/dbconn.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

// mark all as read
$stmt = mysqli_prepare(
    $dbconn,
    "UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['ok' => $ok]);