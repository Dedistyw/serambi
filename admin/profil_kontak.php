<?php
/**
 * Profil Masjid & Kontak Takmir
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

Auth::requireLogin();

$page_title = 'Profil & Kontak';
$success_msg = '';
$error_msg = '';

// Load data profil masjid
$profil_data = getJSONData('profil_masjid');

// Path untuk logo masjid DAN QR Code
$logo_dir = '../assets/images/';
$logo_path = $logo_dir . 'logo-masjid.jpg';
$logo_url = '../assets/images/logo-masjid.jpg';
$qr_path = $logo_dir . 'qr-amal.jpg';
$qr_url = '../assets/images/qr-amal.jpg';

// Cek apakah file logo sudah ada
$current_logo = file_exists($logo_path) ? $logo_url . '?t=' . time() : '';
$current_qr = file_exists($qr_path) ? $qr_url . '?t=' . time() : '';

// ============ HANDLE LOGO DELETE ============
if (isset($_POST['action']) && $_POST['action'] === 'delete_logo') {
    checkCSRF();
    
    header('Content-Type: application/json');
    
    if (file_exists($logo_path)) {
        if (unlink($logo_path)) {
            logActivity('LOGO_DELETE', 'Hapus logo masjid');
            echo json_encode(['success' => true, 'message' => 'Logo berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus file']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File logo tidak ditemukan']);
    }
    exit;
}

// ============ HANDLE QR CODE DELETE ============
if (isset($_POST['action']) && $_POST['action'] === 'delete_qr') {
    checkCSRF();
    
    header('Content-Type: application/json');
    
    if (file_exists($qr_path)) {
        if (unlink($qr_path)) {
            logActivity('QR_DELETE', 'Hapus QR Code Amal');
            echo json_encode(['success' => true, 'message' => 'QR Code berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus file']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File QR Code tidak ditemukan']);
    }
    exit;
}

// ============ PROSES UPDATE PROFIL ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || ($_POST['action'] !== 'delete_logo' && $_POST['action'] !== 'delete_qr'))) {
    checkCSRF();
    
    $data = [
        'SITE_NAME' => sanitize($_POST['SITE_NAME'] ?? ''),
        'MASJID_CITY' => sanitize($_POST['MASJID_CITY'] ?? ''),
        'MASJID_TIMEZONE' => sanitize($_POST['MASJID_TIMEZONE'] ?? 'Asia/Jakarta'),
        'MASJID_PHONE' => sanitize($_POST['MASJID_PHONE'] ?? ''),
        'MASJID_EMAIL' => sanitize($_POST['MASJID_EMAIL'] ?? ''),
        'MASJID_ADDRESS' => sanitize($_POST['MASJID_ADDRESS'] ?? ''),
        'MASJID_DESCRIPTION' => sanitize($_POST['MASJID_DESCRIPTION'] ?? ''),
        // Data Pengurus DKM
        'KETUA_DKM_NAME' => sanitize($_POST['KETUA_DKM_NAME'] ?? ''),
        'KETUA_DKM_PHONE' => sanitize($_POST['KETUA_DKM_PHONE'] ?? ''),
        'SEKRETARIS_DKM_NAME' => sanitize($_POST['SEKRETARIS_DKM_NAME'] ?? ''),
        'SEKRETARIS_DKM_PHONE' => sanitize($_POST['SEKRETARIS_DKM_PHONE'] ?? ''),
        'BENDAHARA_DKM_NAME' => sanitize($_POST['BENDAHARA_DKM_NAME'] ?? ''),
        'BENDAHARA_DKM_PHONE' => sanitize($_POST['BENDAHARA_DKM_PHONE'] ?? ''),
        // Informasi Amal/Jam'iyah
        'AMAL_DESCRIPTION' => sanitize($_POST['AMAL_DESCRIPTION'] ?? ''),
        'APP_VERSION' => $profil_data['APP_VERSION'] ?? '1.0.0',
        'DEVELOPER_NAME' => $profil_data['DEVELOPER_NAME'] ?? 'with ❤️ by hasan dan para muslim',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Validasi
    if (empty($data['SITE_NAME'])) {
        $error_msg = 'Nama masjid harus diisi';
    } elseif (empty($data['MASJID_CITY'])) {
        $error_msg = 'Lokasi/kota masjid harus diisi';
    } else {
        // Proses upload logo jika ada
        if (isset($_FILES['MASJID_LOGO']) && $_FILES['MASJID_LOGO']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadLogo($_FILES['MASJID_LOGO']);
            if (!$upload_result['success']) {
                $error_msg = $upload_result['message'];
            } else {
                $current_logo = $logo_url . '?t=' . time(); // Update preview dengan timestamp
                logActivity('LOGO_UPDATE', 'Upload logo masjid');
            }
        }
        
        // Proses upload QR Code Amal jika ada
        if (isset($_FILES['QR_AMAL']) && $_FILES['QR_AMAL']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadQRCode($_FILES['QR_AMAL']);
            if (!$upload_result['success']) {
                $error_msg = $upload_result['message'];
            } else {
                $current_qr = $qr_url . '?t=' . time(); // Update preview dengan timestamp
                logActivity('QR_UPDATE', 'Upload QR Code Amal');
            }
        }
        
        // Hanya lanjut simpan jika tidak ada error dari upload
        if (empty($error_msg)) {
            // Simpan ke profil_masjid.json
            $profil_data = array_merge($profil_data, $data);
            
            if (saveJSONData('profil_masjid', $profil_data)) {
                logActivity('PROFIL_UPDATE', 'Update profil masjid dan pengurus');
                $success_msg = 'Profil masjid dan data pengurus berhasil diupdate';
                redirect('profil_kontak.php', $success_msg);
            } else {
                $error_msg = 'Gagal menyimpan profil';
            }
        }
    }
}

// ============ FUNGSI UPLOAD LOGO ============
function uploadLogo($file) {
    global $logo_dir, $logo_path;
    
    // Validasi file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, WebP, atau SVG.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 10MB.'];
    }
    
    // Validasi ekstensi file
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        return ['success' => false, 'message' => 'Ekstensi file tidak diizinkan.'];
    }
    
    // Buat direktori jika belum ada
    if (!file_exists($logo_dir)) {
        mkdir($logo_dir, 0755, true);
    }
    
    // Hapus logo lama jika ada
    if (file_exists($logo_path)) {
        unlink($logo_path);
    }
    
    // Untuk file SVG, simpan langsung tanpa konversi
    if ($file_ext === 'svg') {
        if (move_uploaded_file($file['tmp_name'], $logo_path)) {
            return ['success' => true, 'message' => 'Logo SVG berhasil diupload'];
        } else {
            return ['success' => false, 'message' => 'Gagal mengupload file SVG'];
        }
    }
    
    // Konversi gambar ke JPG jika perlu
    $image = null;
    switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'gif':
            $image = imagecreatefromgif($file['tmp_name']);
            break;
        case 'webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            break;
    }
    
    if ($image) {
        // Dapatkan dimensi gambar
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Buat canvas baru
        $canvas = imagecreatetruecolor($width, $height);
        
        // Tambahkan background putih untuk gambar transparan
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        
        // Copy gambar ke canvas
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
        
        // Simpan sebagai JPG dengan kualitas 90%
        if (imagejpeg($canvas, $logo_path, 90)) {
            imagedestroy($image);
            imagedestroy($canvas);
            return ['success' => true, 'message' => 'Logo berhasil diupload dan dikonversi ke JPG'];
        } else {
            imagedestroy($image);
            imagedestroy($canvas);
            return ['success' => false, 'message' => 'Gagal mengkonversi gambar'];
        }
    }
    
    return ['success' => false, 'message' => 'Format file tidak valid'];
}

// ============ FUNGSI UPLOAD QR CODE ============
function uploadQRCode($file) {
    global $logo_dir, $qr_path;
    
    // Validasi file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, WebP, atau SVG.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 10MB.'];
    }
    
    // Validasi ekstensi file
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        return ['success' => false, 'message' => 'Ekstensi file tidak diizinkan.'];
    }
    
    // Buat direktori jika belum ada
    if (!file_exists($logo_dir)) {
        mkdir($logo_dir, 0755, true);
    }
    
    // Hapus QR Code lama jika ada
    if (file_exists($qr_path)) {
        unlink($qr_path);
    }
    
    // Untuk file SVG, simpan langsung tanpa konversi
    if ($file_ext === 'svg') {
        if (move_uploaded_file($file['tmp_name'], $qr_path)) {
            return ['success' => true, 'message' => 'QR Code SVG berhasil diupload'];
        } else {
            return ['success' => false, 'message' => 'Gagal mengupload file SVG'];
        }
    }
    
    // Konversi gambar ke JPG jika perlu
    $image = null;
    switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'gif':
            $image = imagecreatefromgif($file['tmp_name']);
            break;
        case 'webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            break;
    }
    
    if ($image) {
        // Dapatkan dimensi gambar
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Buat canvas baru
        $canvas = imagecreatetruecolor($width, $height);
        
        // Tambahkan background putih untuk gambar transparan
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        
        // Copy gambar ke canvas
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
        
        // Simpan sebagai JPG dengan kualitas 90%
        if (imagejpeg($canvas, $qr_path, 90)) {
            imagedestroy($image);
            imagedestroy($canvas);
            return ['success' => true, 'message' => 'QR Code berhasil diupload dan dikonversi ke JPG'];
        } else {
            imagedestroy($image);
            imagedestroy($canvas);
            return ['success' => false, 'message' => 'Gagal mengkonversi gambar'];
        }
    }
    
    return ['success' => false, 'message' => 'Format file tidak valid'];
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
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-info-circle"></i>
                Profil & Kontak Masjid
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <h5 style="color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                            <i class="fas fa-mosque"></i> Informasi Masjid
                        </h5>
                        
                        <!-- Upload Logo -->
                        <div class="form-group">
                            <label for="MASJID_LOGO" class="form-label">Logo Masjid</label>
                            <div class="logo-upload-container">
                                <div class="logo-preview mb-3">
                                    <div id="logoPreview" class="logo-preview-image">
                                        <?php if ($current_logo): ?>
                                            <img src="<?php echo $current_logo; ?>" alt="Logo Masjid" id="previewLogoImg">
                                            <div class="logo-overlay">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeLogo()">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-logo">
                                                <i class="fas fa-mosque fa-3x"></i>
                                                <p>Belum ada logo</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" 
                                               class="custom-file-input" 
                                               id="MASJID_LOGO" 
                                               name="MASJID_LOGO" 
                                               accept=".jpg,.jpeg,.png,.gif,.webp,.svg"
                                               onchange="previewLogo(event)">
                                        <label class="custom-file-label" for="MASJID_LOGO" id="logoLabel">
                                            Pilih file logo...
                                        </label>
                                    </div>
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fas fa-image"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-text">
                                    Format: JPG, PNG, GIF, WebP, SVG (Maks. 10MB). Otomatis dikonversi ke JPG kecuali SVG.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upload QR Code Amal -->
                        <div class="form-group">
                            <label for="QR_AMAL" class="form-label">QR Code Amal/Jam'iyah</label>
                            <div class="qr-upload-container">
                                <div class="qr-preview mb-3">
                                    <div id="qrPreview" class="qr-preview-image">
                                        <?php if ($current_qr): ?>
                                            <img src="<?php echo $current_qr; ?>" alt="QR Code Amal" id="previewQrImg">
                                            <div class="qr-overlay">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeQR()">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-qr">
                                                <i class="fas fa-qrcode fa-3x"></i>
                                                <p>Belum ada QR Code</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" 
                                               class="custom-file-input" 
                                               id="QR_AMAL" 
                                               name="QR_AMAL" 
                                               accept=".jpg,.jpeg,.png,.gif,.webp,.svg"
                                               onchange="previewQR(event)">
                                        <label class="custom-file-label" for="QR_AMAL" id="qrLabel">
                                            Pilih file QR Code...
                                        </label>
                                    </div>
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fas fa-qrcode"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-text">
                                    Format: JPG, PNG, GIF, WebP, SVG (Maks. 10MB). Untuk donasi/infaq/shodaqoh.
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="AMAL_DESCRIPTION" class="form-label">Deskripsi Amal/Jam'iyah</label>
                            <textarea id="AMAL_DESCRIPTION" 
                                      name="AMAL_DESCRIPTION" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Contoh: Rekening BSI 7123-1234-123 a.n. Masjid Al-Ikhlas, atau deskripsi program amal..."><?php echo htmlspecialchars($profil_data['AMAL_DESCRIPTION'] ?? ''); ?></textarea>
                            <div class="form-text">
                                Informasi rekening atau deskripsi program amal yang akan ditampilkan di website
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="SITE_NAME" class="form-label">Nama Masjid *</label>
                            <input type="text" 
                                   id="SITE_NAME" 
                                   name="SITE_NAME" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? ''); ?>"
                                   required
                                   placeholder="Contoh: Masjid Al-Ikhlas">
                            <div class="form-text">
                                Nama masjid akan ditampilkan di header website
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="MASJID_CITY" class="form-label">Lokasi/Kota *</label>
                            <input type="text" 
                                   id="MASJID_CITY" 
                                   name="MASJID_CITY" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($profil_data['MASJID_CITY'] ?? ''); ?>"
                                   required
                                   placeholder="Contoh: Serpong - Tangerang Selatan">
                            <div class="form-text">
                                Lokasi akan ditampilkan di bawah nama masjid
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="MASJID_ADDRESS" class="form-label">Alamat Lengkap</label>
                            <textarea id="MASJID_ADDRESS" 
                                      name="MASJID_ADDRESS" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Alamat lengkap masjid..."><?php echo htmlspecialchars($profil_data['MASJID_ADDRESS'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="MASJID_DESCRIPTION" class="form-label">Deskripsi Masjid</label>
                            <textarea id="MASJID_DESCRIPTION" 
                                      name="MASJID_DESCRIPTION" 
                                      class="form-control" 
                                      rows="4"
                                      placeholder="Deskripsi singkat tentang masjid..."><?php echo htmlspecialchars($profil_data['MASJID_DESCRIPTION'] ?? ''); ?></textarea>
                            <div class="form-text">
                                Deskripsi singkat tentang sejarah atau aktivitas masjid
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 style="color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                            <i class="fas fa-address-book"></i> Kontak & Informasi
                        </h5>
                        
                        <div class="form-group">
                            <label for="MASJID_PHONE" class="form-label">Nomor Telepon/WhatsApp Masjid</label>
                            <input type="text" 
                                   id="MASJID_PHONE" 
                                   name="MASJID_PHONE" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($profil_data['MASJID_PHONE'] ?? ''); ?>"
                                   placeholder="Contoh: +6281234567890">
                            <div class="form-text">
                                Format internasional: +62 (untuk WhatsApp link otomatis)
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="MASJID_EMAIL" class="form-label">Email Masjid</label>
                            <input type="email" 
                                   id="MASJID_EMAIL" 
                                   name="MASJID_EMAIL" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($profil_data['MASJID_EMAIL'] ?? ''); ?>"
                                   placeholder="Contoh: info@masjid-alikhlas.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="MASJID_TIMEZONE" class="form-label">Zona Waktu *</label>
                            <select id="MASJID_TIMEZONE" name="MASJID_TIMEZONE" class="form-control" required>
                                <option value="Asia/Jakarta" <?php echo ($profil_data['MASJID_TIMEZONE'] ?? 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : ''; ?>>WIB (Asia/Jakarta)</option>
                                <option value="Asia/Makassar" <?php echo ($profil_data['MASJID_TIMEZONE'] ?? '') == 'Asia/Makassar' ? 'selected' : ''; ?>>WITA (Asia/Makassar)</option>
                                <option value="Asia/Jayapura" <?php echo ($profil_data['MASJID_TIMEZONE'] ?? '') == 'Asia/Jayapura' ? 'selected' : ''; ?>>WIT (Asia/Jayapura)</option>
                                <option value="Asia/Singapore" <?php echo ($profil_data['MASJID_TIMEZONE'] ?? '') == 'Asia/Singapore' ? 'selected' : ''; ?>>Singapura</option>
                            </select>
                            <div class="form-text">
                                Zona waktu untuk penampilan jam dan jadwal sholat
                            </div>
                        </div>
                        
                        <!-- Data Pengurus DKM -->
                        <h5 style="color: #2c3e50; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                            <i class="fas fa-users"></i> Pengurus DKM
                        </h5>
                        
                        <div class="pengurus-container">
                            <div class="pengurus-card">
                                <h6><i class="fas fa-crown text-warning"></i> Ketua DKM</h6>
                                <div class="form-group">
                                    <label for="KETUA_DKM_NAME" class="form-label">Nama Ketua DKM</label>
                                    <input type="text" 
                                           id="KETUA_DKM_NAME" 
                                           name="KETUA_DKM_NAME" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($profil_data['KETUA_DKM_NAME'] ?? ''); ?>"
                                           placeholder="Contoh: Ustadz Ahmad">
                                </div>
                                <div class="form-group">
                                    <label for="KETUA_DKM_PHONE" class="form-label">Telepon/WA Ketua</label>
                                    <input type="text" 
                                           id="KETUA_DKM_PHONE" 
                                           name="KETUA_DKM_PHONE" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($profil_data['KETUA_DKM_PHONE'] ?? ''); ?>"
                                           placeholder="Contoh: +6281234567890">
                                </div>
                            </div>
                            
                            <div class="pengurus-card">
                                <h6><i class="fas fa-file-alt text-primary"></i> Sekretaris DKM</h6>
                                <div class="form-group">
                                    <label for="SEKRETARIS_DKM_NAME" class="form-label">Nama Sekretaris DKM</label>
                                    <input type="text" 
                                           id="SEKRETARIS_DKM_NAME" 
                                           name="SEKRETARIS_DKM_NAME" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($profil_data['SEKRETARIS_DKM_NAME'] ?? ''); ?>"
                                           placeholder="Contoh: Ustadz Muhammad">
                                </div>
                                <div class="form-group">
                                    <label for="SEKRETARIS_DKM_PHONE" class="form-label">Telepon/WA Sekretaris</label>
                                    <input type="text" 
                                           id="SEKRETARIS_DKM_PHONE" 
                                           name="SEKRETARIS_DKM_PHONE" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($profil_data['SEKRETARIS_DKM_PHONE'] ?? ''); ?>"
                                           placeholder="Contoh: +6281234567890">
                                </div>
                            </div>
                            
                            <div class="pengurus-card">
                                <h6><i class="fas fa-money-bill-alt text-success"></i> Bendahara DKM</h6>
                                <div class="form-group">
                                    <label for="BENDAHARA_DKM_NAME" class="form-label">Nama Bendahara DKM</label>
                                    <input type="text" 
                                           id="BENDAHARA_DKM_NAME" 
                                           name="BENDAHARA_DKM_NAME" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($profil_data['BENDAHARA_DKM_NAME'] ?? ''); ?>"
                                           placeholder="Contoh: Ustadz Ali">
                                </div>
                                <div class="form-group">
                                    <label for="BENDAHARA_DKM_PHONE" class="form-label">Telepon/WA Bendahara</label>
                                    <input type="text" 
                                           id="BENDAHARA_DKM_PHONE" 
                                           name="BENDAHARA_DKM_PHONE" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($profil_data['BENDAHARA_DKM_PHONE'] ?? ''); ?>"
                                           placeholder="Contoh: +6281234567890">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Section -->
                <div class="preview-section mt-4">
                    <h5 style="color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                        <i class="fas fa-eye"></i> Preview Data di Website
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="preview-box">
                                <h6 class="preview-title"><i class="fas fa-mosque"></i> Profil Masjid</h6>
                                
                                <!-- Preview Logo -->
                                <div class="preview-logo mb-3 text-center">
                                    <div id="previewLogoContainer" class="logo-preview-mini">
                                        <?php if ($current_logo): ?>
                                            <img src="<?php echo $current_logo; ?>" alt="Logo Preview" class="img-fluid" style="max-height: 80px;">
                                        <?php else: ?>
                                            <div class="no-logo-mini">
                                                <i class="fas fa-mosque"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Preview QR Code -->
                                <div class="preview-qr mb-3 text-center">
                                    <div id="previewQrContainer" class="qr-preview-mini">
                                        <?php if ($current_qr): ?>
                                            <img src="<?php echo $current_qr; ?>" alt="QR Code Preview" class="img-fluid" style="max-height: 80px;">
                                        <?php else: ?>
                                            <div class="no-qr-mini">
                                                <i class="fas fa-qrcode"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="preview-amal-description" id="previewAmalDescription">
                                        <?php echo htmlspecialchars($profil_data['AMAL_DESCRIPTION'] ?? ''); ?>
                                    </div>
                                </div>
                                
                                <div class="preview-item">
                                    <span class="preview-label">Nama:</span>
                                    <span class="preview-value" id="previewName">
                                        <?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas'); ?>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Lokasi:</span>
                                    <span class="preview-value" id="previewLocation">
                                        <?php echo htmlspecialchars($profil_data['MASJID_CITY'] ?? 'Serpong - Tangerang Selatan'); ?>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Zona Waktu:</span>
                                    <span class="preview-value" id="previewTimezone">
                                        <?php echo htmlspecialchars($profil_data['MASJID_TIMEZONE'] ?? 'Asia/Jakarta'); ?>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Telp/WA Masjid:</span>
                                    <span class="preview-value" id="previewPhone">
                                        <?php 
                                        $phone = $profil_data['MASJID_PHONE'] ?? '';
                                        if ($phone) {
                                            echo '<a href="tel:' . htmlspecialchars($phone) . '" style="color: #25D366; text-decoration: none;">' . 
                                                 htmlspecialchars($phone) . '</a>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Email Masjid:</span>
                                    <span class="preview-value" id="previewEmail">
                                        <?php 
                                        $email = $profil_data['MASJID_EMAIL'] ?? '';
                                        if ($email) {
                                            echo '<a href="mailto:' . htmlspecialchars($email) . '" style="color: #25D366; text-decoration: none;">' . 
                                                 htmlspecialchars($email) . '</a>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="preview-box">
                                <h6 class="preview-title"><i class="fas fa-users"></i> Pengurus DKM</h6>
                                
                                <div class="preview-item">
                                    <span class="preview-label">Ketua DKM:</span>
                                    <span class="preview-value" id="previewKetuaName">
                                        <?php 
                                        $ketua = $profil_data['KETUA_DKM_NAME'] ?? '';
                                        $ketuaPhone = $profil_data['KETUA_DKM_PHONE'] ?? '';
                                        if ($ketua) {
                                            echo htmlspecialchars($ketua);
                                            if ($ketuaPhone) {
                                                echo ' <small><a href="tel:' . htmlspecialchars($ketuaPhone) . '" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Telepon/WA:</span>
                                    <span class="preview-value" id="previewKetuaPhone">
                                        <?php 
                                        if ($ketuaPhone) {
                                            echo '<a href="tel:' . htmlspecialchars($ketuaPhone) . '" style="color: #25D366; text-decoration: none;">' . 
                                                 htmlspecialchars($ketuaPhone) . '</a>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="preview-item">
                                    <span class="preview-label">Sekretaris DKM:</span>
                                    <span class="preview-value" id="previewSekretarisName">
                                        <?php 
                                        $sekretaris = $profil_data['SEKRETARIS_DKM_NAME'] ?? '';
                                        $sekretarisPhone = $profil_data['SEKRETARIS_DKM_PHONE'] ?? '';
                                        if ($sekretaris) {
                                            echo htmlspecialchars($sekretaris);
                                            if ($sekretarisPhone) {
                                                echo ' <small><a href="tel:' . htmlspecialchars($sekretarisPhone) . '" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Telepon/WA:</span>
                                    <span class="preview-value" id="previewSekretarisPhone">
                                        <?php 
                                        if ($sekretarisPhone) {
                                            echo '<a href="tel:' . htmlspecialchars($sekretarisPhone) . '" style="color: #25D366; text-decoration: none;">' . 
                                                 htmlspecialchars($sekretarisPhone) . '</a>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="preview-item">
                                    <span class="preview-label">Bendahara DKM:</span>
                                    <span class="preview-value" id="previewBendaharaName">
                                        <?php 
                                        $bendahara = $profil_data['BENDAHARA_DKM_NAME'] ?? '';
                                        $bendaharaPhone = $profil_data['BENDAHARA_DKM_PHONE'] ?? '';
                                        if ($bendahara) {
                                            echo htmlspecialchars($bendahara);
                                            if ($bendaharaPhone) {
                                                echo ' <small><a href="tel:' . htmlspecialchars($bendaharaPhone) . '" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Telepon/WA:</span>
                                    <span class="preview-value" id="previewBendaharaPhone">
                                        <?php 
                                        if ($bendaharaPhone) {
                                            echo '<a href="tel:' . htmlspecialchars($bendaharaPhone) . '" style="color: #25D366; text-decoration: none;">' . 
                                                 htmlspecialchars($bendaharaPhone) . '</a>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Semua Perubahan
                    </button>
                    <button type="reset" class="btn btn-light" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
            
            <!-- System Information -->
            <div class="system-info-section mt-5">
                <h5 style="color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                    <i class="fas fa-cogs"></i> Informasi Sistem
                </h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h6><i class="fas fa-code"></i> Versi Aplikasi</h6>
                            <div class="info-content">
                                <p><strong>SERAMBI:</strong> 
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($profil_data['APP_VERSION'] ?? '1.0.0'); ?>
                                    </span>
                                </p>
                                <p><strong>PHP:</strong> <?php echo PHP_VERSION; ?></p>
                                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="info-card">
                            <h6><i class="fas fa-users"></i> Pengembang</h6>
                            <div class="info-content">
                                <p><?php echo htmlspecialchars($profil_data['DEVELOPER_NAME'] ?? 'with ❤️ by hasan dan para muslim'); ?></p>
                                <p><strong>Login Terakhir:</strong> 
                                    <?php echo isset($_SESSION['admin_last_login']) ? 
                                        date('d/m/Y H:i', $_SESSION['admin_last_login']) : 
                                        'Baru login'; ?>
                                </p>
                                <p><strong>Pengguna:</strong> 
                                    <span class="badge badge-success">
                                        <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live preview update
document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const nameInput = document.getElementById('SITE_NAME');
    const locationInput = document.getElementById('MASJID_CITY');
    const timezoneInput = document.getElementById('MASJID_TIMEZONE');
    const phoneInput = document.getElementById('MASJID_PHONE');
    const emailInput = document.getElementById('MASJID_EMAIL');
    const logoInput = document.getElementById('MASJID_LOGO');
    const qrInput = document.getElementById('QR_AMAL');
    const amalDescriptionInput = document.getElementById('AMAL_DESCRIPTION');
    
    // Pengurus DKM elements
    const ketuaNameInput = document.getElementById('KETUA_DKM_NAME');
    const ketuaPhoneInput = document.getElementById('KETUA_DKM_PHONE');
    const sekretarisNameInput = document.getElementById('SEKRETARIS_DKM_NAME');
    const sekretarisPhoneInput = document.getElementById('SEKRETARIS_DKM_PHONE');
    const bendaharaNameInput = document.getElementById('BENDAHARA_DKM_NAME');
    const bendaharaPhoneInput = document.getElementById('BENDAHARA_DKM_PHONE');
    
    // Get preview elements
    const previewName = document.getElementById('previewName');
    const previewLocation = document.getElementById('previewLocation');
    const previewTimezone = document.getElementById('previewTimezone');
    const previewPhone = document.getElementById('previewPhone');
    const previewEmail = document.getElementById('previewEmail');
    const previewLogoContainer = document.getElementById('previewLogoContainer');
    const previewQrContainer = document.getElementById('previewQrContainer');
    const previewAmalDescription = document.getElementById('previewAmalDescription');
    
    // Pengurus preview elements
    const previewKetuaName = document.getElementById('previewKetuaName');
    const previewKetuaPhone = document.getElementById('previewKetuaPhone');
    const previewSekretarisName = document.getElementById('previewSekretarisName');
    const previewSekretarisPhone = document.getElementById('previewSekretarisPhone');
    const previewBendaharaName = document.getElementById('previewBendaharaName');
    const previewBendaharaPhone = document.getElementById('previewBendaharaPhone');
    
    // Update preview functions
    function updateNamePreview() {
        previewName.textContent = nameInput.value || 'Masjid Al-Ikhlas';
    }
    
    function updateLocationPreview() {
        previewLocation.textContent = locationInput.value || 'Serpong - Tangerang Selatan';
    }
    
    function updateTimezonePreview() {
        previewTimezone.textContent = timezoneInput.value;
    }
    
    function updatePhonePreview() {
        const phone = phoneInput.value;
        if (phone) {
            previewPhone.innerHTML = `<a href="tel:${phone}" style="color: #25D366; text-decoration: none;">${phone}</a>`;
        } else {
            previewPhone.textContent = '-';
        }
    }
    
    function updateEmailPreview() {
        const email = emailInput.value;
        if (email) {
            previewEmail.innerHTML = `<a href="mailto:${email}" style="color: #25D366; text-decoration: none;">${email}</a>`;
        } else {
            previewEmail.textContent = '-';
        }
    }
    
    function updateAmalDescriptionPreview() {
        const description = amalDescriptionInput.value;
        previewAmalDescription.textContent = description || '';
    }
    
    function updateKetuaPreview() {
        const name = ketuaNameInput.value || '';
        const phone = ketuaPhoneInput.value || '';
        
        if (name) {
            let html = name;
            if (phone) {
                html += ` <small><a href="tel:${phone}" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>`;
            }
            previewKetuaName.innerHTML = html;
        } else {
            previewKetuaName.textContent = '-';
        }
        
        if (phone) {
            previewKetuaPhone.innerHTML = `<a href="tel:${phone}" style="color: #25D366; text-decoration: none;">${phone}</a>`;
        } else {
            previewKetuaPhone.textContent = '-';
        }
    }
    
    function updateSekretarisPreview() {
        const name = sekretarisNameInput.value || '';
        const phone = sekretarisPhoneInput.value || '';
        
        if (name) {
            let html = name;
            if (phone) {
                html += ` <small><a href="tel:${phone}" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>`;
            }
            previewSekretarisName.innerHTML = html;
        } else {
            previewSekretarisName.textContent = '-';
        }
        
        if (phone) {
            previewSekretarisPhone.innerHTML = `<a href="tel:${phone}" style="color: #25D366; text-decoration: none;">${phone}</a>`;
        } else {
            previewSekretarisPhone.textContent = '-';
        }
    }
    
    function updateBendaharaPreview() {
        const name = bendaharaNameInput.value || '';
        const phone = bendaharaPhoneInput.value || '';
        
        if (name) {
            let html = name;
            if (phone) {
                html += ` <small><a href="tel:${phone}" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>`;
            }
            previewBendaharaName.innerHTML = html;
        } else {
            previewBendaharaName.textContent = '-';
        }
        
        if (phone) {
            previewBendaharaPhone.innerHTML = `<a href="tel:${phone}" style="color: #25D366; text-decoration: none;">${phone}</a>`;
        } else {
            previewBendaharaPhone.textContent = '-';
        }
    }
    
    // Add event listeners
    if (nameInput) nameInput.addEventListener('input', updateNamePreview);
    if (locationInput) locationInput.addEventListener('input', updateLocationPreview);
    if (timezoneInput) timezoneInput.addEventListener('change', updateTimezonePreview);
    if (phoneInput) phoneInput.addEventListener('input', updatePhonePreview);
    if (emailInput) emailInput.addEventListener('input', updateEmailPreview);
    if (amalDescriptionInput) amalDescriptionInput.addEventListener('input', updateAmalDescriptionPreview);
    
    // Pengurus event listeners
    if (ketuaNameInput) ketuaNameInput.addEventListener('input', updateKetuaPreview);
    if (ketuaPhoneInput) ketuaPhoneInput.addEventListener('input', updateKetuaPreview);
    if (sekretarisNameInput) sekretarisNameInput.addEventListener('input', updateSekretarisPreview);
    if (sekretarisPhoneInput) sekretarisPhoneInput.addEventListener('input', updateSekretarisPreview);
    if (bendaharaNameInput) bendaharaNameInput.addEventListener('input', updateBendaharaPreview);
    if (bendaharaPhoneInput) bendaharaPhoneInput.addEventListener('input', updateBendaharaPreview);
    
    // File input label update
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Pilih file logo...';
            document.getElementById('logoLabel').textContent = fileName;
        });
    }
    
    if (qrInput) {
        qrInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Pilih file QR Code...';
            document.getElementById('qrLabel').textContent = fileName;
        });
    }
    
    // Initial update
    updateNamePreview();
    updateLocationPreview();
    updateTimezonePreview();
    updatePhonePreview();
    updateEmailPreview();
    updateAmalDescriptionPreview();
    updateKetuaPreview();
    updateSekretarisPreview();
    updateBendaharaPreview();
});

// Preview logo sebelum upload
function previewLogo(event) {
    const input = event.target;
    const previewContainer = document.getElementById('previewLogoContainer');
    const logoPreview = document.getElementById('logoPreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Update main preview
            logoPreview.innerHTML = `
                <img src="${e.target.result}" alt="Logo Preview" id="previewLogoImg">
                <div class="logo-overlay">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeLogo()">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            `;
            
            // Update mini preview
            previewContainer.innerHTML = `<img src="${e.target.result}" alt="Logo Preview" class="img-fluid" style="max-height: 80px;">`;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Preview QR Code sebelum upload
function previewQR(event) {
    const input = event.target;
    const previewContainer = document.getElementById('previewQrContainer');
    const qrPreview = document.getElementById('qrPreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Update main preview
            qrPreview.innerHTML = `
                <img src="${e.target.result}" alt="QR Code Preview" id="previewQrImg">
                <div class="qr-overlay">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeQR()">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            `;
            
            // Update mini preview
            previewContainer.innerHTML = `<img src="${e.target.result}" alt="QR Code Preview" class="img-fluid" style="max-height: 80px;">`;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus logo dari preview
function removeLogo() {
    if (confirm('Apakah Anda yakin ingin menghapus logo masjid?')) {
        // Kirim request untuk hapus logo via AJAX
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete_logo&csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update preview
                const logoPreview = document.getElementById('logoPreview');
                const previewContainer = document.getElementById('previewLogoContainer');
                
                logoPreview.innerHTML = `
                    <div class="no-logo">
                        <i class="fas fa-mosque fa-3x"></i>
                        <p>Belum ada logo</p>
                    </div>
                `;
                
                previewContainer.innerHTML = `
                    <div class="no-logo-mini">
                        <i class="fas fa-mosque"></i>
                    </div>
                `;
                
                // Reset file input
                document.getElementById('MASJID_LOGO').value = '';
                document.getElementById('logoLabel').textContent = 'Pilih file logo...';
                
                // Show success message
                showAlert('success', data.message);
            } else {
                showAlert('danger', 'Gagal menghapus logo: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Terjadi kesalahan saat menghapus logo');
        });
    }
}

// Hapus QR Code dari preview
function removeQR() {
    if (confirm('Apakah Anda yakin ingin menghapus QR Code amal?')) {
        // Kirim request untuk hapus QR Code via AJAX
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete_qr&csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update preview
                const qrPreview = document.getElementById('qrPreview');
                const previewContainer = document.getElementById('previewQrContainer');
                
                qrPreview.innerHTML = `
                    <div class="no-qr">
                        <i class="fas fa-qrcode fa-3x"></i>
                        <p>Belum ada QR Code</p>
                    </div>
                `;
                
                previewContainer.innerHTML = `
                    <div class="no-qr-mini">
                        <i class="fas fa-qrcode"></i>
                    </div>
                `;
                
                // Reset file input
                document.getElementById('QR_AMAL').value = '';
                document.getElementById('qrLabel').textContent = 'Pilih file QR Code...';
                
                // Show success message
                showAlert('success', data.message);
            } else {
                showAlert('danger', 'Gagal menghapus QR Code: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Terjadi kesalahan saat menghapus QR Code');
        });
    }
}

// Reset form
function resetForm() {
    // Reset semua input kecuali yang sudah ada
    const form = document.querySelector('form');
    form.reset();
    
    // Update preview dengan data asli dari server
    const previews = {
        name: '<?php echo htmlspecialchars($profil_data["SITE_NAME"] ?? "Masjid Al-Ikhlas"); ?>',
        location: '<?php echo htmlspecialchars($profil_data["MASJID_CITY"] ?? "Serpong - Tangerang Selatan"); ?>',
        timezone: '<?php echo htmlspecialchars($profil_data["MASJID_TIMEZONE"] ?? "Asia/Jakarta"); ?>',
        phone: '<?php echo htmlspecialchars($profil_data["MASJID_PHONE"] ?? ""); ?>',
        email: '<?php echo htmlspecialchars($profil_data["MASJID_EMAIL"] ?? ""); ?>',
        amalDescription: '<?php echo htmlspecialchars($profil_data["AMAL_DESCRIPTION"] ?? ""); ?>',
        ketuaName: '<?php echo htmlspecialchars($profil_data["KETUA_DKM_NAME"] ?? ""); ?>',
        ketuaPhone: '<?php echo htmlspecialchars($profil_data["KETUA_DKM_PHONE"] ?? ""); ?>',
        sekretarisName: '<?php echo htmlspecialchars($profil_data["SEKRETARIS_DKM_NAME"] ?? ""); ?>',
        sekretarisPhone: '<?php echo htmlspecialchars($profil_data["SEKRETARIS_DKM_PHONE"] ?? ""); ?>',
        bendaharaName: '<?php echo htmlspecialchars($profil_data["BENDAHARA_DKM_NAME"] ?? ""); ?>',
        bendaharaPhone: '<?php echo htmlspecialchars($profil_data["BENDAHARA_DKM_PHONE"] ?? ""); ?>'
    };
    
    document.getElementById('previewName').textContent = previews.name;
    document.getElementById('previewLocation').textContent = previews.location;
    document.getElementById('previewTimezone').textContent = previews.timezone;
    document.getElementById('previewAmalDescription').textContent = previews.amalDescription;
    
    if (previews.phone) {
        document.getElementById('previewPhone').innerHTML = `<a href="tel:${previews.phone}" style="color: #25D366; text-decoration: none;">${previews.phone}</a>`;
    } else {
        document.getElementById('previewPhone').textContent = '-';
    }
    
    if (previews.email) {
        document.getElementById('previewEmail').innerHTML = `<a href="mailto:${previews.email}" style="color: #25D366; text-decoration: none;">${previews.email}</a>`;
    } else {
        document.getElementById('previewEmail').textContent = '-';
    }
    
    // Update pengurus preview
    updatePengurusPreview(previews.ketuaName, previews.ketuaPhone, previews.sekretarisName, previews.sekretarisPhone, previews.bendaharaName, previews.bendaharaPhone);
    
    // Reset file input label
    document.getElementById('logoLabel').textContent = 'Pilih file logo...';
    document.getElementById('qrLabel').textContent = 'Pilih file QR Code...';
}

// Helper function untuk update preview pengurus
function updatePengurusPreview(ketuaName, ketuaPhone, sekretarisName, sekretarisPhone, bendaharaName, bendaharaPhone) {
    // Ketua
    if (ketuaName) {
        let html = ketuaName;
        if (ketuaPhone) {
            html += ` <small><a href="tel:${ketuaPhone}" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>`;
        }
        document.getElementById('previewKetuaName').innerHTML = html;
    } else {
        document.getElementById('previewKetuaName').textContent = '-';
    }
    
    if (ketuaPhone) {
        document.getElementById('previewKetuaPhone').innerHTML = `<a href="tel:${ketuaPhone}" style="color: #25D366; text-decoration: none;">${ketuaPhone}</a>`;
    } else {
        document.getElementById('previewKetuaPhone').textContent = '-';
    }
    
    // Sekretaris
    if (sekretarisName) {
        let html = sekretarisName;
        if (sekretarisPhone) {
            html += ` <small><a href="tel:${sekretarisPhone}" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>`;
        }
        document.getElementById('previewSekretarisName').innerHTML = html;
    } else {
        document.getElementById('previewSekretarisName').textContent = '-';
    }
    
    if (sekretarisPhone) {
        document.getElementById('previewSekretarisPhone').innerHTML = `<a href="tel:${sekretarisPhone}" style="color: #25D366; text-decoration: none;">${sekretarisPhone}</a>`;
    } else {
        document.getElementById('previewSekretarisPhone').textContent = '-';
    }
    
    // Bendahara
    if (bendaharaName) {
        let html = bendaharaName;
        if (bendaharaPhone) {
            html += ` <small><a href="tel:${bendaharaPhone}" style="color: #25D366; text-decoration: none;"><i class="fas fa-phone"></i></a></small>`;
        }
        document.getElementById('previewBendaharaName').innerHTML = html;
    } else {
        document.getElementById('previewBendaharaName').textContent = '-';
    }
    
    if (bendaharaPhone) {
        document.getElementById('previewBendaharaPhone').innerHTML = `<a href="tel:${bendaharaPhone}" style="color: #25D366; text-decoration: none;">${bendaharaPhone}</a>`;
    } else {
        document.getElementById('previewBendaharaPhone').textContent = '-';
    }
}

// Helper function untuk menampilkan alert
function showAlert(type, message) {
    // Cek apakah sudah ada alert container
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.querySelector('.content-area').insertBefore(alertContainer, document.querySelector('.content-area').firstChild);
    }
    
    // Buat alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // Tambahkan ke container
    alertContainer.appendChild(alertDiv);
    
    // Auto remove setelah 5 detik
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<style>
/* Logo Upload Styles */
.logo-upload-container, .qr-upload-container {
    margin-bottom: 20px;
}

.logo-preview, .qr-preview {
    display: flex;
    justify-content: center;
}

.logo-preview-image, .qr-preview-image {
    position: relative;
    width: 200px;
    height: 200px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
}

.logo-preview-image img, .qr-preview-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.logo-overlay, .qr-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.logo-preview-image:hover .logo-overlay,
.qr-preview-image:hover .qr-overlay {
    opacity: 1;
}

.no-logo, .no-qr {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6c757d;
}

.no-logo i, .no-qr i {
    margin-bottom: 10px;
}

/* Mini logo preview */
.logo-preview-mini, .qr-preview-mini {
    display: inline-block;
    padding: 10px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    max-width: 120px;
    max-height: 80px;
    overflow: hidden;
}

.logo-preview-mini img, .qr-preview-mini img {
    max-height: 60px;
    width: auto;
}

.no-logo-mini, .no-qr-mini {
    width: 80px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    color: #6c757d;
}

.preview-amal-description {
    margin-top: 10px;
    font-size: 0.9em;
    color: #6c757d;
    background: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    border-left: 3px solid #25D366;
}

/* Pengurus Styles */
.pengurus-container {
    margin-top: 20px;
}

.pengurus-card {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.pengurus-card h6 {
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pengurus-card h6 i {
    font-size: 1.1em;
}

/* Form Styles */
.custom-file-input:focus ~ .custom-file-label {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #ced4da;
}

.preview-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.preview-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    height: 100%;
}

.preview-title {
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.preview-item:last-child {
    border-bottom: none;
}

.preview-label {
    font-weight: 600;
    color: #2c3e50;
    min-width: 140px;
}

.preview-value {
    text-align: right;
    flex: 1;
    color: #6c757d;
}

.system-info-section {
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.info-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    height: 100%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-card h6 {
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-content p {
    margin: 0 0 10px 0;
    display: flex;
    justify-content: space-between;
}

.info-content p:last-child {
    margin-bottom: 0;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
    display: flex;
    gap: 10px;
}

.alert-container {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.alert-container .alert {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .preview-item {
        flex-direction: column;
    }
    
    .preview-value {
        text-align: left;
        margin-top: 5px;
    }
    
    .logo-preview-image, .qr-preview-image {
        width: 150px;
        height: 150px;
    }
    
    .alert-container {
        left: 10px;
        right: 10px;
        max-width: none;
    }
    
    .preview-label {
        min-width: 120px;
    }
    
    .pengurus-card {
        padding: 12px;
    }
}
</style>

<?php include 'footer.php'; ?>
