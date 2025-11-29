<?php
session_start();

// Jika sudah install maka arahkan ke index
if (file_exists('.env')) {
    header('Location: index.php');
    exit;
}

// Initialize step dari GET atau session
$step = $_GET['step'] ?? ($_SESSION['current_step'] ?? 1);
$_SESSION['current_step'] = $step;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $step = $_POST['step'];

    /* ============================================================
        STEP 1 — TEST KONEKSI DATABASE & BUAT DATABASE
    ============================================================ */
    if ($step == 1) {
        $db_config = [
            'host' => $_POST['db_host'],
            'user' => $_POST['db_user'],
            'pass' => $_POST['db_pass'],
            'name' => $_POST['db_name']
        ];

        // Coba konek ke DB langsung
        $test = @new mysqli(
            $db_config['host'],
            $db_config['user'],
            $db_config['pass'],
            $db_config['name']
        );

        if ($test->connect_errno) {
            // Jika gagal, coba konek tanpa nama database
            $test2 = @new mysqli(
                $db_config['host'],
                $db_config['user'],
                $db_config['pass']
            );

            if ($test2->connect_errno) {
                $errors[] = "Gagal konek MySQL: " . $test2->connect_error;
            } else {
                // Buat database jika belum ada
                if ($test2->query("CREATE DATABASE IF NOT EXISTS `{$db_config['name']}`")) {
                    $_SESSION['db_config'] = $db_config;
                    $step = 2; // LANGSUNG KE STEP 2
                    $_SESSION['current_step'] = 2;
                } else {
                    $errors[] = "Gagal membuat database: " . $test2->error;
                }
            }
        } else {
            $_SESSION['db_config'] = $db_config;
            $step = 2; // LANGSUNG KE STEP 2
            $_SESSION['current_step'] = 2;
        }
    }

    /* ============================================================
        STEP 2 — DATA WEBSITE / MASJID
    ============================================================ */
    if ($step == 2) {
        // Validasi input
        if (empty($_POST['site_name']) || empty($_POST['timezone'])) {
            $errors[] = "Nama masjid dan timezone harus diisi!";
        } else {
            $_SESSION['site_config'] = [
                'site_name' => $_POST['site_name'],
                'masjid_city' => $_POST['masjid_city'],
                'timezone' => $_POST['timezone']
            ];
            $step = 3; // LANGSUNG KE STEP 3
            $_SESSION['current_step'] = 3;
        }
    }

    /* ============================================================
        STEP 3 — BUAT .env & IMPORT SETUP.SQL
    ============================================================ */
    if ($step == 3) {
        // Pastikan session data ada
        if (!isset($_SESSION['db_config']) || !isset($_SESSION['site_config'])) {
            $errors[] = "Data konfigurasi tidak lengkap! Silakan mulai dari awal.";
            $step = 1;
            $_SESSION['current_step'] = 1;
        } else {
            $db_config  = $_SESSION['db_config'];
            $site_config = $_SESSION['site_config'];

            // Validasi data
            $site_name = trim($site_config['site_name']);
            $timezone = trim($site_config['timezone']);
            $masjid_city = trim($site_config['masjid_city']);

            /* ======== BUAT FILE .env DARI .env.example ======== */
            if (!file_exists('.env.example')) {
                $errors[] = ".env.example tidak ditemukan!";
            } else {
                $env_template = file_get_contents('.env.example');
                
                // Replace configuration
                $replacements = [
                    'DB_HOST=localhost' => 'DB_HOST=' . $db_config['host'],
                    'DB_USER=root' => 'DB_USER=' . $db_config['user'],
                    'DB_PASS=' => 'DB_PASS=' . $db_config['pass'],
                    'DB_NAME=serambi_db' => 'DB_NAME=' . $db_config['name'],
                    'SITE_NAME=Masjid Al-Ikhlas' => 'SITE_NAME=' . $site_name,
                    'MASJID_TIMEZONE=Asia/Jakarta' => 'MASJID_TIMEZONE=' . $timezone,
                    'SITE_URL=http://localhost/serambi' => 'SITE_URL=http://' . $_SERVER['HTTP_HOST'] . '/serambi'
                ];

                foreach ($replacements as $search => $replace) {
                    $env_template = str_replace($search, $replace, $env_template);
                }

                if (file_put_contents('.env', $env_template) === false) {
                    $errors[] = "❌ Gagal membuat file .env! Check folder permissions.";
                } else {
                    $errors[] = "✅ File .env berhasil dibuat!";
                    $errors[] = "🕌 Nama Masjid: " . htmlspecialchars($site_name);
                    $errors[] = "📍 Lokasi: " . htmlspecialchars($masjid_city);
                    $errors[] = "🌐 Timezone: " . $timezone;
                }
            }

            // IMPORT SQL
            $sql_path = __DIR__ . '/database/setup.sql';
            if (!file_exists($sql_path)) {
                $errors[] = "setup.sql tidak ditemukan di folder /database!";
            } else {
                $sql = file_get_contents($sql_path);
                
                $db = new mysqli(
                    $db_config['host'],
                    $db_config['user'],
                    $db_config['pass'],
                    $db_config['name']
                );

                if ($db->connect_errno) {
                    $errors[] = "❌ Gagal koneksi MySQL: " . $db->connect_error;
                } else {
                    // Hapus baris CREATE DATABASE dari SQL
                    $sql = str_replace('CREATE DATABASE IF NOT EXISTS serambi_db;', '', $sql);
                    $sql = str_replace('USE serambi_db;', '', $sql);
                    
                    if ($db->multi_query($sql)) {
                        do {
                            if ($result = $db->store_result()) {
                                $result->free();
                            }
                        } while ($db->more_results() && $db->next_result());
                        
                        // Update admin password
                        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
                        $db->query("UPDATE admin SET password_hash = '$hashed_password' WHERE username = 'admin'");
                        
                        $success = true;
                        $step = 4;
                        $_SESSION['current_step'] = 4;
                    } else {
                        $errors[] = "❌ Gagal import SQL: " . $db->error;
                    }
                    $db->close();
                }
            }
        }
    }
}

// Update current step
$_SESSION['current_step'] = $step;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Install Serambi Berkah v1.0</title>
    <style>
        body { font-family: Arial; background: #eef2f3; padding: 30px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; }
        input, select { width: 100%; padding: 12px; margin-top: 8px; border-radius: 6px; border: 1px solid #ccc; }
        button { width: 100%; padding: 14px; background: #2E8B57; color: #fff; border: none; margin-top: 15px; border-radius: 6px; cursor: pointer; }
        .error { padding: 10px; background: #ffdddd; margin-bottom: 15px; border-left: 4px solid #d00; }
        .success { padding: 20px; background: #e7ffe7; border-left: 4px solid #0a0; margin-bottom: 20px; }
        .info { padding: 10px; background: #d1ecf1; margin-bottom: 15px; border-left: 4px solid #0c5460; }
        small { color: #666; font-size: 12px; display: block; margin-top: 5px; }
        .step-indicator { text-align: center; margin-bottom: 20px; font-weight: bold; color: #2E8B57; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🕌 Instalasi Serambi Berkah v1.0</h2>
        
        <!-- STEP INDICATOR -->
        <div class="step-indicator">
            Step <?php echo $step; ?> of 3
            <?php if($step == 1): ?> - Database Configuration
            <?php elseif($step == 2): ?> - Website Settings  
            <?php elseif($step == 3): ?> - Finalizing Setup
            <?php endif; ?>
        </div>
        <hr>

        <!-- ERROR MESSAGES -->
        <?php foreach($errors as $e): ?>
            <?php if (strpos($e, '✅') === 0): ?>
                <div class="info"><?php echo htmlspecialchars($e); ?></div>
            <?php else: ?>
                <div class="error"><?php echo htmlspecialchars($e); ?></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- SUCCESS MESSAGE -->
        <?php if($success): ?>
            <div class="success">
                <h3>🎉 Instalasi Berhasil!</h3>
                <p>Silakan login menggunakan akun default:</p>
                <p><b>Username:</b> admin<br>
                <b>Password:</b> admin123</p>
                <a href="index.php">➡ Masuk ke Website</a>
            </div>
        <?php endif; ?>

        <!-- STEP 1: DATABASE CONFIG -->
        <?php if($step == 1): ?>
            <form method="POST">
                <input type="hidden" name="step" value="1">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>
                
                <label>Database Username</label>
                <input type="text" name="db_user" value="root" required>
                
                <label>Database Password</label>
                <input type="password" name="db_pass" placeholder="Password MySQL Anda">
                
                <label>Database Name</label>
                <input type="text" name="db_name" value="serambi_db" required>
                
                <button type="submit">Lanjutkan ke Step 2 →</button>
            </form>
        <?php endif; ?>

        <!-- STEP 2: WEBSITE SETTINGS -->
        <?php if($step == 2): ?>
            <?php
            // Auto-detect server timezone
            $server_timezone = date_default_timezone_get();
            ?>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                
                <label>Nama Masjid</label>
                <input type="text" name="site_name" value="Masjid Al-Ikhlas" required>
                <small>Contoh: Masjid Al-Ikhlas, Musholla Al-Barakah, dll.</small>
                
                <label>Nama Kota/Lokasi</label>
                <input type="text" name="masjid_city" value="Jakarta" required>
                <small>Contoh: Jakarta, Bandung, Surabaya, dll.</small>
                
                <label>Zona Waktu</label>
                <select name="timezone" required>
                    <optgroup label="🕒 WIB (UTC+7) - Indonesia Barat">
                        <option value="Asia/Jakarta" <?php echo $server_timezone == 'Asia/Jakarta' ? 'selected' : ''; ?>>Jakarta & Sekitarnya</option>
                        <option value="Asia/Pontianak">Pontianak & Kalimantan Barat</option>
                    </optgroup>
                    
                    <optgroup label="🕒 WITA (UTC+8) - Indonesia Tengah">
                        <option value="Asia/Makassar" <?php echo $server_timezone == 'Asia/Makassar' ? 'selected' : ''; ?>>Bali, NTT, NTB, Sulawesi, Kalimantan</option>
                    </optgroup>
                    
                    <optgroup label="🕒 WIT (UTC+9) - Indonesia Timur">
                        <option value="Asia/Jayapura" <?php echo $server_timezone == 'Asia/Jayapura' ? 'selected' : ''; ?>>Papua & Maluku</option>
                    </optgroup>
                </select>
                <small>Zona waktu terdeteksi: <strong><?php echo $server_timezone; ?></strong></small>

                <button type="submit">Lanjut ke Step 3 →</button>
            </form>
        <?php endif; ?>

        <!-- STEP 3: PROCESSING -->
        <?php if($step == 3 && !$success): ?>
            <div class="info">
                <h3>⏳ Sedang Memproses Instalasi...</h3>
                <p>Harap tunggu, sistem sedang:</p>
                <ul>
                    <li>Membuat file konfigurasi (.env)</li>
                    <li>Mengimpor struktur database</li>
                    <li>Menyiapkan akun administrator</li>
                </ul>
            </div>
            
            <!-- Auto-submit form untuk process -->
            <form id="autoProcess" method="POST" style="display: none;">
                <input type="hidden" name="step" value="3">
            </form>
            <script>
                document.getElementById('autoProcess').submit();
            </script>
        <?php endif; ?>
    </div>
</body>
</html>