<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars(env('SITE_NAME')); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .stat-card { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .stat-number { 
            font-size: 2.5rem; 
            font-weight: bold; 
            color: #2E8B57; 
            margin-bottom: 10px; 
        }
        .stat-label { 
            color: #666; 
            font-size: 0.9rem; 
        }
        .recent-activities { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .activity-item { 
            padding: 10px 0; 
            border-bottom: 1px solid #eee; 
            display: flex;
            align-items: center;
        }
        .activity-item:last-child { 
            border-bottom: none; 
        }
        .activity-icon {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .profile-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-avatar {
            width: 60px;
            height: 60px;
            background: #2E8B57;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .profile-details h3 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .profile-details p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        .btn-profile {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .btn-profile:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰ Menu</button>
            
            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
                    </div>
                    <div class="profile-details">
                        <h3>Halo, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 👋</h3>
                        <p>Selamat datang di Dashboard Admin <?php echo htmlspecialchars(env('SITE_NAME')); ?></p>
                        <a href="profil.php" class="btn-profile">✏️ Edit Profil</a>
                    </div>
                </div>
            </div>

            <h1>📊 Dashboard Admin</h1>

            <!-- Statistics -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $pengumuman = query("SELECT COUNT(*) as total FROM pengumuman");
                        echo $pengumuman ? $pengumuman->fetch_assoc()['total'] : 0;
                        ?>
                    </div>
                    <div class="stat-label">📢 Total Pengumuman</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $galeri = query("SELECT COUNT(*) as total FROM galeri");
                        echo $galeri ? $galeri->fetch_assoc()['total'] : 0;
                        ?>
                    </div>
                    <div class="stat-label">📷 Foto Galeri</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number">
                        Rp <?php 
                        $keuangan = query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pemasukan' AND MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
                        $total = $keuangan ? $keuangan->fetch_assoc()['total'] : 0;
                        echo number_format($total, 0, ',', '.');
                        ?>
                    </div>
                    <div class="stat-label">💰 Pemasukan Bulan Ini</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $mutiara = query("SELECT COUNT(*) as total FROM mutiara_kata");
                        echo $mutiara ? $mutiara->fetch_assoc()['total'] : 0;
                        ?>
                    </div>
                    <div class="stat-label">💎 Total Mutiara Kata</div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="recent-activities">
                <h3>📋 Aktivitas Terbaru</h3>
                <div class="activity-item">
                    <span class="activity-icon">📢</span>
                    <div>
                        <strong>Pengumuman Terbaru:</strong>
                        <?php
                        $pengumuman = query("SELECT judul, created_at FROM pengumuman ORDER BY created_at DESC LIMIT 1");
                        if($pengumuman && $row = $pengumuman->fetch_assoc()) {
                            echo htmlspecialchars($row['judul']) . " - " . date('d M Y', strtotime($row['created_at']));
                        } else {
                            echo "Belum ada pengumuman";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="activity-item">
                    <span class="activity-icon">💰</span>
                    <div>
                        <strong>Transaksi Terakhir:</strong>
                        <?php
                        $transaksi = query("SELECT jenis, jumlah, keterangan FROM keuangan ORDER BY created_at DESC LIMIT 1");
                        if($transaksi && $row = $transaksi->fetch_assoc()) {
                            $jenis = $row['jenis'] == 'pemasukan' ? '➕' : '➖';
                            echo $jenis . " Rp " . number_format($row['jumlah'], 0, ',', '.') . " - " . htmlspecialchars($row['keterangan']);
                        } else {
                            echo "Belum ada transaksi";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="activity-item">
                    <span class="activity-icon">🕒</span>
                    <div>
                        <strong>Last Login:</strong>
                        <?php
                        // PERBAIKAN: Gunakan query biasa tanpa prepared statement
                        $username = $_SESSION['admin_username'];
                        $last_login = query("SELECT last_login FROM admin WHERE username = '$username'");
                        if($last_login && $row = $last_login->fetch_assoc()) {
                            echo $row['last_login'] ? date('d M Y H:i', strtotime($row['last_login'])) : 'Pertama kali login';
                        } else {
                            echo 'Tidak tercatat';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>