<?php
/**
 * بررسی نصب و راه‌اندازی — پس از نصب موفق می‌توانید این فایل را حذف کنید.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>نصب سامانه</title>
<style>
body{font-family:Tahoma,sans-serif;background:#f2f5fa;padding:30px;direction:rtl}
.box{max-width:680px;margin:auto;background:#fff;border-radius:14px;padding:24px;box-shadow:0 4px 18px rgba(0,0,0,.08)}
h1{font-size:1.25rem}
li{margin:8px 0}
.ok{color:#15803d;font-weight:bold}
.bad{color:#b91c1c;font-weight:bold}
</style>
</head>
<body>
<div class="box">
<h1>بررسی نصب سامانه ثبت ساعت کاری</h1>
<ul>
<?php
function install_row($label, $ok, $extra = '') {
    echo '<li>' . htmlspecialchars($label) . ': <span class="' . ($ok ? 'ok' : 'bad') . '">' . ($ok ? 'مناسب' : 'نامناسب') . '</span> ' . htmlspecialchars($extra) . '</li>';
}
install_row('نسخه PHP (نیازمند 7.4 به بالا)', version_compare(PHP_VERSION, '7.4.0', '>='), '(' . PHP_VERSION . ')');
install_row('افزونه PDO MySQL', extension_loaded('pdo_mysql'));
install_row('افزونه mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? '' : '(اختیاری — در صورت نبود، جایگزین داخلی فعال می‌شود)');
install_row('افزونه Zip (خروجی اکسل xlsx)', class_exists('ZipArchive'), class_exists('ZipArchive') ? '' : '(در نبود آن، خروجی به‌صورت CSV داده می‌شود)');
try {
    db();
    install_row('اتصال به دیتابیس و ساخت جدول‌ها', true, '— جدول entries آماده است');
} catch (Throwable $ex) {
    install_row('اتصال به دیتابیس', false, $ex->getMessage());
}
?>
</ul>
<p>اگر همه موارد «مناسب» بود، سامانه آماده استفاده است. برای امنیت بیشتر این فایل را از سرور حذف کنید.</p>
<p><a href="login.php">رفتن به صفحه ورود</a></p>
</div>
</body>
</html>
