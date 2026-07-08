<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isSuperAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: operator/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Iltimos, login va parolni kiriting';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role, machine_id, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (!$user['is_active']) {
                $error = 'Sizning hisobingiz faol emas';
            } elseif (verifyPassword($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['machine_id'] = $user['machine_id'];
                
                if ($user['role'] === 'super_admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: operator/dashboard.php');
                }
                exit;
            } else {
                $error = 'Login yoki parol noto\'g\'ri';
            }
        } else {
            $error = 'Login yoki parol noto\'g\'ri';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirish - Merganbaz Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🔧 Merganbaz</h2>
            <h3 style="text-align: center; margin-bottom: 20px; color: var(--secondary-color); font-size: 1.1rem;">Admin Panel</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Login <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required autofocus 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Parol <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Kirish</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center; color: var(--secondary-color); font-size: 0.9rem;">
                <p>Standart login: <strong>admin</strong></p>
                <p>Standart parol: <strong>admin123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
