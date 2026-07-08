<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireOperator();

header('Content-Type: application/json');

try {
    $operator_id = $_SESSION['user_id'];
    $machine_id = $_SESSION['machine_id'];
    
    if (!$machine_id) {
        jsonResponse(false, 'Sizga stanok tayinlanmagan');
    }

    $reason = sanitize($_POST['reason'] ?? '');
    $reason_text = sanitize($_POST['reason_text'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($reason)) {
        jsonResponse(false, 'Sabab tanlanishi shart');
    }

    if ($reason === 'other' && empty($reason_text)) {
        jsonResponse(false, 'Iltimos, sabab matnini kiriting');
    }

    // Insert machine stop
    $stmt = $conn->prepare("INSERT INTO machine_stops (machine_id, operator_id, reason, reason_text, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $machine_id, $operator_id, $reason, $reason_text, $notes);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Stanok to\'xtatildi');
    } else {
        jsonResponse(false, 'Xatolik yuz berdi');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
