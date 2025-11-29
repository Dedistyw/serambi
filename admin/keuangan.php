<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Keuangan - <?php echo env('SITE_NAME'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .income { color: #27ae60; font-weight: bold; }
        .expense { color: #e74c3c; font-weight: bold; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 1.8rem; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰ Menu</button>
            
            <h1>💰 Manage Keuangan</h1>

            <!-- Statistics -->
            <div class="stats-grid">
                <?php
                // Total pemasukan bulan ini
                $pemasukan = query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pemasukan' AND MONTH(tanggal) = MONTH(CURRENT_DATE())");
                $total_pemasukan = $pemasukan ? ($pemasukan->fetch_assoc()['total'] ?? 0) : 0;
                
                // Total pengeluaran bulan ini
                $pengeluaran = query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pengeluaran' AND MONTH(tanggal) = MONTH(CURRENT_DATE())");
                $total_pengeluaran = $pengeluaran ? ($pengeluaran->fetch_assoc()['total'] ?? 0) : 0;
                
                // Saldo
                $saldo = $total_pemasukan - $total_pengeluaran;
                ?>
                
                <div class="stat-card">
                    <div class="stat-number income">Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></div>
                    <div class="stat-label">Pemasukan Bulan Ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number expense">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></div>
                    <div class="stat-label">Pengeluaran Bulan Ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" style="color: #3498db;">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></div>
                    <div class="stat-label">Saldo Bulan Ini</div>
                </div>
            </div>

            <!-- Add Transaction Form -->
            <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
                <h3>Tambah Transaksi</h3>
                <form method="POST" action="save_keuangan.php">
                    <div class="form-group">
                        <label>Jenis Transaksi:</label>
                        <select name="jenis" required>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah (Rp):</label>
                        <input type="number" name="jumlah" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Keterangan:</label>
                        <input type="text" name="keterangan" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal:</label>
                        <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                </form>
            </div>

            <!-- Transactions List -->
            <div style="background: white; padding: 25px; border-radius: 10px;">
                <h3>Riwayat Transaksi</h3>
                <div class="table">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $transaksi = query("SELECT * FROM keuangan ORDER BY tanggal DESC, created_at DESC LIMIT 50");
                            if($transaksi && $transaksi->num_rows > 0):
                                while($row = $transaksi->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                                <td>
                                    <?php if($row['jenis'] == 'pemasukan'): ?>
                                        <span style="color: #27ae60;">Pemasukan</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c;">Pengeluaran</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                <td class="<?php echo $row['jenis']; ?>">
                                    <?php echo $row['jenis'] == 'pemasukan' ? '+' : '-'; ?>
                                    Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?>
                                </td>
                                <td>
                                    <a href="delete_keuangan.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">Belum ada transaksi</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
