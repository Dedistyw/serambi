<?php 
require_once 'auth_check.php';

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    query("UPDATE galeri SET judul = '$judul', deskripsi = '$deskripsi' WHERE id = $id");
    header("Location: galeri.php?success=1");
    exit;
}

// Handle success message
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = '<div class="alert alert-success">Foto berhasil diperbarui!</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Galeri - <?php echo htmlspecialchars(env('SITE_NAME')); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .galeri-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
        }
        .galeri-item { 
            background: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .galeri-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .galeri-item img { 
            width: 100%; 
            height: 180px; 
            object-fit: cover; 
        }
        .galeri-item-content { 
            padding: 15px; 
        }
        .galeri-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn-edit {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
        }
        .btn-edit:hover {
            background: #2980b9;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .modal-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        .modal-close:hover {
            color: #333;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        @media (max-width: 768px) {
            .galeri-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            .modal-content {
                width: 95%;
                padding: 20px;
            }
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
            
            <h1>📷 Manage Galeri</h1>

            <!-- Success Message -->
            <?php echo $success_message; ?>

            <!-- Upload Form -->
            <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3>Upload Foto Baru</h3>
                <form method="POST" action="save_galeri.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Judul Foto:</label>
                        <input type="text" name="judul" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi:</label>
                        <textarea name="deskripsi"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Pilih Foto:</label>
                        <input type="file" name="gambar" accept="image/*" required>
                        <small>Format: JPG, PNG, GIF, WEBP. Maksimal 10MB</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Upload Foto</button>
                </form>
            </div>

            <!-- Galeri List -->
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3>Daftar Foto Galeri</h3>
                <div class="galeri-grid">
                    <?php
                    $galeri = query("SELECT * FROM galeri ORDER BY created_at DESC");
                    if($galeri && $galeri->num_rows > 0):
                        while($row = $galeri->fetch_assoc()):
                    ?>
                    <div class="galeri-item">
                        <img src="../uploads/<?php echo htmlspecialchars($row['gambar']); ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                        <div class="galeri-item-content">
                            <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                            <p><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                            <small><?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                            <div class="galeri-actions">
                                <button class="btn-edit" onclick="editGaleri(<?php echo $row['id']; ?>, '<?php echo addslashes($row['judul']); ?>', '<?php echo addslashes($row['deskripsi']); ?>')">✏️ Edit</button>
                                <a href="delete_galeri.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Hapus foto ini?')">🗑️ Hapus</a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                        <p>Belum ada foto di galeri</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Edit Foto Galeri</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="edit_id" id="editId">
                <div class="form-group">
                    <label>Judul Foto:</label>
                    <input type="text" name="judul" id="editJudul" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi:</label>
                    <textarea name="deskripsi" id="editDeskripsi"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        function editGaleri(id, judul, deskripsi) {
            document.getElementById('editId').value = id;
            document.getElementById('editJudul').value = judul;
            document.getElementById('editDeskripsi').value = deskripsi;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>