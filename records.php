<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/jalali.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

list($jyT, $jmT, $jdT) = j_today();

/* ─── حذف رکورد ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('DELETE FROM entries WHERE id = ?');
        $st->execute([$id]);
        flash_set('رکورد موردنظر حذف شد.');
    }
    redirect('records.php?y=' . (int)($_POST['y'] ?? $jyT) . '&m=' . (int)($_POST['m'] ?? $jmT));
}

/* ─── ماه انتخابی ─── */
$jy = isset($_GET['y']) ? (int)$_GET['y'] : $jyT;
$jm = isset($_GET['m']) ? (int)$_GET['m'] : $jmT;
if ($jm < 1 || $jm > 12) $jm = $jmT;
if ($jy < 1300 || $jy > 1500) $jy = $jyT;

list($mStart, $mEnd) = j_month_range($jy, $jm);
list($prevY, $prevM) = j_month_shift($jy, $jm, -1);
list($nextY, $nextM) = j_month_shift($jy, $jm, 1);

$st = $pdo->prepare('SELECT * FROM entries WHERE g_date BETWEEN ? AND ? ORDER BY g_date, check_in');
$st->execute([$mStart, $mEnd]);
$rows = $st->fetchAll();

$byDay = [];
foreach ($rows as $r) $byDay[$r['g_date']][] = $r;

$monthSec = 0;
foreach ($rows as $r) {
    if ($r['check_out']) $monthSec += strtotime($r['check_out']) - strtotime($r['check_in']);
}

$yearStart = 1395;
$yearEnd   = $jyT + 1;

page_header('گزارش و ویرایش ماه ' . j_month_name($jm) . ' ' . fa_digits($jy), 'records');
?>

<div class="toolbar">
    <a class="btn ghost" href="records.php?y=<?= (int)$prevY ?>&m=<?= (int)$prevM ?>">ماه قبل</a>
    <form method="get" id="month-nav">
        <select name="m" aria-label="ماه">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>"<?= $i === $jm ? ' selected' : '' ?>><?= e(j_month_name($i)) ?></option>
            <?php endfor; ?>
        </select>
        <select name="y" aria-label="سال">
            <?php for ($yy = $yearEnd; $yy >= $yearStart; $yy--): ?>
                <option value="<?= $yy ?>"<?= $yy === $jy ? ' selected' : '' ?>><?= e(fa_digits($yy)) ?></option>
            <?php endfor; ?>
        </select>
        <noscript><button class="btn sm" type="submit">نمایش</button></noscript>
    </form>
    <a class="btn ghost" href="records.php?y=<?= (int)$nextY ?>&m=<?= (int)$nextM ?>">ماه بعد</a>
    <span class="spacer"></span>
    <a class="btn" href="edit.php?y=<?= (int)$jy ?>&m=<?= (int)$jm ?>">ثبت دستی</a>
    <a class="btn green" href="export.php?y=<?= (int)$jy ?>&m=<?= (int)$jm ?>&fmt=xlsx">خروجی اکسل</a>
    <a class="btn ghost" href="export.php?y=<?= (int)$jy ?>&m=<?= (int)$jm ?>&fmt=csv">CSV</a>
</div>

<div class="stats">
    <div class="stat">
        <div class="label">مجموع کارکرد <?= e(j_month_name($jm) . ' ' . fa_digits($jy)) ?></div>
        <div class="value"><?= e(dur_fa($monthSec)) ?></div>
    </div>
    <div class="stat">
        <div class="label">معادل اعشاری</div>
        <div class="value"><?= e(fa_digits(number_format(hours_decimal($monthSec), 2))) ?> ساعت</div>
    </div>
    <div class="stat">
        <div class="label">روزهای دارای کارکرد</div>
        <div class="value"><?= e(fa_digits(count($byDay))) ?> روز</div>
    </div>
</div>

<?php if (!$rows): ?>
    <div class="card empty">هیچ رکوردی برای <?= e(j_month_name($jm) . ' ' . fa_digits($jy)) ?> ثبت نشده است. با دکمه «ثبت دستی» می‌توانید روزهای قبل را اضافه کنید.</div>
<?php else: ?>
    <table class="tbl">
        <thead>
        <tr><th>ورود</th><th>خروج</th><th>مدت</th><th>توضیح ورود</th><th>توضیح خروج</th><th>عملیات</th></tr>
        </thead>
        <tbody>
        <?php foreach ($byDay as $gdate => $dayRows):
            $daySec = 0;
            foreach ($dayRows as $r) {
                if ($r['check_out']) $daySec += strtotime($r['check_out']) - strtotime($r['check_in']);
            }
            $gp = explode('-', $gdate);
            list($djy, $djm, $djd) = g2j((int)$gp[0], (int)$gp[1], (int)$gp[2]);
        ?>
            <tr class="day-head">
                <td colspan="6">
                    <?= e(j_weekday_fa($gp[0], $gp[1], $gp[2])) ?> — <?= e(fa_digits(sprintf('%04d/%02d/%02d', $djy, $djm, $djd))) ?>
                    <span class="day-total">مجموع روز: <?= e(fa_digits(hm($daySec))) ?></span>
                    <a class="btn sm ghost add-day" href="edit.php?y=<?= (int)$djy ?>&m=<?= (int)$djm ?>&d=<?= (int)$djd ?>">افزودن به این روز</a>
                </td>
            </tr>
            <?php foreach ($dayRows as $r): ?>
            <tr>
                <td><?= e(fa_digits(substr($r['check_in'], 11, 5))) ?></td>
                <td><?= $r['check_out'] ? e(fa_digits(substr($r['check_out'], 11, 5))) : '<span class="muted">در جریان</span>' ?></td>
                <td><?= $r['check_out'] ? e(fa_digits(hm(strtotime($r['check_out']) - strtotime($r['check_in'])))) : '<span class="muted">—</span>' ?></td>
                <td class="notes"><?= $r['note'] ? e($r['note']) : '' ?></td>
                <td class="notes"><?= $r['note_out'] ? e($r['note_out']) : '' ?></td>
                <td class="actions">
                    <a class="btn sm ghost" href="edit.php?id=<?= (int)$r['id'] ?>">ویرایش</a>
                    <form method="post" data-confirm="این رکورد حذف شود؟">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="y" value="<?= (int)$jy ?>">
                        <input type="hidden" name="m" value="<?= (int)$jm ?>">
                        <button class="btn sm danger-ghost" type="submit">حذف</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php page_footer();
