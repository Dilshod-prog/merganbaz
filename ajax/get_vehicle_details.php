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
    $html .= '<div class="card-header">📋 Asosiy Ma\'lumotlar</div>';
    $html .= '<div class="card-body">';
    $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">';
    
    $html .= '<div>';
    $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>Tachka Nomeri:</strong></p>';
    $html .= '<p style="font-size: 1.5rem; font-weight: 700; color: var(--dark-color); margin: 0;">' . htmlspecialchars($vehicle['vehicle_number']) . '</p>';
    $html .= '</div>';
    
    $html .= '<div>';
    $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>Status:</strong></p>';
    $html .= '<span class="badge ' . getStatusBadgeClass($vehicle['status']) . '" style="font-size: 1rem; padding: 8px 16px;">' . getStatusText($vehicle['status']) . '</span>';
    $html .= '</div>';
    
    if ($vehicle['current_machine_name']) {
        $html .= '<div>';
        $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>Hozirgi Stanok:</strong></p>';
        $html .= '<p style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color); margin: 0;">' . htmlspecialchars($vehicle['current_machine_name']) . '</p>';
        $html .= '</div>';
    }
    
    $html .= '<div>';
    $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>Yaratilgan:</strong></p>';
    $html .= '<p style="font-size: 1rem; margin: 0;">' . formatDateWithSeconds($vehicle['created_at']) . '</p>';
    $html .= '</div>';
    
    if ($vehicle['completed_at']) {
        $html .= '<div>';
        $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>Tugallangan:</strong></p>';
        $html .= '<p style="font-size: 1rem; color: var(--success-color); font-weight: 600; margin: 0;">' . formatDateWithSeconds($vehicle['completed_at']) . '</p>';
        $html .= '</div>';
        
        // Calculate total time
        $html .= '<div>';
        $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>Jami Vaqt:</strong></p>';
        $html .= '<p style="font-size: 1.3rem; font-weight: 700; color: var(--warning-color); margin: 0;">' . formatTimeDiff($vehicle['created_at'], $vehicle['completed_at']) . '</p>';
        $html .= '</div>';
    } else {
        // Calculate elapsed time
        $html .= '<div>';
        $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>O\'tgan Vaqt:</strong></p>';
        $html .= '<p style="font-size: 1.3rem; font-weight: 700; color: var(--info-color); margin: 0;">' . formatTimeDiff($vehicle['created_at'], null) . '</p>';
        $html .= '</div>';
    }
    
    if ($vehicle['description']) {
        $html .= '<div style="grid-column: 1 / -1;">';
        $html .= '<p style="margin: 8px 0; color: var(--secondary-color);"><strong>Tavsif:</strong></p>';
        $html .= '<p style="margin: 0; padding: 12px; background: var(--light-color); border-radius: 8px;">' . htmlspecialchars($vehicle['description']) . '</p>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div></div></div>';

    // Machine Sequence
    $html .= '<div class="col-full">';
    $html .= '<div class="card">';
    $html .= '<div class="card-header">🔧 Stanoklar Ketma-ketligi</div>';
    $html .= '<div class="card-body">';
    $html .= '<div class="table-responsive"><table>';
    $html .= '<thead><tr><th>Tartib</th><th>Stanok</th><th>Status</th><th>Boshlangan</th><th>Tugallangan</th><th>Vaqt</th></tr></thead>';
    $html .= '<tbody>';
    
    if ($sequence->num_rows > 0) {
        while ($seq = $sequence->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td style="font-size: 1.2rem; font-weight: 700;">' . $seq['sequence_order'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($seq['machine_name']) . '</strong><br><small style="color: var(--secondary-color);">' . htmlspecialchars($seq['machine_code']) . '</small></td>';
            $html .= '<td><span class="badge ' . getStatusBadgeClass($seq['status']) . '">' . getStatusText($seq['status']) . '</span></td>';
            $html .= '<td>' . ($seq['started_at'] ? formatDateWithSeconds($seq['started_at']) : '-') . '</td>';
            $html .= '<td>' . ($seq['completed_at'] ? formatDateWithSeconds($seq['completed_at']) : '-') . '</td>';
            
            // Calculate time for this step
            if ($seq['started_at'] && $seq['completed_at']) {
                $html .= '<td style="font-weight: 600; color: var(--success-color);">' . formatTimeDiff($seq['started_at'], $seq['completed_at']) . '</td>';
            } elseif ($seq['started_at']) {
                $html .= '<td style="font-weight: 600; color: var(--info-color);">' . formatTimeDiff($seq['started_at'], null) . '</td>';
            } else {
                $html .= '<td>-</td>';
            }
            
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" class="text-center">Ketma-ketlik mavjud emas</td></tr>';
    }
    
    $html .= '</tbody></table></div></div></div></div>';

    // Work Logs
    $html .= '<div class="col-full">';
    $html .= '<div class="card">';
    $html .= '<div class="card-header">📝 Ishlar Tarixi</div>';
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
            $html .= '<td style="white-space: nowrap;"><strong>' . formatDateWithSeconds($log['created_at']) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($log['machine_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($log['operator_name']) . '</td>';
            $html .= '<td><strong>' . ($actions[$log['action']] ?? $log['action']) . '</strong></td>';
            $html .= '<td>' . ($log['notes'] ? '<span style="background: var(--light-color); padding: 4px 8px; border-radius: 4px;">' . htmlspecialchars($log['notes']) . '</span>' : '-') . '</td>';
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
