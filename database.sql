-- Merganbaz Machine Management System Database
-- Created: 2026-07-08

CREATE DATABASE IF NOT EXISTS merganbaz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE merganbaz;

-- Users table (Super Admin and Machine Operators)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('super_admin', 'operator') DEFAULT 'operator',
    machine_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_machine (machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Machines (Stanoks) table
CREATE TABLE IF NOT EXISTS machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_name VARCHAR(100) NOT NULL,
    machine_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_machine_code (machine_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicles (Tachkas) table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    current_machine_id INT NULL,
    current_sequence_step INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed', 'stopped') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_vehicle_number (vehicle_number),
    INDEX idx_status (status),
    INDEX idx_current_machine (current_machine_id),
    FOREIGN KEY (current_machine_id) REFERENCES machines(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicle Machine Sequence (defines which machines to use and in what order)
CREATE TABLE IF NOT EXISTS vehicle_machine_sequence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    machine_id INT NOT NULL,
    sequence_order INT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_machine (machine_id),
    INDEX idx_sequence (vehicle_id, sequence_order),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Work logs (tracks all operations on vehicles)
CREATE TABLE IF NOT EXISTS work_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    machine_id INT NOT NULL,
    operator_id INT NOT NULL,
    action ENUM('started', 'completed', 'stopped', 'resumed') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_machine (machine_id),
    INDEX idx_operator (operator_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Machine stops (tracks when and why machines are stopped)
CREATE TABLE IF NOT EXISTS machine_stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    operator_id INT NOT NULL,
    vehicle_id INT NULL,
    reason ENUM('obed', 'no_gas', 'technical_issue', 'other') NOT NULL,
    reason_text VARCHAR(100),
    notes TEXT,
    stopped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resumed_at TIMESTAMP NULL,
    duration_minutes INT GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, stopped_at, resumed_at)) STORED,
    INDEX idx_machine (machine_id),
    INDEX idx_operator (operator_id),
    INDEX idx_stopped_at (stopped_at),
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Operator permissions (defines what operators can do)
CREATE TABLE IF NOT EXISTS operator_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operator_id INT NOT NULL,
    can_start_work TINYINT(1) DEFAULT 1,
    can_stop_work TINYINT(1) DEFAULT 1,
    can_complete_work TINYINT(1) DEFAULT 1,
    can_stop_machine TINYINT(1) DEFAULT 1,
    can_view_reports TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_operator (operator_id),
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default super admin (password: admin123 - should be changed!)
INSERT INTO users (username, password, full_name, phone, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', '+998900000000', 'super_admin');

-- Sample data for testing
INSERT INTO machines (machine_name, machine_code, description) VALUES 
('Stanok 1', 'STK001', 'Birinchi stanok'),
('Stanok 2', 'STK002', 'Ikkinchi stanok'),
('Stanok 3', 'STK003', 'Uchinchi stanok');
