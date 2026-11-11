<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (current_user()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    if (check_credentials($u, $p)) {
        session_regenerate_id(true);
        $_SESSION['app_user'] = APP_USERNAME;
        redirect('index.php');
    } else {
        $error = 'نام کاربری یا رمز عبور اشتباه است.';
    }
}
$token = csrf_token();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود به سامانه | <?= e(APP_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='46' fill='%232563eb'/%3E%3Cpath d='M50 26v26l17 11' stroke='%23fff' stroke-width='9' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
  <form method="post" class="login-card">
    <h1>ساعت کاری من</h1>
    <p class="sub">برای ثبت و مدیریت ساعت‌های کاری، وارد شوید</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <label for="username">نام کاربری</label>
    <input type="text" id="username" name="username" autofocus required autocomplete="username">
    <label for="password">رمز عبور</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
    <button class="btn block" type="submit">ورود</button>
  </form>
</body>
</html>
