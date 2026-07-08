<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

header('Content-Type: application/json');

try {
    $machine_id = isset($_POST['machine_id']) && $_POST['machine_id'] !== '' ? intval($_POST['machine_id']) : null;
    $machine_name = sanitize($_POST['machine_name'] ?? '');
    $machine_code = sanitize($_POST['machine_code'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    // Validation
    if (empty($machine_name) || empty($machine_code)) {
        jsonResponse(false, 'Iltimos, barcha majburiy maydonlarni to\'ldiring');
    }

    if ($machine_id) {
        // Update existing machine
        
        // Check if code is taken by another machine
        $stmt = $conn->prepare("SELECT id FROM machines WHERE machine_code = ? AND id != ?");
        $stmt->bind_param("si", $machine_code, $machine_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            jsonResponse(false, 'Bu kod band qilingan');
        }

        $stmt = $conn->prepare("UPDATE machines SET machine_name = ?, machine_code = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sssi", $machine_name, $machine_code, $description, $machine_id);
        
        if ($stmt->execute()) {
            jsonResponse(true, 'Stanok muvaffaqiyatli yangilandi');
        } else {
            jsonResponse(false, 'Xatolik: ' . $stmt->error);
        }

    } else {
        // Create new machine
        
        // Check if code is taken
        $stmt = $conn->prepare("SELECT id FROM machines WHERE machine_code = ?");
        $stmt->bind_param("s", $machine_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            jsonResponse(false, 'Bu kod band qilingan');
        }

        $stmt = $conn->prepare("INSERT INTO machines (machine_name, machine_code, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $machine_name, $machine_code, $description);
        
        if ($stmt->execute()) {
            jsonResponse(true, 'Stanok muvaffaqiyatli qo\'shildi');
        } else {
            jsonResponse(false, 'Xatolik: ' . $stmt->error);
        }
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
