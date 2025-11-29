<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pengumuman - <?php echo env('SITE_NAME'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2E8B57; color: white; }
        .btn-edit { background: #3498db; color: white; padding: 5px 10px; font-size: 0.8rem; }
        .btn-delete { background: #e74c3c; color: white; padding: 5px 10px; font-size: 0.8rem; }
        .table { width: 100%; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table th, .table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .form-group { margin-bottom: 20px; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        textarea { height: 150px; resize: vertical; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰ Menu</button>
            
            <h1>📢 Manage Pengumuman</h1>

            <!-- Add New Form -->
            <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
                <h3>Tambah Pengumuman Baru</h3>
                <form method="POST" action="save_pengumuman.php">
                    <div class="form-group">
                        <label>Judul Pengumuman:</label>
                        <input type="text" name="judul" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Isi Pengumuman:</label>
                        <textarea name="isi" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="penting"> Tandai sebagai penting
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Pengumuman</button>
                </form>
            </div>

            <!-- Pengumuman List -->
            <div style="background: white; padding: 25px; border-radius: 10px;">
                <h3>Daftar Pengumuman</h3>
                <div class="table">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pengumuman = query("SELECT * FROM pengumuman ORDER BY created_at DESC");
                            if($pengumuman && $pengumuman->num_rows > 0):
                                while($row = $pengumuman->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                <td>
                                    <?php if($row['penting']): ?>
                                        <span style="color: #e74c3c; font-weight: bold;">PENTING</span>
                                    <?php else: ?>
                                        <span style="color: #27ae60;">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="edit_pengumuman.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                                    <a href="delete_pengumuman.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Hapus pengumuman?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">Belum ada pengumuman</td>
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