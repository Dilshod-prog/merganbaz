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

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get the in-progress sequence for this vehicle and machine
        $stmt = $conn->prepare("
            SELECT id, sequence_order FROM vehicle_machine_sequence 
            WHERE vehicle_id = ? AND machine_id = ? AND status = 'in_progress'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $vehicle_id, $machine_id);
        $stmt->execute();
        $sequence = $stmt->get_result()->fetch_assoc();

        if (!$sequence) {
            throw new Exception('Bu tachka uchun aktiv vazifa topilmadi');
        }

        // Update sequence status
        $stmt = $conn->prepare("UPDATE vehicle_machine_sequence SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $sequence['id']);
        $stmt->execute();

        // Check if there are more machines in the sequence
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM vehicle_machine_sequence 
            WHERE vehicle_id = ? AND status = 'pending'
        ");
        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();
        $remaining = $stmt->get_result()->fetch_assoc();

        if ($remaining['count'] > 0) {
            // There are more machines, set vehicle status to pending
            $stmt = $conn->prepare("UPDATE vehicles SET status = 'pending', current_machine_id = NULL WHERE id = ?");
            $stmt->bind_param("i", $vehicle_id);
            $stmt->execute();
        } else {
            // All machines completed, mark vehicle as completed
            $stmt = $conn->prepare("UPDATE vehicles SET status = 'completed', current_machine_id = NULL, completed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $vehicle_id);
            $stmt->execute();
        }

        // Log the action
        $log_notes = $notes ?: 'Ish tugallandi';
        $stmt = $conn->prepare("INSERT INTO work_logs (vehicle_id, machine_id, operator_id, action, notes) VALUES (?, ?, ?, 'completed', ?)");
        $stmt->bind_param("iiis", $vehicle_id, $machine_id, $operator_id, $log_notes);
        $stmt->execute();

        $conn->commit();
        
        if ($remaining['count'] > 0) {
            jsonResponse(true, 'Ish tugallandi. Tachka keyingi stanokka o\'tdi.');
        } else {
            jsonResponse(true, 'Ish tugallandi. Tachka barcha stanokalardan o\'tdi!');
        }

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
