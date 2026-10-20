-- Shared account database for reports, library, inventory, and sysadmin.
-- Run this file in phpMyAdmin/MySQL. Sample accounts are for local setup only;
-- change their passwords before exposing the apps publicly.

CREATE DATABASE IF NOT EXISTS arthurnerritech_arthurnerri
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE arthurnerritech_arthurnerri;

CREATE TABLE IF NOT EXISTS arthurnerri_users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('residents', 'brgyOfficer', 'mdrrmOfficer', 'systemAdmin') NOT NULL DEFAULT 'residents',
    barangay VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_username (username),
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expand the enum first so existing rows can be renamed without being coerced.
ALTER TABLE arthurnerri_users
    MODIFY role ENUM('user', 'admin', 'mainAdmin', 'residents', 'brgyOfficer', 'mdrrmOfficer', 'systemAdmin') NOT NULL DEFAULT 'residents';
UPDATE arthurnerri_users SET role = 'residents' WHERE role = 'user';
UPDATE arthurnerri_users SET role = 'brgyOfficer' WHERE role = 'admin';
UPDATE arthurnerri_users SET role = 'mdrrmOfficer' WHERE role = 'mainAdmin';
ALTER TABLE arthurnerri_users
    MODIFY role ENUM('residents', 'brgyOfficer', 'mdrrmOfficer', 'systemAdmin') NOT NULL DEFAULT 'residents';

-- Existing installations may have been created by an older app schema. Keep
-- columns used by registration and sysadmin statistics available there too.
DELIMITER $$
DROP PROCEDURE IF EXISTS ensure_account_user_columns$$
CREATE PROCEDURE ensure_account_user_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'arthurnerri_users' AND COLUMN_NAME = 'created_at'
    ) THEN
        ALTER TABLE arthurnerri_users ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'arthurnerri_users' AND COLUMN_NAME = 'barangay'
    ) THEN
        ALTER TABLE arthurnerri_users ADD COLUMN barangay VARCHAR(100) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'arthurnerri_users' AND COLUMN_NAME = 'updated_at'
    ) THEN
        ALTER TABLE arthurnerri_users ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
    END IF;
END$$
CALL ensure_account_user_columns()$$
DROP PROCEDURE ensure_account_user_columns$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_audit_username (username),
    INDEX idx_audit_role (role),
    INDEX idx_audit_action (action),
    INDEX idx_audit_timestamp (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bring forward databases created with the earlier login.sql audit table
-- (which had created_at instead of timestamp and no details column).
DELIMITER $$
DROP PROCEDURE IF EXISTS ensure_account_audit_columns$$
CREATE PROCEDURE ensure_account_audit_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'timestamp'
    ) THEN
        ALTER TABLE audit_logs ADD COLUMN `timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'created_at'
        ) THEN
            UPDATE audit_logs SET `timestamp` = created_at WHERE created_at IS NOT NULL;
        END IF;
        ALTER TABLE audit_logs MODIFY COLUMN `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'details'
    ) THEN
        ALTER TABLE audit_logs ADD COLUMN details TEXT DEFAULT NULL AFTER action;
    END IF;
END$$
CALL ensure_account_audit_columns()$$
DROP PROCEDURE ensure_account_audit_columns$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_resets_token (token),
    INDEX idx_password_resets_user (user_id),
    INDEX idx_password_resets_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Local setup accounts. These INSERT IGNORE statements preserve any account
-- already created with one of these usernames/emails.
INSERT IGNORE INTO arthurnerri_users (username, email, password, role) VALUES
('systemadmin', 'systemadmin@example.com', '$2y$10$WoEt7c4iSNHeOfTGkHdlkeCvHWrExD.Ng4RgnmeyccEjdQVZM7yDO', 'systemAdmin'),
('mainadmin', 'admin@example.com', '$2y$10$fslfjuAMa.4WnOyuSng72.74QS4uYMzWa3I6ZL1DLxjo17tO1jrjC', 'mdrrmOfficer'),
('brgyOfficer', 'barangay@example.com', '$2y$10$fslfjuAMa.4WnOyuSng72.74QS4uYMzWa3I6ZL1DLxjo17tO1jrjC', 'brgyOfficer'),
('communityuser', 'user@example.com', '$2y$10$fslfjuAMa.4WnOyuSng72.74QS4uYMzWa3I6ZL1DLxjo17tO1jrjC', 'residents');

-- Sample password for systemadmin: SystemAdmin@12345
-- Sample password for mainadmin, brgyOfficer, communityuser: Admin@12345
INSERT INTO audit_logs (username, role, action)
SELECT 'SYSTEM', 'systemAdmin', 'Database initialized'
WHERE NOT EXISTS (
    SELECT 1 FROM audit_logs WHERE username = 'SYSTEM' AND action = 'Database initialized'
);

SHOW TABLES;
DESCRIBE arthurnerri_users;
DESCRIBE audit_logs;
DESCRIBE password_resets;
SELECT id, username, email, role, created_at, updated_at
FROM arthurnerri_users ORDER BY id ASC;
SELECT id, username, role, action, details, `timestamp`
FROM audit_logs ORDER BY id DESC;
