<?php
/**
 * CRUD Pengumuman Masjid
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

Auth::requireLogin();

$page_title = 'Pengumuman';
$success_msg = '';
$error_msg = '';

// Proses CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Load data pengumuman
$pengumuman_data = getJSONData('pengumuman');

// Tambah/Edit Pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $data = [
        'id' => $_POST['id'] ?? generateId(),
        'judul' => sanitize($_POST['judul'] ?? ''),
        'isi' => sanitize($_POST['isi'] ?? ''),
        'penting' => isset($_POST['penting']) ? 1 : 0,
        'aktif' => isset($_POST['aktif']) ? 1 : 0,
        'tanggal_berlaku' => !empty($_POST['tanggal_berlaku']) ? $_POST['tanggal_berlaku'] : null,
        'created_at' => $_POST['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if (empty($data['judul'])) {
        $error_msg = 'Judul pengumuman harus diisi';
    } else {
        if ($action === 'edit' && $id) {
            // Edit existing
            $found = false;
            foreach ($pengumuman_data as $key => $item) {
                if ($item['id'] == $id) {
                    $data['created_at'] = $item['created_at']; // Pertahankan created_at
                    $pengumuman_data[$key] = $data;
                    $found = true;
                    logActivity('PENGUMUMAN_EDIT', "ID: {$id}, Judul: {$data['judul']}");
                    break;
                }
            }
            if ($found) {
                $success_msg = 'Pengumuman berhasil diupdate';
            } else {
                $error_msg = 'Pengumuman tidak ditemukan';
            }
        } else {
            // Tambah baru
            $pengumuman_data[] = $data;
            logActivity('PENGUMUMAN_TAMBAH', "Judul: {$data['judul']}");
            $success_msg = 'Pengumuman berhasil ditambahkan';
        }
        
        if (saveJSONData('pengumuman', $pengumuman_data)) {
            if ($success_msg) {
                redirect('pengumuman.php', $success_msg);
            }
        } else {
            $error_msg = 'Gagal menyimpan pengumuman';
        }
    }
}

// Hapus Pengumuman
if ($action === 'hapus' && $id) {
    if (checkCSRFToken($_GET['token'] ?? '')) {
        $found = false;
        foreach ($pengumuman_data as $key => $item) {
            if ($item['id'] == $id) {
                $judul = $item['judul'];
                unset($pengumuman_data[$key]);
                $pengumuman_data = array_values($pengumuman_data); // Reset index
                if (saveJSONData('pengumuman', $pengumuman_data)) {
                    logActivity('PENGUMUMAN_HAPUS', "ID: {$id}, Judul: {$judul}");
                    redirect('pengumuman.php', 'Pengumuman berhasil dihapus');
                } else {
                    $error_msg = 'Gagal menghapus pengumuman';
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $error_msg = 'Pengumuman tidak ditemukan';
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
    }
}

// Proses Upload File Jadwal
if (isset($_POST['upload_jadwal'])) {
    checkCSRF();
    
    $jadwal_type = $_POST['jadwal_type'] ?? '';
    $allowed_types = ['khotbah', 'takjil'];
    
    if (in_array($jadwal_type, $allowed_types) && isset($_FILES['jadwal_file']) && $_FILES['jadwal_file']['error'] === 0) {
        $file = $_FILES['jadwal_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $file_size = $file['size'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if ($file_size <= $max_size) {
                // Tentukan nama file berdasarkan tipe
                $filename = $jadwal_type === 'khotbah' ? 'jadwal-khotbah.jpg' : 'jadwal-takjil.jpg';
                $upload_path = '../assets/images/' . $filename;
                
                // Coba upload
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $success_msg = 'File ' . ($jadwal_type === 'khotbah' ? 'Jadwal Khotbah' : 'Jadwal Takjil') . ' berhasil diupload';
                } else {
                    $error_msg = 'Gagal mengupload file. Pastikan folder assets/images memiliki izin write.';
                }
            } else {
                $error_msg = 'Ukuran file terlalu besar. Maksimal 5MB';
            }
        } else {
            $error_msg = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP';
        }
    } else {
        $error_msg = 'Silakan pilih file dan jenis jadwal yang akan diupload';
    }
}

// Get data for edit
$edit_data = [];
if ($action === 'edit' && $id) {
    foreach ($pengumuman_data as $item) {
        if ($item['id'] == $id) {
            $edit_data = $item;
            break;
        }
    }
    if (empty($edit_data)) {
        $error_msg = 'Pengumuman tidak ditemukan';
        $action = '';
    }
}

// Urutkan pengumuman berdasarkan tanggal dibuat (terbaru dulu)
usort($pengumuman_data, function($a, $b) {
    $time_a = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
    $time_b = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
    return $time_b - $time_a;
});

// Ambil 5 pengumuman terbaru untuk tampilan awal
$recent_pengumuman = array_slice($pengumuman_data, 0, 5);
$total_pengumuman = count($pengumuman_data);

include 'header.php';
?>

<div class="content-area">
    <?php if ($error_msg): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'tambah' || $action === 'edit'): ?>
        <!-- Form Tambah/Edit -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bullhorn"></i>
                    <?php echo $action === 'edit' ? 'Edit Pengumuman' : 'Tambah Pengumuman Baru'; ?>
                </h3>
                <a href="pengumuman.php" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? generateId(); ?>">
                    
                    <?php if (isset($edit_data['created_at'])): ?>
                        <input type="hidden" name="created_at" value="<?php echo $edit_data['created_at']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="judul" class="form-label">Judul Pengumuman *</label>
                                <input type="text" 
                                       id="judul" 
                                       name="judul" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($edit_data['judul'] ?? ''); ?>"
                                       required
                                       placeholder="Contoh: Pengajian Rutin Jumat Malam">
                            </div>
                            
                            <div class="form-group">
                                <label for="isi" class="form-label">Isi Pengumuman *</label>
                                <textarea id="isi" 
                                          name="isi" 
                                          class="form-control" 
                                          rows="8"
                                          required
                                          placeholder="Tulis isi lengkap pengumuman di sini..."><?php echo htmlspecialchars($edit_data['isi'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    Gunakan bahasa yang jelas dan mudah dipahami oleh jamaah.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card" style="border: 1px solid #dee2e6;">
                                <div class="card-body">
                                    <h5 style="margin-bottom: 20px; color: #2c3e50;">
                                        <i class="fas fa-cog"></i> Pengaturan
                                    </h5>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="aktif" 
                                                   name="aktif" 
                                                   value="1"
                                                   <?php echo isset($edit_data['aktif']) && $edit_data['aktif'] == 1 ? 'checked' : 'checked'; ?>>
                                            <label class="form-check-label" for="aktif">
                                                Aktif (ditampilkan di website)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="penting" 
                                                   name="penting" 
                                                   value="1"
                                                   <?php echo isset($edit_data['penting']) && $edit_data['penting'] == 1 ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="penting">
                                                Tandai sebagai PENTING
                                            </label>
                                            <div class="form-text">
                                                Akan ditampilkan dengan tanda seru merah
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_berlaku" class="form-label">Tanggal Berlaku</label>
                                        <input type="date" 
                                               id="tanggal_berlaku" 
                                               name="tanggal_berlaku" 
                                               class="form-control"
                                               value="<?php echo $edit_data['tanggal_berlaku'] ?? ''; ?>">
                                        <div class="form-text">
                                            Kosongkan jika tidak ada batas waktu
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Info</label>
                                        <div class="info-box">
                                            <p><strong>Dibuat:</strong> 
                                                <?php echo isset($edit_data['created_at']) ? 
                                                    date('d/m/Y H:i', strtotime($edit_data['created_at'])) : 
                                                    'Baru'; ?>
                                            </p>
                                            <?php if (isset($edit_data['updated_at'])): ?>
                                                <p><strong>Terakhir Update:</strong> 
                                                    <?php echo date('d/m/Y H:i', strtotime($edit_data['updated_at'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'edit' ? 'Update Pengumuman' : 'Simpan Pengumuman'; ?>
                        </button>
                        <a href="pengumuman.php" class="btn btn-light">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Daftar Pengumuman -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bullhorn"></i>
                    Daftar Pengumuman
                </h3>
                <div class="card-actions">
                    <a href="?action=tambah" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Pengumuman
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($pengumuman_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Belum ada pengumuman. 
                        <a href="?action=tambah" class="alert-link">Tambah pengumuman pertama</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <div class="pengumuman-container" id="pengumumanContainer">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Judul</th>
                                        <th width="120">Status</th>
                                        <th width="150">Tanggal</th>
                                        <th width="150" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="pengumumanBody">
                                    <?php foreach ($recent_pengumuman as $index => $item): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <div style="font-weight: 500;">
                                                    <?php if (isset($item['penting']) && $item['penting'] == 1): ?>
                                                        <span class="badge badge-danger" style="margin-right: 8px;">PENTING</span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($item['judul'] ?? 'Tanpa Judul'); ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo isset($item['isi']) ? mb_substr(strip_tags($item['isi']), 0, 100) . '...' : ''; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if (isset($item['aktif']) && $item['aktif'] == 1): ?>
                                                    <span class="badge badge-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($item['tanggal_berlaku']) && $item['tanggal_berlaku']): ?>
                                                    <div style="margin-top: 5px; font-size: 0.8em;">
                                                        <i class="far fa-calendar"></i> 
                                                        <?php echo date('d/m/Y', strtotime($item['tanggal_berlaku'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.85em;">
                                                    <div><i class="far fa-calendar-plus"></i> 
                                                        <?php echo isset($item['created_at']) ? date('d/m/Y', strtotime($item['created_at'])) : '-'; ?>
                                                    </div>
                                                    <?php if (isset($item['updated_at'])): ?>
                                                        <div style="margin-top: 3px; color: #6c757d;">
                                                            <i class="far fa-edit"></i> 
                                                            <?php echo date('d/m/Y', strtotime($item['updated_at'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="?action=edit&id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" 
                                                       onclick="confirmDelete('<?php echo $item['id']; ?>', '<?php echo htmlspecialchars($item['judul'] ?? ''); ?>')"
                                                       class="btn btn-sm btn-outline-danger"
                                                       title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Data tambahan yang akan ditampilkan saat scroll -->
                                    <?php if ($total_pengumuman > 5): ?>
                                        <?php for ($i = 5; $i < $total_pengumuman; $i++): ?>
                                            <?php $item = $pengumuman_data[$i]; ?>
                                            <tr class="hidden-pengumuman" style="display: none;">
                                                <td><?php echo $i + 1; ?></td>
                                                <td>
                                                    <div style="font-weight: 500;">
                                                        <?php if (isset($item['penting']) && $item['penting'] == 1): ?>
                                                            <span class="badge badge-danger" style="margin-right: 8px;">PENTING</span>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($item['judul'] ?? 'Tanpa Judul'); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo isset($item['isi']) ? mb_substr(strip_tags($item['isi']), 0, 100) . '...' : ''; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if (isset($item['aktif']) && $item['aktif'] == 1): ?>
                                                        <span class="badge badge-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Nonaktif</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($item['tanggal_berlaku']) && $item['tanggal_berlaku']): ?>
                                                        <div style="margin-top: 5px; font-size: 0.8em;">
                                                            <i class="far fa-calendar"></i> 
                                                            <?php echo date('d/m/Y', strtotime($item['tanggal_berlaku'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-size: 0.85em;">
                                                        <div><i class="far fa-calendar-plus"></i> 
                                                            <?php echo isset($item['created_at']) ? date('d/m/Y', strtotime($item['created_at'])) : '-'; ?>
                                                        </div>
                                                        <?php if (isset($item['updated_at'])): ?>
                                                            <div style="margin-top: 3px; color: #6c757d;">
                                                                <i class="far fa-edit"></i> 
                                                                <?php echo date('d/m/Y', strtotime($item['updated_at'])); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <a href="?action=edit&id=<?php echo $item['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" 
                                                           onclick="confirmDelete('<?php echo $item['id']; ?>', '<?php echo htmlspecialchars($item['judul'] ?? ''); ?>')"
                                                           class="btn btn-sm btn-outline-danger"
                                                           title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <?php if ($total_pengumuman > 5): ?>
                                <div class="text-center mt-3 mb-3">
                                    <div id="loadMoreIndicator" style="display: none;">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        <span style="margin-left: 10px; color: #6c757d;">Memuat pengumuman...</span>
                                    </div>
                                    <button id="loadMoreBtn" class="btn btn-outline-primary">
                                        <i class="fas fa-chevron-down"></i> Tampilkan Lebih Banyak (<?php echo $total_pengumuman - 5; ?> tersembunyi)
                                    </button>
                                    <button id="showAllBtn" class="btn btn-link" style="display: none;">
                                        <i class="fas fa-eye"></i> Tampilkan Semua
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Upload File Jadwal -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-images"></i>
                    Upload File Jadwal
                </h3>
                <div class="card-actions">
                    <span class="badge badge-info">Preview file yang akan tampil di halaman utama</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card preview-card">
                            <div class="card-body">
                                <h5>Jadwal Khotbah Jumat</h5>
                                <p class="text-muted">Tampil di atas menu Pengumuman & Waktu Sholat</p>
                                
                                <?php
                                $khotbah_path = '../assets/images/jadwal-khotbah.jpg';
                                $khotbah_exists = file_exists($khotbah_path);
                                ?>
                                
                                <div class="preview-container">
                                    <?php if ($khotbah_exists): ?>
                                        <img src="<?php echo $khotbah_path . '?t=' . time(); ?>" 
                                             class="preview-image"
                                             alt="Preview Jadwal Khotbah"
                                             onclick="openPreview('<?php echo $khotbah_path; ?>', 'Jadwal Khotbah Jumat')">
                                        <div class="file-info">
                                            <i class="fas fa-check-circle text-success"></i>
                                            File tersedia
                                            <small>(<?php echo date('d/m/Y H:i', filemtime($khotbah_path)); ?>)</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                            <p>Belum ada file</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="POST" enctype="multipart/form-data" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                    <input type="hidden" name="jadwal_type" value="khotbah">
                                    
                                    <div class="form-group">
                                        <label for="khotbahFile" class="form-label">Upload File Baru</label>
                                        <input type="file" 
                                               id="khotbahFile" 
                                               name="jadwal_file" 
                                               class="form-control-file"
                                               accept=".jpg,.jpeg,.png,.gif,.webp">
                                        <div class="form-text">
                                            Ukuran maksimal 5MB. Format: JPG, PNG, GIF, WebP
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="upload_jadwal" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload Jadwal Khotbah
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card preview-card">
                            <div class="card-body">
                                <h5>Jadwal Takjil Ramadhan</h5>
                                <p class="text-muted">Tampil di atas menu Keuangan & Kontak</p>
                                
                                <?php
                                $takjil_path = '../assets/images/jadwal-takjil.jpg';
                                $takjil_exists = file_exists($takjil_path);
                                ?>
                                
                                <div class="preview-container">
                                    <?php if ($takjil_exists): ?>
                                        <img src="<?php echo $takjil_path . '?t=' . time(); ?>" 
                                             class="preview-image"
                                             alt="Preview Jadwal Takjil"
                                             onclick="openPreview('<?php echo $takjil_path; ?>', 'Jadwal Takjil Ramadhan')">
                                        <div class="file-info">
                                            <i class="fas fa-check-circle text-success"></i>
                                            File tersedia
                                            <small>(<?php echo date('d/m/Y H:i', filemtime($takjil_path)); ?>)</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                            <p>Belum ada file</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="POST" enctype="multipart/form-data" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                    <input type="hidden" name="jadwal_type" value="takjil">
                                    
                                    <div class="form-group">
                                        <label for="takjilFile" class="form-label">Upload File Baru</label>
                                        <input type="file" 
                                               id="takjilFile" 
                                               name="jadwal_file" 
                                               class="form-control-file"
                                               accept=".jpg,.jpeg,.png,.gif,.webp">
                                        <div class="form-text">
                                            Ukuran maksimal 5MB. Format: JPG, PNG, GIF, WebP
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="upload_jadwal" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload Jadwal Takjil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Catatan:</strong> File akan otomatis menggantikan file yang lama dengan nama yang sama. 
                    Pastikan gambar memiliki rasio 16:9 untuk tampilan optimal di halaman utama.
                </div>
            </div>
        </div>
        
        <!-- Preview Modal -->
        <div id="previewModal" class="modal-preview">
            <button class="modal-close" onclick="closePreview()">&times;</button>
            <img class="modal-content-preview" id="previewImage">
            <div class="modal-title" id="previewTitle"></div>
        </div>
        
    <?php endif; ?>
</div>

<script>
function confirmDelete(id, judul) {
    if (confirm(`Apakah Anda yakin ingin menghapus pengumuman:\n"${judul}"?`)) {
        const token = '<?php echo generateCSRF(); ?>';
        window.location.href = `?action=hapus&id=${id}&token=${token}`;
    }
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('isi');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Trigger once on load
        textarea.dispatchEvent(new Event('input'));
    }
    
    // Set min date untuk tanggal_berlaku ke hari ini
    const tanggalInput = document.getElementById('tanggal_berlaku');
    if (tanggalInput) {
        const today = new Date().toISOString().split('T')[0];
        tanggalInput.min = today;
    }
    
    // Load more functionality
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const showAllBtn = document.getElementById('showAllBtn');
    const hiddenRows = document.querySelectorAll('.hidden-pengumuman');
    const loadMoreIndicator = document.getElementById('loadMoreIndicator');
    
    if (loadMoreBtn && hiddenRows.length > 0) {
        let currentIndex = 0;
        const batchSize = 5; // Tampilkan 5 baris sekaligus
        
        loadMoreBtn.addEventListener('click', function() {
            loadMoreIndicator.style.display = 'block';
            loadMoreBtn.style.display = 'none';
            
            setTimeout(function() {
                let loaded = 0;
                for (let i = currentIndex; i < Math.min(currentIndex + batchSize, hiddenRows.length); i++) {
                    hiddenRows[i].style.display = 'table-row';
                    loaded++;
                }
                currentIndex += batchSize;
                
                loadMoreIndicator.style.display = 'none';
                
                if (currentIndex < hiddenRows.length) {
                    loadMoreBtn.style.display = 'block';
                    loadMoreBtn.innerHTML = `<i class="fas fa-chevron-down"></i> Tampilkan Lebih Banyak (${hiddenRows.length - currentIndex} tersembunyi)`;
                } else {
                    showAllBtn.style.display = 'inline-block';
                }
            }, 300); // Simulasi loading
        });
        
        showAllBtn.addEventListener('click', function() {
            loadMoreBtn.style.display = 'none';
            showAllBtn.style.display = 'none';
            loadMoreIndicator.style.display = 'block';
            
            setTimeout(function() {
                hiddenRows.forEach(row => {
                    row.style.display = 'table-row';
                });
                loadMoreIndicator.style.display = 'none';
            }, 300);
        });
    }
});

// Preview modal functions
function openPreview(imageSrc, title) {
    const modal = document.getElementById('previewModal');
    const previewImg = document.getElementById('previewImage');
    const previewTitle = document.getElementById('previewTitle');
    
    previewImg.src = imageSrc;
    previewTitle.textContent = title;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePreview();
    }
});

// Close modal with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreview();
    }
});

// Preview image before upload
document.getElementById('khotbahFile')?.addEventListener('change', function(e) {
    previewFile(this, 'khotbah');
});

document.getElementById('takjilFile')?.addEventListener('change', function(e) {
    previewFile(this, 'takjil');
});

function previewFile(input, type) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const previewContainer = input.closest('.preview-card').querySelector('.preview-container');
        
        reader.onload = function(e) {
            previewContainer.innerHTML = `
                <img src="${e.target.result}" 
                     class="preview-image" 
                     alt="Preview ${type === 'khotbah' ? 'Jadwal Khotbah' : 'Jadwal Takjil'}"
                     onclick="openPreview('${e.target.result}', '${type === 'khotbah' ? 'Jadwal Khotbah Jumat' : 'Jadwal Takjil Ramadhan'} (Preview)')">
                <div class="file-info">
                    <i class="fas fa-eye text-info"></i>
                    Preview file baru
                </div>
            `;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
    display: flex;
    gap: 10px;
}

.info-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    font-size: 0.9em;
}

.info-box p {
    margin: 0 0 8px 0;
}

.info-box p:last-child {
    margin-bottom: 0;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn-group .btn {
    padding: 6px 10px;
}

/* Preview card styles */
.preview-card {
    height: 100%;
    border: 1px solid #e1e5e9;
}

.preview-container {
    width: 100%;
    height: 200px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    border: 1px dashed #dee2e6;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin-bottom: 15px;
    position: relative;
}

.preview-image {
    max-width: 100%;
    max-height: 180px;
    object-fit: contain;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.preview-image:hover {
    transform: scale(1.05);
}

.no-image {
    text-align: center;
    color: #6c757d;
}

.no-image i {
    margin-bottom: 10px;
}

.file-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.9);
    padding: 8px 15px;
    font-size: 0.85em;
    text-align: center;
    border-top: 1px solid #dee2e6;
}

/* Modal preview */
.modal-preview {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s;
}

.modal-content-preview {
    max-width: 90%;
    max-height: 80%;
    border-radius: 10px;
    animation: zoomIn 0.3s;
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 40px;
    cursor: pointer;
    background: none;
    border: none;
    z-index: 10000;
}

.modal-title {
    position: absolute;
    bottom: 20px;
    left: 0;
    right: 0;
    text-align: center;
    color: white;
    font-size: 1.2em;
    padding: 15px;
    background: rgba(0,0,0,0.7);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes zoomIn {
    from { transform: scale(0.8); }
    to { transform: scale(1); }
}

/* Pengumuman table with scroll */
.pengumuman-container {
    max-height: 600px;
    overflow-y: auto;
    padding-right: 10px;
}

.pengumuman-container::-webkit-scrollbar {
    width: 8px;
}

.pengumuman-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.pengumuman-container::-webkit-scrollbar-thumb {
    background: #2E8B57;
    border-radius: 10px;
}

.pengumuman-container::-webkit-scrollbar-thumb:hover {
    background: #26734a;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .preview-card {
        margin-bottom: 20px;
    }
    
    .modal-content-preview {
        max-width: 95%;
        max-height: 70%;
    }
}
</style>

<?php include 'footer.php'; ?>
