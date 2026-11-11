<?php
/**
 * اتصال به دیتابیس MySQL و ساخت خودکار جدول‌ها
 */

function db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
    } catch (PDOException $e) {
        /* شاید دیتابیس هنوز ساخته نشده باشد؛ تلاش برای ساخت خودکار آن */
        try {
            $root = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
            $safeName = str_replace('`', '', DB_NAME);
            $root->exec('CREATE DATABASE IF NOT EXISTS `' . $safeName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
        } catch (PDOException $e2) {
            throw new RuntimeException('اتصال به MySQL ناموفق بود: ' . $e2->getMessage());
        }
    }

    /* ساخت جدول در صورت عدم وجود */
    $pdo->exec("CREATE TABLE IF NOT EXISTS entries (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        g_date DATE NOT NULL COMMENT 'تاریخ میلادی شروع نوبت (برای محاسبات)',
        check_in DATETIME NOT NULL COMMENT 'زمان ورود',
        check_out DATETIME DEFAULT NULL COMMENT 'زمان خروج (NULL یعنی در جریان)',
        note VARCHAR(1000) DEFAULT NULL COMMENT 'توضیح ورود',
        note_out VARCHAR(1000) DEFAULT NULL COMMENT 'توضیح خروج',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_gdate (g_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    return $pdo;
}
