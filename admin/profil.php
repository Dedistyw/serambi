<?php
require_once '../includes/config.php';
require_once 'auth_check.php';

$errors = [];
$success = false;
$show_reset_option = false;

// Get current admin data
$admin = query("SELECT * FROM admin WHERE id = 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $reset_verification = $_POST['reset_verification'] ?? '';
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong";
    }
    
    if (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter";
    }
    
    // Jika menggunakan fitur reset password
    if (!empty($reset_verification)) {
        $correct_verification = "RESET_" . date('Ymd');
        
        if ($reset_verification === $correct_verification) {
            // Reset password berhasil - bypass password lama
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $errors[] = "Password baru minimal 6 karakter";
                }
                
                if ($new_password !== $confirm_password) {
                    $errors[] = "Konfirmasi password baru tidak sesuai";
                }
                
                if (empty($errors)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $escaped_username = mysqli_real_escape_string($koneksi, $username);
                    $update_query = "UPDATE admin SET username = '$escaped_username', password_hash = '$hashed_password' WHERE id = 1";
                    
                    $result = query($update_query);
                    
                    if ($result) {
                        $_SESSION['admin_username'] = $username;
                        $success = true;
                        $admin = query("SELECT * FROM admin WHERE id = 1")->fetch_assoc();
                        
                        // Clear form
                        $new_password = '';
                        $confirm_password = '';
                    } else {
                        $errors[] = "Gagal memperbarui database";
                    }
                }
            } else {
                $errors[] = "Password baru harus diisi";
            }
        } else {
            $errors[] = "Kode verifikasi reset password salah. Harap salin kode yang ditampilkan.";
        }
    }
    // Jika mengubah password normal
    elseif (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Password saat ini harus diisi untuk mengubah password";
            $show_reset_option = true;
        } else {
            // Password verification dengan bcrypt
            $password_verified = password_verify($current_password, $admin['password_hash']);
            
            if (!$password_verified) {
                $errors[] = "Password saat ini salah";
                $show_reset_option = true;
            }
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = "Password baru minimal 6 karakter";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Konfirmasi password baru tidak sesuai";
        }
        
        if (empty($errors) && $password_verified) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $escaped_username = mysqli_real_escape_string($koneksi, $username);
            $update_query = "UPDATE admin SET username = '$escaped_username', password_hash = '$hashed_password' WHERE id = 1";
            
            $result = query($update_query);
            
            if ($result) {
                $_SESSION['admin_username'] = $username;
                $success = true;
                $admin = query("SELECT * FROM admin WHERE id = 1")->fetch_assoc();
                
                // Clear form
                $current_password = '';
                $new_password = '';
                $confirm_password = '';
            } else {
                $errors[] = "Gagal memperbarui database";
            }
        }
    }
    // Jika hanya mengubah username
    else {
        $escaped_username = mysqli_real_escape_string($koneksi, $username);
        $update_query = "UPDATE admin SET username = '$escaped_username' WHERE id = 1";
        
        $result = query($update_query);
        
        if ($result) {
            $_SESSION['admin_username'] = $username;
            $success = true;
            $admin = query("SELECT * FROM admin WHERE id = 1")->fetch_assoc();
        } else {
            $errors[] = "Gagal memperbarui database";
        }
    }
}

// Generate kode verifikasi hari ini
$today_verification = "RESET_" . date('Ymd');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            background: #f8f9fa;
        }
        
        .mobile-menu-btn {
            display: none;
            background: #2E8B57;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .profile-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 700px;
            margin: 0 auto;
            border: 1px solid #e9ecef;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #f1f3f4;
            padding-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #2E8B57, #3CB371);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
        }
        
        .profile-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .profile-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
        }
        
        input[type="text"], 
        input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        input[type="text"]:focus, 
        input[type="password"]:focus {
            border-color: #2E8B57;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2E8B57, #3CB371);
            color: white;
            padding: 15px 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 139, 87, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 139, 87, 0.4);
            background: linear-gradient(135deg, #26734d, #32a067);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #e67e22, #d35400);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
        }
        
        .password-note {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 8px;
            font-style: italic;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
            padding: 5px;
            border-radius: 3px;
            text-align: center;
        }
        
        .strength-weak { background: #ffe6e6; color: #d63031; }
        .strength-medium { background: #fff9e6; color: #f39c12; }
        .strength-strong { background: #e6f7e6; color: #27ae60; }
        
        .reset-section {
            background: #fff3cd;
            border: 2px dashed #ffeaa7;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .reset-section h4 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .verification-code {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            margin: 15px 0;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
        }
        
        .verification-code:hover {
            background: #e9ecef;
            border-color: #2E8B57;
        }
        
        .verification-code.copied {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .reset-instructions {
            background: #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .profile-card {
                padding: 25px;
                margin: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .profile-header h1 {
                font-size: 1.6rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary, .btn-warning {
                width: 100%;
                text-align: center;
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
            
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                    <h1>👤 Edit Profil Admin</h1>
                    <p>Kelola informasi akun administrator Anda</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <strong>Berhasil!</strong> Profil admin berhasil diperbarui.
                        <?php if (!empty($new_password)): ?>
                            <br><small>Password telah diubah. Silakan gunakan password baru untuk login berikutnya.</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php foreach($errors as $error): ?>
                    <div class="alert alert-error">
                        ❌ <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>

                <div class="info-box">
                    <strong>💡 Informasi:</strong> Password menggunakan sistem bcrypt hash yang aman.
                    Jika lupa password, gunakan fitur reset di bawah.
                </div>

                <form method="POST" id="profileForm">
                    <div class="form-group">
                        <label for="username">📝 Username</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($admin['username']); ?>" 
                               required
                               placeholder="Masukkan username baru">
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">🔐 Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password" 
                               placeholder="Masukkan password saat ini untuk verifikasi"
                               value="<?php echo htmlspecialchars($current_password ?? ''); ?>">
                        <div class="password-note">Wajib diisi hanya jika ingin mengubah password</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">🔄 Password Baru</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Password baru (minimal 6 karakter)"
                               value="<?php echo htmlspecialchars($new_password ?? ''); ?>"
                               onkeyup="checkPasswordStrength(this.value)">
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">✅ Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Ketik ulang password baru"
                               value="<?php echo htmlspecialchars($confirm_password ?? ''); ?>"
                               onkeyup="checkPasswordMatch()">
                        <div id="passwordMatch" class="password-note"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            💾 Simpan Perubahan
                        </button>
                        <a href="index.php" class="btn-secondary">
                            ← Kembali ke Dashboard
                        </a>
                    </div>
                </form>
                
                <!-- Reset Password Section -->
                <div class="reset-section">
                    <h4>🔄 Reset Password (Emergency)</h4>
                    <p>Jika lupa password saat ini, gunakan fitur reset dengan kode verifikasi:</p>
                    
                    <div class="verification-code" id="verificationCode">
                        <strong><?php echo $today_verification; ?></strong>
                        <div style="font-size: 0.8rem; margin-top: 5px; color: #6c757d;">
                            Klik untuk menyalin
                        </div>
                    </div>
                    
                    <form method="POST" id="resetForm">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>">
                        
                        <div class="form-group">
                            <label for="reset_verification">🔒 Masukkan Kode Verifikasi</label>
                            <input type="text" id="reset_verification" name="reset_verification" 
                                   placeholder="Salin kode verifikasi di atas"
                                   required
                                   value="<?php echo htmlspecialchars($reset_verification ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="reset_new_password">🔄 Password Baru</label>
                            <input type="password" id="reset_new_password" name="new_password" 
                                   placeholder="Password baru (minimal 6 karakter)"
                                   required
                                   value="<?php echo htmlspecialchars($new_password ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="reset_confirm_password">✅ Konfirmasi Password Baru</label>
                            <input type="password" id="reset_confirm_password" name="confirm_password" 
                                   placeholder="Ketik ulang password baru"
                                   required
                                   value="<?php echo htmlspecialchars($confirm_password ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn-warning">
                            🔄 Reset Password
                        </button>
                    </form>
                    
                    <div class="reset-instructions">
                        <strong>Catatan:</strong> 
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>Kode verifikasi berubah setiap hari untuk keamanan</li>
                            <li>Pastikan kode yang dimasukkan sama persis dengan yang ditampilkan</li>
                            <li>Klik kode verifikasi untuk menyalin otomatis</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) sidebar.classList.toggle('active');
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                strengthDiv.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            let message = '';
            let className = '';
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                case 2:
                    message = 'Password lemah';
                    className = 'strength-weak';
                    break;
                case 3:
                case 4:
                    message = 'Password cukup kuat';
                    className = 'strength-medium';
                    break;
                case 5:
                    message = 'Password sangat kuat!';
                    className = 'strength-strong';
                    break;
            }
            
            strengthDiv.innerHTML = message;
            strengthDiv.className = 'password-strength ' + className;
        }

        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '✅ Password cocok';
                matchDiv.style.color = '#27ae60';
            } else {
                matchDiv.innerHTML = '❌ Password tidak cocok';
                matchDiv.style.color = '#e74c3c';
            }
        }

        // Form validation untuk reset form
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('reset_new_password').value;
            const confirmPassword = document.getElementById('reset_confirm_password').value;
            const verification = document.getElementById('reset_verification').value;
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password baru minimal 6 karakter');
                document.getElementById('reset_new_password').focus();
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak sesuai');
                document.getElementById('reset_confirm_password').focus();
                return;
            }
            
            if (!verification) {
                e.preventDefault();
                alert('Harap masukkan kode verifikasi');
                document.getElementById('reset_verification').focus();
                return;
            }
            
            if (!confirm('Yakin ingin reset password? Pastikan Anda sudah mencatat kode verifikasi dengan benar.')) {
                e.preventDefault();
            }
        });

        // Auto-copy verification code on click
        document.getElementById('verificationCode').addEventListener('click', function() {
            const text = this.querySelector('strong').textContent;
            navigator.clipboard.writeText(text).then(() => {
                // Visual feedback
                this.classList.add('copied');
                const originalHTML = this.innerHTML;
                this.innerHTML = '<strong>✅ Berhasil disalin!</strong>';
                
                setTimeout(() => {
                    this.classList.remove('copied');
                    this.innerHTML = originalHTML;
                }, 2000);
            }).catch(err => {
                // Fallback untuk browser lama
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Visual feedback
                this.classList.add('copied');
                const originalHTML = this.innerHTML;
                this.innerHTML = '<strong>✅ Berhasil disalin!</strong>';
                
                setTimeout(() => {
                    this.classList.remove('copied');
                    this.innerHTML = originalHTML;
                }, 2000);
            });
        });

        // Scroll to reset section jika ada error
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($show_reset_option): ?>
                document.querySelector('.reset-section').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'center'
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>