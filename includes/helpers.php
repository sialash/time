<?php
/**
 * توابع کمکی عمومی
 */

/** ایمن‌سازی خروجی HTML */
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** ریدایرکت و پایان اسکریپت */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/** ذخیره پیام یک‌بارمصرف (فلش) */
function flash_set($msg, $type = 'success') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'][] = ['m' => $msg, 't' => $type];
}

/** نمایش و پاک کردن پیام‌های فلش */
function flash_render() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['flash'])) return '';
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $out .= '<div class="flash flash-' . e($f['t']) . '">' . e($f['m']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/** مدت به شکل 07:25 (ساعت:دقیقه) */
function hm($seconds) {
    $s = max(0, (int)$seconds);
    return sprintf('%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60));
}

/** مدت به شکل فارسی؛ مثال: «۷ ساعت و ۲۵ دقیقه» */
function dur_fa($seconds) {
    $s = max(0, (int)$seconds);
    $h = intdiv($s, 3600);
    $m = intdiv($s % 3600, 60);
    if ($h && $m) return fa_digits($h) . ' ساعت و ' . fa_digits($m) . ' دقیقه';
    if ($h) return fa_digits($h) . ' ساعت';
    return fa_digits($m) . ' دقیقه';
}

/** مدت به ساعت اعشاری؛ مثال: 7.42 */
function hours_decimal($seconds) {
    return round(max(0, (int)$seconds) / 3600, 2);
}

/** محافظت CSV در برابر تزریق فرمول اکسل */
function csv_safe($v) {
    $v = (string)$v;
    if ($v !== '' && in_array($v[0], ['=', '+', '-', '@'], true)) {
        $v = "'" . $v;
    }
    return $v;
}

/* اگر افزونه mbstring روی هاست نبود، جایگزین ساده */
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $length = null) {
        preg_match_all('/./us', (string)$s, $m);
        return implode('', array_slice($m[0], (int)$start, $length));
    }
}
