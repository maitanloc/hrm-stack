/* Run this script against the active HRM database (for example: hrm_db or HRM_SYSTEM). */

/* ===================================================== */
/* Attendance Risk Flow - Phase scaffold tables          */
/* ===================================================== */

CREATE TABLE IF NOT EXISTS trusted_devices (
    trusted_device_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    device_id VARCHAR(128) NOT NULL,
    platform ENUM('ANDROID', 'IOS') NOT NULL,
    status ENUM('ACTIVE', 'REVOKED') NOT NULL DEFAULT 'ACTIVE',
    first_verified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trusted_devices_employee_device (employee_id, device_id),
    KEY idx_trusted_devices_employee_status (employee_id, status),
    CONSTRAINT fk_trusted_devices_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_prechecks (
    precheck_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    precheck_token VARCHAR(128) NOT NULL,
    employee_id INT NOT NULL,
    device_id VARCHAR(128) NOT NULL,
    attendance_type ENUM('CHECKIN', 'CHECKOUT') NOT NULL,
    risk_level ENUM('GREEN', 'YELLOW', 'RED') NOT NULL,
    action ENUM('ALLOW', 'ALLOW_FLAG', 'BLOCK') NOT NULL,
    reason_code VARCHAR(64) NOT NULL,
    next_action ENUM('NONE', 'RETRY', 'REVERIFY_DEVICE', 'REQUEST_EXCEPTION') NOT NULL,
    lat DECIMAL(10, 7) NOT NULL,
    lng DECIMAL(10, 7) NOT NULL,
    accuracy_m DECIMAL(8, 2) NULL,
    distance_m DECIMAL(10, 2) NULL,
    requires_manager_review TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance_prechecks_token (precheck_token),
    KEY idx_attendance_prechecks_employee_created (employee_id, created_at),
    KEY idx_attendance_prechecks_risk_level (risk_level),
    CONSTRAINT fk_attendance_prechecks_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS risk_alerts (
    risk_alert_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_id INT NULL,
    attendance_type ENUM('CHECKIN', 'CHECKOUT') NOT NULL,
    risk_level ENUM('GREEN', 'YELLOW', 'RED') NOT NULL,
    reason_code VARCHAR(64) NOT NULL,
    distance_m DECIMAL(10, 2) NULL,
    happened_at DATETIME NOT NULL,
    status ENUM('OPEN', 'RESOLVED') NOT NULL DEFAULT 'OPEN',
    details_json JSON NULL,
    resolved_by INT NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_risk_alerts_employee_happened (employee_id, happened_at),
    KEY idx_risk_alerts_level_status (risk_level, status),
    KEY idx_risk_alerts_happened_at (happened_at),
    CONSTRAINT fk_risk_alerts_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_risk_alerts_resolved_by
        FOREIGN KEY (resolved_by) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_exception_requests (
    exception_request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_id INT NULL,
    precheck_token VARCHAR(128) NULL,
    reason TEXT NOT NULL,
    lat DECIMAL(10, 7) NULL,
    lng DECIMAL(10, 7) NULL,
    status ENUM('PENDING', 'APPROVED_ONCE', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    requested_at DATETIME NOT NULL,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    valid_until DATETIME NULL,
    review_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_exception_requests_employee_requested (employee_id, requested_at),
    KEY idx_exception_requests_status (status),
    CONSTRAINT fk_exception_requests_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_exception_requests_approved_by
        FOREIGN KEY (approved_by) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
