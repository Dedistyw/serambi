<?php
/**
 * Settings Admin - Ganti Password & Pengaturan Sistem
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

Auth::requireLogin();

$page_title = 'Settings Admin';
$success_msg = '';
$error_msg = '';

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validasi
        if (empty($current_password)) {
            $error_msg = 'Password saat ini harus diisi';
        } elseif (empty($new_password)) {
            $error_msg = 'Password baru harus diisi';
        } elseif (strlen($new_password) < 6) {
            $error_msg = 'Password baru minimal 6 karakter';
        } elseif ($new_password !== $confirm_password) {
            $error_msg = 'Konfirmasi password tidak cocok';
        } else {
            // Ganti password
            if (Auth::changePassword($current_password, $new_password)) {
                $success_msg = 'Password berhasil diubah';
                redirect('settings.php', $success_msg);
            } else {
                $error_msg = 'Password saat ini salah';
            }
        }
    }
    
    // Restore backup (tambahkan setelah change password)
    if ($action === 'restore') {
        $backup_name = $_POST['backup_name'] ?? '';
        
        if (empty($backup_name)) {
            $error_msg = 'Nama backup tidak valid';
            redirect('settings.php', $error_msg, 'error');
        }
        
        $backup_path = '../backups/' . $backup_name;
        $data_dir = '../uploads/data/';
        
        if (!is_dir($backup_path)) {
            $error_msg = 'Backup tidak ditemukan';
            redirect('settings.php', $error_msg, 'error');
        }
        
        // Backup data saat ini sebelum restore
        $timestamp = date('Y-m-d_H-i-s');
        $pre_restore_backup = '../backups/pre_restore_' . $timestamp;
        
        if (!file_exists($pre_restore_backup)) {
            mkdir($pre_restore_backup, 0755, true);
        }
        
        // Backup file data saat ini
        $current_files = glob($data_dir . '*.json');
        $backup_count = 0;
        foreach ($current_files as $file) {
            $filename = basename($file);
            if (copy($file, $pre_restore_backup . '/' . $filename)) {
                $backup_count++;
            }
        }
        
        // Clear current data files
        foreach ($current_files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Restore from backup
        $backup_files = glob($backup_path . '/*.json');
        $restored_count = 0;
        
        foreach ($backup_files as $file) {
            $filename = basename($file);
            if (copy($file, $data_dir . $filename)) {
                $restored_count++;
            }
        }
        
        if ($restored_count > 0) {
            logActivity('BACKUP_RESTORE', "Restored: {$backup_name} (Files: {$restored_count})");
            $success_msg = "Berhasil restore {$restored_count} file dari backup '{$backup_name}'";
            redirect('settings.php', $success_msg);
        } else {
            $error_msg = 'Gagal melakukan restore';
            redirect('settings.php', $error_msg, 'error');
        }
    }
}

// Backup data
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    if (checkCSRFToken($_GET['token'] ?? '')) {
        // Buat backup folder
        $backup_dir = '../backups/' . date('Y-m-d_H-i-s');
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Copy data files
        $data_dir = '../uploads/data/';
        $files = glob($data_dir . '*.json');
        
        $backup_count = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            if (copy($file, $backup_dir . '/' . $filename)) {
                $backup_count++;
            }
        }
        
        if ($backup_count > 0) {
            logActivity('BACKUP_CREATE', "Files: {$backup_count}");
            $success_msg = "Backup berhasil dibuat. {$backup_count} file tersimpan di: " . basename($backup_dir);
            redirect('settings.php', $success_msg);
        } else {
            $error_msg = 'Gagal membuat backup';
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
    }
}

// Clear logs
if (isset($_GET['action']) && $_GET['action'] === 'clear_logs') {
    if (checkCSRFToken($_GET['token'] ?? '')) {
        $log_file = '../uploads/data/activity.log';
        if (file_exists($log_file)) {
            if (file_put_contents($log_file, '') !== false) {
                logActivity('LOGS_CLEARED', 'Admin cleared all logs');
                $success_msg = 'Log aktivitas berhasil dihapus';
                redirect('settings.php', $success_msg);
            } else {
                $error_msg = 'Gagal menghapus log';
            }
        } else {
            $error_msg = 'File log tidak ditemukan';
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
    }
}

// Delete backup
if (isset($_GET['action']) && $_GET['action'] === 'delete_backup') {
    if (checkCSRFToken($_GET['token'] ?? '')) {
        $backup_name = $_GET['backup_name'] ?? '';
        
        if (empty($backup_name)) {
            $error_msg = 'Nama backup tidak valid';
            redirect('settings.php', $error_msg, 'error');
        }
        
        // Validasi nama backup (mencegah directory traversal)
        if (preg_match('/\.\./', $backup_name)) {
            $error_msg = 'Nama backup tidak valid';
            redirect('settings.php', $error_msg, 'error');
        }
        
        $backup_path = '../backups/' . $backup_name;
        
        if (!is_dir($backup_path)) {
            $error_msg = 'Backup tidak ditemukan';
            redirect('settings.php', $error_msg, 'error');
        }
        
        // Hapus folder backup beserta isinya
        if (deleteDirectory($backup_path)) {
            logActivity('BACKUP_DELETE', "Deleted: {$backup_name}");
            $success_msg = "Backup '{$backup_name}' berhasil dihapus";
            redirect('settings.php', $success_msg);
        } else {
            $error_msg = 'Gagal menghapus backup';
            redirect('settings.php', $error_msg, 'error');
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
        redirect('settings.php', $error_msg, 'error');
    }
}

// Get system info
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'server_name' => $_SERVER['SERVER_NAME'],
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Count data
$data_dir = '../uploads/data/';
$files = glob($data_dir . '*.json');
$data_files_count = count($files);

$backup_dir = '../backups/';
$backups = is_dir($backup_dir) ? glob($backup_dir . '*', GLOB_ONLYDIR) : [];
$backup_count = count($backups);

include 'header.php';
?>

<div class="content-area">
    <?php if ($error_msg): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>
    
    <!-- Change Password -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-key"></i>
                Ganti Password Admin
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Password Saat Ini *</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="form-control" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Password minimal 6 karakter. Disarankan menggunakan kombinasi huruf, angka, dan simbol.
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="new_password" class="form-label">Password Baru *</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   required
                                   minlength="6">
                            <div class="form-text">
                                Minimal 6 karakter
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru *</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   required
                                   minlength="6">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Ganti Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Backup & Restore -->
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-database"></i>
                Backup & Restore Data
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-box">
                        <h6><i class="fas fa-info-circle"></i> Informasi Backup</h6>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">File Data:</span>
                                <span class="stat-value"><?php echo $data_files_count; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Backup Tersedia:</span>
                                <span class="stat-value"><?php echo $backup_count; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Backup Terakhir:</span>
                                <span class="stat-value">
                                    <?php 
                                    if ($backup_count > 0) {
                                        $latest_backup = max($backups);
                                        echo date('d/m/Y', filemtime($latest_backup));
                                    } else {
                                        echo 'Belum ada';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Penting:</strong> Lakukan backup secara berkala untuk mencegah kehilangan data.
                        Backup disimpan di folder <code>backups/</code>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="action-buttons">
                        <a href="?action=backup&token=<?php echo generateCSRF(); ?>" 
                           class="btn btn-success btn-block mb-3"
                           onclick="return confirm('Buat backup data sekarang?')">
                            <i class="fas fa-download"></i> Buat Backup Sekarang
                        </a>
                        
                        <button type="button" class="btn btn-info btn-block mb-3" onclick="showRestoreModal()">
                            <i class="fas fa-upload"></i> Restore dari Backup
                        </button>
                        
                        <a href="?action=clear_logs&token=<?php echo generateCSRF(); ?>" 
                           class="btn btn-warning btn-block"
                           onclick="return confirm('Hapus semua log aktivitas? Tindakan ini tidak dapat dibatalkan.')">
                            <i class="fas fa-trash"></i> Hapus Log Aktivitas
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- List Backup Files -->
            <?php if ($backup_count > 0): ?>
                <div class="backup-list mt-4">
                    <h6><i class="fas fa-history"></i> Daftar Backup Tersedia</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nama Backup</th>
                                    <th>Tanggal</th>
                                    <th>Ukuran</th>
                                    <th>File</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Urutkan backup dari terbaru
                                usort($backups, function($a, $b) {
                                    return filemtime($b) - filemtime($a);
                                });
                                
                                foreach (array_slice($backups, 0, 10) as $backup): 
                                    $backup_name = basename($backup);
                                    $backup_date = date('d/m/Y H:i', filemtime($backup));
                                    $backup_files = glob($backup . '/*.json');
                                    $file_count = count($backup_files);
                                    
                                    // Hitung total size
                                    $total_size = 0;
                                    foreach ($backup_files as $file) {
                                        $total_size += filesize($file);
                                    }
                                    $size_text = $total_size > 1024 * 1024 ? 
                                        round($total_size / 1024 / 1024, 2) . ' MB' : 
                                        round($total_size / 1024, 2) . ' KB';
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $backup_name; ?></strong>
                                        </td>
                                        <td><?php echo $backup_date; ?></td>
                                        <td><?php echo $size_text; ?></td>
                                        <td><?php echo $file_count; ?> file</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="javascript:void(0);" 
                                                   onclick="showRestoreConfirm('<?php echo $backup_name; ?>')"
                                                   class="btn btn-outline-primary"
                                                   title="Restore">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                                <a href="?action=delete_backup&backup_name=<?php echo urlencode($backup_name); ?>&token=<?php echo generateCSRF(); ?>" 
                                                   onclick="return confirm('Hapus backup \"<?php echo addslashes($backup_name); ?>\"?\\n\\nPERINGATAN: Tindakan ini tidak dapat dibatalkan!')"
                                                   class="btn btn-outline-danger"
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
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- System Information -->
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-server"></i>
                Informasi Sistem
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="system-info">
                        <h6><i class="fas fa-code"></i> PHP & Server</h6>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">PHP Version:</span>
                                <span class="info-value"><?php echo $system_info['php_version']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Server Software:</span>
                                <span class="info-value"><?php echo $system_info['server_software']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Server Name:</span>
                                <span class="info-value"><?php echo $system_info['server_name']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Document Root:</span>
                                <span class="info-value"><?php echo $system_info['document_root']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="system-info">
                        <h6><i class="fas fa-cog"></i> Konfigurasi PHP</h6>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Memory Limit:</span>
                                <span class="info-value"><?php echo $system_info['memory_limit']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Upload Max Filesize:</span>
                                <span class="info-value"><?php echo $system_info['upload_max_filesize']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Post Max Size:</span>
                                <span class="info-value"><?php echo $system_info['post_max_size']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Max Execution Time:</span>
                                <span class="info-value"><?php echo $system_info['max_execution_time']; ?> detik</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Disk Usage -->
            <div class="disk-usage mt-4">
                <h6><i class="fas fa-hdd"></i> Penggunaan Disk</h6>
                <div class="progress" style="height: 20px; margin: 10px 0;">
                    <?php
                    $data_dir_size = 0;
                    $image_dir_size = 0;
                    
                    // Hitung ukuran folder data
                    if (is_dir($data_dir)) {
                        foreach (glob($data_dir . '*') as $file) {
                            $data_dir_size += filesize($file);
                        }
                    }
                    
                    // Hitung ukuran folder images
                    $image_dir = '../uploads/images/';
                    if (is_dir($image_dir)) {
                        foreach (glob($image_dir . '*') as $file) {
                            $image_dir_size += filesize($file);
                        }
                    }
                    
                    $total_size = $data_dir_size + $image_dir_size;
                    $data_percent = $total_size > 0 ? round(($data_dir_size / $total_size) * 100) : 0;
                    $image_percent = $total_size > 0 ? round(($image_dir_size / $total_size) * 100) : 0;
                    ?>
                    <div class="progress-bar bg-info" style="width: <?php echo $data_percent; ?>%">
                        Data: <?php echo round($data_dir_size / 1024 / 1024, 2); ?>MB
                    </div>
                    <div class="progress-bar bg-success" style="width: <?php echo $image_percent; ?>%">
                        Gambar: <?php echo round($image_dir_size / 1024 / 1024, 2); ?>MB
                    </div>
                </div>
                <div class="progress-info">
                    Total: <?php echo round($total_size / 1024 / 1024, 2); ?>MB
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal" id="restoreModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-upload"></i>
                Restore dari Backup
            </h3>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>PERINGATAN!</strong> Restore akan mengganti semua data saat ini dengan data dari backup.
                Pastikan Anda telah membuat backup terbaru sebelum melanjutkan.
            </div>
            
            <form id="restoreForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                <input type="hidden" name="action" value="restore">
                
                <div class="form-group">
                    <label for="backup_select" class="form-label">Pilih Backup</label>
                    <select id="backup_select" name="backup_name" class="form-control" required>
                        <option value="">Pilih backup...</option>
                        <?php foreach ($backups as $backup): 
                            $backup_name = basename($backup);
                            $backup_date = date('d/m/Y H:i', filemtime($backup));
                        ?>
                            <option value="<?php echo $backup_name; ?>">
                                <?php echo $backup_name . ' (' . $backup_date . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="confirmRestore()">
                <i class="fas fa-undo"></i> Restore Sekarang
            </button>
            <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        </div>
    </div>
</div>

<script>
function showRestoreModal() {
    document.getElementById('restoreModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function showRestoreConfirm(backupName) {
    if (confirm(`Restore dari backup "${backupName}"?\n\nPERINGATAN: Semua data saat ini akan diganti dengan data dari backup.`)) {
        // Set nilai select dan submit form
        document.getElementById('backup_select').value = backupName;
        confirmRestore();
    }
}

function confirmRestore() {
    const backupSelect = document.getElementById('backup_select');
    if (!backupSelect.value) {
        alert('Pilih backup terlebih dahulu');
        return;
    }
    
    const backupName = backupSelect.options[backupSelect.selectedIndex].text;
    
    if (confirm(`Restore dari backup:\n${backupName}\n\nPERINGATAN: Semua data saat ini akan diganti!\nSebelum restore, akan dibuat backup otomatis dari data saat ini.`)) {
        // Submit form
        document.getElementById('restoreForm').submit();
    }
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
    
    // Password strength indicator
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword) {
        newPassword.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            updatePasswordStrength(strength);
        });
    }
    
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            checkPasswordMatch();
        });
    }
});

function checkPasswordStrength(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Complexity checks
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    return Math.min(strength, 5); // Max 5
}

function updatePasswordStrength(strength) {
    let strengthText = 'Sangat Lemah';
    let strengthColor = '#dc3545';
    
    if (strength >= 4) {
        strengthText = 'Kuat';
        strengthColor = '#28a745';
    } else if (strength >= 3) {
        strengthText = 'Cukup';
        strengthColor = '#ffc107';
    } else if (strength >= 2) {
        strengthText = 'Lemah';
        strengthColor = '#fd7e14';
    }
    
    // Update UI
    const indicator = document.getElementById('passwordStrength') || createPasswordStrengthIndicator();
    indicator.innerHTML = `<span style="color: ${strengthColor}; font-weight: bold;">${strengthText}</span>`;
}

function createPasswordStrengthIndicator() {
    const div = document.createElement('div');
    div.id = 'passwordStrength';
    div.className = 'form-text';
    newPassword.parentNode.appendChild(div);
    return div;
}

function checkPasswordMatch() {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    const matchIndicator = document.getElementById('passwordMatch') || createPasswordMatchIndicator();
    
    if (!newPass || !confirmPass) {
        matchIndicator.textContent = '';
        return;
    }
    
    if (newPass === confirmPass) {
        matchIndicator.innerHTML = '<span style="color: #28a745; font-weight: bold;">✓ Password cocok</span>';
    } else {
        matchIndicator.innerHTML = '<span style="color: #dc3545; font-weight: bold;">✗ Password tidak cocok</span>';
    }
}

function createPasswordMatchIndicator() {
    const div = document.createElement('div');
    div.id = 'passwordMatch';
    div.className = 'form-text';
    confirmPassword.parentNode.appendChild(div);
    return div;
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
    border: 1px solid #e1e5e9;
}

.info-box h6 {
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
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

.action-buttons .btn {
    margin-bottom: 10px;
}

.btn-block {
    width: 100%;
}

.backup-list {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.backup-list h6 {
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.system-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    height: 100%;
}

.system-info h6 {
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e1e5e9;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #2c3e50;
}

.info-value {
    color: #6c757d;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.disk-usage {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.disk-usage h6 {
    color: #2c3e50;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.85em;
    color: #6c757d;
    margin-top: 5px;
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
    
    .btn-group {
        flex-direction: column;
    }
}
</style>

<?php include 'footer.php'; ?>
