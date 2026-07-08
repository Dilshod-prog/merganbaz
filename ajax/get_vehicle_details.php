<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

try {
    $vehicle_id = intval($_GET['vehicle_id'] ?? 0);

    if ($vehicle_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri tachka ID');
    }

    // Get vehicle info
    $stmt = $conn->prepare("
        SELECT v.*, m.machine_name as current_machine_name
        FROM vehicles v
        LEFT JOIN machines m ON v.current_machine_id = m.id
        WHERE v.id = ?
    ");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();

    if (!$vehicle) {
        jsonResponse(false, 'Tachka topilmadi');
    }

    // Get machine sequence
    $stmt = $conn->prepare("
        SELECT vms.*, m.machine_name, m.machine_code
        FROM vehicle_machine_sequence vms
        JOIN machines m ON vms.machine_id = m.id
        WHERE vms.vehicle_id = ?
        ORDER BY vms.sequence_order
    ");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $sequence = $stmt->get_result();

    // Get work logs
    $stmt = $conn->prepare("
        SELECT wl.*, m.machine_name, u.full_name as operator_name
        FROM work_logs wl
        JOIN machines m ON wl.machine_id = m.id
        JOIN users u ON wl.operator_id = u.id
        WHERE wl.vehicle_id = ?
        ORDER BY wl.created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $logs = $stmt->get_result();

    // Build HTML
    $html = '<div class="row">';
    
    // Vehicle Info
    $html .= '<div class="col-full">';
    $html .= '<div class="card">';
    $html .= '<div class="card-header">Asosiy Ma\'lumotlar</div>';
    $html .= '<div class="card-body">';
    $html .= '<p><strong>Tachka Nomeri:</strong> ' . htmlspecialchars($vehicle['vehicle_number']) . '</p>';
    $html .= '<p><strong>Status:</strong> <span class="badge ' . getStatusBadgeClass($vehicle['status']) . '">' . getStatusText($vehicle['status']) . '</span></p>';
    $html .= '<p><strong>Hozirgi Stanok:</strong> ' . htmlspecialchars($vehicle['current_machine_name'] ?? '-') . '</p>';
    $html .= '<p><strong>Tavsif:</strong> ' . htmlspecialchars($vehicle['description'] ?: '-') . '</p>';
    $html .= '<p><strong>Yaratilgan:</strong> ' . formatDate($vehicle['created_at']) . '</p>';
    if ($vehicle['completed_at']) {
        $html .= '<p><strong>Tugallangan:</strong> ' . formatDate($vehicle['completed_at']) . '</p>';
    }
    $html .= '</div></div></div>';

    // Machine Sequence
    $html .= '<div class="col-full">';
    $html .= '<div class="card">';
    $html .= '<div class="card-header">Stanoklar Ketma-ketligi</div>';
    $html .= '<div class="card-body">';
    $html .= '<div class="table-responsive"><table>';
    $html .= '<thead><tr><th>Tartib</th><th>Stanok</th><th>Status</th><th>Boshlangan</th><th>Tugallangan</th></tr></thead>';
    $html .= '<tbody>';
    
    if ($sequence->num_rows > 0) {
        while ($seq = $sequence->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $seq['sequence_order'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($seq['machine_name']) . '</strong><br><small>' . htmlspecialchars($seq['machine_code']) . '</small></td>';
            $html .= '<td><span class="badge ' . getStatusBadgeClass($seq['status']) . '">' . getStatusText($seq['status']) . '</span></td>';
            $html .= '<td>' . formatDate($seq['started_at']) . '</td>';
            $html .= '<td>' . formatDate($seq['completed_at']) . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" class="text-center">Ketma-ketlik mavjud emas</td></tr>';
    }
    
    $html .= '</tbody></table></div></div></div></div>';

    // Work Logs
    $html .= '<div class="col-full">';
    $html .= '<div class="card">';
    $html .= '<div class="card-header">Ishlar Tarixi</div>';
    $html .= '<div class="card-body">';
    $html .= '<div class="table-responsive"><table>';
    $html .= '<thead><tr><th>Vaqt</th><th>Stanok</th><th>Operator</th><th>Harakat</th><th>Izoh</th></tr></thead>';
    $html .= '<tbody>';
    
    if ($logs->num_rows > 0) {
        while ($log = $logs->fetch_assoc()) {
            $actions = [
                'started' => '▶️ Boshlandi',
                'completed' => '✅ Tugallandi',
                'stopped' => '⏸️ To\'xtatildi',
                'resumed' => '▶️ Davom ettirildi'
            ];
            
            $html .= '<tr>';
            $html .= '<td>' . formatDate($log['created_at']) . '</td>';
            $html .= '<td>' . htmlspecialchars($log['machine_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($log['operator_name']) . '</td>';
            $html .= '<td>' . ($actions[$log['action']] ?? $log['action']) . '</td>';
            $html .= '<td>' . htmlspecialchars($log['notes'] ?: '-') . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" class="text-center">Hali ishlar boshlanmagan</td></tr>';
    }
    
    $html .= '</tbody></table></div></div></div></div>';
    
    $html .= '</div>';

    jsonResponse(true, '', ['html' => $html]);

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
