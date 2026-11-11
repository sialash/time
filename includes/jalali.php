<?php
/**
 * توابع تبدیل تاریخ شمسی (جلالی) ↔ میلادی
 * الگوریتم استاندارد جلالی — بدون نیاز به هیچ افزونه‌ای
 */

/** میلادی به شمسی — خروجی: [سال، ماه، روز] */
function g2j($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400)
          + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

/** شمسی به میلادی — خروجی: [سال، ماه، روز] */
function j2g($jy, $jm, $jd) {
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4)
          + $jd + (($jm < 7) ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186));
    $gy = 400 * intdiv($days, 146097);
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * intdiv(--$days, 36524);
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $gy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $isLeap = (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0));
    $md = [31, $isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $gm = 0;
    while ($gm < 12 && $gd > $md[$gm]) {
        $gd -= $md[$gm];
        $gm++;
    }
    return [$gy, $gm + 1, $gd];
}

/** نام ماه شمسی */
function j_month_name($m) {
    $names = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    return $names[(int)$m] ?? '';
}

/** نام روز هفته فارسی بر اساس تاریخ میلادی */
function j_weekday_fa($gy, $gm, $gd) {
    $names = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
    $w = (int)date('w', mktime(0, 0, 0, (int)$gm, (int)$gd, (int)$gy));
    return $names[$w];
}

/** تبدیل ارقام انگلیسی به فارسی برای نمایش */
function fa_digits($s) {
    return strtr((string)$s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

/** تبدیل ارقام فارسی/عربی به انگلیسی برای پردازش ورودی کاربر */
function en_digits($s) {
    return strtr((string)$s, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '٫' => '.',
    ]);
}

/** تاریخ میلادی Y-m-d → رشته شمسی 1405/08/20 */
function jdate_from_g($ymd) {
    $t = strtotime($ymd);
    if ($t === false) return '';
    list($jy, $jm, $jd) = g2j((int)date('Y', $t), (int)date('n', $t), (int)date('j', $t));
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

/** تاریخ کامل فارسی؛ مثال: «پنجشنبه ۲۰ آبان ۱۴۰۵» */
function jdate_full($ymd) {
    $t = strtotime($ymd);
    if ($t === false) return '';
    list($jy, $jm, $jd) = g2j((int)date('Y', $t), (int)date('n', $t), (int)date('j', $t));
    return j_weekday_fa(date('Y', $t), date('n', $t), date('j', $t))
         . ' ' . fa_digits($jd) . ' ' . j_month_name($jm) . ' ' . fa_digits($jy);
}

/** امروز شمسی — خروجی: [سال، ماه، روز] */
function j_today() {
    return g2j((int)date('Y'), (int)date('n'), (int)date('j'));
}

/** جابه‌جایی بین ماه‌های شمسی؛ مثال: j_month_shift(1405, 1, -1) → [1404, 12] */
function j_month_shift($jy, $jm, $delta) {
    $i = ((int)$jy * 12) + ((int)$jm - 1) + (int)$delta;
    return [intdiv($i, 12), ($i % 12) + 1];
}

/** بازه میلادی یک ماه شمسی — خروجی: [اولین روز Y-m-d، آخرین روز Y-m-d] */
function j_month_range($jy, $jm) {
    list($gy, $gm, $gd) = j2g((int)$jy, (int)$jm, 1);
    $start = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    list($ny, $nm) = j_month_shift($jy, $jm, 1);
    list($gy2, $gm2, $gd2) = j2g($ny, $nm, 1);
    $end = date('Y-m-d', strtotime(sprintf('%04d-%02d-%02d', $gy2, $gm2, $gd2) . ' -1 day'));
    return [$start, $end];
}
