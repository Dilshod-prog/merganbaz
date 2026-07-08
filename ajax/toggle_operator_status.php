<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

header('Content-Type: application/json');

try {
    $operator_id = intval($_POST['operator_id'] ?? 0);
    $status = intval($_POST['status'] ?? 0);

    if ($operator_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri operator ID');
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'operator'");
    $stmt->bind_param("ii", $status, $operator_id);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Status muvaffaqiyatli o\'zgartirildi');
    } else {
        jsonResponse(false, 'Xatolik yuz berdi');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
