<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireOperator();

$operator_id = $_SESSION['user_id'];
$machine_id = $_SESSION['machine_id'];

// Get operator permissions
$stmt = $conn->prepare("
    SELECT * FROM operator_permissions WHERE operator_id = ?
");
$stmt->bind_param("i", $operator_id);
$stmt->execute();
$permissions = $stmt->get_result()->fetch_assoc();

// Get machine info
$machine = null;
if ($machine_id) {
    $stmt = $conn->prepare("SELECT * FROM machines WHERE id = ?");
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();
    $machine = $stmt->get_result()->fetch_assoc();
}

// Get vehicles for this machine based on status
if ($machine_id) {
    // Vehicles pending or in progress for this machine
    $vehicles = $conn->query("
        SELECT v.*, vms.status as sequence_status, vms.sequence_order
        FROM vehicles v
        JOIN vehicle_machine_sequence vms ON v.id = vms.vehicle_id AND vms.machine_id = $machine_id
        WHERE v.status IN ('pending', 'in_progress')
        AND vms.status IN ('pending', 'in_progress')
        ORDER BY v.created_at
    ");
} else {
    $vehicles = null;
}

// Get active machine stops
$active_stops = null;
if ($machine_id) {
    $stmt = $conn->prepare("
        SELECT ms.*, v.vehicle_number 
        FROM machine_stops ms
        LEFT JOIN vehicles v ON ms.vehicle_id = v.id
        WHERE ms.machine_id = ? AND ms.operator_id = ? AND ms.resumed_at IS NULL
        ORDER BY ms.stopped_at DESC
    ");
    $stmt->bind_param("ii", $machine_id, $operator_id);
    $stmt->execute();
    $active_stops = $stmt->get_result();
}

// Get today's completed work count
$today_completed = 0;
if ($machine_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM work_logs 
        WHERE machine_id = ? 
        AND operator_id = ? 
        AND action = 'completed' 
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->bind_param("ii", $machine_id, $operator_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $today_completed = $result['count'];
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Dashboard - Merganbaz</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>🔧 Merganbaz</h1>
                <div class="header-info">
                    <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-secondary btn-sm">Chiqish</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!$machine): ?>
            <div class="alert alert-warning">
                ⚠️ Sizga stanok tayinlanmagan. Iltimos, administratorga murojaat qiling.
            </div>
        <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    Mening Stanokím: <strong><?php echo htmlspecialchars($machine['machine_name']); ?></strong>
                    <span class="badge badge-primary"><?php echo htmlspecialchars($machine['machine_code']); ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col col-md-6">
                            <div class="stat-card success">
                                <div class="stat-label">Bugun Bajarilgan</div>
                                <div class="stat-number"><?php echo $today_completed; ?></div>
                            </div>
                        </div>
                        <div class="col col-md-6">
                            <div class="stat-card <?php echo ($active_stops && $active_stops->num_rows > 0) ? 'danger' : ''; ?>">
                                <div class="stat-label">To'xtatishlar</div>
                                <div class="stat-number"><?php echo $active_stops ? $active_stops->num_rows : 0; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($active_stops && $active_stops->num_rows > 0): ?>
            <div class="card">
                <div class="card-header" style="background: var(--danger-color); color: white;">
                    ⚠️ Aktiv To'xtatishlar
                </div>
                <div class="card-body">
                    <?php while ($stop = $active_stops->fetch_assoc()): ?>
                    <div class="alert alert-danger">
                        <p><strong>Sabab:</strong> <?php echo getStopReasonText($stop['reason']); ?>
                        <?php if ($stop['reason_text']): ?>
                            - <?php echo htmlspecialchars($stop['reason_text']); ?>
                        <?php endif; ?>
                        </p>
                        <?php if ($stop['vehicle_number']): ?>
                        <p><strong>Tachka:</strong> <?php echo htmlspecialchars($stop['vehicle_number']); ?></p>
                        <?php endif; ?>
                        <p><strong>To'xtatilgan:</strong> <?php echo formatDate($stop['stopped_at']); ?></p>
                        <?php if ($stop['notes']): ?>
                        <p><strong>Izoh:</strong> <?php echo htmlspecialchars($stop['notes']); ?></p>
                        <?php endif; ?>
                        <button onclick="resumeMachine(<?php echo $stop['id']; ?>)" class="btn btn-success btn-sm mt-10">
                            ▶️ Davom Ettirish
                        </button>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($permissions && $permissions['can_stop_machine']): ?>
            <div class="card">
                <div class="card-body">
                    <button onclick="openStopMachineModal()" class="btn btn-warning btn-block">
                        ⏸️ Stanokni To'xtatish
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    Tachkalar Ro'yxati
                </div>
                <div class="card-body">
                    <?php if ($vehicles && $vehicles->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tachka №</th>
                                    <th>Ketma-ketlik</th>
                                    <th>Status</th>
                                    <th>Amallar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></strong></td>
                                    <td>Qadam #<?php echo $vehicle['sequence_order']; ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($vehicle['sequence_status']); ?>">
                                            <?php echo getStatusText($vehicle['sequence_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($vehicle['sequence_status'] === 'pending' && $permissions['can_start_work']): ?>
                                            <button onclick="startWork(<?php echo $vehicle['id']; ?>)" class="btn btn-primary btn-sm">
                                                ▶️ Boshlash
                                            </button>
                                        <?php elseif ($vehicle['sequence_status'] === 'in_progress'): ?>
                                            <?php if ($permissions['can_complete_work']): ?>
                                            <button onclick="completeWork(<?php echo $vehicle['id']; ?>)" class="btn btn-success btn-sm">
                                                ✅ Tugatish
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($permissions['can_stop_work']): ?>
                                            <button onclick="stopWork(<?php echo $vehicle['id']; ?>)" class="btn btn-warning btn-sm">
                                                ⏸️ To'xtatish
                                            </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p class="text-center">Hozircha bu stanok uchun tachkalar yo'q</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <!-- Stop Machine Modal -->
    <div id="stopMachineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Stanokni To'xtatish</h3>
                <span class="close" onclick="closeStopMachineModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="stopMachineForm">
                    <div class="form-group">
                        <label for="stop_reason">To'xtatish Sababi <span class="required">*</span></label>
                        <select id="stop_reason" name="reason" required>
                            <option value="">Sababni tanlang</option>
                            <option value="obed">Tushlik (Obed)</option>
                            <option value="no_gas">Gaz yo'q</option>
                            <option value="technical_issue">Texnik nosozlik</option>
                            <option value="other">Boshqa</option>
                        </select>
                    </div>

                    <div class="form-group" id="reasonTextGroup" style="display: none;">
                        <label for="reason_text">Sabab Matnи</label>
                        <input type="text" id="reason_text" name="reason_text" placeholder="Qo'shimcha ma'lumot...">
                    </div>

                    <div class="form-group">
                        <label for="stop_notes">Izoh</label>
                        <textarea id="stop_notes" name="notes" placeholder="Qo'shimcha izohlar..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="closeStopMachineModal()" class="btn btn-secondary">Bekor qilish</button>
                <button onclick="submitStopMachine()" class="btn btn-warning">To'xtatish</button>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        // Show/hide reason text field
        document.addEventListener('DOMContentLoaded', function() {
            const reasonSelect = document.getElementById('stop_reason');
            if (reasonSelect) {
                reasonSelect.addEventListener('change', function() {
                    const reasonTextGroup = document.getElementById('reasonTextGroup');
                    if (this.value === 'other') {
                        reasonTextGroup.style.display = 'block';
                        document.getElementById('reason_text').required = true;
                    } else {
                        reasonTextGroup.style.display = 'none';
                        document.getElementById('reason_text').required = false;
                    }
                });
            }
        });

        function openStopMachineModal() {
            document.getElementById('stopMachineForm').reset();
            document.getElementById('reasonTextGroup').style.display = 'none';
            document.getElementById('stopMachineModal').classList.add('show');
        }

        function closeStopMachineModal() {
            document.getElementById('stopMachineModal').classList.remove('show');
        }

        async function submitStopMachine() {
            const form = document.getElementById('stopMachineForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('../ajax/stop_machine.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('Xatolik yuz berdi: ' + error.message);
            }
        }

        async function resumeMachine(stopId) {
            if (!confirm('Stanokni davom ettirishni xohlaysizmi?')) {
                return;
            }

            try {
                const response = await fetch('../ajax/resume_machine.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `stop_id=${stopId}`
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('Xatolik yuz berdi: ' + error.message);
            }
        }

        async function startWork(vehicleId) {
            if (!confirm('Bu tachkada ishni boshlaysizmi?')) {
                return;
            }

            try {
                const response = await fetch('../ajax/start_work.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `vehicle_id=${vehicleId}`
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('Xatolik yuz berdi: ' + error.message);
            }
        }

        async function completeWork(vehicleId) {
            if (!confirm('Bu tachkadagi ishni tugatasizmi?')) {
                return;
            }

            const notes = prompt('Izoh (ixtiyoriy):');

            try {
                const response = await fetch('../ajax/complete_work.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `vehicle_id=${vehicleId}&notes=${encodeURIComponent(notes || '')}`
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('Xatolik yuz berdi: ' + error.message);
            }
        }

        async function stopWork(vehicleId) {
            const reason = prompt('To\'xtatish sababi:');
            if (!reason) return;

            try {
                const response = await fetch('../ajax/stop_work.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `vehicle_id=${vehicleId}&notes=${encodeURIComponent(reason)}`
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('Xatolik yuz berdi: ' + error.message);
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        // Auto-refresh every 30 seconds
        // startAutoRefresh(30);
    </script>
</body>
</html>
