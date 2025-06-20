<?php
/**
 * Quick Admin Login - Untuk testing login admin
 * Hapus file ini setelah testing selesai
 */

require_once 'config.php';
require_once 'src/includes/session_manager.php';

echo "<h2>🔐 Admin Login Test</h2>";

try {
    // Test login admin
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@hireway.com']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<div style='background: #d1f2eb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Admin user ditemukan!</strong><br>";
        echo "📧 Email: {$admin['email']}<br>";
        echo "👤 Name: {$admin['name']}<br>";
        echo "🎯 Role: {$admin['role']}<br>";
        echo "</div>";
        
        // Auto login untuk testing
        start_user_session($admin['id'], $admin['name'], $admin['role']);
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "🔄 <strong>Auto login berhasil!</strong><br>";
        echo "🚀 Redirecting ke admin dashboard dalam 3 detik...<br>";
        echo "</div>";
        
        echo "<script>setTimeout(function() { window.location.href = 'admin.php'; }, 3000);</script>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ <strong>Admin user tidak ditemukan!</strong><br>";
        echo "Jalankan setup_database.php terlebih dahulu.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Manual Login:</strong></p>";
echo "<ul>";
echo "<li>URL: <a href='src/auth/login.php'>src/auth/login.php</a></li>";
echo "<li>Email: admin@hireway.com</li>";
echo "<li>Password: password</li>";
echo "</ul>";
?>
