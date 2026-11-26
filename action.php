<?php

require 'dbconn.php';
require 'notify.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

include "dbconn.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . 'language.php';

$allowedDepartments = [];
foreach ($departmentKeys as $departmentKey) {
    $allowedDepartments[] = $lang['en'][$departmentKey];
}

function savePhotoUpload(?array $upload, string $folder): ?string
{
    if (!in_array($folder, ['student_photos', 'user_photos'], true)) {
        throw new InvalidArgumentException("Invalid photo folder.");
    }

    if ($upload === null || $upload['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($upload['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        exit("The photo upload failed. Please try again.");
    }

    if ($upload['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        exit("The photo must be 5 MB or smaller.");
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    $imageInfo = getimagesize($upload['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if ($imageInfo === false || $imageInfo['mime'] !== $mime || !isset($extensions[$mime])) {
        http_response_code(400);
        exit("Choose a valid JPG, PNG, or WebP photo.");
    }

    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        http_response_code(500);
        exit("The photo upload folder could not be created.");
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($upload['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        http_response_code(500);
        exit("The photo could not be saved.");
    }

    return 'uploads/' . $folder . '/' . $filename;
}

function removeUploadedPhoto(?string $photoPath, string $folder): void
{
    if (!in_array($folder, ['student_photos', 'user_photos'], true)) {
        throw new InvalidArgumentException("Invalid photo folder.");
    }

    $pattern = '~\Auploads/' . preg_quote($folder, '~') . '/[a-f0-9]{32}\.(?:jpg|png|webp)\z~';
    if ($photoPath === null || !preg_match($pattern, $photoPath)) {
        return;
    }

    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photoPath);
    if (is_file($fullPath) && !unlink($fullPath)) {
        throw new RuntimeException("The old student photo could not be removed.");
    }
}

function getOppositeAccountRole(string $role): ?string
{
    if ($role === 'Super Admin') {
        return 'Chief Clerk';
    }

    if ($role === 'Chief Clerk') {
        return 'Super Admin';
    }

    return null;
}

function createAccountNotification(mysqli $dbconn, int $recipientUserId, string $message): void
{
    $stmt = mysqli_prepare($dbconn,
        "INSERT INTO notifications (recipient_user_id, message) VALUES (?, ?)");
    if (!$stmt) {
        throw new RuntimeException("Could not prepare notification insert: " . mysqli_error($dbconn));
    }

    mysqli_stmt_bind_param($stmt, "is", $recipientUserId, $message);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException("Could not save notification: " . $error);
    }

    mysqli_stmt_close($stmt);
}

// ===== Add User =====
if (isset($_POST['add_user'])) {
    $username  = trim($_POST['username'] ?? '');
    $worker_id = filter_var($_POST['worker_id'] ?? '', FILTER_VALIDATE_INT);
    $password  = $_POST['password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $role      = $_POST['role'] ?? '';

    if ($username === '' || $worker_id === false || $worker_id < 1 || $password === '' ||
        !in_array($department, $allowedDepartments, true) ||
        !in_array($role, ['Chief Clerk', 'Super Admin'], true)) {
        http_response_code(400);
        exit("Enter valid user details.");
    }

    $photoPath = savePhotoUpload($_FILES['photo'] ?? null, 'user_photos');
    $stmt = mysqli_prepare($dbconn,
        "INSERT INTO users (username, worker_id, password, role, department, photo) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sissss", $username, $worker_id, $password, $role, $department, $photoPath);

    if (mysqli_stmt_execute($stmt)) {
        $recipientRole = getOppositeAccountRole($_SESSION['role'] ?? '');
        $recipientUserId = mysqli_insert_id($dbconn);
        if ($recipientRole !== null && $role === $recipientRole && $recipientUserId > 0) {
            $recipientStmt = mysqli_prepare($dbconn, "SELECT status FROM users WHERE user_id = ?");
            if (!$recipientStmt) {
                throw new RuntimeException("Could not prepare recipient account lookup: " . mysqli_error($dbconn));
            }
            mysqli_stmt_bind_param($recipientStmt, "i", $recipientUserId);
            if (!mysqli_stmt_execute($recipientStmt)) {
                throw new RuntimeException("Could not check recipient account status: " . mysqli_stmt_error($recipientStmt));
            }
            $newRecipient = mysqli_fetch_assoc(mysqli_stmt_get_result($recipientStmt));
            mysqli_stmt_close($recipientStmt);

            if (($newRecipient['status'] ?? '') === 'Active') {
                $actorUsername = $_SESSION['username'] ?? 'Another account';
                createAccountNotification(
                    $dbconn,
                    $recipientUserId,
                    "Your account was created by {$actorUsername}."
                );
            }
        }
        header("Location: user.php");
        exit;
    } else {
        removeUploadedPhoto($photoPath, 'user_photos');
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

// ===== Update User =====
if (isset($_POST['update_user'])) {
    $username  = trim($_POST['username'] ?? '');
    $worker_id = filter_var($_POST['worker_id'] ?? '', FILTER_VALIDATE_INT);
    $password  = $_POST['password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $role      = $_POST['role'] ?? '';
    $status    = $_POST['status'] ?? '';

    if ($username === '' || $worker_id === false || $worker_id < 1 ||
        !in_array($department, $allowedDepartments, true) ||
        !in_array($role, ['Chief Clerk', 'Super Admin'], true) ||
        !in_array($status, ['Active', 'Inactive'], true)) {
        http_response_code(400);
        exit("Enter valid user details.");
    }

    $currentStmt = mysqli_prepare($dbconn, "SELECT user_id, photo FROM users WHERE worker_id = ?");
    mysqli_stmt_bind_param($currentStmt, "i", $worker_id);
    mysqli_stmt_execute($currentStmt);
    $currentUser = mysqli_fetch_assoc(mysqli_stmt_get_result($currentStmt));
    if (!$currentUser) {
        http_response_code(404);
        exit("User not found.");
    }

    $newPhotoPath = savePhotoUpload($_FILES['photo'] ?? null, 'user_photos');
    $photoPath = $newPhotoPath ?? $currentUser['photo'];

    if ($password === '') {
        $stmt = mysqli_prepare($dbconn,
            "UPDATE users SET username = ?, role = ?, department = ?, status = ?, photo = ? WHERE worker_id = ?");
        mysqli_stmt_bind_param($stmt, "sssssi", $username, $role, $department, $status, $photoPath, $worker_id);
    } else {
        $stmt = mysqli_prepare($dbconn,
            "UPDATE users SET username = ?, password = ?, role = ?, department = ?, status = ?, photo = ? WHERE worker_id = ?");
        mysqli_stmt_bind_param($stmt, "ssssssi", $username, $password, $role, $department, $status, $photoPath, $worker_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        if ($newPhotoPath !== null) {
            removeUploadedPhoto($currentUser['photo'], 'user_photos');
        }
        $recipientRole = getOppositeAccountRole($_SESSION['role'] ?? '');
        $recipientUserId = (int) $currentUser['user_id'];
        $actorUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($recipientRole !== null && $role === $recipientRole &&
            $status === 'Active' && $recipientUserId !== $actorUserId) {
            $actorUsername = $_SESSION['username'] ?? 'Another account';
            createAccountNotification(
                $dbconn,
                $recipientUserId,
                "Your account was updated by {$actorUsername}."
            );
        }
        header("Location: user.php");
        exit;
    } else {
        removeUploadedPhoto($newPhotoPath, 'user_photos');
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

// ===== Delete User =====
if (isset($_GET['delete_user'])) {
    $worker_id = filter_var($_GET['delete_user'], FILTER_VALIDATE_INT);
    if ($worker_id === false || $worker_id < 1) {
        http_response_code(400);
        exit("Invalid user ID.");
    }

    $photoStmt = mysqli_prepare($dbconn, "SELECT photo FROM users WHERE worker_id = ?");
    mysqli_stmt_bind_param($photoStmt, "i", $worker_id);
    mysqli_stmt_execute($photoStmt);
    $userPhoto = mysqli_fetch_assoc(mysqli_stmt_get_result($photoStmt));

    $stmt = mysqli_prepare($dbconn, "DELETE FROM users WHERE worker_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $worker_id);

    if (mysqli_stmt_execute($stmt)) {
        if ($userPhoto) {
            removeUploadedPhoto($userPhoto['photo'], 'user_photos');
        }
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $_SESSION['toast_flash'] = 'record_deleted';
        }
        header("Location: user.php");
        exit;
    } else {
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

// ===== Update Leave/MC =====
if (isset($_POST['update_leave'])) {
    $leave_mc_id = filter_var($_POST['leave_mc_id'] ?? '', FILTER_VALIDATE_INT);
    $type        = $_POST['type'] ?? '';
    $start_date  = $_POST['start_date'] ?? '';
    $end_date    = $_POST['end_date'] ?? '';
    $remarks     = trim($_POST['remarks'] ?? '');

    if ($leave_mc_id === false || $leave_mc_id < 1 || !in_array($type, ['Leave', 'MC'], true)) {
        http_response_code(400);
        exit("Invalid leave record or type.");
    }

    if ($start_date === '' || $end_date === '' || $end_date < $start_date || $remarks === '') {
        http_response_code(400);
        exit("Enter valid dates and remarks.");
    }

    $stmt = mysqli_prepare($dbconn,
        "UPDATE leave_mc SET type = ?, start_date = ?, end_date = ?, remarks = ?
         WHERE leave_mc_id = ?");
    mysqli_stmt_bind_param($stmt, "ssssi", $type, $start_date, $end_date, $remarks, $leave_mc_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: leave.php");
        exit;
    } else {
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

// ===== Add Leave/MC =====
if (isset($_POST['add_leave'])) {
    $student_id = filter_var($_POST['student_id'] ?? '', FILTER_VALIDATE_INT);
    $type       = $_POST['type'] ?? '';

    if ($student_id === false || $student_id < 1 || !in_array($type, ['Leave', 'MC'], true)) {
        http_response_code(400);
        exit("Invalid student or type.");
    }

    if ($_POST['end_date'] < $_POST['start_date']) {
        exit("End date cannot be before start date.");
    }

    $stmt = mysqli_prepare($dbconn,
        "INSERT INTO leave_mc (student_id, type, start_date, end_date, remarks)
         VALUES (?, ?, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "issss",
        $student_id, $type, $_POST['start_date'], $_POST['end_date'], $_POST['remarks']);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: leave.php");
        exit;
    } else {
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

if (isset($_GET['delete_leave'])) {
    $leave_mc_id = filter_var($_GET['delete_leave'], FILTER_VALIDATE_INT);
    if ($leave_mc_id === false || $leave_mc_id < 1) {
        http_response_code(400);
        exit("Invalid leave record ID.");
    }

    $stmt = mysqli_prepare($dbconn, "DELETE FROM leave_mc WHERE leave_mc_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $leave_mc_id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $_SESSION['toast_flash'] = 'record_deleted';
        }
        header("Location: leave.php");
        exit;
    } else {
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

if (isset($_POST['add'])) {
    $photoPath = savePhotoUpload($_FILES['photo'] ?? null, 'student_photos');
    $stmt = mysqli_prepare($dbconn,
        "INSERT INTO students (name, ic_no, student_no, course, institution, department, phone, email, supervisor, start_date, end_date, status, photo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "sssssssssssss",
        $_POST['name'], $_POST['ic_no'], $_POST['student_no'], $_POST['course'],
        $_POST['institution'], $_POST['department'], $_POST['phone'], $_POST['email'], $_POST['supervisor'],
        $_POST['start_date'], $_POST['end_date'], $_POST['status'], $photoPath);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['toast_flash'] = 'student_added';
        header("Location: students.php");
        exit;
    } else {
        removeUploadedPhoto($photoPath, 'student_photos');
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

if (isset($_POST['update'])) {
    $studentId = filter_var($_POST['student_id'] ?? '', FILTER_VALIDATE_INT);
    if ($studentId === false || $studentId < 1) {
        http_response_code(400);
        exit("Invalid student ID.");
    }

    $currentStmt = mysqli_prepare($dbconn, "SELECT photo FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($currentStmt, "i", $studentId);
    mysqli_stmt_execute($currentStmt);
    $currentStudent = mysqli_fetch_assoc(mysqli_stmt_get_result($currentStmt));
    if (!$currentStudent) {
        http_response_code(404);
        exit("Student not found.");
    }

    $newPhotoPath = savePhotoUpload($_FILES['photo'] ?? null, 'student_photos');
    $photoPath = $newPhotoPath ?? $currentStudent['photo'];
    $stmt = mysqli_prepare($dbconn,
        "UPDATE students SET name=?, ic_no=?, student_no=?, course=?, institution=?, department=?,
         phone=?, email=?, supervisor=?, start_date=?, end_date=?, status=?, photo=?
         WHERE student_id=?");

    mysqli_stmt_bind_param($stmt, "sssssssssssssi",
        $_POST['name'], $_POST['ic_no'], $_POST['student_no'], $_POST['course'],
        $_POST['institution'], $_POST['department'], $_POST['phone'], $_POST['email'], $_POST['supervisor'],
        $_POST['start_date'], $_POST['end_date'], $_POST['status'], $photoPath, $studentId);

    if (mysqli_stmt_execute($stmt)) {
        if ($newPhotoPath !== null) {
            removeUploadedPhoto($currentStudent['photo'], 'student_photos');
        }
        header("Location: students.php");
        exit;
    } else {
        removeUploadedPhoto($newPhotoPath, 'student_photos');
        die("Error: " . mysqli_stmt_error($stmt));
    }
}

if (isset($_GET['delete'])) {
    $student_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($student_id === false || $student_id < 1) {
        http_response_code(400);
        exit("Invalid student ID.");
    }

    $stmt = mysqli_prepare($dbconn, "DELETE FROM students WHERE student_id=?");
    mysqli_stmt_bind_param($stmt, "i", $student_id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $_SESSION['toast_flash'] = 'record_deleted';
        }
        header("Location: students.php");
        exit;
    } else {
        die("Error: " . mysqli_stmt_error($stmt));
    }
}