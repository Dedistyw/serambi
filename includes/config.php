<?php
/**
 * Serambi Berkah v1.0 - Configuration Loader
 * Auto-load .env file dengan fallback ke defaults
 */

// Error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Load .env file jika ada
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos(trim($line), '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if (preg_match('/^"(.+)"$/', $value, $matches)) {
            $value = $matches[1];
        }
        
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// Default values jika .env tidak ada atau key missing
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'dedi1234');
if (!defined('DB_NAME')) define('DB_NAME', 'serambi_db');
if (!defined('DEBUG')) define('DEBUG', true);
if (!defined('MASJID_TIMEZONE')) define('MASJID_TIMEZONE', 'Asia/Jakarta');
if (!defined('SITE_NAME')) define('SITE_NAME', 'Masjid Al-Ikhlas');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/serambi');
if (!defined('UPLOAD_MAX_SIZE')) define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024);

// Set timezone
if (defined('MASJID_TIMEZONE') && MASJID_TIMEZONE != '') {
    date_default_timezone_set(MASJID_TIMEZONE);
} else {
    date_default_timezone_set('Asia/Jakarta');
}

// Debug mode
if (defined('DEBUG') && DEBUG === 'false') {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database connection dengan error handling jelas
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new mysqli('localhost','root','dedi1234','serambi_db');
            if ($db->connect_error) {
                throw new Exception(
                    "❌ KONEKSI DATABASE GAGAL<br>" .
                    "🔧 Error: " . $db->connect_error . "<br>" .
                    "📝 Solusi: Cek file .env - pastikan DB settings sudah benar<br>" .
                    "💡 Atau jalankan setup.php untuk instalasi otomatis"
                );
            }
            $db->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("<div style='padding:20px; background:#ffebee; color:#c62828; border-radius:10px; max-width:600px; margin:50px auto; font-family: Arial;'>" . 
                "<h3>🚨 serambi System Error</h3>" . 
                $e->getMessage() . 
                "</div>");
        }
    }
    return $db;
}

// Safe query function
function query($sql) {
    $db = getDB();
    $result = $db->query($sql);
    if (!$result) {
        $errorMsg = "❌ ERROR QUERY DATABASE<br>" .
                   "🔧 Error: " . $db->error . "<br>" .
                   "📝 Query: " . htmlspecialchars($sql);
        
        if (defined('DEBUG') && DEBUG) {
            die("<div style='padding:20px; background:#ffebee; color:#c62828; border-radius:10px;'>" . $errorMsg . "</div>");
        } else {
            error_log("serambi_db Query Error: " . $db->error . " - Query: " . $sql);
            return false;
        }
    }
    return $result;
}

// Helper functions
function env($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

function asset($path) {
    return rtrim(env('SITE_URL', ''), '/') . '/assets/' . ltrim($path, '/');
}

function url($path = '') {
    return rtrim(env('SITE_URL', ''), '/') . '/' . ltrim($path, '/');
}

function isMobile() {
    return preg_match("/(android|iphone|ipod|blackberry|mobile)/i", $_SERVER['HTTP_USER_AGENT']);
}

// Greeting berdasarkan waktu
function getGreeting() {
    $hour = date('H');
    if ($hour < 11) return 'Assalamualaikum wr wb , Selamat Pagi';
    if ($hour < 15) return 'Assalamualaikum wr wb , Selamat Siang'; 
    if ($hour < 19) return 'Assalamualaikum wr wb , Selamat Sore';
    return 'Selamat Malam';
}

// Get jadwal sholat hari ini
function getJadwalSholatHariIni() {
    return query("SELECT * FROM jadwal WHERE jenis = 'sholat' ORDER BY urutan");
}
?>
