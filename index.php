<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/jalali.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

/* ─── ثبت ورود / خروج ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $note   = trim((string)($_POST['note'] ?? ''));

    if ($action === 'checkin') {
        $exists = $pdo->query('SELECT id FROM entries WHERE check_out IS NULL LIMIT 1')->fetch();
        if ($exists) {
            flash_set('یک نوبت کاری باز وجود دارد؛ برای ثبت ورود جدید ابتدا باید خروج بزنید.', 'error');
        } else {
            $st = $pdo->prepare('INSERT INTO entries (g_date, check_in, note) VALUES (?, ?, ?)');
            $st->execute([date('Y-m-d'), date('Y-m-d H:i:s'), $note !== '' ? $note : null]);
            flash_set('ورود شما با موفقیت ثبت شد. روز خوبی داشته باشید!');
        }
    } elseif ($action === 'checkout') {
        $openRow = $pdo->query('SELECT * FROM entries WHERE check_out IS NULL ORDER BY check_in DESC LIMIT 1')->fetch();
        if (!$openRow) {
            flash_set('نوبت کاری بازی برای ثبت خروج پیدا نشد.', 'error');
        } else {
            $st = $pdo->prepare('UPDATE entries SET check_out = ?, note_out = ? WHERE id = ?');
            $st->execute([date('Y-m-d H:i:s'), $note !== '' ? $note : null, $openRow['id']]);
            flash_set('خروج شما ثبت شد. مراقب خودتان باشید!');
        }
    }
    redirect('index.php');
}

/* ─── اطلاعات امروز و ماه جاری ─── */
list($jy, $jm, $jd) = j_today();
list($mStart, $mEnd) = j_month_range($jy, $jm);
$today = date('Y-m-d');

$open = $pdo->query('SELECT * FROM entries WHERE check_out IS NULL ORDER BY check_in DESC LIMIT 1')->fetch();

$st = $pdo->prepare('SELECT * FROM entries WHERE g_date = ? ORDER BY check_in');
$st->execute([$today]);
$todayRows = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM entries WHERE g_date BETWEEN ? AND ? ORDER BY g_date, check_in');
$st->execute([$mStart, $mEnd]);
$monthRows = $st->fetchAll();

$todaySec = 0;
foreach ($todayRows as $r) {
    if ($r['check_out']) {
        $todaySec += strtotime($r['check_out']) - strtotime($r['check_in']);
    } elseif ($open && (int)$r['id'] === (int)$open['id']) {
        $todaySec += max(0, time() - strtotime($r['check_in']));
    }
}
$monthSec = 0;
foreach ($monthRows as $r) {
    if ($r['check_out']) $monthSec += strtotime($r['check_out']) - strtotime($r['check_in']);
}
$monthDays = count(array_unique(array_column($monthRows, 'g_date')));
$elapsed = $open ? max(0, time() - strtotime($open['check_in'])) : 0;

page_header('داشبورد', 'index');
$todayFa = jdate_full($today);
?>

<div class="card hero">
    <div>
        <div class="date"><?= e($todayFa) ?></div>
        <div class="clock muted" id="live-clock" data-date-text="<?= e($todayFa) ?>"><?= e(fa_digits(date('H:i:s'))) ?></div>
    </div>
    <div class="status">
        <?php if ($open): ?>
            <span class="badge badge-green">در حال کار</span>
            <div class="muted small">شروع از ساعت <?= e(fa_digits(substr($open['check_in'], 11, 5))) ?></div>
            <div class="timer" id="elapsed" data-start="<?= e($open['check_in']) ?>"><?= e(sprintf('%02d:%02d:%02d', intdiv($elapsed, 3600), intdiv($elapsed % 3600, 60), $elapsed % 60)) ?></div>
        <?php else: ?>
            <span class="badge badge-gray">خارج از کار</span>
            <div class="muted small">در حال حاضر نوبت کاری فعالی ندارید</div>
        <?php endif; ?>
    </div>
</div>

<div class="card clock-wrap">
    <form method="post" action="index.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $open ? 'checkout' : 'checkin' ?>">
        <textarea name="note" rows="2" placeholder="توضیحات (اختیاری)؛ مثلاً: کار روی پروژه حسابداری، جلسه با تیم فروش، مأموریت بیرون از شرکت..."></textarea>
        <button type="submit" class="clock-btn <?= $open ? 'out pulse-red' : 'pulse-green' ?>"><?= $open ? 'ثبت خروج' : 'ثبت ورود' ?></button>
        <p class="muted small"><?= $open ? 'برای پایان این نوبت کاری، دکمه را بزنید.' : 'برای شروع نوبت کاری، دکمه را بزنید.' ?></p>
    </form>
</div>

<div class="stats">
    <div class="stat">
        <div class="label">مجموع کارکرد امروز</div>
        <div class="value"><?= e(dur_fa($todaySec)) ?></div>
    </div>
    <div class="stat">
        <div class="label">مجموع کارکرد <?= e(j_month_name($jm) . ' ' . fa_digits($jy)) ?></div>
        <div class="value"><?= e(dur_fa($monthSec)) ?></div>
    </div>
    <div class="stat">
        <div class="label">روزهای دارای کارکرد در این ماه</div>
        <div class="value"><?= e(fa_digits($monthDays)) ?> روز</div>
    </div>
</div>

<h2 class="section-title">
    نوبت‌های کاری امروز
    <a class="btn sm ghost" href="records.php?y=<?= (int)$jy ?>&m=<?= (int)$jm ?>">گزارش کامل ماه</a>
</h2>

<?php if (!$todayRows): ?>
    <div class="card empty">امروز هنوز رکوردی ثبت نشده است.</div>
<?php else: ?>
    <table class="tbl">
        <thead>
        <tr><th>ورود</th><th>خروج</th><th>مدت</th><th>توضیحات</th><th>عملیات</th></tr>
        </thead>
        <tbody>
        <?php foreach ($todayRows as $r): ?>
            <tr>
                <td><?= e(fa_digits(substr($r['check_in'], 11, 5))) ?></td>
                <td><?= $r['check_out'] ? e(fa_digits(substr($r['check_out'], 11, 5))) : '<span class="muted">در جریان</span>' ?></td>
                <td><?= $r['check_out'] ? e(fa_digits(hm(strtotime($r['check_out']) - strtotime($r['check_in'])))) : '<span class="muted">—</span>' ?></td>
                <td class="notes">
                    <?= $r['note'] ? '<div>' . e($r['note']) . '</div>' : '' ?>
                    <?= $r['note_out'] ? '<div class="muted small">خروج: ' . e($r['note_out']) . '</div>' : '' ?>
                </td>
                <td><a class="btn sm ghost" href="edit.php?id=<?= (int)$r['id'] ?>">ویرایش</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php page_footer();
