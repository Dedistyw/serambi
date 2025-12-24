<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Juga tambahkan di .htaccess

session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect jika sudah login
if (Auth::isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        if (Auth::login($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah';
            sleep(2); // Delay untuk brute force protection
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SERAMBI</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2E8B57, #3CB371);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #2E8B57;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2E8B57, #3CB371);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(46, 139, 87, 0.2);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-info {
            background: #e7f3ff;
            color: #0066cc;
            border: 1px solid #b3d9ff;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #eee;
            color: #777;
            font-size: 0.9em;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input {
            padding-right: 45px;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 70%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 2.5em;
        }
        
        @media (max-width: 480px) {
            .login-container {
                border-radius: 10px;
            }
            
            .login-header, .login-form {
                padding: 20px;
            }
        }
        
        .masjid-info {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .masjid-name {
            color: #2E8B57;
            font-weight: bold;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🕌 SERAMBI</h1>
            <p>Sistem Informasi Masjid - Panel Admin</p>
        </div>
        
        <div class="masjid-info">
            <div class="masjid-name"><?php echo htmlspecialchars(getConstant('SITE_NAME', 'Masjid')); ?></div>
            <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                <?php echo htmlspecialchars(getConstant('MASJID_CITY', '')); ?>
            </div>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Tampilkan info login default untuk pertama kali
            $admin_data = getJSONData('admin');
            if (empty($admin_data) || !isset($admin_data['username'])) {
                echo '<div class="alert alert-info">
                        🔐 Login pertama kali:<br>
                        Username: <strong>admin</strong><br>
                        Password: <strong>admin123</strong><br>
                        <small>Ganti password setelah login pertama!</small>
                      </div>';
            }
            ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($username); ?>"
                           required
                           autofocus>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        👁️
                    </button>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                
                <button type="submit" class="btn-login">
                    🔑 Masuk ke Dashboard
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <div>Versi <?php echo htmlspecialchars(getConstant('APP_VERSION', '1.0.0')); ?></div>
            <div style="margin-top: 5px; font-size: 0.85em;">
                <?php echo htmlspecialchars(getConstant('DEVELOPER_NAME', 'by hasan dan para muslim')); ?>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        // Enter key support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                document.querySelector('form').submit();
            }
        });
        
        // Auto focus on username
        document.getElementById('username').focus();
    </script>
</body>
</html>
