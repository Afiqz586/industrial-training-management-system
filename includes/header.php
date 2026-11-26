<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
}

$currentLang = $_SESSION['lang'] ?? 'en';

include 'language.php';

$L = $lang[$currentLang];

if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

include_once __DIR__ . '/../dbconn.php';

$photoStmt = mysqli_prepare($dbconn, "SELECT photo FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($photoStmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($photoStmt);
$headerPhoto = mysqli_fetch_assoc(mysqli_stmt_get_result($photoStmt))['photo'] ?? null;

$headerUsername = $_SESSION['username'];
$headerRole = $_SESSION['role'];
$notificationStmt = mysqli_prepare(
    $dbconn,
    "SELECT notification_id, message, is_read
     FROM notifications
     WHERE recipient_user_id = ?
     ORDER BY notification_id DESC"
);
if (!$notificationStmt) {
    throw new RuntimeException("Could not prepare notification query: " . mysqli_error($dbconn));
}
mysqli_stmt_bind_param($notificationStmt, "i", $_SESSION['user_id']);
if (!mysqli_stmt_execute($notificationStmt)) {
    throw new RuntimeException("Could not load notifications: " . mysqli_stmt_error($notificationStmt));
}
$notificationResult = mysqli_stmt_get_result($notificationStmt);
$notifications = [];
$unreadNotificationCount = 0;
while ($notification = mysqli_fetch_assoc($notificationResult)) {
    $notifications[] = $notification;
    if (!(int) $notification['is_read']) {
        $unreadNotificationCount++;
    }
}
mysqli_stmt_close($notificationStmt);
?>

<?php $current = basename($_SERVER['PHP_SELF']); ?>
<?php
$toastFlash = $_SESSION['toast_flash'] ?? null;
unset($_SESSION['toast_flash']);
?>

        <div id="toastBox"></div>
        <div class="notification">
            <button id="notif-btn" class="notification-button" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notificationDropdown">
             <i class="fa-regular fa-bell" style="color: rgb(101, 97, 97);"></i>
            </button>
            <span id="notif-badge" class="notification-badge" <?= $unreadNotificationCount === 0 ? 'hidden' : '' ?>>
                <?= $unreadNotificationCount ?>
            </span>
            <div id="notificationDropdown" class="dropdown-content notification-dropdown">
                <div class="notification-heading">
                    <strong>Notifications</strong>
                    <button id="clear-btn" type="button">Clear all</button>
                </div>
                <ul id="notif-list" aria-live="polite">
                    <?php foreach ($notifications as $notification): ?>
                        <li data-notification-id="<?= (int) $notification['notification_id'] ?>"
                            class="<?= (int) $notification['is_read'] ? '' : 'unread' ?>">
                            <?= htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php if ($toastFlash !== null): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    switch (<?= json_encode($toastFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) {
                        case "student_added":
                            showToast(addstudentMsg);
                            break;
                        case "record_deleted":
                            showToast(deleteRecordMsg);
                            break;
                    }
                });
            </script>
        <?php endif; ?>
        <div class="profile">
            <button id="profile-btn" class="drop-btn" type="button" aria-label="Open profile menu" aria-expanded="false" aria-controls="dropDown">
                <span class="profile-avatar">
                    <?php if (!empty($headerPhoto)): ?>
                        <img src="<?= htmlspecialchars($headerPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <?php endif; ?>
                </span>
            </button>
            <div id="dropDown" class="dropdown-content">
                <span class="dropdown-item"><?php echo htmlspecialchars($headerUsername);?></span>
                <span class="dropdown-item"><?php echo htmlspecialchars($headerRole);?></span>
                <a class="dropdown-item" href="logout.php">
                    <i class="fa-solid fa-right-from-bracket" style="color: rgb(250, 1, 1);" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($L['logout']);?>
                </a>
            </div>
        </div>