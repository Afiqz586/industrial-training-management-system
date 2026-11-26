<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

include("dbconn.php");
date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');

$username = $_SESSION['username'];

$limit  = 5;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$like   = "%$search%";

// dashboard card counts
$counts = mysqli_fetch_assoc(mysqli_query($dbconn,
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(type = 'MC'), 0) AS mc,
            COALESCE(SUM(type = 'Leave'), 0) AS leave_count
     FROM leave_mc"));

$studentCountResult = mysqli_query($dbconn, "SELECT COUNT(*) AS total_students FROM students");
$totalStudents = (int) mysqli_fetch_assoc($studentCountResult)['total_students'];

$stmt = mysqli_prepare($dbconn,
    "SELECT COUNT(*) AS total_absentstudents
     FROM attendance
     WHERE `date` = ? AND status = 'Absent'");
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$studentAbsentCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_absentstudents'];


$studentPresentCount = $totalStudents - $studentAbsentCount;

$from  = "FROM leave_mc l JOIN students s ON s.student_id = l.student_id";
$where = "WHERE CONCAT_WS(' ', l.leave_mc_id, s.student_no, s.name, s.department, l.type, l.start_date, l.end_date, l.remarks) LIKE ?";

$stmt = mysqli_prepare($dbconn, "SELECT COUNT(*) $from $where");
mysqli_stmt_bind_param($stmt, "s", $like);
mysqli_stmt_execute($stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];


$stmt = mysqli_prepare($dbconn, "SELECT l.*, s.student_no, s.name, s.department $from $where ORDER BY l.start_date DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "sii", $like, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">
    <?php include 'includes/header.php';?>
     <?php include 'includes/sidebar.php';?>
     
    <main class="main-content">
        
        
        <div class="welcome page-heading">
            <h1><?= htmlspecialchars($L['welcome']) ?>, <?php echo htmlspecialchars($username); ?>!</h1>
        </div>
    
        
        <h1><?= htmlspecialchars($L['dashboard']) ?></h1>

        <div class="cards">
            <div class="dash-card">
                <i class="fa-solid fa-users"></i>
                <h2><?= $totalStudents ?></h2>
                <h5><?= htmlspecialchars($L['total students']) ?></h5>
            </div>
            <div class="dash-card">
                <i class="fa-solid fa-user-check"></i>
                <h2><?= $studentPresentCount ?></h2>
                <h5><?= htmlspecialchars($L['present']) ?></h5>
            </div>
            <div class="dash-card">
                <i class="fa-solid fa-user-xmark"></i>
                <h2><?= $studentAbsentCount ?></h2>
                <h5><?= htmlspecialchars($L['absent']) ?></h5>
            </div>
        </div>
        <a href="leave.php" class="view-all"><?= htmlspecialchars($L['view all']) ?> <i class="fa-solid fa-arrow-right-long"></i></a>
        <div class="table-list" id="studentList">
            <table border="1">
                <thead>
                    <tr>
                        <th class="table_th"><?= htmlspecialchars($L['student ID']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['name']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['department']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['leave type']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['start date']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['end date']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['remarks']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                    <tr><td colspan="7"><?= htmlspecialchars($L['no record found']) ?></td></tr>
                    <?php } ?>
                    <?php while ($info = $result->fetch_assoc()) { ?>
                    <tr>
                        <td class="table_td"><?php echo htmlspecialchars($info['student_no']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['name']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['department']); ?></td>
                        <td class="table_td">
                        <?php if ($info['type'] == 'MC'): ?>
                            <span class="status-badge mc"><?= htmlspecialchars($L['mc']) ?></span>
                        <?php elseif ($info['type'] == 'Leave'): ?>
                            <span class="status-badge leave"><?= htmlspecialchars($L['leave']) ?></span>
                        <?php endif; ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['start_date']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['end_date']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['remarks']); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
    </main>
</div>


<script src="script.js"></script>
</body>
</html>