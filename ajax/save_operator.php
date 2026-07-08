<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

header('Content-Type: application/json');

try {
    $operator_id = isset($_POST['operator_id']) && $_POST['operator_id'] !== '' ? intval($_POST['operator_id']) : null;
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $machine_id = !empty($_POST['machine_id']) ? intval($_POST['machine_id']) : null;
    
    // Permissions
    $can_start_work = isset($_POST['can_start_work']) ? 1 : 0;
    $can_complete_work = isset($_POST['can_complete_work']) ? 1 : 0;
    $can_stop_work = isset($_POST['can_stop_work']) ? 1 : 0;
    $can_stop_machine = isset($_POST['can_stop_machine']) ? 1 : 0;
    $can_view_reports = isset($_POST['can_view_reports']) ? 1 : 0;

    // Validation
    if (empty($full_name) || empty($username)) {
        jsonResponse(false, 'Iltimos, barcha majburiy maydonlarni to\'ldiring');
    }

    if ($operator_id) {
        // Update existing operator
        
        // Check if username is taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $operator_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            jsonResponse(false, 'Bu login band qilingan');
        }

        // Update user
        if (!empty($password)) {
            $hashed_password = hashPassword($password);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, password = ?, phone = ?, machine_id = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $full_name, $username, $hashed_password, $phone, $machine_id, $operator_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, phone = ?, machine_id = ? WHERE id = ?");
            $stmt->bind_param("sssii", $full_name, $username, $phone, $machine_id, $operator_id);
        }
        
        if (!$stmt->execute()) {
            jsonResponse(false, 'Xatolik: ' . $stmt->error);
        }

        // Update permissions
        $stmt = $conn->prepare("
            INSERT INTO operator_permissions (operator_id, can_start_work, can_stop_work, can_complete_work, can_stop_machine, can_view_reports)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                can_start_work = VALUES(can_start_work),
                can_stop_work = VALUES(can_stop_work),
                can_complete_work = VALUES(can_complete_work),
                can_stop_machine = VALUES(can_stop_machine),
                can_view_reports = VALUES(can_view_reports)
        ");
        $stmt->bind_param("iiiiii", $operator_id, $can_start_work, $can_stop_work, $can_complete_work, $can_stop_machine, $can_view_reports);
        $stmt->execute();

        jsonResponse(true, 'Operator muvaffaqiyatli yangilandi');

    } else {
        // Create new operator
        
        if (empty($password)) {
            jsonResponse(false, 'Parol kiritilishi shart');
        }

        // Check if username is taken
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            jsonResponse(false, 'Bu login band qilingan');
        }

        // Insert user
        $hashed_password = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, phone, role, machine_id) VALUES (?, ?, ?, ?, 'operator', ?)");
        $stmt->bind_param("ssssi", $username, $hashed_password, $full_name, $phone, $machine_id);
        
        if (!$stmt->execute()) {
            jsonResponse(false, 'Xatolik: ' . $stmt->error);
        }

        $new_operator_id = $conn->insert_id;

        // Insert permissions
        $stmt = $conn->prepare("
            INSERT INTO operator_permissions (operator_id, can_start_work, can_stop_work, can_complete_work, can_stop_machine, can_view_reports)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiiii", $new_operator_id, $can_start_work, $can_stop_work, $can_complete_work, $can_stop_machine, $can_view_reports);
        $stmt->execute();

        jsonResponse(true, 'Operator muvaffaqiyatli qo\'shildi');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
