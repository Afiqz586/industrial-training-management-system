<?php $current = basename($_SERVER['PHP_SELF']); ?>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="images/MPSLogo.png" class="header-logo" alt="MPS Logo">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-content">
        <ul class="menu-list">
            <li class="menu-item">
                <a href="index.php" class="menu-link <?= $current == 'index.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-house"></i>
                    <span class="menu-text"><?= htmlspecialchars($L['dashboard']) ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="students.php" class="menu-link <?= $current == 'students.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span class="menu-text"><?= htmlspecialchars($L['students']) ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="attendance.php" class="menu-link <?= $current == 'attendance.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-check"></i>
                    <span class="menu-text"><?= htmlspecialchars($L['attendance']) ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="leave.php" class="menu-link <?= $current == 'leave.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-xmark"></i>
                    <span class="menu-text"><?= htmlspecialchars($L['leave_mc']) ?></span>
                </a>
            </li>
        </ul>

        <ul class="menu-list logout-list">
            <li class="menu-item">
                <a href="login.php" class="menu-link">
                    <i class="fa-solid fa-right-from-bracket" style="color: rgb(250, 1, 1);"></i>
                    <span class="menu-text"><?= htmlspecialchars($L['logout']) ?></span>
                </a>
            </li>
        </ul>
    </div>
</aside>

<script>
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('collapsed');
    });
</script>