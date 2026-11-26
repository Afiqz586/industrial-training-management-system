<?php
include("dbconn.php");
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
if (!in_array($status, ['', 'Ongoing', 'Completed'], true)) {
    $status = '';
}
$like   = "%$search%";

$where = "WHERE CONCAT_WS(' ', student_no, name, course, institution, department, start_date, end_date, status) LIKE ?
          AND (? = '' OR status = ?)";

$stmt = mysqli_prepare($dbconn, "SELECT COUNT(*) FROM students $where");
mysqli_stmt_bind_param($stmt, "sss", $like, $status, $status);
mysqli_stmt_execute($stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

$total_pages = max(1, (int)ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$stmt = mysqli_prepare($dbconn, "SELECT * FROM students $where ORDER BY student_id ASC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "sssii", $like, $status, $status, $limit, $offset);
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

    <?php if($_SESSION['role'] == 'Super Admin'){
        include 'includes/adminsidebar.php';
    } else if($_SESSION['role'] == 'Chief Clerk')
        include 'includes/sidebar.php';?>

    <main class="main-content">
        <div id="liststudents">
        <h1><?= htmlspecialchars($L['student list']) ?></h1>
        </div>
        <div class="toolbar">
            <div class="filter">
            <button class="filter-button" type="button" aria-label="Filter" aria-expanded="false" aria-controls="filterDropdown">
            <i class="fa-solid fa-sliders" style="color: rgb(124, 117, 117);"></i>
            </button>
            <div id="filterDropdown" class="dropdown-content filter-dropdown">
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('')); ?>"><?= htmlspecialchars($L['all status']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Ongoing')); ?>"><?= htmlspecialchars($L['ongoing']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Completed')); ?>"><?= htmlspecialchars($L['completed']) ?></a>
            </div>
            </div>
            <a id="modalBtn" class="btnadd" data-modal-target="addStudentModal"><?= htmlspecialchars($L['add student']) ?></a>
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
                        <th class="table_th"><?= htmlspecialchars($L['student no']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['name']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['institution']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['department']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['start date']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['end date']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['status']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['actions']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                    <tr><td colspan="8"><?= htmlspecialchars($L['no record found']) ?></td></tr>
                    <?php } ?>
                    <?php while($info = $result->fetch_assoc()) { ?>
                    <tr>
                        <td class="table_td"><?php echo htmlspecialchars($info['student_no']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['name']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['institution']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['department']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['start_date']); ?></td>
                        <td class="table_td"><?php echo htmlspecialchars($info['end_date']); ?></td>
                        <td class="table_td">
                        <?php if ($info['status'] == 'Ongoing'): ?>
                            <span class="status-badge ongoing"><?= htmlspecialchars($L['ongoing']) ?></span>
                        <?php elseif ($info['status'] == 'Completed'): ?>
                            <span class="status-badge completed"><?= htmlspecialchars($L['completed']) ?></span>
                        <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-icons">
                                <a href="#" class="icon-edit open-modal" data-modal-target="studentDetailsModal"
                                    data-name="<?php echo htmlspecialchars($info['name']); ?>"
                                    data-student_no="<?php echo htmlspecialchars($info['student_no']); ?>"
                                    data-ic_no="<?php echo htmlspecialchars($info['ic_no']); ?>"
                                    data-course="<?php echo htmlspecialchars($info['course']); ?>"
                                    data-institution="<?php echo htmlspecialchars($info['institution']); ?>"
                                    data-department="<?php echo htmlspecialchars($info['department']); ?>"
                                    data-phone="<?php echo htmlspecialchars($info['phone']); ?>"
                                    data-email="<?php echo htmlspecialchars($info['email']); ?>"
                                    data-supervisor="<?php echo htmlspecialchars($info['supervisor']); ?>"
                                    data-start_date="<?php echo htmlspecialchars($info['start_date']); ?>"
                                    data-end_date="<?php echo htmlspecialchars($info['end_date']); ?>"
                                    data-status="<?php echo htmlspecialchars($info['status']); ?>"
                                    data-photo="<?php echo htmlspecialchars($info['photo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-eye" style="color: rgb(147, 156, 151);"></i></a>
                                <a href="#" class="icon-edit open-modal" data-modal-target="studentEditModal" aria-label="Edit <?php echo htmlspecialchars($info['name']); ?>"
                                    data-student_id="<?php echo (int)$info['student_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($info['name']); ?>"
                                    data-ic_no="<?php echo htmlspecialchars($info['ic_no']); ?>"
                                    data-student_no="<?php echo htmlspecialchars($info['student_no']); ?>"
                                    data-phone="<?php echo htmlspecialchars($info['phone']); ?>"
                                    data-institution="<?php echo htmlspecialchars($info['institution']); ?>"
                                    data-email="<?php echo htmlspecialchars($info['email']); ?>"
                                    data-course="<?php echo htmlspecialchars($info['course']); ?>"
                                    data-supervisor="<?php echo htmlspecialchars($info['supervisor']); ?>"
                                    data-start_date="<?php echo htmlspecialchars($info['start_date']); ?>"
                                    data-end_date="<?php echo htmlspecialchars($info['end_date']); ?>"
                                    data-status="<?php echo htmlspecialchars($info['status']); ?>"
                                    data-photo="<?php echo htmlspecialchars($info['photo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-department="<?php echo htmlspecialchars($info['department']); ?>"><i class="fa-solid fa-pencil" style="color: rgb(53, 157, 232);"></i></a>
                                <a href="action.php?delete=<?php echo $info['student_id']; ?>" class="icon-delete" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fa-solid fa-trash" style="color: rgb(250, 1, 1);"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <div id="addStudentModal" class="modal">
                <div class ="wrapper">
                    <div class="form-wrapper">
                        <h1><?= htmlspecialchars($L['add student']) ?></h1>
                        <form method="POST" action="action.php" enctype="multipart/form-data">
                            <button type="button" class="photo-preview-button" aria-label="Adjust student photo crop" hidden>
                                <img class="photo-preview" alt="Selected student photo">
                                <span>Click photo to adjust crop</span>
                            </button>
                            <input type="text" name="name" placeholder="<?= htmlspecialchars($L['name']) ?>" required>
                            <input type="text" name="ic_no" placeholder="YYMMDD-PB-####"  maxlength="14" oninput="formatIC(this)" required>
                            <input type="text" name="student_no" placeholder="<?= htmlspecialchars($L['student no']) ?>" required>
                            <input type="text" name="phone" placeholder="XXX-XXXX XXXX" maxlength="13" oninput="formatPhone(this)" required>
                            <input type="text" name="institution" placeholder="<?= htmlspecialchars($L['institution']) ?>" required>
                            <input type="email" name="email" placeholder="<?= htmlspecialchars($L['email']) ?>" required>
                            <input type="text" name="course" placeholder="<?= htmlspecialchars($L['course']) ?>" required>
                            <input type="text" name="supervisor" placeholder="<?= htmlspecialchars($L['supervisor']) ?>" required>
                            <input type="date" name="start_date" placeholder="<?= htmlspecialchars($L['start date']) ?>" required>
                            <input type="date" name="end_date" placeholder="<?= htmlspecialchars($L['end date']) ?>" required>
                            <select name="status" required>
                                <option value="" disabled selected><?= htmlspecialchars($L['status']) ?></option>
                                <option value="Ongoing"><?= htmlspecialchars($L['ongoing']) ?></option>
                                <option value="Completed"><?= htmlspecialchars($L['completed']) ?></option>
                            </select>
                            <input type="text" name="department" placeholder="<?= htmlspecialchars($L['department']) ?>" required>
                            <div class="file-upload">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="Choose a student photo" onchange="previewImage(event)">
                                <span class="file-upload-content">
                                    <span class="file-upload-button"><?= htmlspecialchars($L['choose file']) ?></span>
                                    <span class="file-upload-name"><?= htmlspecialchars($L['no file chosen']) ?></span>
                                </span>
                            </div>
                            <div class="form-buttons">
                                <button type="button" onclick="showToast(addcancelMsg)" class="btncancel" id="cancelModalBtn"><?= htmlspecialchars($L['cancel']) ?></button>
                                <button type="submit" class="btnadd2" name="add"><?= htmlspecialchars($L['add']) ?></button>
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

             <div id="studentDetailsModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div class="wrapper">
                        <div class="form-wrapper">
                            <h1><?= htmlspecialchars($L['student details']) ?></h1>
                            <div class="student-details-profile">
                                <div class="student-details-photo">
                                    <img data-field="photo" alt="Student photo" hidden>
                                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                                </div>
                                <div class="student-details-identity">
                                    <span class="student-details-label"><?= htmlspecialchars($L['student number']) ?></span>
                                    <strong data-field="student_no"></strong>
                                    <span class="student-details-label"><?= htmlspecialchars($L['full name']) ?></span>
                                    <strong data-field="name"></strong>
                                </div>
                            </div>
                            <div class="details">
                                <section class="details-section">
                                    <h2><?= htmlspecialchars($L['personal information']) ?></h2>
                                    <div class="details-grid">
                                        <p><b><?= htmlspecialchars($L['ic no']) ?></b><span data-field="ic_no"></span></p>
                                    </div>
                                </section>
                                <section class="details-section">
                                    <h2><?= htmlspecialchars($L['placement details']) ?></h2>
                                    <div class="details-grid">
                                        <p><b><?= htmlspecialchars($L['course']) ?></b><span data-field="course"></span></p>
                                        <p><b><?= htmlspecialchars($L['institution']) ?></b><span data-field="institution"></span></p>
                                        <p><b><?= htmlspecialchars($L['department']) ?></b><span data-field="department"></span></p>
                                    </div>
                                </section>
                                <section class="details-section">
                                    <h2><?= htmlspecialchars($L['contact details']) ?></h2>
                                    <div class="details-grid">
                                        <p><b><?= htmlspecialchars($L['phone']) ?></b><span data-field="phone"></span></p>
                                        <p><b><?= htmlspecialchars($L['email']) ?></b><span data-field="email"></span></p>
                                        <p><b><?= htmlspecialchars($L['supervisor']) ?></b><span data-field="supervisor"></span></p>
                                    </div>
                                </section>
                                <section class="details-section">
                                    <h2><?= htmlspecialchars($L['internship period']) ?></h2>
                                    <div class="details-grid">
                                        <p><b><?= htmlspecialchars($L['start date']) ?></b><span data-field="start_date"></span></p>
                                        <p><b><?= htmlspecialchars($L['end date']) ?></b><span data-field="end_date"></span></p>
                                        <p><b><?= htmlspecialchars($L['status']) ?></b><span data-field="status"></span></p>
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

        <div id="studentEditModal" class="modal">
            <div class="modal-content">
            <span class="close">&times;</span>
            <div class="wrapper">
            <div class="form-wrapper">
                <h1><?= htmlspecialchars($L['edit student']) ?></h1>
                    <form method="POST" action="action.php" enctype="multipart/form-data">
                    <input type="hidden" name="student_id" data-field="student_id">
                    <button type="button" class="photo-preview-button" aria-label="Adjust student photo crop" hidden>
                        <img class="photo-preview" alt="Selected student photo">
                        <span>Click photo to adjust crop</span>
                    </button>
                    <input type="text" name="name" placeholder="<?= htmlspecialchars($L['name']) ?>" data-field="name" required>
                    <input type="text" name="ic_no" placeholder="YYMMDD-PB-####"  maxlength="14" oninput="formatIC(this)" data-field="ic_no" required>
                    <input type="text" name="student_no" placeholder="<?= htmlspecialchars($L['student no']) ?>" data-field="student_no" required>
                    <input type="text" name="phone" placeholder="XXX-XXXX XXXX" maxlength="13" oninput="formatPhone(this)" data-field="phone" required>
                    <input type="text" name="institution" placeholder="<?= htmlspecialchars($L['institution']) ?>" data-field="institution" required>
                    <input type="email" name="email" placeholder="<?= htmlspecialchars($L['email']) ?>" data-field="email" required>
                    <input type="text" name="course" placeholder="<?= htmlspecialchars($L['course']) ?>" data-field="course" required>
                    <input type="text" name="supervisor" placeholder="<?= htmlspecialchars($L['supervisor']) ?>" data-field="supervisor" required>
                    <input type="date" name="start_date" placeholder="<?= htmlspecialchars($L['start date']) ?>" data-field="start_date" required>
                    <input type="date" name="end_date" placeholder="<?= htmlspecialchars($L['end date']) ?>" data-field="end_date" required>
                    <select name="status" data-field="status" required>
                        <option value="" disabled><?= htmlspecialchars($L['status']) ?></option>
                        <option value="Ongoing"><?= htmlspecialchars($L['ongoing']) ?></option>
                        <option value="Completed"><?= htmlspecialchars($L['completed']) ?></option>
                    </select>
                <input type="text" name="department" placeholder="<?= htmlspecialchars($L['department']) ?>" data-field="department" required>
                <div class="file-upload">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="Choose a new student photo" onchange="previewImage(event)">
                    <span class="file-upload-content">
                        <span class="file-upload-button"><?= htmlspecialchars($L['choose file']) ?></span>
                        <span class="file-upload-name"><?= htmlspecialchars($L['no file chosen']) ?></span>
                    </span>
                </div>
            <div class="form-buttons">
                <button onclick="showToast(editstudentMsg); setTimeout(() => this.form.requestSubmit(this), 1500); return false;" type="submit" class="btnedit" name="update"><?= htmlspecialchars($L['update']) ?></button>
                <button type="button" onclick="showToast(editcancelMsg, true); setTimeout(() => { window.location.href = 'students.php'; }, 1500);" class="btncancel"><?= htmlspecialchars($L['cancel']) ?></button>
        </div>
            </form>
        </div>
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