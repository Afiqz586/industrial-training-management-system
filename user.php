<?php
include("dbconn.php");
include_once("language.php");
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
if (!in_array($role, ['', 'Chief Clerk', 'Super Admin'], true)) {
    $role = '';
}
$like   = "%$search%";

$where = "WHERE CONCAT_WS(' ', user_id, username, password, worker_id, role) LIKE ?
          AND (? = '' OR role = ?)";

$stmt = mysqli_prepare($dbconn, "SELECT COUNT(*) FROM users $where");
mysqli_stmt_bind_param($stmt, "sss", $like, $role, $role);
mysqli_stmt_execute($stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

$total_pages = max(1, ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$stmt = mysqli_prepare($dbconn, "SELECT worker_id, username, role, department, status, photo FROM users $where LIMIT ? OFFSET ?");
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
    <link rel="stylesheet" href="style.css">
</head>
<body class="index-page">
     <div class="layout">

    <?php include 'includes/header.php';?>
    <?php include 'includes/adminsidebar.php';?>

    <main class="main-content">
        <div id="liststudents">
        <h1><?= htmlspecialchars($L['user list']) ?></h1>
        </div>
        <div class="toolbar">
            <div class="filter">
            <button class="filter-button" type="button" aria-label="Filter" aria-expanded="false" aria-controls="filterDropdown">
            <i class="fa-solid fa-sliders" style="color: rgb(124, 117, 117);"></i>
            </button>
            <div id="filterDropdown" class="dropdown-content filter-dropdown">
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('')); ?>"><?= htmlspecialchars($L['all role']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Chief Clerk')); ?>"><?= htmlspecialchars($L['chief clerk']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Super Admin')); ?>"><?= htmlspecialchars($L['super admin']) ?></a>
            </div>
            </div>
            <a id="modalBtn" class="btnadd" data-modal-target="addUserModal"><?= htmlspecialchars($L['add user']) ?></a>
            <form method="get" class="search-bar">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
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
                        <th class="table_th"><?= htmlspecialchars($L['worker ID']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['username']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['role']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['status']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['actions']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                    <tr><td colspan="5"><?= htmlspecialchars($L['no record found']) ?></td></tr>
                    <?php } ?>
                    <?php while($info = $result->fetch_assoc()) { ?>
                    <tr>
                        <td class="table_td"><?php echo htmlspecialchars($info['worker_id']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['username']); ?></td>
                        <td class="table_td">
                            <?php
                            $roleLabel = $info['role'] === 'Chief Clerk'
                                ? $L['chief clerk']
                                : ($info['role'] === 'Super Admin' ? $L['super admin'] : $info['role']);
                            echo htmlspecialchars($roleLabel);
                            ?>
                        </td>
                        <td class="table_td">
                        <?php if ($info['status'] == 'Active'): ?>
                            <span class="status-badge active"><?= htmlspecialchars($L['active']) ?></span>
                        <?php elseif ($info['status'] == 'Inactive'): ?>
                            <span class="status-badge inactive"><?= htmlspecialchars($L['inactive']) ?></span>
                        <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-icons">
                                <a href="#" class="icon-edit open-modal" data-modal-target="userDetailsModal"
                                    data-username="<?php echo htmlspecialchars($info['username']); ?>"
                                    data-worker_id="<?php echo htmlspecialchars((int)$info['worker_id']); ?>"
                                    data-role="<?php echo htmlspecialchars($info['role']); ?>"
                                    data-role_display="<?php echo htmlspecialchars($info['role'] === 'Chief Clerk' ? $L['chief clerk'] : ($info['role'] === 'Super Admin' ? $L['super admin'] : $info['role'])); ?>"
                                    data-department="<?php echo htmlspecialchars($L[strtolower(trim((string) $info['department']))] ?? $info['department'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-status="<?php echo htmlspecialchars($info['status']); ?>"
                                    data-status_display="<?php echo htmlspecialchars($info['status'] === 'Active' ? $L['active'] : $L['inactive']); ?>"
                                    data-photo="<?php echo htmlspecialchars($info['photo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-eye" style="color: rgb(147, 156, 151);"></i></a>
                                <a href="#" class="icon-edit open-modal" data-modal-target="userEditModal" aria-label="Edit <?php echo htmlspecialchars($info['username']); ?>"
                                    data-username="<?php echo htmlspecialchars($info['username']); ?>"
                                    data-worker_id="<?php echo htmlspecialchars((int)$info['worker_id']); ?>"
                                    data-role="<?php echo htmlspecialchars($info['role']); ?>"
                                    data-department="<?php echo htmlspecialchars($info['department']); ?>"
                                    data-status="<?php echo htmlspecialchars($info['status']); ?>"
                                    data-photo="<?php echo htmlspecialchars($info['photo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-solid fa-pencil" style="color: rgb(53, 157, 232);"></i></a>
                                <a href="action.php?delete_user=<?php echo (int)$info['worker_id']; ?>" class="icon-delete" aria-label="Delete <?php echo htmlspecialchars($info['username']); ?>" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fa-solid fa-trash" style="color: rgb(250, 1, 1);"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <div id="addUserModal" class="modal">
                <div class ="wrapper">
                    <div class="form-wrapper">
                        <h1><?= htmlspecialchars($L['add user']) ?></h1>
                        <form method="POST" action="action.php" enctype="multipart/form-data">
                            <button type="button" class="photo-preview-button" aria-label="Adjust user photo crop" hidden>
                                <img class="photo-preview" alt="Selected user photo">
                                <span>Click photo to adjust crop</span>
                            </button>
                            <input type="text" name="username" placeholder="<?= htmlspecialchars($L['username']) ?>" required>
                            <input type="text" name="worker_id" placeholder="<?= htmlspecialchars($L['worker ID']) ?>" required>
                            <input type="text" name="password" placeholder="<?= htmlspecialchars($L['password']) ?>" required>
                            <select name="department" required>
                                <option value="" disabled selected><?= htmlspecialchars($L['department']) ?></option>
                                <?php foreach ($departmentKeys as $departmentKey) { ?>
                                    <option value="<?= htmlspecialchars($lang['en'][$departmentKey], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($L[$departmentKey], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php } ?>
                            </select>
                            <select name="role" required>
                                <option value="" disabled selected><?= htmlspecialchars($L['role']) ?></option>
                                <option value="Chief Clerk"><?= htmlspecialchars($L['chief clerk']) ?></option>
                                <option value="Super Admin"><?= htmlspecialchars($L['super admin']) ?></option>
                            </select>
                            <div class="file-upload">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="Choose a user photo" onchange="previewImage(event)">
                                <span class="file-upload-content">
                                <span class="file-upload-button"><?= htmlspecialchars($L['choose file']) ?></span>
                                <span class="file-upload-name"><?= htmlspecialchars($L['no file chosen']) ?></span>
                                </span>
                            </div>
                            <div class="form-buttons">
                                <button type="button" class="btncancel" id="cancelModalBtn"><?= htmlspecialchars($L['cancel']) ?></button>
                                <button type="submit" class="btnadd2" name="add_user"><?= htmlspecialchars($L['add']) ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="profilePhotoCropModal" class="photo-crop-modal" role="dialog" aria-modal="true" aria-labelledby="profilePhotoCropTitle" hidden>
                <div class="photo-crop-dialog">
                    <h2 id="profilePhotoCropTitle">Adjust profile photo</h2>
                    <p>Drag the photo to choose which part appears in the square profile picture.</p>
                    <canvas class="photo-crop-canvas" width="320" height="320" aria-label="Photo crop area"></canvas>
                    <label class="photo-crop-zoom" for="profilePhotoZoom">
                        Zoom
                        <input id="profilePhotoZoom" type="range" min="1" max="3" step="0.01" value="1">
                    </label>
                    <div class="photo-crop-actions">
                        <button type="button" class="btncancel" data-crop-cancel>Cancel</button>
                        <button type="button" class="btnadd2" data-crop-apply>Apply crop</button>
                    </div>
                </div>
            </div>

             <div id="userDetailsModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div class="wrapper">
                        <div class="form-wrapper">
                            <h1><?= htmlspecialchars($L['user details']) ?></h1>
                            <div class="user-details-profile">
                                <div class="user-details-avatar" aria-hidden="true">
                                    <img data-field="photo" alt="" hidden>
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="user-details-identity">
                                    <span class="user-details-label"><?= htmlspecialchars($L['username']) ?></span>
                                    <strong data-field="username"></strong>
                                    <span class="user-details-label"><?= htmlspecialchars($L['worker ID']) ?></span>
                                    <strong data-field="worker_id"></strong>
                                </div>
                            </div>
                            <div class="details">
                                <section class="details-section">
                                    <h2><?= htmlspecialchars($L['account information']) ?></h2>
                                    <div class="details-grid">
                                        <p><b><?= htmlspecialchars($L['role']) ?></b><span data-field="role_display"></span></p>
                                        <p><b><?= htmlspecialchars($L['status']) ?></b><span data-field="status_display"></span></p>
                                    </div>
                                </section>
                                <section class="details-section">
                                    <h2><?= htmlspecialchars($L['organization information']) ?></h2>
                                    <div class="details-grid">
                                        <p><b><?= htmlspecialchars($L['department']) ?></b><span data-field="department"></span></p>
                                    </div>
                                </section>
                            </div>
                            <div class="form-buttons details-actions">
                                <button type="button" class="btncancel" id="cancelModalBtn"><?= htmlspecialchars($L['close']) ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <div id="userEditModal" class="modal">
            <div class="modal-content">
            <span class="close">&times;</span>
            <div class="wrapper">
            <div class="form-wrapper">
                <h1><?= htmlspecialchars($L['edit user']) ?></h1>
                    <form method="POST" action="action.php" enctype="multipart/form-data">
                    <input type="hidden" name="worker_id" data-field="worker_id">
                    <button type="button" class="photo-preview-button" aria-label="Adjust user photo crop" hidden>
                        <img class="photo-preview" data-field="photo" alt="Current user photo">
                        <span>Click photo to adjust crop</span>
                    </button>
                    <input type="text" name="username" placeholder="<?= htmlspecialchars($L['username']) ?>" data-field="username" required>
                    <input type="password" name="password" placeholder="<?= htmlspecialchars($L['new password']) ?>">
                    <select name="role" data-field="role" required>
                        <option value="Chief Clerk"><?= htmlspecialchars($L['chief clerk']) ?></option>
                        <option value="Super Admin"><?= htmlspecialchars($L['super admin']) ?></option>
                    </select>
                    <select name="department" data-field="department" required>
                        <option value="" disabled selected><?= htmlspecialchars($L['department']) ?></option>
                        <?php foreach ($departmentKeys as $departmentKey) { ?>
                            <option value="<?= htmlspecialchars($lang['en'][$departmentKey], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($L[$departmentKey], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php } ?>
                    </select>
                    <select name="status" data-field="status" required>
                        <option value="Active"><?= htmlspecialchars($L['active']) ?></option>
                        <option value="Inactive"><?= htmlspecialchars($L['inactive']) ?></option>
                    </select>
                    <div class="file-upload">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="Choose a new user photo" onchange="previewImage(event)">
                    <span class="file-upload-content">
                        <span class="file-upload-button"><?= htmlspecialchars($L['choose file']) ?></span>
                        <span class="file-upload-name"><?= htmlspecialchars($L['no file chosen']) ?></span>
                    </span>
                </div>
            <div class="form-buttons">
                <button type="submit" class="btnedit" name="update_user"><?= htmlspecialchars($L['update']) ?></button>
                <button type="button" onclick="window.location.href='user.php';" class="btncancel"><?= htmlspecialchars($L['cancel']) ?></button>
            </div>
            </form>
        </div>
        </div>
        </div>

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