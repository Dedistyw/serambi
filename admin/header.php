<?php
/**
 * Header untuk semua halaman admin
 */
if (!isset($page_title)) {
    $page_title = 'Dashboard';
}
$site_name = getConstant('SITE_NAME', 'Masjid Al-Ikhlas');
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - SERAMBI Admin</title>
    <link rel="stylesheet" href="style-admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Additional inline styles if needed */
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar akan diinclude di sini -->
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo htmlspecialchars($page_title); ?></h1>
                </div>
                
                <div class="header-right">
                    <div class="quick-stats">
                        <div class="stat-item">
                            <span class="stat-label">Tanggal</span>
                            <span class="stat-value"><?php echo date('d/m/Y'); ?></span>
                        </div>
                    </div>
                    
                    <div class="user-info">
                        <span class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </span>
                        <div class="user-details">
                            <span class="username"><?php echo htmlspecialchars($admin_username); ?></span>
                            <span class="user-role">Administrator</span>
                        </div>
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="content-area">
                <!-- Notifikasi flash message -->
                <?php
                $flash_message = getFlashMessage();
                if ($flash_message):
                ?>
                <div class="flash-message">
                    <div class="flash-content">
                        <?php echo htmlspecialchars($flash_message); ?>
                    </div>
                    <button class="flash-close">&times;</button>
                </div>
                <script>
                    $(document).ready(function() {
                        setTimeout(function() {
                            $('.flash-message').fadeOut();
                        }, 5000);
                        
                        $('.flash-close').click(function() {
                            $(this).parent().fadeOut();
                        });
                    });
                </script>
                <?php endif; ?>
