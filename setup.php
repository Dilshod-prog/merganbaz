<?php
/**
 * Merganbaz - Automatic Setup Script
 * Bu fayl database yaratadi va admin user qo'shadi
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // O'z parolingizni kiriting agar bor bo'lsa
define('DB_NAME', 'merganbaz');

echo "<!DOCTYPE html>
<html lang='uz'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Merganbaz Setup</title>
    <link rel='stylesheet' href='css/style.css'>
</head>
<body>
<div class='login-container'>
<div class='login-box' style='max-width: 600px;'>
<h2>🔧 Merganbaz Setup</h2>
<h3 style='text-align: center; margin-bottom: 20px;'>Database O'rnatish</h3>
<hr style='margin: 20px 0;'>";

$success = true;
$messages = [];

try {
    // Step 1: Connect without database
    echo "<div class='alert alert-info'>1️⃣ MySQL ga ulanish...</div>";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        throw new Exception("MySQL ga ulanib bo'lmadi: " . $conn->connect_error);
    }
    echo "<div class='alert alert-success'>✅ MySQL ga muvaffaqiyatli ulandi</div>";
    
    // Step 2: Create database
    echo "<div class='alert alert-info'>2️⃣ Database yaratish...</div>";
    $conn->query("DROP DATABASE IF EXISTS " . DB_NAME);
    if (!$conn->query("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        throw new Exception("Database yaratib bo'lmadi: " . $conn->error);
    }
    echo "<div class='alert alert-success'>✅ Database yaratildi: " . DB_NAME . "</div>";
    
    // Step 3: Select database
    $conn->select_db(DB_NAME);
    
    // Step 4: Create tables
    echo "<div class='alert alert-info'>3️⃣ Jadvallar yaratish...</div>";
    
    // Users table
    $conn->query("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Machines table
    $conn->query("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Vehicles table
    $conn->query("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Vehicle Machine Sequence table
    $conn->query("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Work logs table
    $conn->query("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Machine stops table
    $conn->query("
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
            duration_minutes INT AS (TIMESTAMPDIFF(MINUTE, stopped_at, resumed_at)) STORED,
            INDEX idx_machine (machine_id),
            INDEX idx_operator (operator_id),
            INDEX idx_stopped_at (stopped_at),
            FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
            FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Operator permissions table
    $conn->query("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "<div class='alert alert-success'>✅ Barcha jadvallar yaratildi (7 ta)</div>";
    
    // Step 5: Insert admin user
    echo "<div class='alert alert-info'>4️⃣ Admin user yaratish...</div>";
    
    // Generate password hash for admin123
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, phone, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $username = 'admin';
    $full_name = 'Super Admin';
    $phone = '+998900000000';
    $role = 'super_admin';
    $stmt->bind_param("sssss", $username, $password_hash, $full_name, $phone, $role);
    
    if (!$stmt->execute()) {
        throw new Exception("Admin user yaratib bo'lmadi: " . $stmt->error);
    }
    echo "<div class='alert alert-success'>✅ Admin user yaratildi</div>";
    
    // Step 6: Insert sample machines
    echo "<div class='alert alert-info'>5️⃣ Test stanoklar yaratish...</div>";
    $machines = [
        ['Stanok 1', 'STK001', 'Birinchi stanok'],
        ['Stanok 2', 'STK002', 'Ikkinchi stanok'],
        ['Stanok 3', 'STK003', 'Uchinchi stanok']
    ];
    
    $stmt = $conn->prepare("INSERT INTO machines (machine_name, machine_code, description) VALUES (?, ?, ?)");
    foreach ($machines as $machine) {
        $stmt->bind_param("sss", $machine[0], $machine[1], $machine[2]);
        $stmt->execute();
    }
    echo "<div class='alert alert-success'>✅ Test stanoklar yaratildi (3 ta)</div>";
    
    echo "<hr style='margin: 30px 0;'>";
    echo "<div class='alert alert-success' style='background: linear-gradient(135deg, #10b981, #059669); color: white;'>";
    echo "<h3 style='margin: 0 0 15px 0;'>🎉 O'rnatish Muvaffaqiyatli Tugallandi!</h3>";
    echo "<p style='margin: 5px 0;'><strong>Database:</strong> merganbaz ✅</p>";
    echo "<p style='margin: 5px 0;'><strong>Jadvallar:</strong> 7 ta ✅</p>";
    echo "<p style='margin: 5px 0;'><strong>Admin user:</strong> yaratildi ✅</p>";
    echo "<p style='margin: 5px 0;'><strong>Test stanoklar:</strong> 3 ta ✅</p>";
    echo "</div>";
    
    echo "<div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔐 Login Ma'lumotlari</h3>";
    echo "<p style='margin: 10px 0;'><strong>Login:</strong> <code style='background: white; padding: 5px 10px; border-radius: 4px; color: #2563eb;'>admin</code></p>";
    echo "<p style='margin: 10px 0;'><strong>Parol:</strong> <code style='background: white; padding: 5px 10px; border-radius: 4px; color: #2563eb;'>admin123</code></p>";
    echo "</div>";
    
    echo "<a href='login.php' class='btn btn-primary btn-block' style='text-decoration: none; display: block;'>🚀 Login Sahifasiga O'tish</a>";
    
    // Update config.php if needed
    if (DB_PASS !== '') {
        echo "<div class='alert alert-warning' style='margin-top: 20px;'>";
        echo "⚠️ <strong>Eslatma:</strong> Agar MySQL parolingiz bo'lsa, <code>includes/config.php</code> faylida parolni kiriting!";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h3>❌ Xatolik yuz berdi</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<h4>💡 Yechimlar:</h4>";
    echo "<ul>";
    echo "<li>XAMPP/WAMP da MySQL ni ishga tushiring</li>";
    echo "<li><code>includes/config.php</code> da parol to'g'riligini tekshiring</li>";
    echo "<li>MySQL ga to'g'ridan kirib ko'ring: <code>mysql -u root -p</code></li>";
    echo "</ul>";
    echo "<a href='setup.php' class='btn btn-warning' style='text-decoration: none; display: inline-block;'>🔄 Qayta Urinish</a>";
    echo "</div>";
    
    $success = false;
}

echo "</div></div></body></html>";

if (isset($conn)) {
    $conn->close();
}
?>
