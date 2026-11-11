<?php
/**
 * احراز هویت (نشست) و توکن CSRF
 */
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** کاربر جاری یا null */
function current_user() {
    return $_SESSION['app_user'] ?? null;
}

/** اجبار به ورود؛ اگر لاگین نبود به صفحه ورود می‌رود */
function require_login() {
    if (!current_user()) {
        redirect('login.php');
    }
}

/** توکن CSRF برای فرم‌ها */
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/** بررسی توکن CSRF در درخواست‌های POST */
function verify_csrf() {
    $t = (string)($_POST['csrf'] ?? '');
    if ($t === '' || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('درخواست نامعتبر است. لطفاً صفحه را دوباره بارگذاری کنید.');
    }
}

/** بررسی نام کاربری و رمز عبور (پشتیبانی از رمز هش‌شده bcrypt) */
function check_credentials($user, $pass) {
    $userOk = hash_equals((string)APP_USERNAME, (string)$user);
    if (strpos((string)APP_PASSWORD, '$2y$') === 0) {
        return $userOk && password_verify((string)$pass, APP_PASSWORD);
    }
    return $userOk && hash_equals((string)APP_PASSWORD, (string)$pass);
}
