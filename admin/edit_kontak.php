<?php
require_once 'auth_check.php';

$id = intval($_GET['id'] ?? 0);
$kontak = null;

if($id > 0) {
    $result = query("SELECT * FROM kontak WHERE id = $id");
    if($result && $result->num_rows > 0) {
        $kontak = $result->fetch_assoc();
    }
}

if(!$kontak) {
    $_SESSION['error'] = "Kontak tidak ditemukan!";
    header('Location: kontak.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? '';
    $jabatan = $_POST['jabatan'] ?? '';
    $telepon = $_POST['telepon'] ?? '';
    $wa_link = $_POST['wa_link'] ?? '';
    $urutan = intval($_POST['urutan'] ?? 0);
    
    if(!empty($nama) && !empty($jabatan) && !empty($telepon)) {
        $update = query("UPDATE kontak SET nama = '$nama', jabatan = '$jabatan', telepon = '$telepon', wa_link = '$wa_link', urutan = $urutan WHERE id = $id");
        
        if($update) {
            $_SESSION['success'] = "Kontak berhasil diupdate!";
            header('Location: kontak.php');
            exit;
        } else {
            $_SESSION['error'] = "Gagal mengupdate kontak!";
        }
    } else {
        $_SESSION['error'] = "Semua field harus diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kontak - <?php echo env('SITE_NAME'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰ Menu</button>
            
            <h1>✏️ Edit Kontak</h1>

            <div style="background: white; padding: 25px; border-radius: 10px;">
                <form method="POST">
                    <div class="form-group">
                        <label>Nama:</label>
                        <input type="text" name="nama" value="<?php echo htmlspecialchars($kontak['nama']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Jabatan:</label>
                        <input type="text" name="jabatan" value="<?php echo htmlspecialchars($kontak['jabatan']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nomor Telepon/WhatsApp:</label>
                        <input type="text" name="telepon" value="<?php echo htmlspecialchars($kontak['telepon']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>WhatsApp Link:</label>
                        <input type="text" name="wa_link" value="<?php echo htmlspecialchars($kontak['wa_link']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Urutan:</label>
                        <input type="number" name="urutan" value="<?php echo $kontak['urutan']; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Kontak</button>
                    <a href="kontak.php" class="btn btn-secondary">Kembali</a>
                </form>
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
