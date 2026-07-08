<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Merganbaz Debug - Database Tekshirish</h2>";
echo "<hr>";

// 1. Config faylini yuklash
echo "<h3>1. Config yuklash</h3>";
if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
    echo "✅ Config fayli topildi<br>";
} else {
    die("❌ Config fayli topilmadi!");
}

// 2. Database ulanishini tekshirish
echo "<h3>2. Database Ulanish</h3>";
if ($conn->connect_error) {
    die("❌ Database ulanmadi: " . $conn->connect_error);
} else {
    echo "✅ Database muvaffaqiyatli ulandi!<br>";
    echo "📊 Database nomi: <strong>merganbaz</strong><br>";
}

// 3. Users jadvalini tekshirish
echo "<h3>3. Users Jadvalini Tekshirish</h3>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "✅ Users jadvali mavjud<br>";
} else {
    echo "❌ Users jadvali mavjud emas! Database.sql ni import qiling!<br>";
}

// 4. Admin user ni tekshirish
echo "<h3>4. Admin User Mavjudligi</h3>";
$result = $conn->query("SELECT id, username, full_name, role, is_active FROM users WHERE username = 'admin'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ Admin user topildi!<br>";
    echo "📋 ID: " . $user['id'] . "<br>";
    echo "📋 Username: " . $user['username'] . "<br>";
    echo "📋 Full Name: " . $user['full_name'] . "<br>";
    echo "📋 Role: " . $user['role'] . "<br>";
    echo "📋 Active: " . ($user['is_active'] ? 'Ha' : 'Yo\'q') . "<br>";
} else {
    echo "❌ Admin user topilmadi! Database.sql ni qayta import qiling!<br>";
}

// 5. Parol hash ni tekshirish
echo "<h3>5. Parol Hash</h3>";
$result = $conn->query("SELECT password FROM users WHERE username = 'admin'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "📋 Hozirgi hash: <code>" . htmlspecialchars($user['password']) . "</code><br>";
    
    // Test: admin123 bilan solishtirish
    $test_password = 'admin123';
    if (password_verify($test_password, $user['password'])) {
        echo "✅ Parol to'g'ri! (admin123 ishlashi kerak)<br>";
    } else {
        echo "❌ Parol noto'g'ri! Parolni yangilash kerak!<br>";
        
        // Yangi parol hash yaratish
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        echo "<br><strong>🔧 Muammo yechimi:</strong><br>";
        echo "phpMyAdmin da quyidagi SQL buyrug'ini bajaring:<br>";
        echo "<textarea style='width:100%; height:60px; font-family:monospace;'>UPDATE users SET password = '$new_hash' WHERE username = 'admin';</textarea><br>";
    }
}

// 6. Barcha userlarni ko'rsatish
echo "<h3>6. Barcha Foydalanuvchilar</h3>";
$result = $conn->query("SELECT id, username, full_name, role, is_active FROM users");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Ism</th><th>Role</th><th>Active</th></tr>";
    while ($user = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Hech qanday foydalanuvchi yo'q!<br>";
}

// 7. Barcha jadvallarni ko'rsatish
echo "<h3>7. Database Jadvallari</h3>";
$result = $conn->query("SHOW TABLES");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>✅ " . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "❌ Jadvallar topilmadi!<br>";
}

echo "<hr>";
echo "<h3>✅ Agar barcha tekshiruvlar muvaffaqiyatli bo'lsa:</h3>";
echo "<a href='login.php' style='display:inline-block; padding:10px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:5px;'>Login Sahifasiga O'tish</a>";
?>
