<?php

/**
 * Миграция 003: поля user (email_verified, confirm_*, password_reset_*)
 * MySQL не поддерживает ADD COLUMN IF NOT EXISTS, поэтому — PHP.
 */

declare(strict_types=1);

/** @var PDO $pdo */
$cols = [];
foreach ($pdo->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $cols[$c['Field']] = true;
}

$add = [
    'email_verified' => "ALTER TABLE user ADD COLUMN email_verified tinyint(1) NOT NULL DEFAULT 1",
    'confirm_token' => "ALTER TABLE user ADD COLUMN confirm_token varchar(64) NULL",
    'confirm_expires' => "ALTER TABLE user ADD COLUMN confirm_expires datetime NULL",
    'password_reset_token' => "ALTER TABLE user ADD COLUMN password_reset_token varchar(64) NULL",
    'password_reset_expires' => "ALTER TABLE user ADD COLUMN password_reset_expires datetime NULL",
];

foreach ($add as $col => $sql) {
    if (empty($cols[$col])) {
        $pdo->exec($sql);
    }
}

$pdo->exec("UPDATE user SET email_verified = 1 WHERE email_verified = 0");
