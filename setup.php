<?php
/**
 * SETUP SCRIPT - Jalankan sekali saat pertama kali install
 */
echo "<h2>SERAMBI - Setup System</h2>";

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include functions
require_once 'includes/functions.php';

// Setup folders
function setupDirectories() {
    echo "<h3>Membuat Direktori...</h3>";
    
    $folders = [
        'uploads',
        'uploads/data',
        'uploads/images',
        'backups',
        'assets/css',
        'assets/js',
        'assets/images'
    ];
    
    // Gunakan direktori saat ini (root path) sebagai base
    $base_path = __DIR__ . '/';
    
    foreach ($folders as $folder) {
        $path = $base_path . $folder;
        if (!file_exists($path)) {
            if (mkdir($path, 0755, true)) {
                echo "✅ Direktori dibuat: $path<br>";
            } else {
                echo "❌ Gagal membuat direktori: $path<br>";
            }
        } else {
            echo "✅ Direktori sudah ada: $path<br>";
        }
    }
}

// Setup default files
function setupDefaultFiles() {
    echo "<h3>Membuat File Default...</h3>";
    
    $files_to_create = [
        'uploads/data/activity.log',
        'uploads/data/visitors.json',
        'uploads/data/admin.json',
        'uploads/data/api_meta.json',
        'uploads/data/profil_masjid.json',
        'uploads/data/pengumuman.json',
        'uploads/data/mutiara_kata.json',
        'uploads/data/galeri.json',
        'uploads/data/keuangan.json',
        'uploads/data/jadwal_sholat.json'
    ];
    
    $default_visitors = json_encode([
        'total_visitors' => 0,
        'today_visitors' => 0,
        'unique_visitors' => 0,
        'visitors_by_day' => [],
        'last_reset' => date('Y-m-d'),
        'last_visit' => null
    ], JSON_PRETTY_PRINT);
    
    $default_admin = json_encode([
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'email' => 'admin@json_en.HASAN',
        'created_at' => date('Y-m-d H:i:s'),
        'last_modified' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
    $default_profil = json_encode([
        'SITE_NAME' => 'Masjid Al-Ikhlas',
        'MASJID_CITY' => 'Serpong - Tangerang Selatan',
        'MASJID_TIMEZONE' => 'Asia/Jakarta',
        'MASJID_PHONE' => '+62123456789',
        'MASJID_EMAIL' => 'info@masjid.HASAN',
        'APP_VERSION' => '1.0.0',
        'DEVELOPER_NAME' => 'with ❤️ by HASAN dan para Muslim',
        'created_at' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
    foreach ($files_to_create as $file) {
        $filepath = __DIR__ . '/' . $file;
        
        if (!file_exists($filepath)) {
            $content = '[]'; // Default untuk kebanyakan file
            
            // Khusus file tertentu
            if ($file === 'uploads/data/visitors.json') {
                $content = $default_visitors;
            } elseif ($file === 'uploads/data/admin.json') {
                $content = $default_admin;
            } elseif ($file === 'uploads/data/profil_masjid.json') {
                $content = $default_profil;
            } elseif ($file === 'uploads/data/activity.log') {
                $content = "=== SERAMBI Activity Log ===\n" . 
                          "Created: " . date('Y-m-d H:i:s') . "\n" .
                          "=================================\n\n";
            }
            
            if (file_put_contents($filepath, $content)) {
                echo "✅ File dibuat: $file<br>";
                
                // Set permission yang aman
                if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                    chmod($filepath, 0644);
                }
            } else {
                echo "❌ Gagal membuat file: $file<br>";
            }
        } else {
            echo "✅ File sudah ada: $file<br>";
        }
    }
}

// Check permissions
function checkPermissions() {
    echo "<h3>Checking Permissions...</h3>";
    
    $paths_to_check = [
        'uploads' => 'Read/Write',
        'uploads/data' => 'Read/Write',
        'uploads/images' => 'Read/Write',
        'assets' => 'Read/Write',
        'assets/images' => 'Read/Write',
        'includes' => 'Read',
        'admin' => 'Read'
    ];
    
    foreach ($paths_to_check as $path => $required) {
        $full_path = __DIR__ . '/' . $path;
        
        if (file_exists($full_path)) {
            if (is_writable($full_path) && $required === 'Read/Write') {
                echo "✅ $path: Write permission OK<br>";
            } elseif (is_readable($full_path)) {
                echo "✅ $path: Read permission OK<br>";
            } else {
                echo "❌ $path: Permission issue (Required: $required)<br>";
            }
        } else {
            echo "⚠️ $path: Directory not found<br>";
        }
    }
}

// Test system functions
function testSystem() {
    echo "<h3>Testing System Functions...</h3>";
    
    // Test getJSONData
    $test_data = getJSONData('visitors');
    if (is_array($test_data)) {
        echo "✅ getJSONData() working<br>";
    } else {
        echo "❌ getJSONData() failed<br>";
    }
    
    // Test saveJSONData
    $test_save = ['test' => 'value'];
    if (saveJSONData('test_file', $test_save)) {
        echo "✅ saveJSONData() working<br>";
        
        // Cleanup
        $test_file = __DIR__ . '/uploads/data/test_file.json';
        if (file_exists($test_file)) {
            unlink($test_file);
        }
    } else {
        echo "❌ saveJSONData() failed<br>";
    }
    
    // Test logActivity
    if (function_exists('logActivity')) {
        logActivity('SETUP_TEST', 'Setup script executed');
        echo "✅ logActivity() working<br>";
    } else {
        echo "❌ logActivity() not found<br>";
    }
    
    // Test trackVisitor
    if (function_exists('trackVisitor')) {
        $result = trackVisitor();
        echo "✅ trackVisitor() working (Tracked: " . ($result ? 'Yes' : 'No') . ")<br>";
    } else {
        echo "❌ trackVisitor() not found<br>";
    }
}

// Main setup process
echo "<div style='background:#f5f5f5; padding:20px; border-radius:10px;'>";

setupDirectories();
echo "<hr>";
setupDefaultFiles();
echo "<hr>";
checkPermissions();
echo "<hr>";
testSystem();

echo "</div>";

echo "<h3 style='color:green; margin-top:20px;'>Setup Selesai!</h3>";
echo "<p>Sistem SERAMBI telah diinisialisasi. File-file yang telah dibuat:</p>";
echo "<ul>";
echo "<li>📁 uploads/data/ - Berisi semua file data JSON</li>";
echo "<li>📁 uploads/images/ - Untuk menyimpan gambar</li>";
echo "<li>📄 uploads/data/activity.log - Log aktivitas sistem</li>";
echo "<li>📄 uploads/data/visitors.json - Data pengunjung</li>";
echo "<li>📄 uploads/data/admin.json - Data admin (username: admin, password: admin123)</li>";
echo "<li>📄 uploads/data/profil_masjid.json - Profil masjid</li>";
echo "<li>📄 uploads/data/pengumuman.json - Data pengumuman</li>";
echo "<li>📄 uploads/data/mutiara_kata.json - Mutiara kata</li>";
echo "<li>📄 uploads/data/galeri.json - Data galeri</li>";
echo "<li>📄 uploads/data/keuangan.json - Data keuangan</li>";
echo "<li>📄 uploads/data/jadwal_sholat.json - Jadwal sholat</li>";
echo "</ul>";

echo "<p><strong>Login ke Admin Panel:</strong></p>";
echo "<ul>";
echo "<li>URL: <a href='admin/login.php'>admin/login.php</a></li>";
echo "<li>Username: admin</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";

echo "<p><strong>Setelah setup:</strong></p>";
echo "<ol>";
echo "<li>Hapus file setup.php ini dari server</li>";
echo "<li>Akses halaman depan: <a href='index.php'>index.php</a></li>";
echo "<li>Login ke admin panel untuk mengatur konten</li>";
echo "</ol>";

echo "<p style='color:red; font-weight:bold;'>⚠️ JANGAN LUPA HAPUS FILE setup.php SETELAH SELESAI!</p>";

// Tambahkan info lokasi untuk debugging
echo "<hr><div style='background:#e0f7fa; padding:10px; border-radius:5px; font-size:12px;'>";
echo "<strong>Debug Info:</strong><br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Script Location: " . __FILE__ . "<br>";
echo "Working Directory: " . getcwd() . "<br>";
echo "</div>";
?>
