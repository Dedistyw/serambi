<?php
/**
 * CRUD Galeri Foto Masjid
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

Auth::requireLogin();

$page_title = 'Galeri Foto';
$success_msg = '';
$error_msg = '';

// Override PHP settings untuk upload file besar
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '128M');

// Proses CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Load data galeri
$galeri_data = getJSONData('galeri');

// Proses upload/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'upload') {
        // Upload foto baru
        $judul = sanitize($_POST['judul'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');
        
        if (empty($judul)) {
            $error_msg = 'Judul foto harus diisi';
        } elseif (!isset($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            $error_msg = 'File foto harus diupload';
        } else {
            // Handle upload error dengan detail
            $upload_error = $_FILES['foto']['error'];
            if ($upload_error !== UPLOAD_ERR_OK) {
                switch ($upload_error) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_msg = 'Ukuran file terlalu besar. Maksimal 10MB';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error_msg = 'File hanya terupload sebagian';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $error_msg = 'Folder temporary tidak ditemukan';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $error_msg = 'Gagal menulis file ke disk';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $error_msg = 'Ekstensi file tidak diizinkan';
                        break;
                    default:
                        $error_msg = 'Error upload tidak diketahui (Error Code: ' . $upload_error . ')';
                }
            } else {
                // Validasi ukuran file
                $max_size = 10 * 1024 * 1024; // 10MB
                if ($_FILES['foto']['size'] > $max_size) {
                    $error_msg = 'Ukuran file terlalu besar. Maksimal 10MB';
                } elseif (validateImage($_FILES['foto'])) {
                    $filename = uploadImage($_FILES['foto']);
                    if ($filename) {
                        $new_data = [
                            'id' => generateId(),
                            'judul' => $judul,
                            'deskripsi' => $deskripsi,
                            'gambar' => $filename,
                            'aktif' => isset($_POST['aktif']) ? 1 : 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'file_size' => formatBytes($_FILES['foto']['size'])
                        ];
                        
                        $galeri_data[] = $new_data;
                        
                        if (saveJSONData('galeri', $galeri_data)) {
                            logActivity('GALERI_UPLOAD', "Judul: {$judul}, File: {$filename}, Size: " . formatBytes($_FILES['foto']['size']));
                            $success_msg = 'Foto berhasil diupload ke galeri';
                            redirect('galeri.php', $success_msg);
                        } else {
                            // Hapus file yang sudah diupload jika gagal save
                            deleteImage($filename);
                            $error_msg = 'Gagal menyimpan data galeri';
                        }
                    } else {
                        $error_msg = 'Gagal mengupload file. Pastikan folder uploads/images memiliki permission yang benar';
                    }
                } else {
                    $error_msg = 'File tidak valid. Hanya JPG, PNG, GIF (maks 10MB)';
                }
            }
        }
        
    } elseif ($post_action === 'edit' && $id) {
        // Edit foto existing
        $judul = sanitize($_POST['judul'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');
        $old_filename = '';
        
        // Cari data lama
        $found = false;
        foreach ($galeri_data as $key => $item) {
            if ($item['id'] == $id) {
                $old_filename = $item['gambar'];
                $found = true;
                
                // Update data
                $galeri_data[$key]['judul'] = $judul;
                $galeri_data[$key]['deskripsi'] = $deskripsi;
                $galeri_data[$key]['aktif'] = isset($_POST['aktif']) ? 1 : 0;
                $galeri_data[$key]['updated_at'] = date('Y-m-d H:i:s');
                
                // Cek jika ada file baru diupload
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $upload_error = $_FILES['foto']['error'];
                    if ($upload_error !== UPLOAD_ERR_OK) {
                        switch ($upload_error) {
                            case UPLOAD_ERR_INI_SIZE:
                            case UPLOAD_ERR_FORM_SIZE:
                                $error_msg = 'Ukuran file terlalu besar. Maksimal 10MB';
                                break;
                            default:
                                $error_msg = 'Error upload file (Code: ' . $upload_error . ')';
                        }
                    } else {
                        $max_size = 10 * 1024 * 1024; // 10MB
                        if ($_FILES['foto']['size'] > $max_size) {
                            $error_msg = 'Ukuran file terlalu besar. Maksimal 10MB';
                        } elseif (validateImage($_FILES['foto'])) {
                            $new_filename = uploadImage($_FILES['foto']);
                            if ($new_filename) {
                                // Hapus file lama
                                deleteImage($old_filename);
                                $galeri_data[$key]['gambar'] = $new_filename;
                                $galeri_data[$key]['file_size'] = formatBytes($_FILES['foto']['size']);
                            } else {
                                $error_msg = 'Gagal mengupload file baru';
                                break;
                            }
                        } else {
                            $error_msg = 'File tidak valid. Hanya JPG, PNG, GIF (maks 10MB)';
                            break;
                        }
                    }
                }
                
                if (empty($error_msg) && saveJSONData('galeri', $galeri_data)) {
                    logActivity('GALERI_EDIT', "ID: {$id}, Judul: {$judul}");
                    $success_msg = 'Foto berhasil diupdate';
                    redirect('galeri.php', $success_msg);
                } elseif (!empty($error_msg)) {
                    // Error sudah di-set
                } else {
                    $error_msg = 'Gagal menyimpan perubahan';
                }
                break;
            }
        }
        
        if (!$found) {
            $error_msg = 'Foto tidak ditemukan';
        }
    }
}

// Hapus foto
if ($action === 'hapus' && $id) {
    if (checkCSRFToken($_GET['token'] ?? '')) {
        $found = false;
        foreach ($galeri_data as $key => $item) {
            if ($item['id'] == $id) {
                // Hapus file dari server
                if (isset($item['gambar'])) {
                    deleteImage($item['gambar']);
                }
                
                // Hapus dari array
                unset($galeri_data[$key]);
                $galeri_data = array_values($galeri_data);
                
                if (saveJSONData('galeri', $galeri_data)) {
                    logActivity('GALERI_HAPUS', "ID: {$id}");
                    redirect('galeri.php', 'Foto berhasil dihapus');
                } else {
                    $error_msg = 'Gagal menghapus foto';
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $error_msg = 'Foto tidak ditemukan';
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
    }
}

// Get data for edit
$edit_data = [];
if ($action === 'edit' && $id) {
    foreach ($galeri_data as $item) {
        if ($item['id'] == $id) {
            $edit_data = $item;
            break;
        }
    }
    if (empty($edit_data)) {
        $error_msg = 'Foto tidak ditemukan';
        $action = '';
    }
}

// Debug info (opsional, bisa dihapus di production)
if (isset($_GET['debug'])) {
    echo "<div style='background:#f8f9fa; padding:15px; margin:10px 0; border-radius:5px; border:1px solid #dee2e6;'>";
    echo "<h4>Debug Upload Info:</h4>";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
    echo "post_max_size: " . ini_get('post_max_size') . "<br>";
    echo "memory_limit: " . ini_get('memory_limit') . "<br>";
    echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
    echo "max_input_time: " . ini_get('max_input_time') . "<br>";
    echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
    
    // Check folder permissions
    $upload_dir = '../uploads/images/';
    echo "Upload Directory: " . realpath($upload_dir) . "<br>";
    echo "Directory exists: " . (file_exists($upload_dir) ? 'Yes' : 'No') . "<br>";
    echo "Directory writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "<br>";
    echo "Free space: " . formatBytes(disk_free_space($upload_dir)) . "<br>";
    echo "</div>";
}

include 'header.php';
?>

<div class="content-area">
    <?php if ($error_msg): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'tambah' || $action === 'edit'): ?>
        <!-- Form Upload/Edit Foto -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-images"></i>
                    <?php echo $action === 'edit' ? 'Edit Foto Galeri' : 'Upload Foto ke Galeri'; ?>
                </h3>
                <a href="galeri.php" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'edit' : 'upload'; ?>">
                    <input type="hidden" id="max_file_size" value="10485760"> <!-- 10MB in bytes -->
                    
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="judul" class="form-label">Judul Foto *</label>
                                <input type="text" 
                                       id="judul" 
                                       name="judul" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($edit_data['judul'] ?? ''); ?>"
                                       required
                                       placeholder="Contoh: Kegiatan Pengajian Rutin">
                            </div>
                            
                            <div class="form-group">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea id="deskripsi" 
                                          name="deskripsi" 
                                          class="form-control" 
                                          rows="4"
                                          placeholder="Deskripsi singkat tentang foto..."><?php echo htmlspecialchars($edit_data['deskripsi'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    Deskripsi akan ditampilkan di bawah judul pada galeri
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="foto" class="form-label">
                                    <?php echo $action === 'edit' ? 'Ganti Foto (kosongkan jika tidak ingin mengganti)' : 'Pilih Foto *'; ?>
                                </label>
                                <input type="file" 
                                       id="foto" 
                                       name="foto" 
                                       class="form-control"
                                       <?php echo $action === 'tambah' ? 'required' : ''; ?>
                                       accept="image/jpeg,image/png,image/gif,image/jpg,image/webp">
                                <div class="form-text">
                                    Format: JPG, PNG, GIF, WebP (maksimal 10MB). 
                                    <?php if ($action === 'edit' && isset($edit_data['gambar'])): ?>
                                        <br>File saat ini: <strong><?php echo htmlspecialchars($edit_data['gambar']); ?></strong>
                                        <?php if (isset($edit_data['file_size'])): ?>
                                            (<?php echo htmlspecialchars($edit_data['file_size']); ?>)
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="fileSizeInfo" class="text-muted mt-2" style="display:none;">
                                    <i class="fas fa-info-circle"></i>
                                    Ukuran file: <span id="fileSizeText"></span>
                                </div>
                            </div>
                            
                            <!-- Progress bar untuk upload -->
                            <div id="uploadProgress" style="display:none;">
                                <div class="progress mb-2">
                                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <div id="progressText" class="text-center small"></div>
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
                                            <div class="form-text">
                                                Foto nonaktif tidak akan ditampilkan di galeri publik
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Preview</label>
                                        <div class="preview-box" id="imagePreview">
                                            <?php if ($action === 'edit' && isset($edit_data['gambar'])): 
                                                $image_path = '../uploads/images/' . $edit_data['gambar'];
                                                if (file_exists($image_path)):
                                            ?>
                                                <img src="<?php echo $image_path . '?' . time(); ?>" 
                                                     alt="Preview" 
                                                     style="max-width: 100%; border-radius: 5px;">
                                            <?php else: ?>
                                                <div style="color: #dc3545; text-align: center; padding: 20px;">
                                                    <i class="fas fa-exclamation-triangle"></i><br>
                                                    File tidak ditemukan
                                                </div>
                                            <?php endif; ?>
                                            <?php else: ?>
                                                <div style="color: #6c757d; text-align: center; padding: 30px; border: 2px dashed #dee2e6; border-radius: 5px;">
                                                    <i class="fas fa-image fa-2x" style="margin-bottom: 10px;"></i><br>
                                                    Preview akan muncul di sini
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Info</label>
                                        <div class="info-box">
                                            <?php if ($action === 'edit'): ?>
                                                <p><strong>Dibuat:</strong> 
                                                    <?php echo isset($edit_data['created_at']) ? 
                                                        date('d/m/Y H:i', strtotime($edit_data['created_at'])) : 
                                                        '-'; ?>
                                                </p>
                                                <?php if (isset($edit_data['updated_at'])): ?>
                                                    <p><strong>Terakhir Update:</strong> 
                                                        <?php echo date('d/m/Y H:i', strtotime($edit_data['updated_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p><strong>Ukuran File:</strong> 
                                                    <?php 
                                                    if (isset($edit_data['gambar'])) {
                                                        $filepath = '../uploads/images/' . $edit_data['gambar'];
                                                        if (file_exists($filepath)) {
                                                            $size = filesize($filepath);
                                                            echo formatBytes($size);
                                                        } else {
                                                            echo '-';
                                                        }
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </p>
                                            <?php else: ?>
                                                <p><i class="fas fa-info-circle"></i> 
                                                    <strong>Ukuran Maksimal:</strong> 10MB<br>
                                                    <strong>Format:</strong> JPG, PNG, GIF, WebP<br>
                                                    <strong>Rekomendasi:</strong> Minimal 800x600px
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'edit' ? 'Update Foto' : 'Upload Foto'; ?>
                        </button>
                        <a href="galeri.php" class="btn btn-light">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Daftar Galeri -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-images"></i>
                    Galeri Foto Masjid
                </h3>
                <div class="card-actions">
                    <a href="?action=tambah" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Upload Foto
                    </a>
                    <a href="?debug=1" class="btn btn-sm btn-info" title="Debug Info">
                        <i class="fas fa-bug"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($galeri_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Belum ada foto di galeri. 
                        <a href="?action=tambah" class="alert-link">Upload foto pertama</a>
                    </div>
                <?php else: ?>
                    <!-- Filter dan Statistik -->
                    <div class="gallery-controls">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stats-box">
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <span class="stat-label">Total Foto:</span>
                                            <span class="stat-value"><?php echo count($galeri_data); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <?php 
                                            $aktif_count = count(array_filter($galeri_data, function($item) {
                                                return isset($item['aktif']) && $item['aktif'] == 1;
                                            }));
                                            ?>
                                            <span class="stat-label">Aktif:</span>
                                            <span class="stat-value text-success"><?php echo $aktif_count; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Nonaktif:</span>
                                            <span class="stat-value text-danger"><?php echo count($galeri_data) - $aktif_count; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-box">
                                    <label for="filterStatus" class="form-label">Filter Status:</label>
                                    <select id="filterStatus" class="form-control form-control-sm" onchange="filterGallery()">
                                        <option value="all">Semua</option>
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grid Galeri -->
                    <div class="gallery-grid" id="galleryGrid">
                        <?php 
                        // Urutkan dari terbaru
                        usort($galeri_data, function($a, $b) {
                            $time_a = strtotime($a['created_at'] ?? 0);
                            $time_b = strtotime($b['created_at'] ?? 0);
                            return $time_b - $time_a;
                        });
                        
                        foreach ($galeri_data as $item): 
                            $image_path = '../uploads/images/' . ($item['gambar'] ?? '');
                            $has_image = file_exists($image_path);
                        ?>
                            <div class="gallery-item" data-status="<?php echo isset($item['aktif']) && $item['aktif'] == 1 ? 'active' : 'inactive'; ?>">
                                <div class="gallery-image">
                                    <?php if ($has_image): ?>
                                        <img src="<?php echo $image_path . '?' . time(); ?>" 
                                             alt="<?php echo htmlspecialchars($item['judul'] ?? ''); ?>"
                                             onclick="viewImage('<?php echo $image_path; ?>', '<?php echo htmlspecialchars($item['judul'] ?? ''); ?>')">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                            <span>File tidak ditemukan</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="gallery-overlay">
                                        <div class="gallery-actions">
                                            <a href="?action=edit&id=<?php echo $item['id']; ?>" 
                                               class="btn-action btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" 
                                               onclick="confirmDelete('<?php echo $item['id']; ?>', '<?php echo htmlspecialchars($item['judul'] ?? ''); ?>')"
                                               class="btn-action btn-delete" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="gallery-info">
                                    <h6><?php echo htmlspecialchars($item['judul'] ?? 'Tanpa Judul'); ?></h6>
                                    <small class="text-muted">
                                        <?php echo isset($item['deskripsi']) ? 
                                            mb_substr(htmlspecialchars($item['deskripsi']), 0, 50) . '...' : 
                                            'Tanpa deskripsi'; ?>
                                    </small>
                                    
                                    <div class="gallery-meta">
                                        <div>
                                            <i class="far fa-calendar"></i>
                                            <?php echo isset($item['created_at']) ? date('d/m/Y', strtotime($item['created_at'])) : '-'; ?>
                                        </div>
                                        <div>
                                            <?php if (isset($item['aktif']) && $item['aktif'] == 1): ?>
                                                <span class="badge badge-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (isset($item['file_size'])): ?>
                                        <div class="file-size mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-hdd"></i> <?php echo htmlspecialchars($item['file_size']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Info Galeri -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        <strong>Tips Galeri:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Klik gambar untuk melihat ukuran penuh</li>
                            <li>Foto akan ditampilkan secara slideshow di halaman utama</li>
                            <li>Rekomendasi ukuran foto: minimal 800x600px</li>
                            <li>Hanya foto aktif yang akan ditampilkan di website</li>
                            <li>Maksimal ukuran file: 10MB</li>
                            <li>Format yang didukung: JPG, PNG, GIF, WebP</li>
                            <li>Total foto yang direkomendasikan: 10-20 foto untuk variasi</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal View Image -->
        <div class="modal" id="imageViewModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-header">
                    <h3 class="modal-title" id="imageViewTitle"></h3>
                    <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImageView" src="" alt="" style="max-width: 100%; max-height: 70vh; border-radius: 5px;">
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id, judul) {
    if (confirm(`Apakah Anda yakin ingin menghapus foto:\n"${judul}"?`)) {
        const token = '<?php echo generateCSRF(); ?>';
        window.location.href = `?action=hapus&id=${id}&token=${token}`;
    }
}

// Validasi ukuran file sebelum upload
function validateForm() {
    const fileInput = document.getElementById('foto');
    const maxSize = parseInt(document.getElementById('max_file_size').value);
    
    if (fileInput && fileInput.files.length > 0) {
        const fileSize = fileInput.files[0].size;
        if (fileSize > maxSize) {
            alert('Ukuran file terlalu besar. Maksimal 10MB');
            return false;
        }
        
        // Tampilkan progress bar
        document.getElementById('uploadProgress').style.display = 'block';
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengupload...';
        
        // Simulasi progress (untuk UI saja)
        let progress = 0;
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        
        const interval = setInterval(() => {
            progress += 10;
            if (progress > 90) progress = 90;
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            
            if (progress === 10) progressText.textContent = 'Mengupload file...';
            if (progress === 50) progressText.textContent = 'Memproses gambar...';
            if (progress === 80) progressText.textContent = 'Menyimpan data...';
        }, 500);
        
        // Clear interval setelah form submit
        setTimeout(() => {
            clearInterval(interval);
        }, 5000);
    }
    
    return true;
}

// Image preview for upload
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('foto');
    const previewBox = document.getElementById('imagePreview');
    const fileSizeInfo = document.getElementById('fileSizeInfo');
    const fileSizeText = document.getElementById('fileSizeText');
    
    if (fileInput && previewBox) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Tampilkan ukuran file
                const size = file.size;
                fileSizeText.textContent = formatBytes(size);
                fileSizeInfo.style.display = 'block';
                
                // Validasi ukuran
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (size > maxSize) {
                    fileSizeInfo.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> Ukuran file terlalu besar! Maksimal 10MB';
                    fileSizeInfo.className = 'text-danger mt-2';
                    return;
                } else {
                    fileSizeInfo.className = 'text-muted mt-2';
                }
                
                // Preview gambar
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBox.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; border-radius: 5px;">`;
                };
                reader.readAsDataURL(file);
            } else {
                fileSizeInfo.style.display = 'none';
            }
        });
    }
    
    // Filter gallery
    window.filterGallery = function() {
        const filter = document.getElementById('filterStatus').value;
        const items = document.querySelectorAll('.gallery-item');
        
        items.forEach(item => {
            if (filter === 'all' || item.getAttribute('data-status') === filter) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    };
});

// Format bytes untuk display
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// View image in modal
function viewImage(src, title) {
    document.getElementById('modalImageView').src = src;
    document.getElementById('imageViewTitle').textContent = title;
    document.getElementById('imageViewModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    const modalCloses = document.querySelectorAll('.modal-close, [data-dismiss="modal"]');
    
    // Close modal
    modalCloses.forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
});
</script>

<style>
.gallery-controls {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
    background: white;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.stat-label {
    font-size: 0.85em;
    color: #6c757d;
}

.stat-value {
    font-size: 1.2em;
    font-weight: bold;
    color: #2c3e50;
}

.filter-box {
    display: flex;
    flex-direction: column;
    height: 100%;
    justify-content: center;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gallery-item {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid #e1e5e9;
}

.gallery-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.gallery-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.gallery-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.gallery-item:hover .gallery-image img {
    transform: scale(1.05);
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #6c757d;
}

.no-image i {
    font-size: 2em;
    margin-bottom: 10px;
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gallery-item:hover .gallery-overlay {
    opacity: 1;
}

.gallery-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: transform 0.3s;
}

.btn-action:hover {
    transform: scale(1.1);
}

.btn-edit {
    background: #2E8B57;
}

.btn-delete {
    background: #dc3545;
}

.gallery-info {
    padding: 15px;
}

.gallery-info h6 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 0.95em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.gallery-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    font-size: 0.8em;
    color: #6c757d;
}

.file-size {
    font-size: 0.75em;
    border-top: 1px solid #f1f1f1;
    padding-top: 5px;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
    display: flex;
    gap: 10px;
}

.preview-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
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

.progress {
    height: 25px;
    border-radius: 5px;
}

.progress-bar {
    border-radius: 5px;
}

@media (max-width: 768px) {
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .gallery-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'footer.php'; ?>
