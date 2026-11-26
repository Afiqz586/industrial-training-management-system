<?php
session_start();
include "dbconn.php";

if (isset($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
}

$currentLang = $_SESSION['lang'] ?? 'en';

include 'language.php';

$L = $lang[$currentLang] ?? $lang['en'];

if (!isset($_POST['Submit'])) {
    header("Location: login.php");
    exit;
}

$username = isset($_POST['username']) && is_string($_POST['username'])
    ? trim($_POST['username'])
    : '';
$password = isset($_POST['password']) && is_string($_POST['password'])
    ? $_POST['password']
    : '';

$stmt = mysqli_prepare(
    $dbconn,
    "SELECT user_id, username, role, password, status
     FROM users
     WHERE username = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || $user['password'] !== $password) {
    $_SESSION['login_error'] = $L['invalid username or password'];
    header("Location: login.php");
    exit;
}

if ($user['status'] !== 'Active') {
    $_SESSION['login_error'] = $L['inactive account'];
    header("Location: login.php");
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'Super Admin') {
    header("Location: adminindex.php");
} else {
    header("Location: index.php");
}
exit;
