<?php
/**
 * SERAMBI - Functions Helper
 * FIXED PATH: Semua data ke folder uploads/
 */

// Enable errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Root path
define('ROOT_PATH', dirname(__DIR__) . '/');

// Get data from JSON file - HANYA BACA, JANGAN BUAT DEFAULT
function getJSONData($filename) {
    $filepath = ROOT_PATH . 'uploads/data/' . $filename . '.json';
    
    // DEBUG: Cek file exists
    if (!file_exists($filepath)) {
        error_log("DEBUG: File $filename.json tidak ditemukan di $filepath");
        return []; // HANYA return empty array, JANGAN buat file
    }
    
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    
    // DEBUG: Cek isi data
    if ($data === null) {
        error_log("DEBUG: JSON decode gagal untuk $filename.json: " . json_last_error_msg());
        error_log("DEBUG: Content: " . substr($content, 0, 200));
    }
    
    return is_array($data) ? $data : [];
}

// Save data to JSON file
function saveJSONData($filename, $data) {
    $filepath = ROOT_PATH . 'uploads/data/' . $filename . '.json';
    
    // Pastikan folder ada
    $dir = dirname($filepath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // DEBUG: Log sebelum save
    error_log("DEBUG: Saving to $filename.json");
    error_log("DEBUG: Data count: " . count($data));
    
    $result = file_put_contents($filepath, $json) !== false;
    
    if (!$result) {
        error_log("ERROR: Gagal menyimpan ke $filepath");
    }
    
    return $result;
}

// Get constant from settings
function getConstant($key, $default = '') {
    $profil = getJSONData('profil_masjid');
    return isset($profil[$key]) ? $profil[$key] : $default;
}

// Generate ID unik
function generateId($length = 8) {
    return bin2hex(random_bytes($length));
}

// fungsi ukuran byte
function formatBytes($bytes, $decimals = 2) {
    if ($bytes == 0) return '0 Bytes';
    
    $k = 1024;
    $dm = $decimals < 0 ? 0 : $decimals;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    $i = floor(log($bytes) / log($k));
    
    return number_format($bytes / pow($k, $i), $dm) . ' ' . $sizes[$i];
}

// Validasi upload gambar
function validateImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    return true;
}

// Upload gambar - FIX PATH: ke uploads/images/
function uploadImage($file, $target_dir = null) {
    if ($target_dir === null) {
        $target_dir = ROOT_PATH . 'uploads/images/';
    }
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'img_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($extension);
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $filename;
    }
    
    return false;
}

// Hapus gambar - FIX PATH: dari uploads/images/
function deleteImage($filename, $directory = null) {
    if ($directory === null) {
        $directory = ROOT_PATH . 'uploads/images/';
    }
    
    $filepath = $directory . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Redirect dengan pesan
function redirect($url, $message = '') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
    }
    header('Location: ' . $url);
    exit;
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return '';
}

// Setup folders - HANYA jika diperlukan
function setupFolders() {
    $folders = [
        'uploads',
        'uploads/data',
        'uploads/images',
        'backups',
        'assets',
        'assets/css',
        'assets/js',
        'assets/images'
    ];
    
    foreach ($folders as $folder) {
        $path = ROOT_PATH . $folder;
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
            error_log("DEBUG: Created folder: $path");
        }
    }
}

// Setup default data - HANYA dipanggil manual
function setupDefaultData($force = false) {
    // Buat admin default jika belum ada
    $admin_data = getJSONData('admin');
    if (empty($admin_data) || $force) {
        $default_admin = [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'email' => 'admin@masjid.HASAN',
            'created_at' => date('Y-m-d H:i:s'),
            'last_modified' => date('Y-m-d H:i:s')
        ];
        saveJSONData('admin', $default_admin);
        error_log("DEBUG: Setup admin default");
    }
    
    // Buat profil default jika belum ada
    $profil_data = getJSONData('profil_masjid');
    if (empty($profil_data) || $force) {
        $default_profil = [
            'SITE_NAME' => 'Masjid Al-Ikhlas',
            'MASJID_CITY' => 'Serpong - Tangerang Selatan',
            'MASJID_TIMEZONE' => 'Asia/Jakarta',
            'MASJID_PHONE' => '+62123456789',
            'MASJID_EMAIL' => 'info@masjid.HASAN',
            'APP_VERSION' => '1.0.0',
            'DEVELOPER_NAME' => 'with ❤️ by HASAN dan para Muslim',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveJSONData('profil_masjid', $default_profil);
        error_log("DEBUG: Setup profil default");
    }
}

// Inisialisasi session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// **PERUBAHAN PENTING: JANGAN PANGGIL setupFolders() dan setupDefaultData() OTOMATIS!**
// Hapus atau comment baris berikut:
// setupFolders();
// setupDefaultData();

// Sebagai gantinya, buat fungsi untuk memeriksa apakah setup diperlukan
function checkSetupRequired() {
    $admin_file = ROOT_PATH . 'uploads/data/admin.json';
    $profil_file = ROOT_PATH . 'uploads/data/profil_masjid.json';
    
    // Jika kedua file tidak ada, setup diperlukan
    if (!file_exists($admin_file) || !file_exists($profil_file)) {
        return true;
    }
    
    // Cek jika file kosong
    $admin_content = file_get_contents($admin_file);
    $profil_content = file_get_contents($profil_file);
    
    if (empty($admin_content) || empty($profil_content) || 
        $admin_content === '[]' || $profil_content === '[]') {
        return true;
    }
    
    return false;
}

// Cek setup hanya di halaman tertentu (misal: halaman setup/install)
// setupFolders(); // HAPUS ini
// setupDefaultData(); // HAPUS ini


/**
 * Menghapus direktori dan semua isinya secara rekursif
 * @param string $dir Path direktori
 * @return bool True jika berhasil, False jika gagal
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    // Gunakan realpath untuk keamanan
    $realPath = realpath($dir);
    if ($realPath === false) {
        return false;
    }
    
    $files = array_diff(scandir($realPath), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $realPath . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            // Rekursif untuk subdirektori
            if (!deleteDirectory($path)) {
                return false;
            }
        } else {
            // Hapus file
            if (!unlink($path)) {
                error_log("Gagal menghapus file: " . $path);
                return false;
            }
        }
    }
    
    // Hapus direktori kosong
    return rmdir($realPath);
}

?>
