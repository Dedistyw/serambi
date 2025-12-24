<?php
/**
 * CRUD Mutiara Kata (Kutipan Islami)
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

Auth::requireLogin();

$page_title = 'Mutiara Kata';
$success_msg = '';
$error_msg = '';

// Proses CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Load data mutiara kata
$mutiara_data = getJSONData('mutiara_kata');

// Tambah/Edit Mutiara Kata
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $data = [
        'id' => $_POST['id'] ?? generateId(),
        'teks' => sanitize($_POST['teks'] ?? ''),
        'sumber' => sanitize($_POST['sumber'] ?? ''),
        'aktif' => isset($_POST['aktif']) ? 1 : 0,
        'created_at' => $_POST['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if (empty($data['teks'])) {
        $error_msg = 'Teks mutiara kata harus diisi';
    } else {
        if ($action === 'edit' && $id) {
            // Edit existing
            $found = false;
            foreach ($mutiara_data as $key => $item) {
                if ($item['id'] == $id) {
                    $data['created_at'] = $item['created_at'];
                    $mutiara_data[$key] = $data;
                    $found = true;
                    logActivity('MUTIARA_EDIT', "ID: {$id}");
                    break;
                }
            }
            if ($found) {
                $success_msg = 'Mutiara kata berhasil diupdate';
            } else {
                $error_msg = 'Mutiara kata tidak ditemukan';
            }
        } else {
            // Tambah baru
            $mutiara_data[] = $data;
            logActivity('MUTIARA_TAMBAH', "Teks: " . substr($data['teks'], 0, 50));
            $success_msg = 'Mutiara kata berhasil ditambahkan';
        }
        
        if (saveJSONData('mutiara_kata', $mutiara_data)) {
            if ($success_msg) {
                redirect('mutiara_kata.php', $success_msg);
            }
        } else {
            $error_msg = 'Gagal menyimpan mutiara kata';
        }
    }
}

// Hapus Mutiara Kata
if ($action === 'hapus' && $id) {
    if (checkCSRFToken($_GET['token'] ?? '')) {
        $found = false;
        foreach ($mutiara_data as $key => $item) {
            if ($item['id'] == $id) {
                unset($mutiara_data[$key]);
                $mutiara_data = array_values($mutiara_data);
                if (saveJSONData('mutiara_kata', $mutiara_data)) {
                    logActivity('MUTIARA_HAPUS', "ID: {$id}");
                    redirect('mutiara_kata.php', 'Mutiara kata berhasil dihapus');
                } else {
                    $error_msg = 'Gagal menghapus mutiara kata';
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $error_msg = 'Mutiara kata tidak ditemukan';
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
    }
}

// Get data for edit
$edit_data = [];
if ($action === 'edit' && $id) {
    foreach ($mutiara_data as $item) {
        if ($item['id'] == $id) {
            $edit_data = $item;
            break;
        }
    }
    if (empty($edit_data)) {
        $error_msg = 'Mutiara kata tidak ditemukan';
        $action = '';
    }
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
        <!-- Form Tambah/Edit -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-quote-right"></i>
                    <?php echo $action === 'edit' ? 'Edit Mutiara Kata' : 'Tambah Mutiara Kata Baru'; ?>
                </h3>
                <a href="mutiara_kata.php" class="btn btn-sm btn-light">
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
                                <label for="teks" class="form-label">Teks Mutiara Kata *</label>
                                <textarea id="teks" 
                                          name="teks" 
                                          class="form-control" 
                                          rows="5"
                                          required
                                          placeholder="Tulis kutipan atau mutiara kata Islami di sini..."><?php echo htmlspecialchars($edit_data['teks'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    Maksimal 500 karakter. Contoh: "Sebaik-baik manusia adalah yang paling bermanfaat bagi orang lain"
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sumber" class="form-label">Sumber</label>
                                <input type="text" 
                                       id="sumber" 
                                       name="sumber" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($edit_data['sumber'] ?? ''); ?>"
                                       placeholder="Contoh: HR. Ahmad, Al-Qur'an Surah Al-Baqarah: 286, atau Nama Ulama">
                                <div class="form-text">
                                    Kosongkan jika tidak ada sumber spesifik
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
                                            <div class="form-text">
                                                Hanya mutiara kata aktif yang akan ditampilkan secara acak di header website
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Preview</label>
                                        <div class="preview-box">
                                            <div style="font-style: italic; color: #555; margin-bottom: 5px;">
                                                "<?php echo isset($edit_data['teks']) ? 
                                                    htmlspecialchars($edit_data['teks']) : 
                                                    '[Teks mutiara kata akan muncul di sini]'; ?>"
                                            </div>
                                            <?php if (isset($edit_data['sumber']) && $edit_data['sumber']): ?>
                                                <div style="text-align: right; font-size: 0.9em; color: #777;">
                                                    ~ <?php echo htmlspecialchars($edit_data['sumber']); ?> ~
                                                </div>
                                            <?php endif; ?>
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
                            <?php echo $action === 'edit' ? 'Update Mutiara Kata' : 'Simpan Mutiara Kata'; ?>
                        </button>
                        <a href="mutiara_kata.php" class="btn btn-light">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Daftar Mutiara Kata -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-quote-right"></i>
                    Daftar Mutiara Kata
                </h3>
                <div class="card-actions">
                    <a href="?action=tambah" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Mutiara Kata
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($mutiara_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Belum ada mutiara kata. 
                        <a href="?action=tambah" class="alert-link">Tambah mutiara kata pertama</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Teks Mutiara Kata</th>
                                    <th width="120">Sumber</th>
                                    <th width="100">Status</th>
                                    <th width="150">Tanggal</th>
                                    <th width="150" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $aktif_count = 0;
                                foreach ($mutiara_data as $index => $item): 
                                    if (isset($item['aktif']) && $item['aktif'] == 1) $aktif_count++;
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div style="font-style: italic; color: #555;">
                                                "<?php echo htmlspecialchars($item['teks'] ?? ''); ?>"
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (isset($item['sumber']) && $item['sumber']): ?>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($item['sumber']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($item['aktif']) && $item['aktif'] == 1): ?>
                                                <span class="badge badge-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.85em;">
                                                <?php echo isset($item['created_at']) ? date('d/m/Y', strtotime($item['created_at'])) : '-'; ?>
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
                                                   onclick="confirmDelete('<?php echo $item['id']; ?>')"
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="stats-summary mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stat-box">
                                    <h5>Statistik Mutiara Kata</h5>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <span class="stat-label">Total:</span>
                                            <span class="stat-value"><?php echo count($mutiara_data); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Aktif:</span>
                                            <span class="stat-value text-success"><?php echo $aktif_count; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Nonaktif:</span>
                                            <span class="stat-value text-danger"><?php echo count($mutiara_data) - $aktif_count; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Tips:</strong> Mutiara kata aktif akan ditampilkan secara acak di header website. 
                                    Minimal 3-5 mutiara kata aktif untuk variasi tampilan.
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus mutiara kata ini?')) {
        const token = '<?php echo generateCSRF(); ?>';
        window.location.href = `?action=hapus&id=${id}&token=${token}`;
    }
}

// Live preview
document.addEventListener('DOMContentLoaded', function() {
    const teksInput = document.getElementById('teks');
    const sumberInput = document.getElementById('sumber');
    const previewBox = document.querySelector('.preview-box');
    
    function updatePreview() {
        if (!previewBox) return;
        
        const teks = teksInput ? teksInput.value : '';
        const sumber = sumberInput ? sumberInput.value : '';
        
        const teksHtml = teks ? 
            `<div style="font-style: italic; color: #555; margin-bottom: 5px;">"${teks}"</div>` : 
            '<div style="color: #999; font-style: italic;">[Teks mutiara kata akan muncul di sini]</div>';
        
        const sumberHtml = sumber ? 
            `<div style="text-align: right; font-size: 0.9em; color: #777;">~ ${sumber} ~</div>` : '';
        
        previewBox.innerHTML = teksHtml + sumberHtml;
    }
    
    if (teksInput) teksInput.addEventListener('input', updatePreview);
    if (sumberInput) sumberInput.addEventListener('input', updatePreview);
    
    // Initial preview
    updatePreview();
    
    // Auto-resize textarea
    if (teksInput) {
        teksInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        teksInput.dispatchEvent(new Event('input'));
    }
});
</script>

<style>
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
    min-height: 100px;
    font-style: italic;
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

.stats-summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.stat-box h5 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.stat-label {
    font-size: 0.9em;
    color: #6c757d;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 1.5em;
    font-weight: bold;
    color: #2c3e50;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'footer.php'; ?>
