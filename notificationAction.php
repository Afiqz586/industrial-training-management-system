<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

$request = json_decode(file_get_contents('php://input'), true);
if (!is_array($request) || ($request['action'] ?? '') !== 'mark_read') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid notification action.']);
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'dbconn.php';

$stmt = mysqli_prepare(
    $dbconn,
    "UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ? AND is_read = 0"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not prepare notification update.']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not update notifications.']);
    mysqli_stmt_close($stmt);
    exit;
}

mysqli_stmt_close($stmt);
echo json_encode(['ok' => true]);
