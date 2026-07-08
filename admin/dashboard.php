<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

// Get statistics
$stats = [];

// Total machines
$result = $conn->query("SELECT COUNT(*) as count FROM machines WHERE is_active = 1");
$stats['machines'] = $result->fetch_assoc()['count'];

// Total operators
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'operator' AND is_active = 1");
$stats['operators'] = $result->fetch_assoc()['count'];

// Total vehicles
$result = $conn->query("SELECT COUNT(*) as count FROM vehicles");
$stats['vehicles'] = $result->fetch_assoc()['count'];

// Active vehicles (in progress)
$result = $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'in_progress'");
$stats['active_vehicles'] = $result->fetch_assoc()['count'];

// Stopped machines
$result = $conn->query("SELECT COUNT(*) as count FROM machine_stops WHERE resumed_at IS NULL");
$stats['stopped_machines'] = $result->fetch_assoc()['count'];

// Completed today
$result = $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'completed' AND DATE(completed_at) = CURDATE()");
$stats['completed_today'] = $result->fetch_assoc()['count'];

// In progress count
$result = $conn->query("SELECT COUNT(DISTINCT v.id) as count FROM vehicles v JOIN vehicle_machine_sequence vms ON v.id = vms.vehicle_id WHERE vms.status = 'in_progress'");
$stats['in_progress'] = $result->fetch_assoc()['count'];

// Recent vehicles
$recent_vehicles = $conn->query("
    SELECT v.*, m.machine_name 
    FROM vehicles v
    LEFT JOIN machines m ON v.current_machine_id = m.id
    ORDER BY v.updated_at DESC
    LIMIT 10
");

// Recent work logs
$recent_logs = $conn->query("
    SELECT wl.*, v.vehicle_number, m.machine_name, u.full_name as operator_name
    FROM work_logs wl
    JOIN vehicles v ON wl.vehicle_id = v.id
    JOIN machines m ON wl.machine_id = m.id
    JOIN users u ON wl.operator_id = u.id
    ORDER BY wl.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Merganbaz</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="machines.php">Stanoklar</a></li>
                <li><a href="operators.php">Operatorlar</a></li>
                <li><a href="vehicles.php">Tachkalar</a></li>
                <li><a href="reports.php">Hisobotlar</a></li>
            </ul>
        </nav>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-number"><?php echo $stats['machines']; ?></div>
                    <div class="stat-label">Stanoklar</div>
                </div>
            </div>
            <div class="col col-md-3">
                <div class="stat-card success">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $stats['operators']; ?></div>
                    <div class="stat-label">Operatorlar</div>
                </div>
            </div>
            <div class="col col-md-3">
                <div class="stat-card warning">
                    <div class="stat-icon">🚗</div>
                    <div class="stat-number"><?php echo $stats['vehicles']; ?></div>
                    <div class="stat-label">Jami Tachkalar</div>
                </div>
            </div>
            <div class="col col-md-3">
                <div class="stat-card info">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-number"><?php echo $stats['active_vehicles']; ?></div>
                    <div class="stat-label">Aktiv Tachkalar</div>
                </div>
            </div>
        </div>

        <?php if ($stats['stopped_machines'] > 0): ?>
        <div class="alert alert-warning" style="display: flex; align-items: center; gap: 12px; font-size: 1.1rem;">
            <span style="font-size: 2rem;">⚠️</span>
            <span><strong>Diqqat:</strong> <?php echo $stats['stopped_machines']; ?> ta stanok to'xtatilgan holatda!</span>
        </div>
        <?php endif; ?>

        <!-- Additional Stats Row -->
        <div class="row">
            <div class="col col-md-4">
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $stats['completed_today']; ?></div>
                    <div class="stat-label">Bugun Tugallandi</div>
                </div>
            </div>
            <div class="col col-md-4">
                <div class="stat-card info">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">Jarayonda</div>
                </div>
            </div>
            <div class="col col-md-4">
                <div class="stat-card <?php echo $stats['stopped_machines'] > 0 ? 'danger' : ''; ?>">
                    <div class="stat-icon">⏸️</div>
                    <div class="stat-number"><?php echo $stats['stopped_machines']; ?></div>
                    <div class="stat-label">To'xtatilgan</div>
                </div>
            </div>
        </div>

        <?php if ($stats['stopped_machines'] > 0): ?>
        <div class="alert alert-warning">
            ⚠️ Diqqat: <?php echo $stats['stopped_machines']; ?> ta stanok to'xtatilgan holatda!
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Recent Vehicles -->
            <div class="col col-md-6 col-full">
                <div class="card">
                    <div class="card-header">
                        So'nggi Tachkalar
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tachka №</th>
                                        <th>Stanok</th>
                                        <th>Status</th>
                                        <th>Yangilangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_vehicles->num_rows > 0): ?>
                                        <?php while ($vehicle = $recent_vehicles->fetch_assoc()): ?>
                                        <tr style="cursor: pointer;" onclick="viewVehicleDetails(<?php echo $vehicle['id']; ?>)" title="Batafsil ma'lumot">
                                            <td><strong><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($vehicle['machine_name'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($vehicle['status']); ?>">
                                                    <?php echo getStatusText($vehicle['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDateWithSeconds($vehicle['updated_at']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Ma'lumot yo'q</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-20">
                            <a href="vehicles.php" class="btn btn-primary btn-block">Barcha Tachkalarni Ko'rish</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Work Logs -->
            <div class="col col-md-6 col-full">
                <div class="card">
                    <div class="card-header">
                        So'nggi Ishlar
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tachka №</th>
                                        <th>Operator</th>
                                        <th>Harakat</th>
                                        <th>Vaqt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_logs->num_rows > 0): ?>
                                        <?php while ($log = $recent_logs->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($log['vehicle_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($log['operator_name']); ?></td>
                                            <td>
                                                <?php
                                                $actions = [
                                                    'started' => '▶️ Boshlandi',
                                                    'completed' => '✅ Tugallandi',
                                                    'stopped' => '⏸️ To\'xtatildi',
                                                    'resumed' => '▶️ Davom ettirildi'
                                                ];
                                                echo $actions[$log['action']] ?? $log['action'];
                                                ?>
                                            </td>
                                            <td><?php echo formatDateWithSeconds($log['created_at']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Ma'lumot yo'q</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Details Modal -->
    <div id="vehicleModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3>🚗 Tachka Batafsil Ma'lumotlari</h3>
                <span class="close" onclick="closeVehicleModal()">&times;</span>
            </div>
            <div class="modal-body" id="vehicleModalContent">
                <div class="spinner"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeVehicleModal()" class="btn btn-secondary">Yopish</button>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        function viewVehicleDetails(vehicleId) {
            document.getElementById('vehicleModal').classList.add('show');
            document.getElementById('vehicleModalContent').innerHTML = '<div class="spinner"></div>';
            
            fetch('../ajax/get_vehicle_details.php?vehicle_id=' + vehicleId)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        document.getElementById('vehicleModalContent').innerHTML = result.html;
                    } else {
                        document.getElementById('vehicleModalContent').innerHTML = 
                            '<div class="alert alert-danger">' + result.message + '</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('vehicleModalContent').innerHTML = 
                        '<div class="alert alert-danger">Xatolik yuz berdi</div>';
                });
        }
        
        function closeVehicleModal() {
            document.getElementById('vehicleModal').classList.remove('show');
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
