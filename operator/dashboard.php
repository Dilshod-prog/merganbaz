<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireOperator();

$operator_id = $_SESSION['user_id'];
$machine_id = $_SESSION['machine_id'];

// Get operator permissions
$stmt = $conn->prepare("SELECT * FROM operator_permissions WHERE operator_id = ?");
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

// Get today's stats
$today_started = 0;
$today_completed = 0;
if ($machine_id) {
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN action = 'started' THEN 1 ELSE 0 END) as started,
            SUM(CASE WHEN action = 'completed' THEN 1 ELSE 0 END) as completed
        FROM work_logs 
        WHERE machine_id = ? AND operator_id = ? AND DATE(created_at) = CURDATE()
    ");
    $stmt->bind_param("ii", $machine_id, $operator_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $today_started = $result['started'] ?: 0;
    $today_completed = $result['completed'] ?: 0;
}

// Get vehicles for this machine
$vehicles = null;
if ($machine_id) {
    $vehicles = $conn->query("
        SELECT v.*, vms.status as sequence_status, vms.sequence_order,
               vms.id as sequence_id
        FROM vehicles v
        JOIN vehicle_machine_sequence vms ON v.id = vms.vehicle_id AND vms.machine_id = $machine_id
        WHERE v.status IN ('pending', 'in_progress')
        AND vms.status IN ('pending', 'in_progress')
        ORDER BY 
            CASE WHEN vms.status = 'in_progress' THEN 0 ELSE 1 END,
            v.created_at ASC
    ");
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
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator - Merganbaz</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background: #f1f5f9;">
    
    <?php if (!$machine): ?>
        <div class="operator-container">
            <div class="empty-state">
                <div class="empty-state-icon">⚠️</div>
                <h3>Sizga Stanok Tayinlanmagan</h3>
                <p>Iltimos, administratorga murojaat qiling</p>
                <a href="../logout.php" class="btn btn-secondary" style="margin-top: 20px;">Chiqish</a>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Operator Header -->
        <div class="operator-header">
            <h1>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
            <p>🔧 <?php echo htmlspecialchars($machine['machine_name']); ?> - <?php echo htmlspecialchars($machine['machine_code']); ?></p>
            <a href="../logout.php" class="btn btn-secondary btn-sm" style="margin-top: 12px;">Chiqish</a>
        </div>

        <div class="operator-container">
            
            <!-- Today's Stats -->
            <div class="operator-stats">
                <div class="operator-stat">
                    <div class="operator-stat-number"><?php echo $today_started; ?></div>
                    <div class="operator-stat-label">Boshlangan</div>
                </div>
                <div class="operator-stat">
                    <div class="operator-stat-number" style="color: var(--success-color);"><?php echo $today_completed; ?></div>
                    <div class="operator-stat-label">Tugallangan</div>
                </div>
                <div class="operator-stat">
                    <div class="operator-stat-number" style="color: var(--warning-color);"><?php echo $vehicles ? $vehicles->num_rows : 0; ?></div>
                    <div class="operator-stat-label">Navbatda</div>
                </div>
            </div>

            <!-- Active Stops -->
            <?php if ($active_stops && $active_stops->num_rows > 0): ?>
                <?php while ($stop = $active_stops->fetch_assoc()): ?>
                <div class="stop-alert">
                    <h3>⚠️ STANOK TO'XTATILGAN</h3>
                    <div class="stop-alert-info">
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
                    </div>
                    <button onclick="resumeMachine(<?php echo $stop['id']; ?>)" class="btn-operator btn-operator-start" style="width: 100%;">
                        <span class="btn-icon">▶️</span>
                        DAVOM ETTIRISH
                    </button>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <!-- Machine Stop Button -->
            <?php if ($permissions && $permissions['can_stop_machine']): ?>
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-body">
                    <button onclick="openStopMachineModal()" class="btn-operator btn-operator-stop" style="width: 100%;">
                        <span class="btn-icon">⏸️</span>
                        STANOKNI TO'XTATISH
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Vehicles List -->
            <h2 style="margin: 32px 0 20px 0; font-size: 1.8rem; color: var(--dark-color);">📋 Tachkalar Navbati</h2>
            
            <?php if ($vehicles && $vehicles->num_rows > 0): ?>
                <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                <div class="vehicle-card <?php echo $vehicle['sequence_status'] === 'in_progress' ? 'active' : 'pending'; ?>">
                    <div class="vehicle-number">
                        <span class="status-icon <?php echo $vehicle['sequence_status'] === 'in_progress' ? 'active' : 'pending'; ?>"></span>
                        🚗 <?php echo htmlspecialchars($vehicle['vehicle_number']); ?>
                    </div>
                    
                    <div class="vehicle-info">
                        <div class="vehicle-info-item">
                            <span>📍</span>
                            <span><strong>Qadam:</strong> #<?php echo $vehicle['sequence_order']; ?></span>
                        </div>
                        <div class="vehicle-info-item">
                            <span>⏱️</span>
                            <span><strong>Yaratilgan:</strong> <?php echo formatDate($vehicle['created_at']); ?></span>
                        </div>
                        <div class="vehicle-info-item">
                            <span class="badge <?php echo getStatusBadgeClass($vehicle['sequence_status']); ?>" style="font-size: 1rem; padding: 6px 14px;">
                                <?php echo getStatusText($vehicle['sequence_status']); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($vehicle['description']): ?>
                    <div style="background: var(--light-color); padding: 12px 16px; border-radius: var(--radius); margin-bottom: 16px;">
                        <p style="margin: 0; color: var(--secondary-color);"><strong>📝 Tavsif:</strong> <?php echo htmlspecialchars($vehicle['description']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="operator-actions">
                        <?php if ($vehicle['sequence_status'] === 'pending' && $permissions['can_start_work']): ?>
                            <button onclick="startWork(<?php echo $vehicle['id']; ?>)" class="btn-operator btn-operator-start">
                                <span class="btn-icon">▶️</span>
                                BOSHLASH
                            </button>
                        <?php elseif ($vehicle['sequence_status'] === 'in_progress'): ?>
                            <?php if ($permissions['can_complete_work']): ?>
                            <button onclick="completeWork(<?php echo $vehicle['id']; ?>)" class="btn-operator btn-operator-complete">
                                <span class="btn-icon">✅</span>
                                TUGATISH
                            </button>
                            <?php endif; ?>
                            <?php if ($permissions['can_stop_work']): ?>
                            <button onclick="pauseWork(<?php echo $vehicle['id']; ?>)" class="btn-operator btn-operator-pause">
                                <span class="btn-icon">⏸️</span>
                                PAUZA
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>Navbatda Tachkalar Yo'q</h3>
                    <p>Hozircha sizning stanokingizga tachkalar tayinlanmagan</p>
                </div>
            <?php endif; ?>

        </div>

    <?php endif; ?>

    <!-- Stop Machine Modal -->
    <div id="stopMachineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⏸️ Stanokni To'xtatish</h3>
                <span class="close" onclick="closeStopMachineModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="stopMachineForm" class="stop-form">
                    <div class="form-group">
                        <label for="stop_reason">To'xtatish Sababi <span class="required">*</span></label>
                        <select id="stop_reason" name="reason" required>
                            <option value="">Sababni tanlang</option>
                            <option value="obed">🍽️ Tushlik (Obed)</option>
                            <option value="no_gas">⛽ Gaz yo'q</option>
                            <option value="technical_issue">🔧 Texnik nosozlik</option>
                            <option value="other">📝 Boshqa</option>
                        </select>
                    </div>

                    <div class="form-group" id="reasonTextGroup" style="display: none;">
                        <label for="reason_text">Sabab Matni</label>
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
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);

            try {
                const response = await fetch('../ajax/stop_machine.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('❌ Xatolik yuz berdi: ' + error.message);
            }
        }

        async function resumeMachine(stopId) {
            if (!confirm('❓ Stanokni davom ettirishni xohlaysizmi?')) {
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
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('❌ Xatolik yuz berdi: ' + error.message);
            }
        }

        async function startWork(vehicleId) {
            if (!confirm('❓ Bu tachkada ishni boshlaysizmi?')) {
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
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('❌ Xatolik yuz berdi: ' + error.message);
            }
        }

        async function completeWork(vehicleId) {
            if (!confirm('❓ Bu tachkadagi ishni tugatasizmi?')) {
                return;
            }

            const notes = prompt('📝 Izoh (ixtiyoriy):');

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
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('❌ Xatolik yuz berdi: ' + error.message);
            }
        }

        async function pauseWork(vehicleId) {
            const reason = prompt('⏸️ To\'xtatish sababi:');
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
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('❌ Xatolik yuz berdi: ' + error.message);
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
