<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

// Get all vehicles with their current machine and sequence info
$vehicles = $conn->query("
    SELECT v.*, 
           m.machine_name,
           COUNT(DISTINCT vms.id) as total_machines,
           SUM(CASE WHEN vms.status = 'completed' THEN 1 ELSE 0 END) as completed_machines,
           TIMESTAMPDIFF(SECOND, v.created_at, COALESCE(v.completed_at, NOW())) as elapsed_seconds
    FROM vehicles v
    LEFT JOIN machines m ON v.current_machine_id = m.id
    LEFT JOIN vehicle_machine_sequence vms ON v.id = vms.vehicle_id
    GROUP BY v.id
    ORDER BY v.created_at DESC
");

// Get all active machines for dropdown
$machines = $conn->query("SELECT id, machine_name, machine_code FROM machines WHERE is_active = 1 ORDER BY machine_name");
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tachkalar - Merganbaz</title>
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
                <li><a href="machines.php">Stanoklar</a></li>
                <li><a href="operators.php">Operatorlar</a></li>
                <li><a href="vehicles.php" class="active">Tachkalar</a></li>
                <li><a href="reports.php">Hisobotlar</a></li>
            </ul>
        </nav>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <span>Tachkalar Ro'yxati</span>
                <button onclick="openAddVehicleModal()" class="btn btn-primary btn-sm">+ Tachka Yaratish</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tachka №</th>
                                <th>Hozirgi Stanok</th>
                                <th>Jarayon</th>
                                <th>Status</th>
                                <th>Vaqt</th>
                                <th>Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($vehicles->num_rows > 0): ?>
                                <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $vehicle['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></strong></td>
                                    <td>
                                        <?php if ($vehicle['machine_name']): ?>
                                            <span class="badge badge-primary">
                                                <?php echo htmlspecialchars($vehicle['machine_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($vehicle['total_machines'] > 0): ?>
                                            <span class="badge badge-secondary">
                                                <?php echo $vehicle['completed_machines'] . '/' . $vehicle['total_machines']; ?> tugallandi
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Ketma-ketlik yo'q</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($vehicle['status']); ?>">
                                            <?php echo getStatusText($vehicle['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($vehicle['status'] === 'completed'): ?>
                                            <strong style="color: var(--success-color);">
                                                ✅ <?php echo formatDuration($vehicle['elapsed_seconds']); ?>
                                            </strong>
                                        <?php else: ?>
                                            <strong style="color: var(--info-color);">
                                                ⏳ <?php echo formatDuration($vehicle['elapsed_seconds']); ?>
                                            </strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="viewVehicleDetails(<?php echo $vehicle['id']; ?>)" class="btn btn-primary btn-sm">Ko'rish</button>
                                        <button onclick="editVehicleSequence(<?php echo $vehicle['id']; ?>)" class="btn btn-secondary btn-sm">Ketma-ketlik</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Hozircha tachkalar yo'q</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="vehicleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tachka Yaratish</h3>
                <span class="close" onclick="closeVehicleModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="vehicleForm">
                    <div class="form-group">
                        <label for="vehicle_number">Tachka Nomeri <span class="required">*</span></label>
                        <input type="text" id="vehicle_number" name="vehicle_number" required placeholder="Masalan: 01A234BC">
                    </div>

                    <div class="form-group">
                        <label for="vehicle_description">Tavsif</label>
                        <textarea id="vehicle_description" name="description" placeholder="Tachka haqida qo'shimcha ma'lumot..."></textarea>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            Stanoklar Ketma-ketligi
                            <small style="display: block; font-weight: normal; margin-top: 5px;">
                                Tachka qaysi stanoklardan o'tishi kerakligini tartibi bilan tanlang
                            </small>
                        </div>
                        <div class="card-body">
                            <div id="machineSequenceContainer">
                                <div class="form-group machine-sequence-item">
                                    <label>1. Stanok</label>
                                    <select name="machines[]" class="machine-select" required>
                                        <option value="">Stanok tanlang</option>
                                        <?php
                                        $machines->data_seek(0);
                                        while ($machine = $machines->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $machine['id']; ?>">
                                                <?php echo htmlspecialchars($machine['machine_name'] . ' (' . $machine['machine_code'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="button" onclick="addMachineToSequence()" class="btn btn-secondary btn-sm">+ Stanok Qo'shish</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="closeVehicleModal()" class="btn btn-secondary">Bekor qilish</button>
                <button onclick="saveVehicle()" class="btn btn-primary">Saqlash</button>
            </div>
        </div>
    </div>

    <!-- Vehicle Details Modal -->
    <div id="vehicleDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tachka Ma'lumotlari</h3>
                <span class="close" onclick="closeVehicleDetailsModal()">&times;</span>
            </div>
            <div class="modal-body" id="vehicleDetailsContent">
                <div class="spinner"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeVehicleDetailsModal()" class="btn btn-secondary">Yopish</button>
            </div>
        </div>
    </div>

    <!-- Edit Sequence Modal -->
    <div id="sequenceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ketma-ketlikni Tahrirlash</h3>
                <span class="close" onclick="closeSequenceModal()">&times;</span>
            </div>
            <div class="modal-body" id="sequenceContent">
                <div class="spinner"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeSequenceModal()" class="btn btn-secondary">Yopish</button>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        let machineOptions = `
            <option value="">Stanok tanlang</option>
            <?php
            $machines->data_seek(0);
            while ($machine = $machines->fetch_assoc()):
            ?>
                <option value="<?php echo $machine['id']; ?>">
                    <?php echo htmlspecialchars($machine['machine_name'] . ' (' . $machine['machine_code'] . ')'); ?>
                </option>
            <?php endwhile; ?>
        `;

        function openAddVehicleModal() {
            document.getElementById('vehicleForm').reset();
            document.getElementById('machineSequenceContainer').innerHTML = `
                <div class="form-group machine-sequence-item">
                    <label>1. Stanok</label>
                    <select name="machines[]" class="machine-select" required>
                        ${machineOptions}
                    </select>
                </div>
            `;
            document.getElementById('vehicleModal').classList.add('show');
        }

        function closeVehicleModal() {
            document.getElementById('vehicleModal').classList.remove('show');
        }

        function addMachineToSequence() {
            const container = document.getElementById('machineSequenceContainer');
            const count = container.querySelectorAll('.machine-sequence-item').length + 1;
            
            const div = document.createElement('div');
            div.className = 'form-group machine-sequence-item';
            div.innerHTML = `
                <label>${count}. Stanok <button type="button" onclick="this.closest('.machine-sequence-item').remove(); updateSequenceNumbers()" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">O'chirish</button></label>
                <select name="machines[]" class="machine-select" required>
                    ${machineOptions}
                </select>
            `;
            container.appendChild(div);
        }

        function updateSequenceNumbers() {
            const items = document.querySelectorAll('.machine-sequence-item');
            items.forEach((item, index) => {
                const label = item.querySelector('label');
                const text = label.textContent.split('.')[1] || label.textContent;
                label.innerHTML = `${index + 1}. ${text}`;
            });
        }

        async function saveVehicle() {
            const form = document.getElementById('vehicleForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('../ajax/save_vehicle.php', {
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

        async function viewVehicleDetails(vehicleId) {
            document.getElementById('vehicleDetailsModal').classList.add('show');
            document.getElementById('vehicleDetailsContent').innerHTML = '<div class="spinner"></div>';

            try {
                const response = await fetch(`../ajax/get_vehicle_details.php?vehicle_id=${vehicleId}`);
                const result = await response.json();

                if (result.success) {
                    document.getElementById('vehicleDetailsContent').innerHTML = result.html;
                } else {
                    document.getElementById('vehicleDetailsContent').innerHTML = 
                        `<div class="alert alert-danger">${result.message}</div>`;
                }
            } catch (error) {
                document.getElementById('vehicleDetailsContent').innerHTML = 
                    `<div class="alert alert-danger">Xatolik yuz berdi</div>`;
            }
        }

        function closeVehicleDetailsModal() {
            document.getElementById('vehicleDetailsModal').classList.remove('show');
        }

        async function editVehicleSequence(vehicleId) {
            document.getElementById('sequenceModal').classList.add('show');
            document.getElementById('sequenceContent').innerHTML = '<div class="spinner"></div>';

            try {
                const response = await fetch(`../ajax/get_vehicle_sequence.php?vehicle_id=${vehicleId}`);
                const result = await response.json();

                if (result.success) {
                    document.getElementById('sequenceContent').innerHTML = result.html;
                } else {
                    document.getElementById('sequenceContent').innerHTML = 
                        `<div class="alert alert-danger">${result.message}</div>`;
                }
            } catch (error) {
                document.getElementById('sequenceContent').innerHTML = 
                    `<div class="alert alert-danger">Xatolik yuz berdi</div>`;
            }
        }

        function closeSequenceModal() {
            document.getElementById('sequenceModal').classList.remove('show');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
