<?php
/**
 * Visitor Counter System
 */
session_start();
require_once 'functions.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Get visitor stats
if ($action === 'get_stats') {
    $visitor_data = getJSONData('visitors');
    
    // Prepare last 7 days data
    $last_7_days = [];
    $visitors_by_day = isset($visitor_data['visitors_by_day']) ? $visitor_data['visitors_by_day'] : [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last_7_days[] = isset($visitors_by_day[$date]) ? $visitors_by_day[$date] : 0;
    }
    
    echo json_encode([
        'success' => true,
        'total_visitors' => $visitor_data['total_visitors'] ?? 0,
        'today_visitors' => $visitor_data['today_visitors'] ?? 0,
        'unique_visitors' => $visitor_data['unique_visitors'] ?? 0,
        'last_7_days' => $last_7_days,
        'last_visit' => $visitor_data['last_visit'] ?? null
    ]);
    exit;
}

// Reset today's visitors
if ($action === 'reset_today') {
    $visitor_data = getJSONData('visitors');
    $today = date('Y-m-d');
    
    // Reset today's counter
    $visitor_data['today_visitors'] = 0;
    $visitor_data['last_reset'] = $today;
    
    if (saveJSONData('visitors', $visitor_data)) {
        logActivity('VISITOR_RESET', "Reset counter pengunjung hari ini");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data']);
    }
    exit;
}

// Default: track visitor (called from frontend)
trackVisitor();

/**
 * Track visitor function
 */
function trackVisitor() {
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
        
        // Log activity (for admin panel)
        logActivity('VISITOR_COUNT', "Pengunjung baru - Total: {$visitor_data['total_visitors']}");
        
        echo json_encode([
            'success' => true,
            'total' => $visitor_data['total_visitors'],
            'today' => $visitor_data['today_visitors']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Already visited today'
        ]);
    }
}
