<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>🕌 Serambi Berkah Admin</h3>
        <small><?php echo env('SITE_NAME'); ?></small>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a>
        <a href="pengumuman.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pengumuman.php' ? 'active' : ''; ?>">📢 Pengumuman</a>
        <a href="mutiara_kata.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mutiara_kata.php' ? 'active' : ''; ?>">📖 Mutiara Kata</a>
        <a href="jadwal.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'jadwal.php' ? 'active' : ''; ?>">🕋 Jadwal</a>
        <a href="galeri.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'galeri.php' ? 'active' : ''; ?>">📷 Galeri</a>
        <a href="keuangan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'keuangan.php' ? 'active' : ''; ?>">💰 Keuangan</a>
        <a href="kontak.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'kontak.php' ? 'active' : ''; ?>">👥 Kontak</a>
        <a href="logout.php" style="color: #e74c3c;">🚪 Logout (<?php echo $_SESSION['admin_username']; ?>)</a>
    </div>
</div>
