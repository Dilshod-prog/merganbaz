<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

// Date range filter
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Completed vehicles count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM vehicles 
    WHERE status = 'completed' 
    AND DATE(completed_at) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$completed_count = $stmt->get_result()->fetch_assoc()['count'];

// Work logs by operator
$operator_stats = $conn->query("
    SELECT u.full_name, u.username,
           COUNT(DISTINCT wl.vehicle_id) as vehicles_worked,
           SUM(CASE WHEN wl.action = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM users u
    LEFT JOIN work_logs wl ON u.id = wl.operator_id 
        AND DATE(wl.created_at) BETWEEN '$date_from' AND '$date_to'
    WHERE u.role = 'operator' AND u.is_active = 1
    GROUP BY u.id
    ORDER BY completed_count DESC
");

// Machine stops summary
$stops_summary = $conn->query("
    SELECT m.machine_name, ms.reason, COUNT(*) as stop_count,
           AVG(ms.duration_minutes) as avg_duration
    FROM machine_stops ms
    JOIN machines m ON ms.machine_id = m.id
    WHERE DATE(ms.stopped_at) BETWEEN '$date_from' AND '$date_to'
    GROUP BY m.id, ms.reason
    ORDER BY stop_count DESC
");

// Recent completed vehicles
$completed_vehicles = $conn->query("
    SELECT v.*, 
           TIMESTAMPDIFF(HOUR, v.created_at, v.completed_at) as total_hours
    FROM vehicles v
    WHERE v.status = 'completed'
    AND DATE(v.completed_at) BETWEEN '$date_from' AND '$date_to'
    ORDER BY v.completed_at DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hisobotlar - Merganbaz</title>
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
                <li><a href="vehicles.php">Tachkalar</a></li>
                <li><a href="reports.php" class="active">Hisobotlar</a></li>
            </ul>
        </nav>

        <div class="card">
            <div class="card-header">Davr Tanlang</div>
            <div class="card-body">
                <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                        <label for="date_from">Dan:</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                        <label for="date_to">Gacha:</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Qidirish</button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col col-md-4">
                <div class="stat-card success">
                    <div class="stat-label">Tugallangan Tachkalar</div>
                    <div class="stat-number"><?php echo $completed_count; ?></div>
                    <small><?php echo date('d.m.Y', strtotime($date_from)); ?> - <?php echo date('d.m.Y', strtotime($date_to)); ?></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col col-md-6 col-full">
                <div class="card">
                    <div class="card-header">Operatorlar Statistikasi</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Operator</th>
                                        <th>Ishlangan Tachkalar</th>
                                        <th>Tugallangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($operator_stats->num_rows > 0): ?>
                                        <?php while ($stat = $operator_stats->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($stat['full_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($stat['username']); ?></small>
                                            </td>
                                            <td><?php echo $stat['vehicles_worked']; ?></td>
                                            <td><span class="badge badge-success"><?php echo $stat['completed_count']; ?></span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Ma'lumot yo'q</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col col-md-6 col-full">
                <div class="card">
                    <div class="card-header">To'xtatishlar Statistikasi</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Stanok</th>
                                        <th>Sabab</th>
                                        <th>Soni</th>
                                        <th>O'rtacha (daq.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($stops_summary->num_rows > 0): ?>
                                        <?php while ($stop = $stops_summary->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stop['machine_name']); ?></td>
                                            <td><?php echo getStopReasonText($stop['reason']); ?></td>
                                            <td><span class="badge badge-warning"><?php echo $stop['stop_count']; ?></span></td>
                                            <td><?php echo $stop['avg_duration'] ? round($stop['avg_duration']) . ' min' : '-'; ?></td>
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

        <div class="card">
            <div class="card-header">Tugallangan Tachkalar</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tachka №</th>
                                <th>Boshlangan</th>
                                <th>Tugallangan</th>
                                <th>Jami Vaqt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($completed_vehicles->num_rows > 0): ?>
                                <?php while ($vehicle = $completed_vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></strong></td>
                                    <td><?php echo formatDate($vehicle['created_at']); ?></td>
                                    <td><?php echo formatDate($vehicle['completed_at']); ?></td>
                                    <td>
                                        <?php 
                                        if ($vehicle['total_hours'] < 24) {
                                            echo $vehicle['total_hours'] . ' soat';
                                        } else {
                                            echo round($vehicle['total_hours'] / 24, 1) . ' kun';
                                        }
                                        ?>
                                    </td>
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

    <script src="../js/main.js"></script>
</body>
</html>
