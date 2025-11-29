<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jadwal - <?php echo env('SITE_NAME'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰ Menu</button>
            
            <h1>🕋 Manage Jadwal</h1>

            <!-- Add Schedule Form -->
            <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
                <h3>Tambah Jadwal</h3>
                <form method="POST" action="save_jadwal.php">
                    <div class="form-group">
                        <label>Jenis:</label>
                        <select name="jenis" required>
                            <option value="sholat">Jadwal Sholat</option>
                            <option value="kegiatan">Kegiatan</option>
                            <option value="pengajian">Pengajian</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama:</label>
                        <input type="text" name="nama" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Hari:</label>
                        <select name="hari">
                            <option value="setiap">Setiap Hari</option>
                            <option value="senin">Senin</option>
                            <option value="selasa">Selasa</option>
                            <option value="rabu">Rabu</option>
                            <option value="kamis">Kamis</option>
                            <option value="jumat">Jumat</option>
                            <option value="sabtu">Sabtu</option>
                            <option value="minggu">Minggu</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Waktu:</label>
                        <input type="time" name="waktu" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Keterangan:</label>
                        <textarea name="keterangan"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Urutan:</label>
                        <input type="number" name="urutan" value="0">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                </form>
            </div>

            <!-- Jadwal List -->
            <div style="background: white; padding: 25px; border-radius: 10px;">
                <h3>Daftar Jadwal</h3>
                <div class="table">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Nama</th>
                                <th>Hari</th>
                                <th>Waktu</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $jadwal = query("SELECT * FROM jadwal ORDER BY jenis, urutan, waktu");
                            if($jadwal && $jadwal->num_rows > 0):
                                while($row = $jadwal->fetch_assoc()):
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                    $badge_color = [
                                        'sholat' => '#2E8B57',
                                        'kegiatan' => '#3498db', 
                                        'pengajian' => '#9b59b6'
                                    ];
                                    ?>
                                    <span style="background: <?php echo $badge_color[$row['jenis']]; ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                        <?php echo ucfirst($row['jenis']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo $row['hari'] == 'setiap' ? 'Setiap Hari' : ucfirst($row['hari']); ?></td>
                                <td><?php echo date('H:i', strtotime($row['waktu'])); ?></td>
                                <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                <td>
                                    <a href="delete_jadwal.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Hapus jadwal ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #666;">Belum ada jadwal</td>
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
