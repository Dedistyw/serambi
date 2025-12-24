<?php
/**
 * SETUP SCRIPT - Fixed version with permission handling
 */
echo "<h2>🔧 SERAMBI - Setup System</h2>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include functions
require_once 'includes/functions.php';

// Check if we have write permission
function checkBasePermission() {
    $base_path = __DIR__;
    
    if (!is_writable($base_path)) {
        echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin:20px 0;'>";
        echo "<h3>⚠️ Permission Error!</h3>";
        echo "Folder <code>$base_path</code> tidak writable.<br>";
        echo "<strong>Solusi:</strong> Jalankan perintah berikut di terminal:<br>";
        echo "<pre style='background:#fff; padding:10px;'>";
        echo "sudo chown -R www-data:www-data $base_path/\n";
        echo "sudo chmod -R 755 $base_path/\n";
        echo "sudo chmod -R 775 $base_path/uploads/";
        echo "</pre>";
        echo "</div>";
        return false;
    }
    
    return true;
}

// Modified folder creation with better error handling
function createFolders() {
    echo "<h3>📁 Membuat Direktori...</h3>";
    
    $folders = [
        'uploads',
        'assets',
        'backups',
        'uploads/data',
        'uploads/images',
        'assets/css',
        'assets/js',
        'assets/images'
    ];
    
    $base_path = __DIR__ . '/';
    $created_count = 0;
    $need_sudo = false;
    
    foreach ($folders as $folder) {
        $path = $base_path . $folder;
        
        if (!file_exists($path)) {
            if (mkdir($path, 0755, true)) {
                echo "✅ Direktori dibuat: <code>$folder</code><br>";
                $created_count++;
            } else {
                echo "❌ Gagal membuat direktori: <code>$folder</code><br>";
                echo "   <small style='color:#666;'>Coba jalankan: <code>sudo mkdir -p $path && sudo chmod 755 $path</code></small><br>";
                $need_sudo = true;
            }
        } else {
            $perm = substr(sprintf('%o', fileperms($path)), -4);
            echo "📂 Direktori sudah ada: <code>$folder</code> (permission: $perm)<br>";
            
            // Check if writable
            if (!is_writable($path)) {
                echo "   ⚠️ <small style='color:orange;'>Tidak writable!</small><br>";
                $need_sudo = true;
            }
        }
    }
    
    if ($need_sudo) {
        echo "<div style='background:#fff3cd; padding:10px; border-radius:5px; margin:10px 0;'>";
        echo "<strong>⚠️ Permission Required:</strong><br>";
        echo "Beberapa folder membutuhkan permission khusus. Jalankan:<br>";
        echo "<pre style='background:#fff; padding:10px;'>";
        echo "sudo chown -R www-data:www-data /var/www/html/serambi/\n";
        echo "sudo chmod -R 755 /var/www/html/serambi/\n";
        echo "# Khusus upload folder:\n";
        echo "sudo chmod -R 775 /var/www/html/serambi/uploads/\n";
        echo "sudo chmod -R 775 /var/www/html/serambi/uploads/data/";
        echo "</pre>";
        echo "</div>";
    }
    
    echo "<br><strong>Total direktori dibuat: $created_count</strong><br>";
    return $created_count;
}

// Skip permission setting if not root
function setFolderPermissions() {
    echo "<h3>🔐 Mengatur Permissions Folder...</h3>";
    
    $user = posix_getpwuid(posix_geteuid());
    $is_root = ($user['name'] === 'root' || $user['uid'] === 0);
    
    if (!$is_root) {
        echo "<div style='background:#e8f4fd; padding:10px; border-radius:5px;'>";
        echo "⚠️ Script tidak berjalan sebagai root. Permission tidak bisa diubah.<br>";
        echo "Jalankan setup melalui terminal dengan:<br>";
        echo "<code>sudo php setup.php</code><br>";
        echo "atau atur permission manual dengan:<br>";
        echo "<code>sudo chmod -R 755 /var/www/html/serambi/</code>";
        echo "</div>";
        return 0;
    }
    
    // Rest of the permission code...
    // [Kode permission setting dari sebelumnya]
}

// Main setup with permission check
echo "<div style='background:#f8f9fa; padding:20px; border-radius:10px; margin:20px 0;'>";

// Check permission first
if (!checkBasePermission()) {
    echo "<p style='color:red;'>Setup tidak dapat dilanjutkan tanpa permission yang tepat.</p>";
    echo "</div>";
    exit();
}

createFolders();
// ... rest of the code

echo "</div>";
?>
