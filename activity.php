<?php
include("dbconn.php");
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
if (!in_array($role, ['', 'Chief Clerk', 'Super Admin'], true)) {
    $role = '';
}

$like = "%$search%";



$from  = "FROM activity_logs a JOIN users u ON u.user_id = a.user_id";
$where = "WHERE CONCAT_WS(' ', u.user_id, u.worker_id, u.username, u.role, a.log_id, a.activity, a.description) LIKE ?
          AND (? = '' OR u.role = ?)";


$stmt = mysqli_prepare($dbconn, "SELECT COUNT(*) $from $where");
mysqli_stmt_bind_param($stmt, "sss", $like, $role, $role);
mysqli_stmt_execute($stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

$total_pages = max(1, ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$stmt = mysqli_prepare($dbconn, "SELECT a.*, u.username, u.role $from $where ORDER BY a.log_id DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "sssii", $like, $role, $role, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$filterUrl = function ($selectedRole) use ($search) {
    return '?' . http_build_query([
        'search' => $search,
        'role' => $selectedRole,
    ]);
};
$pageUrl = function ($selectedPage) use ($search, $role) {
    return '?' . http_build_query([
        'page' => $selectedPage,
        'search' => $search,
        'role' => $role,
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
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>

    <div class="layout">
        <?php include 'includes/header.php';?>
        <?php include 'includes/adminsidebar.php';?>

        <main class="main-content">
            <div class="page-heading">
                <h1><?= htmlspecialchars($L['activity logs']) ?></h1>
            </div>
            <div class="toolbar">
                <div class="filter">
                    <button class="filter-button" type="button" aria-label="Filter" aria-expanded="false" aria-controls="filterDropdown">
                    <i class="fa-solid fa-sliders" style="color: rgb(124, 117, 117);"></i>    
                    </button>
                    <div id="filterDropdown" class="dropdown-content filter-dropdown">
                    <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl(''));?>"><?= htmlspecialchars($L['all role']) ?></a>
                    <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Chief Clerk'));?>"><?= htmlspecialchars($L['chief clerk']) ?></a>
                    <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Super Admin'));?>"><?= htmlspecialchars($L['super admin']) ?></a>
                    </div>
                </div>
                <form method="get" class="search-bar">
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="<?= htmlspecialchars($L['search']) ?>"
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btnsearch"><?= htmlspecialchars($L['search']) ?></button>
                </form>
            </div>
            <div class="table-list" id="activityList">
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
                        <?php if($result->num_rows === 0){?>
                        <tr><td colspan ="7"><?= htmlspecialchars($L['no record found']) ?></td></tr>
                        <?php } ?>
                        <?php while($info = $result ->fetch_assoc()) {?>
                        <tr>
                        <td class="table_td"><?php echo htmlspecialchars($info['worker_id']);?></td>    
                        <td class="table_td"><?php echo htmlspecialchars($info['username']);?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['role']);?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['activity_id']);?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['activity']);?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['description']);?></td>
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