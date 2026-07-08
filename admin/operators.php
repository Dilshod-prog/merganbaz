<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireSuperAdmin();

// Get all operators with their machines
$operators = $conn->query("
    SELECT u.*, m.machine_name, m.machine_code,
           op.can_start_work, op.can_stop_work, op.can_complete_work, 
           op.can_stop_machine, op.can_view_reports
    FROM users u
    LEFT JOIN machines m ON u.machine_id = m.id
    LEFT JOIN operator_permissions op ON u.id = op.operator_id
    WHERE u.role = 'operator'
    ORDER BY u.full_name
");

// Get all active machines for dropdown
$machines = $conn->query("SELECT id, machine_name, machine_code FROM machines WHERE is_active = 1 ORDER BY machine_name");
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operatorlar - Merganbaz</title>
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
                <li><a href="operators.php" class="active">Operatorlar</a></li>
                <li><a href="vehicles.php">Tachkalar</a></li>
                <li><a href="reports.php">Hisobotlar</a></li>
            </ul>
        </nav>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <span>Operatorlar Ro'yxati</span>
                <button onclick="openAddOperatorModal()" class="btn btn-primary btn-sm">+ Operator Qo'shish</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="operatorsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ism</th>
                                <th>Login</th>
                                <th>Telefon</th>
                                <th>Stanok</th>
                                <th>Huquqlar</th>
                                <th>Status</th>
                                <th>Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($operators->num_rows > 0): ?>
                                <?php while ($op = $operators->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $op['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($op['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($op['username']); ?></td>
                                    <td><?php echo htmlspecialchars($op['phone'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($op['machine_name']): ?>
                                            <span class="badge badge-primary">
                                                <?php echo htmlspecialchars($op['machine_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Tayinlanmagan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem;">
                                            <?php
                                            $permissions = [];
                                            if ($op['can_start_work']) $permissions[] = '▶️ Boshlash';
                                            if ($op['can_complete_work']) $permissions[] = '✅ Tugatish';
                                            if ($op['can_stop_machine']) $permissions[] = '⏸️ To\'xtatish';
                                            if ($op['can_view_reports']) $permissions[] = '📊 Hisobotlar';
                                            echo $permissions ? implode(', ', $permissions) : '-';
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($op['is_active']): ?>
                                            <span class="badge badge-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Faol emas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick='editOperator(<?php echo json_encode($op); ?>)' class="btn btn-primary btn-sm">Tahrirlash</button>
                                        <?php if ($op['is_active']): ?>
                                            <button onclick="toggleOperatorStatus(<?php echo $op['id']; ?>, 0)" class="btn btn-danger btn-sm">O'chirish</button>
                                        <?php else: ?>
                                            <button onclick="toggleOperatorStatus(<?php echo $op['id']; ?>, 1)" class="btn btn-success btn-sm">Yoqish</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Hozircha operatorlar yo'q</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Operator Modal -->
    <div id="operatorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Operator Qo'shish</h3>
                <span class="close" onclick="closeOperatorModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="operatorForm">
                    <input type="hidden" id="operator_id" name="operator_id">
                    
                    <div class="form-group">
                        <label for="full_name">To'liq Ism <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Login <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Parol <span class="required" id="passwordRequired">*</span></label>
                        <input type="password" id="password" name="password">
                        <small style="color: var(--secondary-color);" id="passwordHint">
                            Tahrirlashda bo'sh qoldiring agar parolni o'zgartirmoqchi bo'lmasangiz
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefon Raqami</label>
                        <input type="tel" id="phone" name="phone" placeholder="+998901234567">
                    </div>

                    <div class="form-group">
                        <label for="machine_id">Stanok</label>
                        <select id="machine_id" name="machine_id">
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

                    <div class="card" style="margin-top: 20px;">
                        <div class="card-header">Huquqlar</div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="can_start_work" id="can_start_work" value="1" checked>
                                    Ishni boshlash
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="can_complete_work" id="can_complete_work" value="1" checked>
                                    Ishni tugatish
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="can_stop_work" id="can_stop_work" value="1" checked>
                                    Ishni to'xtatish
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="can_stop_machine" id="can_stop_machine" value="1" checked>
                                    Stanokni to'xtatish
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="can_view_reports" id="can_view_reports" value="1">
                                    Hisobotlarni ko'rish
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="closeOperatorModal()" class="btn btn-secondary">Bekor qilish</button>
                <button onclick="saveOperator()" class="btn btn-primary">Saqlash</button>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        function openAddOperatorModal() {
            document.getElementById('modalTitle').textContent = 'Operator Qo\'shish';
            document.getElementById('operatorForm').reset();
            document.getElementById('operator_id').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('passwordHint').style.display = 'none';
            document.getElementById('operatorModal').classList.add('show');
        }

        function editOperator(operator) {
            document.getElementById('modalTitle').textContent = 'Operatorni Tahrirlash';
            document.getElementById('operator_id').value = operator.id;
            document.getElementById('full_name').value = operator.full_name;
            document.getElementById('username').value = operator.username;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHint').style.display = 'block';
            document.getElementById('phone').value = operator.phone || '';
            document.getElementById('machine_id').value = operator.machine_id || '';
            
            // Set permissions
            document.getElementById('can_start_work').checked = operator.can_start_work == 1;
            document.getElementById('can_complete_work').checked = operator.can_complete_work == 1;
            document.getElementById('can_stop_work').checked = operator.can_stop_work == 1;
            document.getElementById('can_stop_machine').checked = operator.can_stop_machine == 1;
            document.getElementById('can_view_reports').checked = operator.can_view_reports == 1;
            
            document.getElementById('operatorModal').classList.add('show');
        }

        function closeOperatorModal() {
            document.getElementById('operatorModal').classList.remove('show');
        }

        async function saveOperator() {
            const form = document.getElementById('operatorForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('../ajax/save_operator.php', {
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

        async function toggleOperatorStatus(operatorId, status) {
            if (!confirm(status ? 'Operatorni yoqmoqchimisiz?' : 'Operatorni o\'chirmoqchimisiz?')) {
                return;
            }

            try {
                const response = await fetch('../ajax/toggle_operator_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `operator_id=${operatorId}&status=${status}`
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('operatorModal');
            if (event.target == modal) {
                closeOperatorModal();
            }
        }
    </script>
</body>
</html>
