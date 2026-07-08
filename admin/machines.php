<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

// Get all machines with operator count
$machines = $conn->query("
    SELECT m.*, 
           COUNT(DISTINCT u.id) as operator_count,
           COUNT(DISTINCT v.id) as active_vehicles
    FROM machines m
    LEFT JOIN users u ON m.id = u.machine_id AND u.is_active = 1
    LEFT JOIN vehicles v ON m.id = v.current_machine_id AND v.status = 'in_progress'
    GROUP BY m.id
    ORDER BY m.machine_name
");
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stanoklar - Merganbaz</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>🔧 Merganbaz Admin Panel</h1>
                <div class="header-info">
                    <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-secondary btn-sm">Chiqish</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <nav class="nav">
            <ul class="nav-list">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="machines.php" class="active">Stanoklar</a></li>
                <li><a href="operators.php">Operatorlar</a></li>
                <li><a href="vehicles.php">Tachkalar</a></li>
                <li><a href="reports.php">Hisobotlar</a></li>
            </ul>
        </nav>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <span>Stanoklar Ro'yxati</span>
                <button onclick="openAddMachineModal()" class="btn btn-primary btn-sm">+ Stanok Qo'shish</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Stanok Nomi</th>
                                <th>Kod</th>
                                <th>Tavsif</th>
                                <th>Operatorlar</th>
                                <th>Aktiv Tachkalar</th>
                                <th>Status</th>
                                <th>Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($machines->num_rows > 0): ?>
                                <?php while ($machine = $machines->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $machine['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($machine['machine_name']); ?></strong></td>
                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($machine['machine_code']); ?></span></td>
                                    <td><?php echo htmlspecialchars($machine['description'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge badge-secondary"><?php echo $machine['operator_count']; ?> ta</span>
                                    </td>
                                    <td>
                                        <?php if ($machine['active_vehicles'] > 0): ?>
                                            <span class="badge badge-warning"><?php echo $machine['active_vehicles']; ?> ta</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($machine['is_active']): ?>
                                            <span class="badge badge-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Faol emas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick='editMachine(<?php echo json_encode($machine); ?>)' class="btn btn-primary btn-sm">Tahrirlash</button>
                                        <?php if ($machine['is_active']): ?>
                                            <button onclick="toggleMachineStatus(<?php echo $machine['id']; ?>, 0)" class="btn btn-danger btn-sm">O'chirish</button>
                                        <?php else: ?>
                                            <button onclick="toggleMachineStatus(<?php echo $machine['id']; ?>, 1)" class="btn btn-success btn-sm">Yoqish</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Hozircha stanoklar yo'q</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Machine Modal -->
    <div id="machineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Stanok Qo'shish</h3>
                <span class="close" onclick="closeMachineModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="machineForm">
                    <input type="hidden" id="machine_id" name="machine_id">
                    
                    <div class="form-group">
                        <label for="machine_name">Stanok Nomi <span class="required">*</span></label>
                        <input type="text" id="machine_name" name="machine_name" required placeholder="Masalan: Stanok 1">
                    </div>

                    <div class="form-group">
                        <label for="machine_code">Stanok Kodi <span class="required">*</span></label>
                        <input type="text" id="machine_code" name="machine_code" required placeholder="Masalan: STK001">
                    </div>

                    <div class="form-group">
                        <label for="description">Tavsif</label>
                        <textarea id="description" name="description" placeholder="Stanok haqida qo'shimcha ma'lumot..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="closeMachineModal()" class="btn btn-secondary">Bekor qilish</button>
                <button onclick="saveMachine()" class="btn btn-primary">Saqlash</button>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        function openAddMachineModal() {
            document.getElementById('modalTitle').textContent = 'Stanok Qo\'shish';
            document.getElementById('machineForm').reset();
            document.getElementById('machine_id').value = '';
            document.getElementById('machineModal').classList.add('show');
        }

        function editMachine(machine) {
            document.getElementById('modalTitle').textContent = 'Stanokni Tahrirlash';
            document.getElementById('machine_id').value = machine.id;
            document.getElementById('machine_name').value = machine.machine_name;
            document.getElementById('machine_code').value = machine.machine_code;
            document.getElementById('description').value = machine.description || '';
            document.getElementById('machineModal').classList.add('show');
        }

        function closeMachineModal() {
            document.getElementById('machineModal').classList.remove('show');
        }

        async function saveMachine() {
            const form = document.getElementById('machineForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('../ajax/save_machine.php', {
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

        async function toggleMachineStatus(machineId, status) {
            if (!confirm(status ? 'Stanokni yoqmoqchimisiz?' : 'Stanokni o\'chirmoqchimisiz?')) {
                return;
            }

            try {
                const response = await fetch('../ajax/toggle_machine_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `machine_id=${machineId}&status=${status}`
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('Xatolik: ' + result.message);
                }
            } catch (error) {
                alert('Xatolik yuz berdi: ' + error.message);
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('machineModal');
            if (event.target == modal) {
                closeMachineModal();
            }
        }
    </script>
</body>
</html>
