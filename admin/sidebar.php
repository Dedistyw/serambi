<?php
/**
 * Sidebar navigation untuk admin panel
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-mosque"></i>
            <span class="logo-text">SERAMBI</span>
        </div>
        <div class="masjid-info">
            <h3><?php echo htmlspecialchars(getConstant('SITE_NAME', 'Masjid')); ?></h3>
            <p><?php echo htmlspecialchars(getConstant('MASJID_CITY', '')); ?></p>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="section-label">KONTEN UTAMA</span>
            </li>
            
            <li class="nav-item <?php echo $current_page === 'pengumuman.php' ? 'active' : ''; ?>">
                <a href="pengumuman.php" class="nav-link">
                    <i class="fas fa-bullhorn"></i>
                    <span class="nav-text">Pengumuman</span>
                    <?php
                    $pengumuman = getJSONData('pengumuman');
                    $count_pengumuman = count(array_filter($pengumuman, function($item) {
                        return isset($item['aktif']) && $item['aktif'] == 1;
                    }));
                    ?>
                    <span class="nav-badge"><?php echo $count_pengumuman; ?></span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page === 'mutiara_kata.php' ? 'active' : ''; ?>">
                <a href="mutiara_kata.php" class="nav-link">
                    <i class="fas fa-quote-right"></i>
                    <span class="nav-text">Mutiara Kata</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page === 'jadwal_sholat.php' ? 'active' : ''; ?>">
                <a href="jadwal_sholat.php" class="nav-link">
                    <i class="fas fa-clock"></i>
                    <span class="nav-text">Jadwal Sholat</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="section-label">MEDIA & DOKUMEN</span>
            </li>
            
            <li class="nav-item <?php echo $current_page === 'galeri.php' ? 'active' : ''; ?>">
                <a href="galeri.php" class="nav-link">
                    <i class="fas fa-images"></i>
                    <span class="nav-text">Galeri Foto</span>
                    <?php
                    $galeri = getJSONData('galeri');
                    $count_galeri = count(array_filter($galeri, function($item) {
                        return isset($item['aktif']) && $item['aktif'] == 1;
                    }));
                    ?>
                    <span class="nav-badge"><?php echo $count_galeri; ?></span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page === 'keuangan.php' ? 'active' : ''; ?>">
                <a href="keuangan.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="nav-text">Keuangan</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="section-label">PENGATURAN</span>
            </li>
            
            <li class="nav-item <?php echo $current_page === 'profil_kontak.php' ? 'active' : ''; ?>">
                <a href="profil_kontak.php" class="nav-link">
                    <i class="fas fa-info-circle"></i>
                    <span class="nav-text">Profil & Kontak</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cogs"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../" target="_blank" class="nav-link">
                    <i class="fas fa-external-link-alt"></i>
                    <span class="nav-text">Lihat Website</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="system-info">
            <div class="info-item">
                <span class="info-label">PHP:</span>
                <span class="info-value"><?php echo PHP_VERSION; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Server:</span>
                <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Memory:</span>
                <span class="info-value">
                    <?php 
                    $used_memory = memory_get_usage(true) / 1024 / 1024;
                    echo round($used_memory, 2) . ' MB';
                    ?>
                </span>
            </div>
        </div>
    </div>
</aside>
