<?php
/**
 * Serambi Berkah Helper Functions
 */

// Format currency
function format_currency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Get current date in Indonesian
function indonesian_date($date) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = $days[date('w', $timestamp)];
    $date_num = date('j', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day, $date_num $month $year";
}

// Sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Redirect dengan pesan
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit;
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
?>
