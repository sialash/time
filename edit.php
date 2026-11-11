<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/jalali.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

$errors = [];
$values = ['id' => 0, 'jdate' => '', 't_in' => '', 't_out' => '', 'note' => '', 'note_out' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values['id']       = (int)($_POST['id'] ?? 0);
    $values['jdate']    = en_digits(trim((string)($_POST['jdate'] ?? '')));
    $values['t_in']     = en_digits(trim((string)($_POST['t_in'] ?? '')));
    $values['t_out']    = en_digits(trim((string)($_POST['t_out'] ?? '')));
    $values['note']     = trim((string)($_POST['note'] ?? ''));
    $values['note_out'] = trim((string)($_POST['note_out'] ?? ''));

    $jy = $jm = $jd = 0;
    if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $values['jdate'], $m)) {
        $errors[] = 'تاریخ باید شمسی و به شکل 1405/08/20 وارد شود.';
    } else {
        $jy = (int)$m[1]; $jm = (int)$m[2]; $jd = (int)$m[3];
        if ($jy < 1300 || $jy > 1500 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
            $errors[] = 'تاریخ واردشده معتبر نیست.';
        }
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $values['t_in'])) {
        $errors[] = 'ساعت ورود نامعتبر است (مثال: 09:30).';
    }
    if ($values['t_out'] !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $values['t_out'])) {
        $errors[] = 'ساعت خروج نامعتبر است (مثال: 17:30).';
    }
    if (!$errors && $values['t_out'] !== '' && $values['t_out'] === $values['t_in']) {
        $errors[] = 'ساعت خروج نمی‌تواند دقیقاً برابر ساعت ورود باشد.';
    }

    if (!$errors) {
        list($gy, $gm, $gd) = j2g($jy, $jm, $jd);
        $gdate    = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        $check_in = $gdate . ' ' . $values['t_in'] . ':00';
        if ($values['t_out'] !== '') {
            $check_out = $gdate . ' ' . $values['t_out'] . ':00';
            if ($check_out <= $check_in) {
                /* شیفت شب: خروج در روز بعد */
                $check_out = date('Y-m-d H:i:s', strtotime($check_out . ' +1 day'));
            }
        } else {
            $check_out = null;
        }

        if ($values['id']) {
            $st = $pdo->prepare('UPDATE entries SET g_date = ?, check_in = ?, check_out = ?, note = ?, note_out = ? WHERE id = ?');
            $st->execute([$gdate, $check_in, $check_out, $values['note'] !== '' ? $values['note'] : null,
                          $values['note_out'] !== '' ? $values['note_out'] : null, $values['id']]);
            flash_set('تغییرات با موفقیت ذخیره شد.');
        } else {
            $st = $pdo->prepare('INSERT INTO entries (g_date, check_in, check_out, note, note_out) VALUES (?, ?, ?, ?, ?)');
            $st->execute([$gdate, $check_in, $check_out, $values['note'] !== '' ? $values['note'] : null,
                          $values['note_out'] !== '' ? $values['note_out'] : null]);
            flash_set('رکورد جدید ثبت شد.');
        }
        redirect('records.php?y=' . $jy . '&m=' . $jm);
    }
} else {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $st = $pdo->prepare('SELECT * FROM entries WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            flash_set('رکورد موردنظر پیدا نشد.', 'error');
            redirect('records.php');
        }
        $values['id']       = $id;
        $values['jdate']    = jdate_from_g($row['g_date']);
        $values['t_in']     = substr($row['check_in'], 11, 5);
        $values['t_out']    = $row['check_out'] ? substr($row['check_out'], 11, 5) : '';
        $values['note']     = (string)$row['note'];
        $values['note_out'] = (string)$row['note_out'];
    } else {
        /* رکورد جدید: تاریخ پیش‌فرض از پارامتر ماه/روز یا امروز */
        $jyQ = (int)($_GET['y'] ?? 0);
        $jmQ = (int)($_GET['m'] ?? 0);
        $jdQ = (int)($_GET['d'] ?? 0);
        if ($jyQ && $jmQ && $jdQ) {
            $values['jdate'] = sprintf('%04d/%02d/%02d', $jyQ, $jmQ, $jdQ);
        } else {
            $values['jdate'] = jdate_from_g(date('Y-m-d'));
        }
        $values['t_in'] = date('H:i');
    }
}

page_header($values['id'] ? 'ویرایش رکورد' : 'ثبت دستی رکورد');
?>
<?php if ($errors): ?>
    <div class="flash flash-error"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div>
<?php endif; ?>

<div class="card narrow">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$values['id'] ?>">

        <label for="jdate">تاریخ (شمسی)</label>
        <input type="text" id="jdate" name="jdate" value="<?= e($values['jdate']) ?>" placeholder="مثال: 1405/08/20" required>

        <div class="form-row">
            <div>
                <label for="t_in">ساعت ورود</label>
                <input type="time" id="t_in" name="t_in" value="<?= e($values['t_in']) ?>" required>
            </div>
            <div>
                <label for="t_out">ساعت خروج <span class="muted small">(خالی = در جریان)</span></label>
                <input type="time" id="t_out" name="t_out" value="<?= e($values['t_out']) ?>">
            </div>
        </div>

        <label for="note">توضیح ورود</label>
        <input type="text" id="note" name="note" value="<?= e($values['note']) ?>" placeholder="مثلاً: کار روی پروژه، جلسه، مأموریت...">

        <label for="note_out">توضیح خروج</label>
        <input type="text" id="note_out" name="note_out" value="<?= e($values['note_out']) ?>">

        <div class="btn-row">
            <button class="btn" type="submit">ذخیره</button>
            <a class="btn ghost" href="records.php">بازگشت به گزارش</a>
        </div>
    </form>
    <p class="muted small tip">اگر ساعت خروج کمتر از ورود باشد، خروج در روز بعد (شیفت شب) در نظر گرفته می‌شود و کارکرد به روز شروع تعلق می‌گیرد.</p>
</div>

<?php page_footer();
