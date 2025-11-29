<?php
require_once '../includes/config.php';

// Redirect jika sudah login
if(isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Default credentials dari .env
    $default_user = env('ADMIN_USERNAME', 'admin');
    $default_pass = env('ADMIN_PASSWORD', 'admin123');
    
    if($username === $default_user && $password === $default_pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        
        // Update last login
        query("UPDATE admin SET last_login = NOW() WHERE username = '$username'");
        
        header('Location: index.php');
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?php echo env('SITE_NAME'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-container { max-width: 400px; margin: 100px auto; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h2 { color: #2E8B57; margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; }
        input:focus { border-color: #2E8B57; outline: none; }
        .login-btn { width: 100%; padding: 15px; background: #2E8B57; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
        .login-btn:hover { background: #256f47; }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .default-info { text-align: center; margin-top: 20px; padding: 10px; background: #e3f2fd; border-radius: 8px; font-size: 14px; }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h2>🕌 Admin Serambi Berkah</h2>
            <p><?php echo env('SITE_NAME'); ?></p>
        </div>
        
        <?php if($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" value="" placeholder="Username" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" value="" placeholder="Password" required autocomplete="off">
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="default-info">
            💡 Default: <strong><?php echo env('ADMIN_USERNAME', 'admin'); ?> / <?php echo env('ADMIN_PASSWORD', 'admin123'); ?></strong>
        </div>
    </div>
</body>
</html>
