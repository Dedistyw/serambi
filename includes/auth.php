<?php
/**
 * SERAMBI - Authentication System
 */

require_once 'functions.php';
require_once 'security.php';

class Auth {
    
    // Cek login status
    public static function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    // Login admin
    public static function login($username, $password) {
        $admin_data = getJSONData('admin');
        
        if (empty($admin_data) || !isset($admin_data['username'])) {
            // Buat admin default jika belum ada
            self::createDefaultAdmin();
            $admin_data = getJSONData('admin');
        }
        
        if (isset($admin_data['username']) && $admin_data['username'] === $username) {
            if (verifyPassword($password, $admin_data['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_last_login'] = time();
                
                logActivity('LOGIN_SUCCESS', "User: $username");
                return true;
            }
        }
        
        logActivity('LOGIN_FAILED', "Username: $username");
        return false;
    }
    
    // Logout
    public static function logout() {
        $username = $_SESSION['admin_username'] ?? 'unknown';
        logActivity('LOGOUT', "User: $username");
        
        session_destroy();
        session_start();
    }
    
    // Buat admin default
    private static function createDefaultAdmin() {
        $default_admin = [
            'username' => 'admin',
            'password' => hashPassword('admin123'),
            'email' => 'admin@masjid.hasan',
            'created_at' => date('Y-m-d H:i:s'),
            'last_modified' => date('Y-m-d H:i:s')
        ];
        
        saveJSONData('admin', $default_admin);
        
        // Buat profil masjid default
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
    }
    
    // Ganti password
    public static function changePassword($current_password, $new_password) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $admin_data = getJSONData('admin');
        
        if (verifyPassword($current_password, $admin_data['password'])) {
            $admin_data['password'] = hashPassword($new_password);
            $admin_data['last_modified'] = date('Y-m-d H:i:s');
            
            if (saveJSONData('admin', $admin_data)) {
                logActivity('PASSWORD_CHANGED', "User: " . $_SESSION['admin_username']);
                return true;
            }
        }
        
        logActivity('PASSWORD_CHANGE_FAILED', "User: " . $_SESSION['admin_username']);
        return false;
    }
    
    // Require login
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    // Cek timeout session (30 menit)
    public static function checkSessionTimeout() {
        if (self::isLoggedIn()) {
            $timeout = 30 * 60; // 30 menit
            if (isset($_SESSION['admin_last_login']) && 
                (time() - $_SESSION['admin_last_login']) > $timeout) {
                self::logout();
                return false;
            }
            
            // Update last activity
            $_SESSION['admin_last_login'] = time();
            return true;
        }
        return false;
    }
    
    // Cek CSRF token
    public static function checkCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Check session timeout
Auth::checkSessionTimeout();

// Inisialisasi session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
