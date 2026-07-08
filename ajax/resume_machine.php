<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireOperator();

header('Content-Type: application/json');

try {
    $operator_id = $_SESSION['user_id'];
    $stop_id = intval($_POST['stop_id'] ?? 0);

    if ($stop_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri stop ID');
    }

    // Verify this stop belongs to the operator
    $stmt = $conn->prepare("SELECT id FROM machine_stops WHERE id = ? AND operator_id = ? AND resumed_at IS NULL");
    $stmt->bind_param("ii", $stop_id, $operator_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        jsonResponse(false, 'To\'xtatish topilmadi');
    }

    // Update the stop record
    $stmt = $conn->prepare("UPDATE machine_stops SET resumed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $stop_id);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Stanok davom ettirildi');
    } else {
        jsonResponse(false, 'Xatolik yuz berdi');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
