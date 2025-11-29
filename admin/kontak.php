<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Kontak - <?php echo env('SITE_NAME'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰ Menu</button>
            
            <h1>👥 Manage Kontak Takmir</h1>

            <!-- Add Contact Form -->
            <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
                <h3>Tambah Kontak Takmir</h3>
                <form method="POST" action="save_kontak.php">
                    <div class="form-group">
                        <label>Nama:</label>
                        <input type="text" name="nama" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Jabatan:</label>
                        <input type="text" name="jabatan" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nomor Telepon/WhatsApp:</label>
                        <input type="text" name="telepon" required placeholder="contoh: 081234567890">
                    </div>
                    
                    <div class="form-group">
                        <label>WhatsApp Link (opsional):</label>
                        <input type="text" name="wa_link" placeholder="contoh: https://wa.me/6281234567890">
                        <small>Biarkan kosong untuk generate otomatis dari nomor telepon</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Urutan:</label>
                        <input type="number" name="urutan" value="0">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Kontak</button>
                </form>
            </div>

            <!-- Contacts List -->
            <div style="background: white; padding: 25px; border-radius: 10px;">
                <h3>Daftar Kontak Takmir</h3>
                <div class="table">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Telepon</th>
                                <th>WhatsApp</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $kontak = query("SELECT * FROM kontak ORDER BY urutan, id");
                            if($kontak && $kontak->num_rows > 0):
                                while($row = $kontak->fetch_assoc()):
                                    $wa_link = $row['wa_link'];
                                    if(empty($wa_link) && !empty($row['telepon'])) {
                                        // Auto-generate WA link from phone number
                                        $clean_phone = preg_replace('/[^0-9]/', '', $row['telepon']);
                                        if(substr($clean_phone, 0, 2) === '08') {
                                            $clean_phone = '62' . substr($clean_phone, 1);
                                        }
                                        $wa_link = 'https://wa.me/' . $clean_phone;
                                    }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                                <td><?php echo htmlspecialchars($row['telepon']); ?></td>
                                <td>
                                    <?php if($wa_link): ?>
                                        <a href="<?php echo $wa_link; ?>" target="_blank" style="color: #25D366; text-decoration: none;">
                                            💬 WhatsApp
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #666;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_kontak.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                                    <a href="delete_kontak.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Hapus kontak ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">Belum ada kontak takmir</td>
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
        
        // Auto-generate WhatsApp link from phone number
        document.querySelector('input[name="telepon"]').addEventListener('blur', function() {
            const phone = this.value.trim();
            const waLinkInput = document.querySelector('input[name="wa_link"]');
            
            if(phone && !waLinkInput.value) {
                let cleanPhone = phone.replace(/[^0-9]/g, '');
                if(cleanPhone.startsWith('0')) {
                    cleanPhone = '62' + cleanPhone.substring(1);
                } else if(cleanPhone.startsWith('8')) {
                    cleanPhone = '62' + cleanPhone;
                }
                waLinkInput.value = 'https://wa.me/' + cleanPhone;
            }
        });
    </script>
</body>
</html>
