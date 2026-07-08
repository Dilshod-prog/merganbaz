<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireOperator();

header('Content-Type: application/json');

try {
    $operator_id = $_SESSION['user_id'];
    $machine_id = $_SESSION['machine_id'];
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if (!$machine_id) {
        jsonResponse(false, 'Sizga stanok tayinlanmagan');
    }

    if ($vehicle_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri tachka ID');
    }

    // Log the action
    $log_notes = $notes ?: 'Ish to\'xtatildi';
    $stmt = $conn->prepare("INSERT INTO work_logs (vehicle_id, machine_id, operator_id, action, notes) VALUES (?, ?, ?, 'stopped', ?)");
    $stmt->bind_param("iiis", $vehicle_id, $machine_id, $operator_id, $log_notes);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Ish to\'xtatildi');
    } else {
        jsonResponse(false, 'Xatolik yuz berdi');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
