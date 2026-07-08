<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

header('Content-Type: application/json');

try {
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $machines = $_POST['machines'] ?? [];

    if ($vehicle_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri tachka ID');
    }

    if (empty($machines) || !is_array($machines)) {
        jsonResponse(false, 'Kamida bitta stanok tanlang');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete only pending sequences
        $stmt = $conn->prepare("DELETE FROM vehicle_machine_sequence WHERE vehicle_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();

        // Insert new sequences
        $stmt = $conn->prepare("INSERT INTO vehicle_machine_sequence (vehicle_id, machine_id, sequence_order, status) VALUES (?, ?, ?, 'pending')");
        
        foreach ($machines as $index => $machine_id) {
            if (!empty($machine_id)) {
                $sequence_order = $index + 1;
                $stmt->bind_param("iii", $vehicle_id, $machine_id, $sequence_order);
                $stmt->execute();
            }
        }

        $conn->commit();
        jsonResponse(true, 'Ketma-ketlik muvaffaqiyatli yangilandi');

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
