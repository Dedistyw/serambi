<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Mutiara Kata - <?php echo env('SITE_NAME'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        textarea { height: 100px; resize: vertical; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰ Menu</button>
            
            <h1>📖 Manage Mutiara Kata</h1>

            <!-- Add Quote Form -->
            <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
                <h3>Tambah Mutiara Kata</h3>
                <form method="POST" action="save_mutiara_kata.php">
                    <div class="form-group">
                        <label>Kata-kata (Ayat/Hadist):</label>
                        <textarea name="teks" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Sumber (QS. ... / HR. ...):</label>
                        <input type="text" name="sumber" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Urutan:</label>
                        <input type="number" name="urutan" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="aktif" checked> Aktif (ditampilkan di website)
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Mutiara Kata</button>
                </form>
            </div>

            <!-- Quotes List -->
            <div style="background: white; padding: 25px; border-radius: 10px;">
                <h3>Daftar Mutiara Kata</h3>
                <div class="table">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Kata-kata</th>
                                <th>Sumber</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $quotes = query("SELECT * FROM mutiara_kata ORDER BY urutan, created_at DESC");
                            if($quotes && $quotes->num_rows > 0):
                                while($row = $quotes->fetch_assoc()):
                            ?>
                            <tr>
                                <td style="max-width: 300px;"><?php echo htmlspecialchars($row['teks']); ?></td>
                                <td><?php echo htmlspecialchars($row['sumber']); ?></td>
                                <td>
                                    <?php if($row['aktif']): ?>
                                        <span style="color: #27ae60; font-weight: bold;">Aktif</span>
                                    <?php else: ?>
                                        <span style="color: #666;">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="delete_mutiara_kata.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Hapus mutiara kata ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">Belum ada mutiara kata</td>
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
