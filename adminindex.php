<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit;
}

include("dbconn.php");
date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');

$username = $_SESSION['username'];

$limit  = 5;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
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

$counts = mysqli_fetch_assoc(mysqli_query($dbconn,
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(role = 'Chief Clerk'), 0) AS chief_clerk,
            COALESCE(SUM(role = 'Super Admin'), 0) AS super_admin
     FROM users"));

$usersCountResult = mysqli_query($dbconn, "SELECT COUNT(*) AS total_users FROM users");
$totalUsers = (int) mysqli_fetch_assoc($usersCountResult)['total_users'];

$activeUsersResult = mysqli_query($dbconn,
    "SELECT COUNT(*) AS total_active_users FROM users WHERE status = 'Active'");
$totalactiveUsers = (int) mysqli_fetch_assoc($activeUsersResult)['total_active_users'];


$from  = "FROM activity_logs a JOIN users u ON u.user_id = a.user_id";
$where = "WHERE CONCAT_WS(' ', u.user_id, u.worker_id, u.username, u.role, a.log_id, a.activity, a.description) LIKE ?";

$stmt = mysqli_prepare($dbconn, "SELECT COUNT(*) $from $where");
mysqli_stmt_bind_param($stmt, "s", $like);
mysqli_stmt_execute($stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];


$stmt = mysqli_prepare($dbconn, "SELECT a.*, u.username, u.role $from $where ORDER BY a.log_id DESC LIMIT ? OFFSET ?");
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
     <?php include 'includes/adminsidebar.php';?>
     
    <main class="main-content">
        
        
        <div class="welcome page-heading">
            <h1><?= htmlspecialchars($L['welcome']) ?>, <?php echo htmlspecialchars($username); ?>!</h1>
        </div>
        
        <h1><?= htmlspecialchars($L['dashboard']) ?></h1>

        <div class="cards">
            <div class="dash-card">
                <i class="fa-solid fa-user-tie"></i>
                <h2><?= $totalUsers ?></h2>
                <h5><?= htmlspecialchars($L['total users']) ?></h5>
            </div>
            <div class="dash-card">
                <i class="fa-solid fa-user-check"></i>
                <h2><?= $totalactiveUsers ?></h2>
                <h5><?= htmlspecialchars($L['total active users']) ?></h5>
            </div>
            <div class="dash-card">
                <i class="fa-solid fa-user-graduate"></i>
                <h2><?= $totalStudents?></h2>
                <h5><?= htmlspecialchars($L['total students']) ?></h5>
            </div>
        </div>
        <a href="leave.php" class="view-all"><?= htmlspecialchars($L['view all']) ?><i class="fa-solid fa-arrow-right-long"></i></a>
        <div class="table-list" id="studentList">
            <table border="1">
                <thead>
                    <tr>
                        <th class="table_th"><?= htmlspecialchars($L['worker ID']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['username']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['role']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['activity ID']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['activity']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['description']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                    <tr><td colspan="7"><?= htmlspecialchars($L['no record found']) ?></td></tr>
                    <?php } ?>
                    <?php while ($info = $result->fetch_assoc()) { ?>
                    <tr>
                        <td class="table_td"><?php echo htmlspecialchars($info['user_id']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['username']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['role']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['log_id']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['activity']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['description']); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

    </main>

</div>


<script src="script.js"></script>
</body>
</html>