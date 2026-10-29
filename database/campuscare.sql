
-- =====================================================
-- Project: CampusCare
-- Purpose: Student Complaint and Grievance Management
-- Database: MySQL
-- =====================================================



-- -----------------------------------------------------
-- 1. USERS TABLE
-- Stores students and administrators
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    phone VARCHAR(15) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 2. COMPLAINTS TABLE
-- Stores complaints submitted by students
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS complaints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_number VARCHAR(30) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    category ENUM(
        'Infrastructure',
        'Laboratory',
        'Internet',
        'Cleanliness',
        'Faculty',
        'Administration',
        'Other'
    ) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM(
        'Low',
        'Medium',
        'High',
        'Critical'
    ) NOT NULL DEFAULT 'Medium',
    status ENUM(
        'Submitted',
        'Under Review',
        'In Progress',
        'Resolved',
        'Rejected'
    ) NOT NULL DEFAULT 'Submitted',
    assigned_to INT UNSIGNED DEFAULT NULL,
    resolution_note TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_complaint_student
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_complaint_admin
        FOREIGN KEY (assigned_to)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_complaints_user (user_id),
    INDEX idx_complaints_status (status),
    INDEX idx_complaints_category (category),
    INDEX idx_complaints_created (created_at)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 3. COMPLAINT UPDATES TABLE
-- Maintains a history of complaint status changes
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS complaint_updates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED DEFAULT NULL,
    old_status VARCHAR(30) DEFAULT NULL,
    new_status VARCHAR(30) NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_update_complaint
        FOREIGN KEY (complaint_id)
        REFERENCES complaints(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_update_user
        FOREIGN KEY (updated_by)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_updates_complaint (complaint_id),
    INDEX idx_updates_created (created_at)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- END OF DATABASE SCHEMA
-- -----------------------------------------------------
