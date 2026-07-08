<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireOperator();

header('Content-Type: application/json');

try {
    $operator_id = $_SESSION['user_id'];
    $machine_id = $_SESSION['machine_id'];
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);

    if (!$machine_id) {
        jsonResponse(false, 'Sizga stanok tayinlanmagan');
    }

    if ($vehicle_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri tachka ID');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get the pending sequence for this vehicle and machine
        $stmt = $conn->prepare("
            SELECT id FROM vehicle_machine_sequence 
            WHERE vehicle_id = ? AND machine_id = ? AND status = 'pending'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $vehicle_id, $machine_id);
        $stmt->execute();
        $sequence = $stmt->get_result()->fetch_assoc();

        if (!$sequence) {
            throw new Exception('Bu tachka uchun vazifa topilmadi');
        }

        // Update sequence status
        $stmt = $conn->prepare("UPDATE vehicle_machine_sequence SET status = 'in_progress', started_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $sequence['id']);
        $stmt->execute();

        // Update vehicle status and current machine
        $stmt = $conn->prepare("UPDATE vehicles SET status = 'in_progress', current_machine_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $machine_id, $vehicle_id);
        $stmt->execute();

        // Log the action
        $stmt = $conn->prepare("INSERT INTO work_logs (vehicle_id, machine_id, operator_id, action, notes) VALUES (?, ?, ?, 'started', 'Ish boshlandi')");
        $stmt->bind_param("iii", $vehicle_id, $machine_id, $operator_id);
        $stmt->execute();

        $conn->commit();
        jsonResponse(true, 'Ish muvaffaqiyatli boshlandi');

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
