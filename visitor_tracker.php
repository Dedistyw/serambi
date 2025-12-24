<?php
/**
 * Visitor Tracker - Dipanggil dari halaman depan
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/functions.php';

// Cek jika ini request AJAX atau langsung
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Load visitor data
$visitor_data = getJSONData('visitors');

// Initialize if empty
if (empty($visitor_data)) {
    $visitor_data = [
        'total_visitors' => 0,
        'today_visitors' => 0,
        'unique_visitors' => 0,
        'visitors_by_day' => [],
        'last_reset' => date('Y-m-d'),
        'last_visit' => null
    ];
}

// Check if we need to reset daily counter
$today = date('Y-m-d');
if ($visitor_data['last_reset'] !== $today) {
    $visitor_data['today_visitors'] = 0;
    $visitor_data['last_reset'] = $today;
}

// Check if user already visited today
$session_key = 'visited_' . $today;
if (!isset($_SESSION[$session_key])) {
    // New visit for today
    $visitor_data['total_visitors']++;
    $visitor_data['today_visitors']++;
    
    // Track by day
    if (!isset($visitor_data['visitors_by_day'][$today])) {
        $visitor_data['visitors_by_day'][$today] = 0;
    }
    $visitor_data['visitors_by_day'][$today]++;
    
    // Check for unique visitor (based on session)
    if (!isset($_SESSION['unique_visitor'])) {
        $visitor_data['unique_visitors']++;
        $_SESSION['unique_visitor'] = true;
    }
    
    // Update last visit
    $visitor_data['last_visit'] = date('Y-m-d H:i:s');
    
    // Mark as visited for today
    $_SESSION[$session_key] = true;
    
    // Save data
    saveJSONData('visitors', $visitor_data);
    
    // Only log if not AJAX request
    if (!$is_ajax) {
        // Create logs directory if doesn't exist
        $log_dir = dirname(__FILE__) . '/uploads/data/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        logActivity('VISITOR_COUNT', "Pengunjung baru - Total: {$visitor_data['total_visitors']}, Hari ini: {$visitor_data['today_visitors']}");
    }
}

// Return JSON if AJAX request
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'total' => $visitor_data['total_visitors'],
        'today' => $visitor_data['today_visitors']
    ]);
}
?>
