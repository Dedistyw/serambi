<?php
/**
 * SERAMBI - Security Functions
 */

// Cek CSRF token
function checkCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }
    }
}

// Cek CSRF token untuk GET request
function checkCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}

// Generate CSRF token
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Cek XSS pada output
function escapeOutput($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Validasi input angka
function validateNumber($input, $min = null, $max = null) {
    if (!is_numeric($input)) return false;
    $number = (int) $input;
    
    if ($min !== null && $number < $min) return false;
    if ($max !== null && $number > $max) return false;
    
    return $number;
}

// Validasi email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validasi URL
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Validasi tanggal
function validateDate($date, $format = 'Y-m-d') {
    if (empty($date)) return false;
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Prevent directory traversal
function safePath($path) {
    $path = str_replace(['../', './', '..\\', '.\\'], '', $path);
    return $path;
}

// Log aktivitas - VERSI PERBAIKAN OTOMATIS BUAT FILE
function logActivity($action, $details = '') {
    // MATIKAN ERROR REPORTING UNTUK FUNGSI INI
    $error_reporting = error_reporting();
    error_reporting(0);
    
    // CARI PATH YANG BENAR - COBA 3 KEMUNGKINAN
    $possible_roots = [
        dirname(dirname(__DIR__)), // Dari includes/ ke root
        $_SERVER['DOCUMENT_ROOT'] . '/serambi',
        dirname(__DIR__) . '/..',
        realpath(dirname(__FILE__) . '/../..')
    ];
    
    $log_dir = null;
    $log_file = null;
    
    foreach ($possible_roots as $root) {
        $test_dir = $root . '/uploads/data/';
        $test_file = $test_dir . 'activity.log';
        
        // Coba buat direktori jika belum ada
        if (!is_dir($test_dir)) {
            @mkdir($test_dir, 0755, true);
        }
        
        // Jika direktori berhasil dibuat atau sudah ada
        if (is_dir($test_dir) && is_writable($test_dir)) {
            $log_dir = $test_dir;
            $log_file = $test_file;
            break;
        }
    }
    
    // Jika semua gagal, gunakan default pertama
    if (!$log_dir) {
        $log_dir = $possible_roots[0] . '/uploads/data/';
        $log_file = $log_dir . 'activity.log';
        // Paksa buat direktori
        @mkdir($log_dir, 0755, true);
    }
    
    // BUAT FILE JIKA BELUM ADA
    if (!file_exists($log_file)) {
        $log_content = "=== SERAMBI Activity Log ===\n";
        $log_content .= "Created: " . date('Y-m-d H:i:s') . "\n";
        $log_content .= "Autocreated by logActivity()\n";
        $log_content .= "=================================\n\n";
        
        // Coba buat file
        @file_put_contents($log_file, $log_content);
        
        // Set permission agar bisa ditulis
        if (file_exists($log_file)) {
            @chmod($log_file, 0666);
        }
    }
    
    // Pastikan file writable
    if (file_exists($log_file) && !is_writable($log_file)) {
        @chmod($log_file, 0666);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user = $_SESSION['user']['username'] ?? ($_SESSION['admin_username'] ?? 'guest');
    
    $log_entry = "[$timestamp] [$ip] [$user] [$action] $details\n";
    
    // TULIS KE FILE - PAKAI @ UNTUK SUPPRESS ERROR
    $result = @file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Jika gagal, coba dengan mode berbeda
    if ($result === false) {
        // Coba tanpa LOCK_EX
        $result = @file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Jika masih gagal, coba buat file baru
        if ($result === false) {
            $result = @file_put_contents($log_file, $log_entry);
        }
    }
    
    // Restore error reporting
    error_reporting($error_reporting);
    
    return $result !== false;
}

// Rate limiting sederhana
function checkRateLimit($action, $limit = 10, $timeframe = 60) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return true; // Skip rate limit jika session tidak aktif
    }
    
    $key = 'ratelimit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? '');
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'start' => time()
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    if (time() - $data['start'] > $timeframe) {
        $_SESSION[$key] = [
            'count' => 1,
            'start' => time()
        ];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        logActivity('RATE_LIMIT_EXCEEDED', "Action: $action, IP: " . ($_SERVER['REMOTE_ADDR'] ?? ''));
        return false;
    }
    
    $data['count']++;
    $_SESSION[$key] = $data;
    
    return true;
}

// Generate secure password hash
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Session regeneration
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = time();
    }
}

// Cek apakah user sudah login (untuk API/background process)
function isLoggedIn() {
    return isset($_SESSION['user']) || isset($_SESSION['admin_username']);
}

// Generate random string
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length));
}

// Validasi input file upload
function validateUploadedFile($file) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Cek jika file adalah upload yang valid
    if (!is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    return true;
}

// Inisialisasi keamanan
function initSecurity() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    regenerateSession();
    generateCSRF();
    
    // Security headers
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        
        // HSTS untuk HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// Panggil inisialisasi jika file ini diinclude langsung
if (basename($_SERVER['PHP_SELF']) === 'security.php') {
    initSecurity();
}
