<?php
/**
 * قالب مشترک صفحات (هدر و فوتر)
 */

function page_header($title, $active = '') {
    $flash = flash_render();
    ?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='46' fill='%232563eb'/%3E%3Cpath d='M50 26v26l17 11' stroke='%23fff' stroke-width='9' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
<link rel="stylesheet" href="assets/style.css">
</head>
<body data-server-time="<?= e(date('Y-m-d H:i:s')) ?>">
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="index.php">ساعت کاری من</a>
    <nav>
      <a href="index.php"<?= $active === 'index' ? ' class="active"' : '' ?>>داشبورد</a>
      <a href="records.php"<?= $active === 'records' ? ' class="active"' : '' ?>>گزارش و ویرایش</a>
      <a href="logout.php" class="logout">خروج از حساب</a>
    </nav>
  </div>
</header>
<main class="container">
<?= $flash ?>
<?php
}

function page_footer() {
    ?></main>
<footer class="footer">ثبت ساعت کاری با PHP و MySQL — تقویم شمسی</footer>
<script src="assets/app.js"></script>
</body>
</html><?php
}
