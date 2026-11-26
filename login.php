<?php
session_start();
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

if (isset($_GET['lang']) && is_string($_GET['lang']) && in_array($_GET['lang'], ['en', 'bm'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$currentLang = $_SESSION['lang'] ?? 'en';
include 'language.php';
$L = $lang[$currentLang] ?? $lang['en'];
$languageLabel = $currentLang === 'bm' ? 'BM' : 'EN';
$htmlLang = $currentLang === 'bm' ? 'ms' : 'en';
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($L['log in'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="login">
    <div class="filter login-language-selector">
        <button class="filter-button" type="button" aria-label="Choose language" aria-expanded="false" aria-controls="filterDropdown">
            <i class="fa-solid fa-globe" aria-hidden="true"></i>
            <span><?= htmlspecialchars($languageLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <i class="fa-solid fa-chevron-down language-chevron" aria-hidden="true"></i>
        </button>
        <div id="filterDropdown" class="dropdown-content filter-dropdown">
            <a class="dropdown-item" href="?lang=en" lang="en">English</a>
            <a class="dropdown-item" href="?lang=bm" lang="ms">Malay</a>
        </div>
    </div>
    <div class="wrapper">
        <img src="images/MPSLogo.png" class="login-logo" alt="MPS Logo">
        <div class="form-box login-form">
            <h1><?= htmlspecialchars($L['log in'], ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if ($loginError !== '') { ?>
                <p class="login-error" role="alert"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
            <form name="form" method="post" action="loginProcess.php">
                <div class="input-box">
                    <i class="fa-solid fa-user" style="color: rgb(0, 0, 0)"></i>
                    <input type="text" name="username" placeholder="<?= htmlspecialchars($L['username'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="input-box">
                    <i class="fa-solid fa-lock" style="color: rgb(0, 0, 0)"></i>
                    <input type="password" id="myPassword" name="password" placeholder="<?= htmlspecialchars($L['password'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <button type="button" class="toggle-password" onclick="showPassword(this)" aria-label="Show password" aria-pressed="false">
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>

                <button type="submit" name="Submit" class="btnsubmit"><?= htmlspecialchars($L['log in'], ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>