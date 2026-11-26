<?php
include("dbconn.php");
date_default_timezone_set('Asia/Kuala_Lumpur');
$today  = date('Y-m-d');
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
if (!in_array($status, ['', 'Present', 'Absent'], true)) {
    $status = '';
}
$like   = "%$search%";

$from = "FROM students s
         LEFT JOIN attendance a ON a.student_id = s.student_id AND a.date = ?";
$where = "WHERE s.status = 'Ongoing'
          AND CONCAT_WS(' ', s.student_no, s.name, s.department) LIKE ?
          AND (? = '' OR (? = 'Present' AND a.status = 'Present')
               OR (? = 'Absent' AND (a.status = 'Absent' OR a.status IS NULL)))";

$stmt = mysqli_prepare($dbconn, "SELECT COUNT(*) $from $where");
mysqli_stmt_bind_param($stmt, "sssss", $today, $like, $status, $status, $status);
mysqli_stmt_execute($stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

$total_pages = max(1, (int)ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$sql = "SELECT s.student_id, s.student_no, s.name, s.department, a.status
        $from
        $where
        ORDER BY s.student_no ASC
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($dbconn, $sql);
mysqli_stmt_bind_param($stmt, "sssssii", $today, $like, $status, $status, $status, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$filterUrl = function ($selectedStatus) use ($search) {
    return '?' . http_build_query([
        'search' => $search,
        'status' => $selectedStatus,
    ]);
};
$pageUrl = function ($selectedPage) use ($search, $status) {
    return '?' . http_build_query([
        'page' => $selectedPage,
        'search' => $search,
        'status' => $status,
    ]);
};
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
<body class="index-page">
     <div class="layout">
     <?php include 'includes/header.php';?>
     <?php include 'includes/sidebar.php';?>
     <main class="main-content">
        <div id="liststudents">
        <h1><?= htmlspecialchars($L['student attendance']) ?>: <?php echo date('d M Y'); ?> </h1>
        </div>
        <div class="toolbar">
            <div class="filter">
            <button class="filter-button" type="button" aria-label="Filter" aria-expanded="false" aria-controls="filterDropdown">
            <i class="fa-solid fa-sliders" style="color: rgb(124, 117, 117);"></i>
            </button>
            <div id="filterDropdown" class="dropdown-content filter-dropdown">
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('')); ?>"><?= htmlspecialchars($L['all status']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Present')); ?>"><?= htmlspecialchars($L['present']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Absent')); ?>"><?= htmlspecialchars($L['absent']) ?></a>
            </div>
            </div>
            <button class="btnadd" id="saveAllBtn" onclick="saveAll()"><?= htmlspecialchars($L['save all']) ?></button>
                <form method="get" class="search-bar">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="<?= htmlspecialchars($L['search']) ?>"
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btnsearch"><?= htmlspecialchars($L['search']) ?></button>
                </form>
        </div>
        
        <div class="table-list" id="studentList">
            <table border="1">
                <thead>
                    <tr>
                        <th class="table_th"><?= htmlspecialchars($L['student ID']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['name']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['department']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['attendance']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                    <tr><td colspan="4"><?= htmlspecialchars($L['no record found']) ?></td></tr>
                    <?php } ?>
                    <?php while($info = $result->fetch_assoc()) { ?>
                    <tr>
                        <td class="table_td"><?php echo htmlspecialchars($info['student_no']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['name']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['department']); ?></td>
                        <td class="table_td">
                        <?php if ($info['status'] === 'Present'): ?>
                            <span button class="status-btn present" data-id="<?php echo (int)$info['student_id']; ?>" onclick="toggleStatus(this)">&#10003; <?= htmlspecialchars($L['present']) ?></span>
                        <?php else: ?>
                            <span button class="status-btn absent" data-id="<?php echo (int)$info['student_id']; ?>" onclick="toggleStatus(this)">&#10005; <?= htmlspecialchars($L['absent']) ?></span>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="pagination">
                <ul>
                    <?php if ($page > 1) { ?>
                        <li><a href="<?php echo htmlspecialchars($pageUrl($page - 1)); ?>">&laquo; <?= htmlspecialchars($L['previous']) ?></a></li>
                    <?php } ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                        <li class="<?php echo $i == $page ? 'active' : ''; ?>"><a href="<?php echo htmlspecialchars($pageUrl($i)); ?>"><?php echo $i; ?></a></li>
                    <?php } ?>
                    <?php if ($page < $total_pages) { ?>
                        <li><a href="<?php echo htmlspecialchars($pageUrl($page + 1)); ?>"><?= htmlspecialchars($L['next']) ?> &raquo;</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </main>
    </div>
    <script src="script.js"></script>
</body>
</html>