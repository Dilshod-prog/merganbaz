<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

header('Content-Type: application/json');

try {
    $vehicle_id = intval($_GET['vehicle_id'] ?? 0);

    if ($vehicle_id <= 0) {
        jsonResponse(false, 'Noto\'g\'ri tachka ID');
    }

    // Get vehicle
    $stmt = $conn->prepare("SELECT vehicle_number, status FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();

    if (!$vehicle) {
        jsonResponse(false, 'Tachka topilmadi');
    }

    // Get sequence
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

    // Get all machines for dropdown
    $all_machines = $conn->query("SELECT id, machine_name, machine_code FROM machines WHERE is_active = 1 ORDER BY machine_name");

    $html = '<div class="alert alert-info">';
    $html .= '<strong>Tachka:</strong> ' . htmlspecialchars($vehicle['vehicle_number']);
    $html .= '</div>';

    if ($vehicle['status'] !== 'pending') {
        $html .= '<div class="alert alert-warning">';
        $html .= '⚠️ Diqqat: Bu tachka allaqachon ishga tushgan. Ketma-ketlikni o\'zgartirish tavsiya etilmaydi.';
        $html .= '</div>';
    }

    $html .= '<form id="sequenceEditForm">';
    $html .= '<input type="hidden" name="vehicle_id" value="' . $vehicle_id . '">';
    $html .= '<div id="sequenceEditContainer">';

    if ($sequence->num_rows > 0) {
        $order = 1;
        while ($seq = $sequence->fetch_assoc()) {
            $html .= '<div class="form-group machine-sequence-item">';
            $html .= '<label>' . $order . '. Stanok ';
            if ($seq['status'] === 'pending') {
                $html .= '<button type="button" onclick="this.closest(\'.machine-sequence-item\').remove(); updateEditSequenceNumbers()" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">O\'chirish</button>';
            } else {
                $html .= '<span class="badge ' . getStatusBadgeClass($seq['status']) . '">' . getStatusText($seq['status']) . '</span>';
            }
            $html .= '</label>';
            $html .= '<select name="machines[]" class="machine-select" required ' . ($seq['status'] !== 'pending' ? 'disabled' : '') . '>';
            
            $all_machines->data_seek(0);
            while ($machine = $all_machines->fetch_assoc()) {
                $selected = $machine['id'] == $seq['machine_id'] ? 'selected' : '';
                $html .= '<option value="' . $machine['id'] . '" ' . $selected . '>';
                $html .= htmlspecialchars($machine['machine_name'] . ' (' . $machine['machine_code'] . ')');
                $html .= '</option>';
            }
            
            $html .= '</select>';
            $html .= '</div>';
            $order++;
        }
    } else {
        $html .= '<p class="text-center">Ketma-ketlik mavjud emas</p>';
    }

    $html .= '</div>';
    
    $html .= '<button type="button" onclick="addMachineToEditSequence()" class="btn btn-secondary btn-sm mt-20">+ Stanok Qo\'shish</button>';
    $html .= '</form>';

    $html .= '<div class="mt-20">';
    $html .= '<button onclick="saveEditedSequence()" class="btn btn-primary">Saqlash</button>';
    $html .= '</div>';

    $html .= '<script>';
    $html .= 'function addMachineToEditSequence() {';
    $html .= '  const container = document.getElementById("sequenceEditContainer");';
    $html .= '  const count = container.querySelectorAll(".machine-sequence-item").length + 1;';
    $html .= '  const div = document.createElement("div");';
    $html .= '  div.className = "form-group machine-sequence-item";';
    $html .= '  div.innerHTML = `<label>${count}. Stanok <button type="button" onclick="this.closest(\'.machine-sequence-item\').remove(); updateEditSequenceNumbers()" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">O\\\'chirish</button></label><select name="machines[]" class="machine-select" required>';
    
    $all_machines->data_seek(0);
    while ($machine = $all_machines->fetch_assoc()) {
        $html .= '<option value="' . $machine['id'] . '">' . htmlspecialchars($machine['machine_name'] . ' (' . $machine['machine_code'] . ')') . '</option>';
    }
    
    $html .= '</select>`;';
    $html .= '  container.appendChild(div);';
    $html .= '}';
    
    $html .= 'function updateEditSequenceNumbers() {';
    $html .= '  const items = document.querySelectorAll("#sequenceEditContainer .machine-sequence-item");';
    $html .= '  items.forEach((item, index) => {';
    $html .= '    const label = item.querySelector("label");';
    $html .= '    const buttons = label.querySelectorAll("button, .badge");';
    $html .= '    const suffix = buttons.length > 0 ? buttons[0].outerHTML : "";';
    $html .= '    label.innerHTML = `${index + 1}. Stanok ${suffix}`;';
    $html .= '  });';
    $html .= '}';
    
    $html .= 'async function saveEditedSequence() {';
    $html .= '  const form = document.getElementById("sequenceEditForm");';
    $html .= '  const formData = new FormData(form);';
    $html .= '  try {';
    $html .= '    const response = await fetch("../ajax/update_vehicle_sequence.php", {method: "POST", body: formData});';
    $html .= '    const result = await response.json();';
    $html .= '    if (result.success) { alert(result.message); location.reload(); }';
    $html .= '    else { alert("Xatolik: " + result.message); }';
    $html .= '  } catch (error) { alert("Xatolik yuz berdi: " + error.message); }';
    $html .= '}';
    $html .= '</script>';

    jsonResponse(true, '', ['html' => $html]);

} catch (Exception $e) {
    jsonResponse(false, 'Xatolik: ' . $e->getMessage());
}
?>
