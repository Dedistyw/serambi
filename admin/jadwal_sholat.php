<?php
/**
 * Jadwal Sholat - Manual dan Auto Update dengan API Dunia
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

Auth::requireLogin();

$page_title = 'Jadwal Sholat';
$success_msg = '';
$error_msg = '';

// Default jadwal sholat dengan nama yang cocok dengan API
$default_jadwal = [
    ['id' => '1', 'nama' => 'Fajr (Subuh)', 'waktu' => '04:30:00', 'urutan' => 1, 'aktif' => 1, 'type' => 'wajib'],
    ['id' => '2', 'nama' => 'Sunrise (Terbit)', 'waktu' => '05:45:00', 'urutan' => 2, 'aktif' => 1, 'type' => 'sunnah'],
    ['id' => '3', 'nama' => 'Dhuhr (Zuhur)', 'waktu' => '12:00:00', 'urutan' => 3, 'aktif' => 1, 'type' => 'wajib'],
    ['id' => '4', 'nama' => 'Asr (Ashar)', 'waktu' => '15:30:00', 'urutan' => 4, 'aktif' => 1, 'type' => 'wajib'],
    ['id' => '5', 'nama' => 'Maghrib', 'waktu' => '18:00:00', 'urutan' => 5, 'aktif' => 1, 'type' => 'wajib'],
    ['id' => '6', 'nama' => 'Isha (Isya)', 'waktu' => '19:30:00', 'urutan' => 6, 'aktif' => 1, 'type' => 'wajib']
];

// Load data jadwal sholat
$jadwal_data = getJSONData('jadwal_sholat');
if (empty($jadwal_data)) {
    $jadwal_data = $default_jadwal;
    saveJSONData('jadwal_sholat', $jadwal_data);
}

// Fungsi untuk menambahkan/mengurangi menit pada waktu
function adjustTimeMinutes($time_str, $minutes) {
    if (empty($time_str) || $time_str == '00:00') return $time_str;
    
    // Parse waktu
    $time_clean = preg_replace('/\s*\([^)]+\)/', '', $time_str);
    $time_clean = trim($time_clean);
    
    // Konversi ke timestamp
    $timestamp = strtotime($time_clean);
    if ($timestamp === false) return $time_str;
    
    // Tambah/kurangi menit
    $adjusted = date('H:i', $timestamp + ($minutes * 60));
    
    return $adjusted;
}

// Fungsi untuk adjustment waktu Kemenag
function adjustKemenagTimings($timings, $city) {
    // Kemenag menggunakan aturan khusus untuk Indonesia
    // Berdasarkan SIHAT (Sistem Informasi Hisab dan Rukyat) Kemenag
    
    // Default adjustments untuk kota-kota besar Indonesia
    $city_adjustments = [
        'jakarta' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'surabaya' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'bandung' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'medan' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'makassar' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'semarang' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'palembang' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'denpasar' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'yogyakarta' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1],
        'malang' => ['fajr' => -2, 'isha' => 0, 'maghrib' => 1]
    ];
    
    $city_lower = strtolower($city);
    $adjustment = $city_adjustments[$city_lower] ?? ['fajr' => -2, 'isha' => 0, 'maghrib' => 1];
    
    // Apply adjustments
    $adjusted = $timings;
    
    // Adjust Fajr (Subuh) - Kemenag umumnya lebih awal 2 menit
    if (isset($timings['Fajr'])) {
        $adjusted['Fajr'] = adjustTimeMinutes($timings['Fajr'], $adjustment['fajr']);
    }
    
    // Adjust Isha - sesuai zona
    if (isset($timings['Isha'])) {
        $adjusted['Isha'] = adjustTimeMinutes($timings['Isha'], $adjustment['isha']);
    }
    
    // Adjust Maghrib - Kemenag umumnya +1 menit setelah matahari terbenam
    if (isset($timings['Maghrib'])) {
        $adjusted['Maghrib'] = adjustTimeMinutes($timings['Maghrib'], $adjustment['maghrib']);
    }
    
    // Khusus untuk Indonesia, tambahkan waktu Imsak (10 menit sebelum Subuh)
    if (isset($adjusted['Fajr'])) {
        $adjusted['Imsak'] = adjustTimeMinutes($adjusted['Fajr'], -10);
    }
    
    return $adjusted;
}

// Fungsi untuk mengambil data dari API Aladhan
function getPrayerTimesFromAPI($city, $country, $method = 100, $tanggal = null) {
    if ($tanggal === null) {
        $tanggal = date('Y-m-d');
    }
    
    // Format tanggal untuk API
    $date_parts = explode('-', $tanggal);
    $year = $date_parts[0];
    $month = $date_parts[1];
    $day = $date_parts[2];
    
    // Gunapi API langsung untuk tanggal tertentu
    $url = "http://api.aladhan.com/v1/timingsByCity/{$day}-{$month}-{$year}";
    $url .= "?city=" . urlencode($city);
    $url .= "&country=" . urlencode($country);
    
    // Khusus metode Kemenag (IDN = 100), kita perlu konfigurasi khusus
    if ($method == 100) {
        // Metode Kemenag menggunakan parameter khusus
        $url .= "&method=11"; // Gunakan Qatar sebagai base (mendekati Kemenag)
        $url .= "&shafaq=general"; // Parameter tambahan
        $url .= "&tune=0,0,0,0,0,0,0,0,0"; // Tuning untuk Kemenag
    } else {
        $url .= "&method={$method}";
    }
    
    $url .= "&school=0"; // 0 = Shafi, 1 = Hanafi (hanya untuk Asr)
    
    error_log("API URL: " . $url); // Debug
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('CURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        error_log("API Response Code: " . $http_code); // Debug
        error_log("API Response: " . substr($response, 0, 500)); // Debug
        
        if ($http_code !== 200) {
            throw new Exception("API returned HTTP code: {$http_code}");
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['data']['timings'])) {
            throw new Exception('Invalid API response structure');
        }
        
        // Ambil semua waktu sholat dari API
        $timings = $data['data']['timings'];
        
        // Debug: Tampilkan semua timing yang diterima
        error_log("API Timings Received: " . print_r($timings, true));
        
        // Jika metode Kemenag, kita perlu adjustment khusus
        if ($method == 100) {
            // Adjust waktu berdasarkan standar Kemenag Indonesia
            $timings = adjustKemenagTimings($timings, $city);
        }
        
        // Return semua waktu sholat dengan mapping yang lengkap
        $result = [
            'Fajr' => $timings['Fajr'] ?? '00:00',
            'Sunrise' => $timings['Sunrise'] ?? '00:00',
            'Dhuhr' => $timings['Dhuhr'] ?? '00:00',
            'Asr' => $timings['Asr'] ?? '00:00',
            'Maghrib' => $timings['Maghrib'] ?? '00:00',
            'Isha' => $timings['Isha'] ?? '00:00',
            'Imsak' => $timings['Imsak'] ?? '00:00',
            'Midnight' => $timings['Midnight'] ?? '00:00',
            'method' => $method,
            'method_name' => getMethodName($method),
            'location' => $data['data']['meta']['timezone'] ?? 'Asia/Jakarta',
            'date' => $data['data']['date']['readable'] ?? $tanggal
        ];
        
        error_log("API Processed Result: " . print_r($result, true));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("API Prayer Times Error: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mendapatkan nama metode
function getMethodName($method) {
    $methods = [
        1 => 'University of Islamic Sciences, Karachi',
        2 => 'Muslim World League',
        3 => 'Islamic Society of North America',
        4 => 'Umm al-Qura University, Makkah',
        5 => 'Egyptian General Authority of Survey',
        7 => 'Institute of Geophysics, University of Tehran',
        8 => 'Shia Ithna-Ashari, Leva Institute, Qum',
        9 => 'Gulf Region',
        10 => 'Kuwait',
        11 => 'Qatar',
        12 => 'Majlis Ugama Islam Singapura, Singapore',
        13 => 'Union Organization islamic de France',
        14 => 'Diyanet İşleri Başkanlığı, Turkey',
        15 => 'Spiritual Administration of Muslims of Russia',
        100 => 'Kementerian Agama Republik Indonesia (SIHAT)'
    ];
    
    return $methods[$method] ?? 'Unknown Method';
}

// Fungsi untuk parsing waktu dari API (format: "04:30 (WIB)" atau "04:30")
function parsePrayerTime($time_str) {
    if (empty($time_str) || $time_str == '00:00') return '00:00';
    
    // Hapus informasi zona waktu dalam kurung
    $time_clean = preg_replace('/\s*\([^)]+\)/', '', $time_str);
    
    // Hapus spasi
    $time_clean = trim($time_clean);
    
    // Cek jika format AM/PM
    if (stripos($time_clean, 'AM') !== false || stripos($time_clean, 'PM') !== false) {
        $parsed = date('H:i', strtotime($time_clean));
        return $parsed != '00:00' ? $parsed : '00:00';
    }
    
    // Validasi format HH:MM
    if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time_clean)) {
        return $time_clean;
    }
    
    // Coba format dengan seconds
    if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time_clean)) {
        return substr($time_clean, 0, 5);
    }
    
    return '00:00';
}

// Proses update jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_manual') {
        // Update manual dari form
        $updated_data = [];
        $urutan = 1;
        
        foreach ($jadwal_data as $item) {
            $id = $item['id'];
            $nama = sanitize($_POST["nama_{$id}"] ?? $item['nama']);
            $waktu = $_POST["waktu_{$id}"] ?? $item['waktu'];
            
            // Validasi waktu format HH:MM
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $waktu)) {
                $error_msg = "Format waktu tidak valid untuk {$nama}. Gunakan format HH:MM";
                break;
            }
            
            $waktu .= ':00'; // Tambah detik
            
            $updated_data[] = [
                'id' => $id,
                'nama' => $nama,
                'waktu' => $waktu,
                'urutan' => $urutan,
                'aktif' => isset($_POST["aktif_{$id}"]) ? 1 : 0,
                'type' => $item['type'] ?? 'tambahan',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $urutan++;
        }
        
        if (!$error_msg) {
            $jadwal_data = $updated_data;
            if (saveJSONData('jadwal_sholat', $jadwal_data)) {
                logActivity('JADWAL_UPDATE', 'Update manual jadwal sholat');
                $success_msg = 'Jadwal sholat berhasil diupdate';
                redirect('jadwal_sholat.php', $success_msg);
            } else {
                $error_msg = 'Gagal menyimpan jadwal sholat';
            }
        }
        
    } elseif ($action === 'tambah_waktu') {
        // Tambah waktu sholat baru
        $nama_baru = sanitize($_POST['nama_baru'] ?? '');
        $waktu_baru = $_POST['waktu_baru'] ?? '';
        
        if (empty($nama_baru)) {
            $error_msg = 'Nama waktu sholat harus diisi';
        } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $waktu_baru)) {
            $error_msg = 'Format waktu tidak valid. Gunakan format HH:MM';
        } else {
            $waktu_baru .= ':00';
            $urutan_baru = count($jadwal_data) + 1;
            
            $jadwal_data[] = [
                'id' => generateId(),
                'nama' => $nama_baru,
                'waktu' => $waktu_baru,
                'urutan' => $urutan_baru,
                'aktif' => 1,
                'type' => 'tambahan',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (saveJSONData('jadwal_sholat', $jadwal_data)) {
                logActivity('JADWAL_TAMBAH', "Waktu: {$nama_baru} - {$waktu_baru}");
                $success_msg = 'Waktu sholat baru berhasil ditambahkan';
                redirect('jadwal_sholat.php', $success_msg);
            } else {
                $error_msg = 'Gagal menambahkan waktu sholat';
            }
        }
        
    } elseif ($action === 'auto_update') {
        // Auto update dari API Aladhan
        $kota = sanitize($_POST['kota'] ?? 'Jakarta');
        $negara = sanitize($_POST['negara'] ?? 'Indonesia');
        $method = intval($_POST['method'] ?? 100); // Default ke Kemenag
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        
        error_log("Starting API Update - Kota: {$kota}, Negara: {$negara}, Method: {$method}, Tanggal: {$tanggal}");
        
        // Get prayer times from API
        $api_data = getPrayerTimesFromAPI($kota, $negara, $method, $tanggal);
        
        if ($api_data) {
            error_log("API Data Received: " . print_r($api_data, true));
            
            // Mapping nama waktu yang mungkin berbeda
            $prayer_mapping = [
                'Fajr' => ['Fajr (Subuh)', 'Fajr', 'Subuh', 'fajr'],
                'Sunrise' => ['Sunrise (Terbit)', 'Sunrise', 'Terbit', 'sunrise'],
                'Dhuhr' => ['Dhuhr (Zuhur)', 'Dhuhr', 'Dzuhur', 'Dhuhur', 'dhuhr', 'Zhuhur', 'Zuhur'],
                'Asr' => ['Asr (Ashar)', 'Asr', 'Ashar', 'asr'],
                'Maghrib' => ['Maghrib', 'Magrib', 'maghrib'],
                'Isha' => ['Isha (Isya)', 'Isha', 'Isya', 'isha']
            ];
            
            // Update waktu sholat berdasarkan nama yang cocok
            $updated = false;
            $update_count = 0;
            $update_details = [];
            
            foreach ($jadwal_data as &$item) {
                $nama_waktu = $item['nama'];
                $found_in_api = false;
                $api_time = '00:00';
                $api_key_match = '';
                
                // Cari mapping untuk nama waktu ini
                foreach ($prayer_mapping as $api_key => $possible_names) {
                    if (in_array($nama_waktu, $possible_names)) {
                        if (isset($api_data[$api_key]) && !empty($api_data[$api_key])) {
                            $found_in_api = true;
                            $api_time = $api_data[$api_key];
                            $api_key_match = $api_key;
                            break;
                        }
                    }
                }
                
                // Juga cek langsung jika nama waktu ada di API
                if (!$found_in_api && isset($api_data[$nama_waktu]) && !empty($api_data[$nama_waktu])) {
                    $found_in_api = true;
                    $api_time = $api_data[$nama_waktu];
                    $api_key_match = $nama_waktu;
                }
                
                if ($found_in_api) {
                    $parsed_time = parsePrayerTime($api_time);
                    
                    if ($parsed_time !== '00:00') {
                        $old_time = isset($item['waktu']) ? substr($item['waktu'], 0, 5) : '00:00';
                        $new_time = $parsed_time . ':00';
                        
                        // Cek apakah waktu berbeda
                        if ($old_time != $parsed_time) {
                            $item['waktu'] = $new_time;
                            $item['updated_at'] = date('Y-m-d H:i:s');
                            $item['source'] = 'API';
                            $item['method'] = $method;
                            $updated = true;
                            $update_count++;
                            
                            $update_details[] = "{$nama_waktu}: {$old_time} -> {$parsed_time}";
                            error_log("Updated {$nama_waktu}: {$old_time} -> {$parsed_time} (API Key: {$api_key_match})");
                        } else {
                            error_log("No change for {$nama_waktu}: {$old_time}");
                        }
                    } else {
                        error_log("Failed to parse time for {$nama_waktu}: {$api_time}");
                    }
                } else {
                    error_log("Not found in API: {$nama_waktu}");
                }
            }
            
            if ($updated) {
                // Simpan perubahan
                if (saveJSONData('jadwal_sholat', $jadwal_data)) {
                    // Save API metadata
                    $api_meta = [
                        'last_update' => date('Y-m-d H:i:s'),
                        'city' => $kota,
                        'country' => $negara,
                        'method' => $method,
                        'method_name' => $api_data['method_name'],
                        'timezone' => $api_data['location'],
                        'date' => $tanggal,
                        'updates_count' => $update_count,
                        'update_details' => $update_details
                    ];
                    saveJSONData('api_meta', $api_meta);
                    
                    logActivity('JADWAL_AUTO_UPDATE', "Kota: {$kota}, Negara: {$negara}, Method: {$method}, Updated: {$update_count} times");
                    $success_msg = "Jadwal sholat berhasil diupdate dari API ({$update_count} waktu diperbarui)";
                    redirect('jadwal_sholat.php', $success_msg);
                } else {
                    $error_msg = 'Gagal menyimpan perubahan ke database';
                }
            } else {
                // Debug untuk melihat apa yang salah
                $debug_info = "API Data Keys: " . implode(', ', array_keys($api_data)) . "\n";
                $debug_info .= "Jadwal Names: ";
                foreach ($jadwal_data as $item) {
                    $debug_info .= $item['nama'] . ', ';
                }
                error_log("Update failed. Debug: " . $debug_info);
                
                $error_msg = 'Tidak ada waktu sholat yang berhasil diupdate dari API. Periksa log untuk detail.';
            }
        } else {
            $error_msg = 'Gagal mengambil data dari API. Cek: 1) Koneksi internet, 2) Nama kota dan negara benar, 3) Coba method lain';
            error_log("API Failed: " . $error_msg);
        }
    }
}

// Hapus waktu sholat
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus = $_GET['id'];
    $token = $_GET['token'] ?? '';
    
    if (checkCSRFToken($token)) {
        $found = false;
        foreach ($jadwal_data as $key => $item) {
            if ($item['id'] == $id_hapus) {
                $nama_hapus = $item['nama'];
                unset($jadwal_data[$key]);
                
                // Reset urutan
                $jadwal_data = array_values($jadwal_data);
                foreach ($jadwal_data as $index => &$item) {
                    $item['urutan'] = $index + 1;
                }
                
                if (saveJSONData('jadwal_sholat', $jadwal_data)) {
                    logActivity('JADWAL_HAPUS', "Waktu: {$nama_hapus}");
                    redirect('jadwal_sholat.php', 'Waktu sholat berhasil dihapus');
                } else {
                    $error_msg = 'Gagal menghapus waktu sholat';
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $error_msg = 'Waktu sholat tidak ditemukan';
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
    }
}

// Load API metadata jika ada
$api_meta = getJSONData('api_meta');

// Debug: Tampilkan data jadwal saat ini
error_log("Current Jadwal Data: " . print_r($jadwal_data, true));

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
    
    <!-- Card 1: Jadwal Sholat Saat Ini -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-clock"></i>
                Jadwal Sholat Saat Ini
            </h3>
            <div class="card-actions">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#autoUpdateModal">
                    <i class="fas fa-sync"></i> Auto Update API
                </button>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#tambahModal">
                    <i class="fas fa-plus"></i> Tambah Waktu
                </button>
                <button type="button" class="btn btn-info" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if ($api_meta): ?>
                <div class="alert alert-info mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x mr-3"></i>
                        <div>
                            <strong>Update Terakhir dari API:</strong><br>
                            <small>
                                Tanggal: <?php echo date('d F Y H:i', strtotime($api_meta['last_update'])); ?><br>
                                Lokasi: <?php echo htmlspecialchars($api_meta['city'] . ', ' . $api_meta['country']); ?> | 
                                Metode: <?php echo htmlspecialchars($api_meta['method_name']); ?><br>
                                <?php if (isset($api_meta['updates_count'])): ?>
                                    Diperbarui: <?php echo $api_meta['updates_count']; ?> waktu sholat<br>
                                <?php endif; ?>
                                <?php if ($api_meta['method'] == 100): ?>
                                    <span class="badge badge-success">Kemenag RI (Tepat & Akurat)</span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Belum pernah diupdate dari API.</strong> Gunakan tombol "Auto Update API" untuk sinkronisasi dengan jadwal resmi Kemenag.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                <input type="hidden" name="action" value="update_manual">
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="35%">Nama Sholat</th>
                                <th width="25%">Waktu (HH:MM)</th>
                                <th width="15%">Tipe</th>
                                <th width="10%">Status</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Urutkan berdasarkan urutan
                            usort($jadwal_data, function($a, $b) {
                                return ($a['urutan'] ?? 999) <=> ($b['urutan'] ?? 999);
                            });
                            
                            foreach ($jadwal_data as $index => $item): 
                                $waktu_display = isset($item['waktu']) ? substr($item['waktu'], 0, 5) : '00:00';
                                $type_class = isset($item['type']) && $item['type'] == 'wajib' ? 'badge badge-primary' : 'badge badge-secondary';
                                $method_badge = isset($item['method']) && $item['method'] == 100 ? 'badge badge-success' : '';
                            ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <input type="text" 
                                               name="nama_<?php echo $item['id']; ?>"
                                               class="form-control form-control-sm" 
                                               value="<?php echo htmlspecialchars($item['nama'] ?? ''); ?>"
                                               required>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="time" 
                                                   name="waktu_<?php echo $item['id']; ?>"
                                                   class="form-control form-control-sm" 
                                                   value="<?php echo $waktu_display; ?>"
                                                   required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <i class="fas fa-clock"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if (isset($item['source']) && $item['source'] == 'API'): ?>
                                            <small class="text-success">
                                                <i class="fas fa-sync"></i> API 
                                                <?php if ($method_badge): ?>
                                                    <span class="<?php echo $method_badge; ?>" style="font-size: 0.7em;">Kemenag</span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $type_class; ?>">
                                            <?php echo isset($item['type']) ? ucfirst($item['type']) : 'Tambahan'; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="aktif_<?php echo $item['id']; ?>"
                                                   name="aktif_<?php echo $item['id']; ?>"
                                                   value="1"
                                                   <?php echo isset($item['aktif']) && $item['aktif'] == 1 ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="aktif_<?php echo $item['id']; ?>">
                                                <?php echo isset($item['aktif']) && $item['aktif'] == 1 ? 'Aktif' : 'Nonaktif'; ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <?php if (!in_array($item['nama'], ['Fajr (Subuh)', 'Dhuhr (Zuhur)', 'Asr (Ashar)', 'Maghrib', 'Isha (Isya)', 'Sunrise (Terbit)']) || (isset($item['type']) && $item['type'] !== 'wajib')): ?>
                                                <a href="#" 
                                                   onclick="confirmDelete('<?php echo $item['id']; ?>', '<?php echo htmlspecialchars($item['nama']); ?>')"
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 0.85em;">
                                                    <i class="fas fa-lock"></i> System
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <button type="reset" class="btn btn-light">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </form>
            
            <!-- Info Jadwal -->
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i>
                <strong>Informasi:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li><span class="badge badge-primary">Wajib</span> = 5 waktu sholat fardhu yang tidak bisa dihapus</li>
                    <li><span class="badge badge-secondary">Sunnah/Tambahan</span> = Waktu sholat sunnah yang bisa ditambah/dihapus</li>
                    <li><span class="badge badge-success">Kemenag RI</span> = Metode perhitungan resmi Kementerian Agama Indonesia</li>
                    <li>Format waktu: HH:MM (24 jam)</li>
                    <li>Nonaktifkan jika waktu sholat tidak ingin ditampilkan di website</li>
                    <li>Gunakan <strong>Auto Update API dengan metode Kemenag</strong> untuk sinkronisasi dengan jadwal resmi Indonesia</li>
                    <li>API menggunakan sumber: <a href="https://aladhan.com" target="_blank">Aladhan.com</a> dengan adjustment khusus Kemenag</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Card 2: Preview Jadwal -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-eye"></i>
                Preview Jadwal Sholat di Website
            </h3>
        </div>
        <div class="card-body">
            <div class="preview-jadwal">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                    <?php 
                    $now = new DateTime();
                    $current_time = $now->format('H:i');
                    $found_next = false;
                    
                    // Filter hanya yang aktif
                    $active_jadwal = array_filter($jadwal_data, function($item) {
                        return isset($item['aktif']) && $item['aktif'] == 1;
                    });
                    
                    foreach ($active_jadwal as $item): 
                        $waktu_display = isset($item['waktu']) ? substr($item['waktu'], 0, 5) : '00:00';
                        $is_next = (!$found_next && $waktu_display > $current_time);
                        if ($is_next) $found_next = true;
                        $is_kemenag = isset($item['method']) && $item['method'] == 100;
                    ?>
                        <div class="preview-item <?php echo $is_next ? 'next-prayer' : ''; ?> <?php echo $is_kemenag ? 'kemenag-method' : ''; ?>">
                            <div class="prayer-name">
                                <?php echo htmlspecialchars($item['nama']); ?>
                            </div>
                            <div class="prayer-time"><?php echo $waktu_display; ?></div>
                            <div class="prayer-type">
                                <span class="badge <?php echo isset($item['type']) && $item['type'] == 'wajib' ? 'badge-light' : 'badge-info'; ?>">
                                    <?php echo isset($item['type']) ? ucfirst($item['type']) : 'Tambahan'; ?>
                                </span>
                                <?php if ($is_kemenag): ?>
                                    <br><small class="badge badge-success" style="font-size: 0.7em; margin-top: 3px;">Kemenag</small>
                                <?php endif; ?>
                            </div>
                            <?php if ($is_next): ?>
                                <div class="prayer-badge">Berikutnya</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (!$found_next && !empty($active_jadwal)): ?>
                        <?php 
                        // Jika tidak ada sholat berikutnya, tandai sholat pertama besok
                        $first_item = reset($active_jadwal);
                        if ($first_item):
                            $waktu_display = isset($first_item['waktu']) ? substr($first_item['waktu'], 0, 5) : '00:00';
                            $is_kemenag = isset($first_item['method']) && $first_item['method'] == 100;
                        ?>
                            <div class="preview-item next-prayer <?php echo $is_kemenag ? 'kemenag-method' : ''; ?>">
                                <div class="prayer-name">
                                    <?php echo htmlspecialchars($first_item['nama']); ?>
                                </div>
                                <div class="prayer-time"><?php echo $waktu_display; ?></div>
                                <div class="prayer-badge">Besok</div>
                                <?php if ($is_kemenag): ?>
                                    <small class="badge badge-success" style="position: absolute; bottom: 5px; right: 5px; font-size: 0.6em;">Kemenag</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="preview-info mt-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <h6><i class="fas fa-info-circle"></i> Waktu Sekarang</h6>
                                <div class="current-time" id="currentTime"><?php echo date('H:i:s'); ?></div>
                                <small><?php echo date('d F Y'); ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <h6><i class="fas fa-map-marker-alt"></i> Lokasi</h6>
                                <div><?php echo htmlspecialchars(getConstant('MASJID_CITY', 'Jakarta')); ?></div>
                                <small><?php echo htmlspecialchars(getConstant('MASJID_COUNTRY', 'Indonesia')); ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <h6><i class="fas fa-moon"></i> Jadwal Aktif</h6>
                                <div><?php echo count($active_jadwal); ?> Waktu</div>
                                <small><?php echo count($jadwal_data) - count($active_jadwal); ?> Nonaktif</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <h6><i class="fas fa-sync"></i> Update Terakhir</h6>
                                <div>
                                    <?php if ($api_meta): ?>
                                        <?php echo date('d M', strtotime($api_meta['last_update'])); ?>
                                        <br><small><?php echo date('H:i', strtotime($api_meta['last_update'])); ?> via API</small>
                                        <?php if ($api_meta['method'] == 100): ?>
                                            <br><small class="badge badge-success">Kemenag</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Manual
                                        <br><small>Belum diupdate via API</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Auto Update -->
<div class="modal" id="autoUpdateModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-sync"></i>
                Auto Update Jadwal Sholat dari API
            </h3>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                <input type="hidden" name="action" value="auto_update">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="kota" class="form-label">Kota *</label>
                            <input type="text" 
                                   id="kota" 
                                   name="kota" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getConstant('MASJID_CITY', 'Jakarta')); ?>"
                                   required
                                   placeholder="Contoh: Jakarta, Bandung, Surabaya">
                            <div class="form-text">
                                Masukkan nama kota di Indonesia
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="negara" class="form-label">Negara *</label>
                            <input type="text" 
                                   id="negara" 
                                   name="negara" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getConstant('MASJID_COUNTRY', 'Indonesia')); ?>"
                                   required
                                   placeholder="Contoh: Indonesia">
                            <div class="form-text">
                                Untuk metode Kemenag, gunakan "Indonesia"
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="method" class="form-label">Metode Perhitungan *</label>
                            <select id="method" name="method" class="form-control" required>
                                <option value="100" selected>Kementerian Agama RI (SIHAT) - Direkomendasi untuk Indonesia</option>
                                <option value="5">Egyptian General Authority of Survey</option>
                                <option value="2">Muslim World League (MWL)</option>
                                <option value="1">University of Islamic Sciences, Karachi</option>
                                <option value="3">Islamic Society of North America (ISNA)</option>
                                <option value="4">Umm al-Qura University, Makkah</option>
                                <option value="12">Majlis Ugama Islam Singapura</option>
                                <option value="14">Diyanet İşleri Başkanlığı, Turkey</option>
                            </select>
                            <div class="form-text">
                                Untuk Indonesia: <strong>Kemenag (100)</strong> direkomendasikan | 
                                Metode lain untuk perbandingan
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal" class="form-label">Tanggal *</label>
                            <input type="date" 
                                   id="tanggal" 
                                   name="tanggal" 
                                   class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>"
                                   required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                            <div class="form-text">
                                Pilih tanggal untuk jadwal sholat
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Pastikan nama kota dan negara benar</li>
                        <li>Waktu akan diupdate untuk: <strong>Fajr (Subuh), Sunrise (Terbit), Dhuhr (Zuhur), Asr (Ashar), Maghrib, Isha (Isya)</strong></li>
                        <li>Metode Kemenag akan menyesuaikan waktu sesuai standar Kementerian Agama RI</li>
                        <li>Koneksi internet diperlukan untuk mengakses API</li>
                    </ul>
                </div>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>Keunggulan Metode Kemenag:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Mengikuti hisab dan rukyat resmi Indonesia</li>
                        <li>Waktu lebih akurat untuk wilayah Indonesia</li>
                        <li>Sudah termasuk penyesuaian Imsak (10 menit sebelum Subuh)</li>
                        <li>Mengikuti fatwa MUI untuk penentuan waktu sholat</li>
                    </ul>
                </div>
                
                <div id="apiPreview" class="mt-3" style="display: none;">
                    <div class="alert alert-secondary">
                        <h6><i class="fas fa-spinner fa-spin"></i> Mempersiapkan update...</h6>
                        <small id="previewDetails"></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info" onclick="previewAPISettings()">
                    <i class="fas fa-eye"></i> Preview
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Update dari API
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Tambah Waktu Sholat -->
<div class="modal" id="tambahModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-plus"></i>
                Tambah Waktu Sholat Baru
            </h3>
            <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                <input type="hidden" name="action" value="tambah_waktu">
                
                <div class="form-group">
                    <label for="nama_baru" class="form-label">Nama Waktu Sholat *</label>
                    <input type="text" 
                           id="nama_baru" 
                           name="nama_baru" 
                           class="form-control" 
                           required
                           placeholder="Contoh: Imsak, Tahajud, Dhuha, Witir">
                    <div class="form-text">
                        Nama waktu sholat tambahan selain 5 waktu wajib
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="waktu_baru" class="form-label">Waktu (HH:MM) *</label>
                    <input type="time" 
                           id="waktu_baru" 
                           name="waktu_baru" 
                           class="form-control" 
                           required>
                    <div class="form-text">
                        Format 24 jam. Contoh: 03:30 untuk Imsak
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Waktu sholat tambahan akan ditampilkan setelah waktu sholat wajib.
                    Anda dapat menonaktifkannya kapan saja.
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, nama) {
    if (confirm(`Apakah Anda yakin ingin menghapus waktu sholat:\n"${nama}"?`)) {
        const token = '<?php echo generateCSRF(); ?>';
        window.location.href = `?action=hapus&id=${id}&token=${token}`;
    }
}

// Update waktu sekarang di preview
function updateCurrentTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('id-ID', {hour12: false});
    document.getElementById('currentTime').textContent = timeStr;
}
setInterval(updateCurrentTime, 1000);

// Preview API data
function previewAPISettings() {
    const kota = document.getElementById('kota').value;
    const negara = document.getElementById('negara').value;
    const method = document.getElementById('method').value;
    const methodText = document.getElementById('method').options[document.getElementById('method').selectedIndex].text;
    const tanggal = document.getElementById('tanggal').value;
    
    if (kota && negara && tanggal) {
        const previewDiv = document.getElementById('apiPreview');
        const previewDetails = document.getElementById('previewDetails');
        
        previewDiv.style.display = 'block';
        previewDetails.innerHTML = `
            <strong>Konfigurasi Update:</strong><br>
            • Lokasi: ${kota}, ${negara}<br>
            • Metode: ${methodText}<br>
            • Tanggal: ${tanggal}<br>
            • Waktu yang akan diupdate: Fajr (Subuh), Sunrise (Terbit), Dhuhr (Zuhur), Asr (Ashar), Maghrib, Isha (Isya)<br>
            ${method == 100 ? '• <span class="text-success">Metode Kemenag: Waktu akan disesuaikan standar Indonesia</span><br>' : ''}
            <br>
            <em>Klik "Update dari API" untuk melanjutkan</em>
        `;
    } else {
        alert('Harap isi semua field terlebih dahulu!');
    }
}

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    const modalCloses = document.querySelectorAll('.modal-close, [data-dismiss="modal"]');
    
    // Show modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const modal = document.querySelector(target);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
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
.preview-jadwal {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.preview-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #dee2e6;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.preview-item.next-prayer {
    background: linear-gradient(135deg, #2E8B57, #3CB371);
    border-color: #2E8B57;
    color: white;
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
}

.preview-item.kemenag-method {
    border-left: 4px solid #28a745;
}

.preview-item.next-prayer .badge {
    background: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
}

.preview-item.next-prayer .badge-success {
    background: rgba(40, 167, 69, 0.8) !important;
}

.prayer-name {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 10px;
    line-height: 1.3;
    min-height: 2.6em;
    display: flex;
    align-items: center;
    justify-content: center;
}

.prayer-time {
    font-family: 'Courier New', monospace;
    font-size: 1.5em;
    font-weight: bold;
    margin-bottom: 10px;
    color: #2E8B57;
}

.preview-item.next-prayer .prayer-time {
    color: white;
}

.prayer-type {
    margin-top: 5px;
}

.prayer-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #ffc107;
    color: #212529;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 0.7em;
    font-weight: bold;
}

.preview-info {
    margin-top: 20px;
}

.info-box {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    height: 100%;
}

.info-box h6 {
    color: #2c3e50;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9em;
}

.current-time {
    font-family: 'Courier New', monospace;
    font-size: 1.5em;
    font-weight: bold;
    color: #2E8B57;
}

.form-switch {
    padding-left: 2.5em;
}

.form-switch .form-check-input {
    width: 2em;
    margin-left: -2.5em;
}

.form-switch .form-check-label {
    margin-top: 2px;
}

/* Badge styles */
.badge-primary {
    background-color: #3498db;
}

.badge-secondary {
    background-color: #95a5a6;
}

.badge-info {
    background-color: #17a2b8;
}

.badge-light {
    background-color: #ecf0f1;
    color: #2c3e50;
}

.badge-success {
    background-color: #28a745;
}

/* Responsive table */
.table-responsive {
    overflow-x: auto;
}

.table th, .table td {
    white-space: nowrap;
}

@media (max-width: 768px) {
    .preview-jadwal > div:first-child {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .preview-info .row > div {
        margin-bottom: 15px;
    }
    
    .table-responsive {
        font-size: 0.9em;
    }
    
    .prayer-name {
        font-size: 1em;
        min-height: 2.2em;
    }
    
    .prayer-time {
        font-size: 1.3em;
    }
}

@media (max-width: 480px) {
    .preview-jadwal > div:first-child {
        grid-template-columns: 1fr;
    }
    
    .table-responsive {
        font-size: 0.85em;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 5px;
    }
    
    .preview-item {
        padding: 15px;
    }
    
    .prayer-name {
        font-size: 0.95em;
        min-height: 2em;
    }
    
    .prayer-time {
        font-size: 1.2em;
    }
}
</style>

<?php include 'footer.php'; ?>
