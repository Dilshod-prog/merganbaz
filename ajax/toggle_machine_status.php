<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

header('Content-Type: application/json');

try {
    $machine_id = intval($_POST['machine_id'] ?? 0);
    $status = intval($_POST['status'] ?? 0);

    if ($machine_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri stanok ID');
    }

    $stmt = $conn->prepare("UPDATE machines SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $machine_id);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Status muvaffaqiyatli o\'zgartirildi');
    } else {
        jsonResponse(false, 'Xatolik yuz berdi');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
