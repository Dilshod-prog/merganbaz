<?php
// Common functions

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check if user is super admin
function isSuperAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

// Check if user is operator
function isOperator() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'operator';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

// Redirect if not super admin
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: /operator/dashboard.php');
        exit;
    }
}

// Redirect if not operator
function requireOperator() {
    requireLogin();
    if (!isOperator()) {
        header('Location: /admin/dashboard.php');
        exit;
    }
}

// Sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Format date for display
function formatDate($date) {
    if (!$date) return '-';
    return date('d.m.Y H:i', strtotime($date));
}

// Format date with seconds
function formatDateWithSeconds($date) {
    if (!$date) return '-';
    return date('d.m.Y H:i:s', strtotime($date));
}

// Calculate time difference in human readable format
function formatTimeDiff($start, $end) {
    if (!$start) return '-';
    
    $end = $end ?: date('Y-m-d H:i:s');
    
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    
    $diff = $end_time - $start_time;
    
    if ($diff < 0) return '-';
    
    $days = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    $minutes = floor(($diff % 3600) / 60);
    $seconds = $diff % 60;
    
    $result = [];
    
    if ($days > 0) {
        $result[] = $days . ' kun';
    }
    if ($hours > 0) {
        $result[] = $hours . ' soat';
    }
    if ($minutes > 0) {
        $result[] = $minutes . ' daq';
    }
    if ($seconds > 0 || empty($result)) {
        $result[] = $seconds . ' son';
    }
    
    return implode(' ', $result);
}

// Format time only (for duration)
function formatDuration($seconds) {
    if ($seconds < 0) return '-';
    
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    $result = [];
    
    if ($days > 0) $result[] = $days . 'k';
    if ($hours > 0) $result[] = $hours . 's';
    if ($minutes > 0) $result[] = $minutes . 'd';
    if ($secs > 0 || empty($result)) $result[] = $secs . 'son';
    
    return implode(' ', $result);
}

// Get machine name by ID
function getMachineName($conn, $machine_id) {
    if (!$machine_id) return '-';
    $stmt = $conn->prepare("SELECT machine_name FROM machines WHERE id = ?");
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['machine_name'];
    }
    return '-';
}

// Get vehicle number by ID
function getVehicleNumber($conn, $vehicle_id) {
    if (!$vehicle_id) return '-';
    $stmt = $conn->prepare("SELECT vehicle_number FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['vehicle_number'];
    }
    return '-';
}

// Get operator name by ID
function getOperatorName($conn, $operator_id) {
    if (!$operator_id) return '-';
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $operator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['full_name'];
    }
    return '-';
}

// Get stop reason text
function getStopReasonText($reason) {
    $reasons = [
        'obed' => 'Tushlik (Obed)',
        'no_gas' => 'Gaz yo\'q',
        'technical_issue' => 'Texnik nosozlik',
        'other' => 'Boshqa'
    ];
    return $reasons[$reason] ?? $reason;
}

// Get status badge class
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge-warning',
        'in_progress' => 'badge-primary',
        'completed' => 'badge-success',
        'stopped' => 'badge-danger'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

// Get status text
function getStatusText($status) {
    $texts = [
        'pending' => 'Kutilmoqda',
        'in_progress' => 'Jarayonda',
        'completed' => 'Tugallandi',
        'stopped' => 'To\'xtatildi'
    ];
    return $texts[$status] ?? $status;
}

// JSON response helper
function jsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>
