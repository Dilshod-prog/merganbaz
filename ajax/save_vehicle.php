<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

header('Content-Type: application/json');

try {
    $vehicle_number = sanitize($_POST['vehicle_number'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $machines = $_POST['machines'] ?? [];

    // Validation
    if (empty($vehicle_number)) {
        jsonResponse(false, 'Tachka nomeri kiritilishi shart');
    }

    if (empty($machines) || !is_array($machines)) {
        jsonResponse(false, 'Kamida bitta stanok tanlang');
    }

    // Check if vehicle number already exists
    $stmt = $conn->prepare("SELECT id FROM vehicles WHERE vehicle_number = ?");
    $stmt->bind_param("s", $vehicle_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse(false, 'Bu tachka nomeri allaqachon mavjud');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert vehicle
        $stmt = $conn->prepare("INSERT INTO vehicles (vehicle_number, description, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ss", $vehicle_number, $description);
        
        if (!$stmt->execute()) {
            throw new Exception('Tachka qo\'shishda xatolik: ' . $stmt->error);
        }

        $vehicle_id = $conn->insert_id;

        // Insert machine sequence
        $stmt = $conn->prepare("INSERT INTO vehicle_machine_sequence (vehicle_id, machine_id, sequence_order, status) VALUES (?, ?, ?, 'pending')");
        
        foreach ($machines as $index => $machine_id) {
            if (!empty($machine_id)) {
                $sequence_order = $index + 1;
                $stmt->bind_param("iii", $vehicle_id, $machine_id, $sequence_order);
                
                if (!$stmt->execute()) {
                    throw new Exception('Ketma-ketlik qo\'shishda xatolik: ' . $stmt->error);
                }
            }
        }

        // Commit transaction
        $conn->commit();
        
        jsonResponse(true, 'Tachka muvaffaqiyatli yaratildi');

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
