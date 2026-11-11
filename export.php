<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/jalali.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/xlsx.php';
require_login();
$pdo = db();

list($jyT, $jmT) = j_today();
$jy = isset($_GET['y']) ? (int)$_GET['y'] : $jyT;
$jm = isset($_GET['m']) ? (int)$_GET['m'] : $jmT;
if ($jm < 1 || $jm > 12) $jm = $jmT;
if ($jy < 1300 || $jy > 1500) $jy = $jyT;
$fmt = (($_GET['fmt'] ?? 'xlsx') === 'csv') ? 'csv' : 'xlsx';

list($mStart, $mEnd) = j_month_range($jy, $jm);
$st = $pdo->prepare('SELECT * FROM entries WHERE g_date BETWEEN ? AND ? ORDER BY g_date, check_in');
$st->execute([$mStart, $mEnd]);
$rows = $st->fetchAll();

/* جمع‌بندی روزانه */
$byDay = [];
foreach ($rows as $r) $byDay[$r['g_date']][] = $r;

$daySummaries = [];
$totalSec = 0;
foreach ($byDay as $gdate => $drs) {
    $sec = 0; $first = null; $last = null; $cnt = 0;
    foreach ($drs as $r) {
        if ($r['check_out']) {
            $sec += strtotime($r['check_out']) - strtotime($r['check_in']);
            if ($first === null) $first = $r['check_in'];
            $last = $r['check_out'];
            $cnt++;
        }
    }
    $totalSec += $sec;
    $daySummaries[$gdate] = ['sec' => $sec, 'first' => $first, 'last' => $last, 'cnt' => $cnt];
}

$monthTitle = 'کارکرد ماه ' . j_month_name($jm) . ' ' . fa_digits($jy);
$faName     = 'کارکرد-' . $jy . '-' . sprintf('%02d', $jm);
$asciiName  = 'karnameh-' . $jy . '-' . sprintf('%02d', $jm);

function output_csv($rows, $jy, $jm, $totalSec, $faName, $asciiName) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $asciiName . '.csv"; filename*=UTF-8\'\'' . rawurlencode($faName . '.csv'));
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['کارکرد ماه ' . j_month_name($jm) . ' ' . $jy], ',', '"', '\\');
    fputcsv($out, ['تاریخ', 'روز هفته', 'ورود', 'خروج', 'مدت (ساعت:دقیقه)', 'توضیح ورود', 'توضیح خروج'], ',', '"', '\\');
    foreach ($rows as $r) {
        fputcsv($out, [
            csv_safe(jdate_from_g($r['g_date'])),
            csv_safe(j_weekday_fa(...explode('-', $r['g_date']))),
            csv_safe(substr($r['check_in'], 11, 5)),
            csv_safe($r['check_out'] ? substr($r['check_out'], 11, 5) : ''),
            csv_safe($r['check_out'] ? hm(strtotime($r['check_out']) - strtotime($r['check_in'])) : ''),
            csv_safe((string)$r['note']),
            csv_safe((string)$r['note_out']),
        ], ',', '"', '\\');
    }
    fputcsv($out, [], ',', '"', '\\');
    fputcsv($out, ['جمع کل ماه', '', '', '', hm($totalSec), '', ''], ',', '"', '\\');
    fclose($out);
    exit;
}

if ($fmt === 'csv') {
    output_csv($rows, $jy, $jm, $totalSec, $faName, $asciiName);
}

/* ساخت خروجی xlsx با دو شیت */
$x = new MiniXlsx();

/* شیت ۱: خلاصه روزانه */
$s1 = [];
$s1[] = [['v' => $monthTitle . ' — خلاصه روزانه', 'style' => 'header']];
$s1[] = array_map(function ($h) { return ['v' => $h, 'style' => 'header']; },
    ['ردیف', 'تاریخ', 'روز هفته', 'تعداد نوبت', 'اولین ورود', 'آخرین خروج', 'مجموع (ساعت:دقیقه)', 'مجموع (ساعت اعشاری)']);
$i = 0;
foreach ($daySummaries as $gdate => $d) {
    $i++;
    $s1[] = [
        ['v' => $i, 'num' => true],
        ['v' => jdate_from_g($gdate)],
        ['v' => j_weekday_fa(...explode('-', $gdate))],
        ['v' => $d['cnt'], 'num' => true],
        ['v' => $d['first'] ? substr($d['first'], 11, 5) : ''],
        ['v' => $d['last'] ? substr($d['last'], 11, 5) : ''],
        ['v' => hm($d['sec'])],
        ['v' => hours_decimal($d['sec']), 'num' => true],
    ];
}
$s1[] = [
    ['v' => '', 'style' => 'total'],
    ['v' => 'جمع کل', 'style' => 'total'],
    ['v' => '', 'style' => 'total'],
    ['v' => array_sum(array_column($daySummaries, 'cnt')), 'num' => true, 'style' => 'total'],
    ['v' => '', 'style' => 'total'],
    ['v' => '', 'style' => 'total'],
    ['v' => hm($totalSec), 'style' => 'total'],
    ['v' => hours_decimal($totalSec), 'num' => true, 'style' => 'total'],
];
$x->addSheet('خلاصه روزانه', $s1, [6, 13, 11, 11, 11, 11, 19, 19]);

/* شیت ۲: جزئیات نوبت‌ها */
$s2 = [];
$s2[] = [['v' => $monthTitle . ' — جزئیات نوبت‌ها', 'style' => 'header']];
$s2[] = array_map(function ($h) { return ['v' => $h, 'style' => 'header']; },
    ['ردیف', 'تاریخ', 'روز هفته', 'ورود', 'خروج', 'مدت', 'توضیح ورود', 'توضیح خروج']);
$i = 0;
foreach ($rows as $r) {
    $i++;
    $s2[] = [
        ['v' => $i, 'num' => true],
        ['v' => jdate_from_g($r['g_date'])],
        ['v' => j_weekday_fa(...explode('-', $r['g_date']))],
        ['v' => substr($r['check_in'], 11, 5)],
        ['v' => $r['check_out'] ? substr($r['check_out'], 11, 5) : '—'],
        ['v' => $r['check_out'] ? hm(strtotime($r['check_out']) - strtotime($r['check_in'])) : 'در جریان'],
        ['v' => (string)$r['note']],
        ['v' => (string)$r['note_out']],
    ];
}
$x->addSheet('جزئیات', $s2, [6, 13, 11, 10, 10, 10, 30, 30]);

$data = $x->build();
if ($data === false) {
    /* افزونه Zip نبود → خروجی CSV */
    output_csv($rows, $jy, $jm, $totalSec, $faName, $asciiName);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $asciiName . '.xlsx"; filename*=UTF-8\'\'' . rawurlencode($faName . '.xlsx'));
header('Content-Length: ' . strlen($data));
header('Cache-Control: max-age=0');
echo $data;
exit;
