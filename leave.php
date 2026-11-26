<?php
include("dbconn.php");
$limit  = 5;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$type   = $_GET['type'] ?? '';
if (!in_array($type, ['', 'MC', 'Leave'], true)) {
    $type = '';
}
$like   = "%$search%";

// leave_mc only stores student_id, so join students to get number/name/department
$from  = "FROM leave_mc l JOIN students s ON s.student_id = l.student_id";
$where = "WHERE CONCAT_WS(' ', l.leave_mc_id, s.student_no, s.name, s.department, l.type, l.start_date, l.end_date, l.remarks) LIKE ?
          AND (? = '' OR l.type = ?)";

$stmt = mysqli_prepare($dbconn, "SELECT COUNT(*) $from $where");
mysqli_stmt_bind_param($stmt, "sss", $like, $type, $type);
mysqli_stmt_execute($stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

$total_pages = max(1, ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$stmt = mysqli_prepare($dbconn, "SELECT l.*, s.student_no, s.name, s.department $from $where ORDER BY l.start_date DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "sssii", $like, $type, $type, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// dashboard card counts
$counts = mysqli_fetch_assoc(mysqli_query($dbconn,
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(type = 'MC'), 0) AS mc,
            COALESCE(SUM(type = 'Leave'), 0) AS leave_count
     FROM leave_mc"));


$students = mysqli_query($dbconn, "SELECT student_id, student_no, name FROM students ORDER BY name");

$filterUrl = function ($selectedType) use ($search) {
    return '?' . http_build_query([
        'search' => $search,
        'type' => $selectedType,
    ]);
};
$pageUrl = function ($selectedPage) use ($search, $type) {
    return '?' . http_build_query([
        'page' => $selectedPage,
        'search' => $search,
        'type' => $type,
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
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="page-heading">
            <h1><?= htmlspecialchars($L['leave_mc record']) ?></h1>
        </div>

        <div class="cards">
            <div class="dash-card">
                <i class="fa-solid fa-users"></i>
                <h2><?php echo (int)$counts['total']; ?></h2>
                <h5><?= htmlspecialchars($L['total records']) ?></h5>
            </div>
            <div class="dash-card">
                <i class="fa-solid fa-user-doctor"></i>
                <h2><?php echo (int)$counts['mc']; ?></h2>
                <h5><?= htmlspecialchars($L['mc']) ?></h5>
            </div>
            <div class="dash-card">
                <i class="fa-solid fa-user-xmark"></i>
                <h2><?php echo (int)$counts['leave_count']; ?></h2>
                <h5><?= htmlspecialchars($L['leave']) ?></h5>
            </div>
        </div>

        <div class="toolbar">
            <div class="filter">
            <button class="filter-button" type="button" aria-label="Filter" aria-expanded="false" aria-controls="filterDropdown">
            <i class="fa-solid fa-sliders" style="color: rgb(124, 117, 117);"></i>
            </button>
            <div id="filterDropdown" class="dropdown-content filter-dropdown">
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('')); ?>"><?= htmlspecialchars($L['all leave types']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('MC')); ?>"><?= htmlspecialchars($L['mc']) ?></a>
            <a class="dropdown-item" href="<?php echo htmlspecialchars($filterUrl('Leave')); ?>"><?= htmlspecialchars($L['leave']) ?></a>
            </div>
            </div>
            <a id="modalBtn" class="btnadd"><?= htmlspecialchars($L['add mc_leave']) ?></a>
            <form method="get" class="search-bar">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
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
                        <th class="table_th"><?= htmlspecialchars($L['leave type']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['start date']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['end date']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['Document']) ?></th>
                        <th class="table_th"><?= htmlspecialchars($L['actions']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                    <tr><td colspan="8"><?= htmlspecialchars($L['no record found']) ?></td></tr>
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
                        <td class="table_td"></td>
                        <td class="table_td">
                            <div class="action-icons">
                                <a href="#" class="icon-edit open-modal" data-modal-target="leaveEditModal" aria-label="Edit leave record for <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-leave_mc_id="<?php echo (int)$info['leave_mc_id']; ?>"
                                    data-type="<?php echo htmlspecialchars($info['type']); ?>"
                                    data-start_date="<?php echo htmlspecialchars($info['start_date']); ?>"
                                    data-end_date="<?php echo htmlspecialchars($info['end_date']); ?>"><i class="fa-solid fa-pencil" style="color: rgb(53, 157, 232);"></i></a>
                                <a href="action.php?delete_leave=<?php echo (int)$info['leave_mc_id']; ?>" class="icon-delete" aria-label="Delete leave record for <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?>" onclick="return confirm('Are you sure you want to delete this leave record?')"><i class="fa-solid fa-trash" style="color: rgb(250, 1, 1);"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <div id="myModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div class="wrapper">
                        <div class="form-wrapper">
                            <h1><?= htmlspecialchars($L['add student mc_leave']) ?></h1>
                            <form method="POST" action="action.php">
                                <select name="student_id" id="studentSelect" required>
                                    <option value="">Search student...</option>
                                    <?php while ($st = mysqli_fetch_assoc($students)) { ?>
                                    <option value="<?php echo (int)$st['student_id']; ?>">
                                        <?php echo htmlspecialchars($st['student_no'] . ' - ' . $st['name']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                                <select name="type" required>
                                    <option value="" disabled selected><?= htmlspecialchars($L['leave type']) ?></option>
                                    <option value="Leave"><?= htmlspecialchars($L['leave']) ?></option>
                                    <option value="MC"><?= htmlspecialchars($L['mc']) ?></option>
                                </select>
                                <input type="date" name="start_date" required>
                                <input type="date" name="end_date" required>
                                <input type="text" name="remarks" placeholder="Remarks" required>
                                <div class="file-upload">
                                <input type="file" id="photo-upload" accept="image/*" aria-label="Choose an image" onchange="previewImage(event)">
                                <span class="file-upload-content">
                                    <span class="file-upload-button"><?= htmlspecialchars($L['choose file']) ?></span>
                                    <span class="file-upload-name"><?= htmlspecialchars($L['no file chosen']) ?></span>
                                </span>
                        </div>

                                <div class="form-buttons">
                                    <button type="button" class="btncancel" id="cancelModalBtn"><?= htmlspecialchars($L['cancel']) ?></button>
                                    <button type="submit" class="btnadd2" name="add_leave"><?= htmlspecialchars($L['add']) ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div id="leaveEditModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div class="wrapper">
                        <div class="form-wrapper">
                            <h1><?= htmlspecialchars($L['edit leave_mc record']) ?></h1>
                            <form method="POST" action="action.php">
                                <input type="hidden" name="leave_mc_id" data-field="leave_mc_id">
                                <select name="type" data-field="type" required>
                                    <option value="Leave"><?= htmlspecialchars($L['leave']) ?></option>
                                    <option value="MC"><?= htmlspecialchars($L['mc']) ?></option>
                                </select>
                                <input type="date" name="start_date" data-field="start_date" required>
                                <input type="date" name="end_date" data-field="end_date" required>
                                <input type="text" name="remarks" data-field="remarks" placeholder="<?= htmlspecialchars($L['remarks']) ?>" required>
                                <div class="form-buttons">
                                    <button type="button" class="btncancel" id="cancelModalBtn"><?= htmlspecialchars($L['cancel']) ?></button>
                                    <button type="submit" class="btnadd2" name="update_leave"><?= htmlspecialchars($L['save']) ?></button>
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

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="script.js"></script>
<script>
    const tomSelectPlaceholder = <?= json_encode($L['search student'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const tomSelectNoResults = <?= json_encode($L['no results found'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    new TomSelect("#studentSelect", {
        placeholder: tomSelectPlaceholder,
        maxOptions: null,
        render: {
            no_results: function(data, escape) {
                return '<div class="no-results">' + escape(tomSelectNoResults) + '</div>';
            }
        }
    });
</script>
</body>
</html>
