<?php
/**
 * ─────────────────────────────────────────────
 *  تنظیمات سامانه ثبت ساعت کاری
 *  این فایل را مطابق اطلاعات هاست خود ویرایش کنید.
 * ─────────────────────────────────────────────
 */

/* ─── ۱) اطلاعات اتصال به MySQL ─── */
define('DB_HOST', 'sql204.netafrooz.net');   // معمولاً localhost بگذارید
define('DB_NAME', 'xznu_43391502_siavash');    // نام دیتابیس
define('DB_USER', 'xznu_43391502');        // نام کاربری دیتابیس
define('DB_PASS', 'Sia7257482');            // رمز عبور دیتابیس

/* ─── ۲) منطقه زمانی ─── */
define('APP_TIMEZONE', 'Asia/Tehran');

/* ─── ۳) نام کاربری و رمز عبور ورود به سامانه (حتماً تغییر دهید!) ───
   نکته: اگر مقدار APP_PASSWORD را با هش bcrypt (شروع‌شده با $2y$)
   جایگزین کنید، به‌صورت هش‌شده بررسی می‌شود. */
define('APP_USERNAME', 'siavash');
define('APP_PASSWORD', 'zxzzzzzz');

/* ─── ۴) عنوان سامانه ─── */
define('APP_NAME', 'سامانه ثبت ساعت کاری');

date_default_timezone_set(APP_TIMEZONE);

/* نمایش خطا فقط در حالت اشکال‌زدایی (true کنید اگر صفحه سفید دیدید) */
define('APP_DEBUG', false);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

/* پیام خطای فارسی برای خطاهای پیش‌بینی‌نشده */
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>خطا</title></head>';
    echo '<body style="font-family:Tahoma,sans-serif;direction:rtl;padding:40px;background:#f8fafc">';
    echo '<div style="max-width:600px;margin:auto;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px">';
    echo '<h2 style="color:#b91c1c;margin-top:0">خطایی رخ داد</h2>';
    echo '<p dir="auto" style="background:#fee2e2;padding:10px;border-radius:8px">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>راهنمایی: اطلاعات اتصال دیتابیس در فایل <b>config.php</b> را بررسی کنید و مطمئن شوید سرویس MySQL در حال اجراست. برای بررسی بیشتر می‌توانید فایل <b>install.php</b> را در مرورگر باز کنید.</p>';
    echo '</div></body></html>';
});
