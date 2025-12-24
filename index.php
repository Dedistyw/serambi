<?php
// index.php - Halaman Publik SERAMBI (Versi Ringkas)
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Tambahkan header untuk mencegah caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include functions
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'visitor_tracker.php';

// Ambil semua data yang diperlukan dari JSON files
$profil_data = getJSONData('profil_masjid'); // File yang sama dengan profil_kontak.php
$mutiara_data = getJSONData('mutiara_kata');
$galeri_data = getJSONData('galeri');
$pengumuman_data = getJSONData('pengumuman');
$jadwal_data = getJSONData('jadwal_sholat');
$keuangan_data = getJSONData('keuangan');

// ==================== MUTIARA KATA ====================
$aktif_mutiara = [];
if (!empty($mutiara_data)) {
    $aktif_mutiara = array_filter($mutiara_data, function($item) {
        return isset($item['aktif']) && $item['aktif'] == 1;
    });
    shuffle($aktif_mutiara);
}

// ==================== GALERI ====================
$all_images = [];
if (!empty($galeri_data)) {
    $aktif_galeri = array_filter($galeri_data, function($item) {
        return isset($item['aktif']) && $item['aktif'] == 1;
    });
    shuffle($aktif_galeri);
    $all_images = $aktif_galeri;
}

// Backup: jika tidak ada gambar dari database, ambil dari folder
if (empty($all_images)) {
    $image_dir = 'uploads/images/';
    if (is_dir($image_dir)) {
        $image_files = scandir($image_dir);
        foreach($image_files as $file) {
            if ($file !== '.' && $file !== '..' && !is_dir($image_dir . $file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $site_name = $profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas';
                    $all_images[] = [
                        'gambar' => $file,
                        'judul' => 'Foto Kegiatan Masjid',
                        'deskripsi' => 'Kegiatan di Masjid ' . $site_name
                    ];
                }
            }
        }
    }
}

// Kelompokkan gambar menjadi grup-grup berisi 3 gambar
$image_groups = array_chunk($all_images, 3);
if (count($all_images) > 0 && count($image_groups) > 1) {
    $last_group = end($image_groups);
    if (count($last_group) < 3) {
        $needed = 3 - count($last_group);
        $first_images = array_slice($all_images, 0, $needed);
        $image_groups[count($image_groups) - 1] = array_merge($last_group, $first_images);
    }
}

// ==================== PENGUMUMAN ====================
$pengumuman_aktif = [];
if (!empty($pengumuman_data)) {
    $pengumuman_aktif = array_filter($pengumuman_data, function($item) {
        if (isset($item['aktif']) && $item['aktif'] != 1) return false;
        if (isset($item['tanggal_berlaku']) && $item['tanggal_berlaku']) {
            $today = date('Y-m-d');
            if ($item['tanggal_berlaku'] < $today) return false;
        }
        return true;
    });
    usort($pengumuman_aktif, function($a, $b) {
        $time_a = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
        $time_b = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
        return $time_b - $time_a;
    });
}

// ==================== JADWAL SHOLAT ====================
$jadwal_aktif = [];
if (!empty($jadwal_data)) {
    $jadwal_aktif = array_filter($jadwal_data, function($item) {
        return isset($item['aktif']) && $item['aktif'] == 1;
    });
    usort($jadwal_aktif, function($a, $b) {
        $urut_a = isset($a['urutan']) ? $a['urutan'] : 999;
        $urut_b = isset($b['urutan']) ? $b['urutan'] : 999;
        return $urut_a - $urut_b;
    });
}

// ==================== KEUANGAN ====================
$keuangan_aktif = [];
if (!empty($keuangan_data)) {
    $keuangan_aktif = array_filter($keuangan_data, function($item) {
        return isset($item['aktif']) && $item['aktif'] == 1;
    });
}

// Hitung total keuangan
$total_pemasukan = 0;
$total_pengeluaran = 0;
foreach ($keuangan_aktif as $row) {
    $jumlah = isset($row['jumlah']) ? floatval($row['jumlah']) : 0;
    if (isset($row['jenis'])) {
        if ($row['jenis'] == 'pemasukan') {
            $total_pemasukan += $jumlah;
        } elseif ($row['jenis'] == 'pengeluaran') {
            $total_pengeluaran += $jumlah;
        }
    }
}
$saldo = $total_pemasukan - $total_pengeluaran;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas'); ?></title>
    <style>
        /* RESET & BASE */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333; 
            min-height: 100vh;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 5px; }
        
        /* HEADER */
        .header {
            width: 100%;
            height: 550px;
            background: linear-gradient(rgba(0,0,0,0), rgba(0,0,0,0.3)), 
                        url('assets/images/logo-masjid.jpg') center/cover no-repeat;
            margin-bottom: 7px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 7px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .masjid-name {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
            color: white;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
        }
        .masjid-location {
            font-size: 1.2em;
            color: white;
            opacity: 0.9;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
        }
        
        /* MUTIARA KATA SLIDESHOW */
        .mutiara-slideshow {
            margin-top: 20px;
            padding: 15px;
            text-align: center;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(3px);
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            min-height: 100px;
            width: 80%;
            max-width: 700px;
            position: relative;
            overflow: hidden;
        }
        
        .mutiara-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }
        
        .mutiara-slide.active {
            opacity: 1;
        }
        
        .mutiara-text {
            color: white;
            font-size: 1.5em;
            font-style: italic;
            margin-bottom: 5px;
            text-align: center;
            line-height: 1.4;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
            padding: 0 8px;
            width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .mutiara-sumber {
            color: rgba(255,255,255,0.9);
            font-size: 1em;
            text-align: center;
            font-weight: 500;
            margin-top: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        /* LANDSCAPE PHOTOS SECTION - DISEMPITKAN JARAK */
        .landscape-photos-section {
            background: rgba(255,255,255,0.95);
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .landscape-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .landscape-photo-container {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            position: relative;
            transition: transform 0.3s ease;
            aspect-ratio: 16/9;
            width: 100%;
        }
        
        .landscape-photo-container:hover {
            transform: translateY(-3px);
        }
        
        .landscape-photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .landscape-photo-container img:hover {
            transform: scale(1.03);
        }
        
        .landscape-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 10px 12px;
            color: white;
        }
        
        .landscape-title {
            font-weight: bold;
            font-size: 1em;
            margin-bottom: 3px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
        
        .landscape-desc {
            font-size: 0.85em;
            opacity: 0.9;
        }
        
        /* GALERI */
        .gallery-section {
            background: rgba(255,255,255,0.95);
            padding: 5px;
            margin-bottom: 5px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .gallery-section h2 {
            color: #2E8B57;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.8em;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 10px;
        }
        
        /* MULTI-PHOTO SLIDESHOW CONTAINER */
        .multi-slideshow-container {
            position: relative;
            width: 100%;
            height: 350px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .multi-slideshow-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .multi-slideshow-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            gap: 10px;
            padding: 10px;
            background: #f5f5f5;
        }
        
        .multi-slideshow-slide.active {
            opacity: 1;
        }
        
        .multi-photo-item {
            flex: 1;
            height: 100%;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .multi-photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .multi-photo-item img:hover {
            transform: scale(1.02);
        }
        
        /* OVERLAY UNTUK 3 FOTO */
        .multi-photo-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 12px 15px;
        }
        
        .multi-photo-title {
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
            font-size: 0.85em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* SLIDESHOW NAVIGATION */
        .multi-slideshow-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 10px;
            z-index: 10;
        }
        
        .multi-slideshow-nav button {
            background: rgba(255,255,255,0.3);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: white;
            font-size: 1.3em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }
        
        .multi-slideshow-nav button:hover {
            background: rgba(255,255,255,0.5);
        }
        
        .multi-slideshow-indicators {
            position: absolute;
            bottom: 8px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 6px;
            z-index: 10;
        }
        
        .multi-slideshow-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .multi-slideshow-indicator.active {
            background: #2E8B57;
        }
        
        /* QR CONTAINER */
        .qr-container {
            height: 350px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        
        .qr-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 15px 20px;
        }
        
        .qr-title {
            font-weight: bold;
            color: white;
            margin-bottom: 6px;
            font-size: 1.1em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
        
        .qr-desc {
            color: rgba(255,255,255,0.9);
            font-size: 0.85em;
            line-height: 1.4;
        }
        
        /* FOUR MENU GRID */
        .menu-section {
            background: rgba(255,255,255,0.95);
            padding: 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .menu-column {
            background: #f8f9fa;
            padding: 8px 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            min-height: 450px;
        }
        .menu-column h3 {
            color: #2E8B57;
            margin-bottom: 8px;
            font-size: 1em;
            text-align: center;
            border-bottom: 2px solid #2E8B57;
            padding-bottom: 6px;
            flex-shrink: 0;
        }
        
        /* PENGUMUMAN - DENGAN SCROLL */
        .pengumuman-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .pengumuman-scroll {
            flex: 1;
            overflow-y: auto;
            padding-right: 8px;
            min-height: 0;
            max-height: 350px;
            scrollbar-width: thin;
            scrollbar-color: #2E8B57 #f1f1f1;
        }
        
        .pengumuman-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .pengumuman-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 8px;
        }
        
        .pengumuman-scroll::-webkit-scrollbar-thumb {
            background: #2E8B57;
            border-radius: 8px;
        }
        
        .pengumuman-scroll::-webkit-scrollbar-thumb:hover {
            background: #26734a;
        }
        
        .pengumuman-item {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        
        .pengumuman-item:hover {
            background-color: #f8f9fa;
        }
        
        .pengumuman-item:last-child {
            border-bottom: none;
        }
        
        .pengumuman-judul {
            font-weight: bold;
            font-size: 0.85em;
            color: #2E8B57;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        
        .pengumuman-isi {
            font-size: 0.8em;
            color: #666;
            line-height: 1.3;
        }
        
        .scroll-indicator {
            text-align: center;
            padding: 5px;
            font-size: 0.7em;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            margin-top: 5px;
        }
        
        .scroll-hint {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-3px);}
            60% {transform: translateY(-1px);}
        }
        
        /* WAKTU & SHOLAT */
        .time-sholat-compact {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .live-clock-compact {
            background: linear-gradient(135deg, #2E8B57, #3CB371);
            padding: 12px;
            border-radius: 8px;
            color: white;
            text-align: center;
            margin-bottom: 12px;
            flex-shrink: 0;
        }
        
        #liveTime {
            font-size: 1.6em;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            margin-bottom: 4px;
        }
        
        .clock-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85em;
            opacity: 0.9;
        }
        
        .jadwal-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            align-content: start;
            min-height: 0;
        }
        
        .jadwal-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 6px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
            transition: all 0.3s;
        }
        
        .jadwal-item.berikutnya {
            background: #2E8B57;
            color: white;
            border-color: #2E8B57;
            transform: scale(1.04);
            box-shadow: 0 4px 12px rgba(46, 139, 87, 0.3);
        }
        
        .sholat-name { 
            font-weight: 500; 
            font-size: 0.8em;
        }
        
        .waktu { 
            font-family: 'Courier New', monospace; 
            font-weight: bold; 
            font-size: 0.9em;
        }
        
        /* KEUANGAN */
        .keuangan-detail {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 0;
        }
        
        .keuangan-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .keuangan-label { 
            font-weight: 600; 
            color: #2E8B57; 
            font-size: 0.8em;
        }
        
        .keuangan-value { 
            font-weight: bold; 
            font-family: 'Courier New', monospace; 
            font-size: 0.8em;
        }
        
        .pemasukan { color: #28a745; }
        .pengeluaran { color: #dc3545; }
        .saldo { 
            color: #007bff; 
            background: #e7f3ff;
            padding: 10px 12px;
            border-radius: 6px;
        }
        
        .rincian-toggle {
            background: #2E8B57;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 0.75em;
            cursor: pointer;
            margin-top: 8px;
            width: 100%;
            transition: all 0.3s;
            flex-shrink: 0;
        }
        
        .rincian-toggle:hover {
            background: #26734a;
        }
        
        .rincian-list {
            flex: 1;
            overflow-y: auto;
            margin-top: 8px;
            display: none;
            min-height: 0;
            max-height: 150px;
        }
        
        .rincian-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.75em;
        }
        
        /* PROFIL & KONTAK - DENGAN SCROLL */
        .profil-kontak-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .profil-kontak-scroll {
            flex: 1;
            overflow-y: auto;
            padding-right: 8px;
            min-height: 0;
            max-height: 350px;
            scrollbar-width: thin;
            scrollbar-color: #2E8B57 #f1f1f1;
        }
        
        .profil-kontak-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .profil-kontak-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 8px;
        }
        
        .profil-kontak-scroll::-webkit-scrollbar-thumb {
            background: #2E8B57;
            border-radius: 8px;
        }
        
        .profil-kontak-scroll::-webkit-scrollbar-thumb:hover {
            background: #26734a;
        }
        
        .profil-kontak-full {
            display: grid;
            gap: 8px;
        }
        
        .profil-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            min-height: 40px;
            transition: background-color 0.2s;
        }
        
        .profil-item:hover {
            background-color: #f8f9fa;
        }
        
        .profil-label {
            font-weight: 600;
            color: #2E8B57;
            font-size: 0.75em;
            white-space: nowrap;
            padding-right: 8px;
            flex-shrink: 0;
        }
        
        .profil-value {
            text-align: right;
            font-size: 0.75em;
            word-break: break-word;
            flex: 1;
            min-width: 0;
        }
        
        .wa-link {
            color: #25D366;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .wa-link:hover {
            text-decoration: underline;
        }
        
        .email-link {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .email-link:hover {
            text-decoration: underline;
        }
        
        /* FOOTER */
        .footer {
            background: rgba(255,255,255,0.95);
            padding: 5px;
            margin-top: 5px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            font-size: 0.85em;
            color: #666;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .footer-version {
            color: #2E8B57;
            font-weight: bold;
        }
        
        .footer-developer {
            color: #764ba2;
        }
        
        /* IMAGE MODAL */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
            animation: zoomIn 0.3s;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: white;
            font-size: 35px;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes zoomIn {
            from { transform: scale(0.8); }
            to { transform: scale(1); }
        }
        
        /* RESPONSIVE */
        @media (max-width: 992px) {
            .menu-grid { grid-template-columns: repeat(2, 1fr); }
            .jadwal-grid { grid-template-columns: repeat(2, 1fr); }
            .gallery-grid { grid-template-columns: 1fr; }
            .landscape-grid { grid-template-columns: 1fr; }
            .header { height: 300px; }
            .masjid-name { font-size: 2em; }
            .multi-slideshow-container, .qr-container { height: 300px; }
            .menu-column { min-height: 400px; }
            
            .pengumuman-scroll,
            .profil-kontak-scroll {
                max-height: 300px;
            }
            
            .landscape-photo-container {
                aspect-ratio: 16/9;
                width: 100%;
            }
            
            .multi-slideshow-container {
                height: 400px !important;
            }
            
            .multi-photo-item img {
                object-fit: contain !important;
                background: #f0f0f0;
            }
            
            .mutiara-slideshow {
                width: 95%;
                padding: 15px;
            }
            
            .mutiara-text {
                font-size: 1em;
            }
            
            .mutiara-sumber {
                font-size: 0.8em;
            }
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .menu-grid { grid-template-columns: 1fr; }
            .jadwal-grid { grid-template-columns: 1fr; }
            .gallery-grid { grid-template-columns: 1fr; }
            .landscape-grid { grid-template-columns: 1fr; }
            .header { height: 250px; }
            .masjid-name { font-size: 1.8em; }
            .footer-content { flex-direction: column; gap: 12px; }
            .menu-column { min-height: 350px; margin-bottom: 15px; }
            
            .pengumuman-scroll,
            .profil-kontak-scroll {
                max-height: 250px;
            }
            
            .landscape-photo-container {
                aspect-ratio: 16/9;
                width: 100%;
            }
            
            .multi-slideshow-container {
                height: 450px !important;
            }
            
            .multi-slideshow-slide {
                display: flex !important;
                flex-direction: row !important;
                gap: 6px;
                padding: 6px;
            }
            
            .multi-photo-item {
                flex: 1;
                height: 100% !important;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8f8f8;
            }
            
            .multi-photo-item img {
                width: 100%;
                height: auto !important;
                max-height: 100%;
                object-fit: contain !important;
                object-position: center;
            }
            
            .multi-photo-overlay {
                background: linear-gradient(transparent, rgba(0,0,0,0.6));
                padding: 8px 10px;
            }
            
            .multi-photo-title {
                font-size: 0.75em;
                white-space: normal;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            
            .qr-container {
                height: 250px;
                margin-top: 20px;
            }
            
            .mutiara-slideshow {
                width: 90%;
                padding: 12px;
                margin-top: 12px;
            }
            
            .mutiara-text {
                font-size: 0.95em;
            }
            
            .mutiara-sumber {
                font-size: 0.8em;
            }
        }
        
        @media (max-width: 480px) {
            .header { height: 200px; }
            .masjid-name { font-size: 1.5em; }
            .masjid-location { font-size: 1em; }
            
            .pengumuman-scroll,
            .profil-kontak-scroll {
                max-height: 200px;
            }
            
            .landscape-photo-container {
                aspect-ratio: 16/9;
                width: 100%;
            }
            
            .multi-slideshow-container {
                height: 350px !important;
            }
            
            .multi-slideshow-slide {
                gap: 4px;
                padding: 4px;
            }
            
            .multi-photo-overlay {
                padding: 6px 8px;
            }
            
            .multi-photo-title {
                font-size: 0.7em;
                -webkit-line-clamp: 2;
            }
            
            .qr-container {
                height: 200px;
            }
            
            .mutiara-slideshow {
                padding: 8px;
                margin-top: 8px;
                min-height: 40px;
            }
            
            .mutiara-text {
                font-size: 0.9em;
                line-height: 1.2;
            }
            
            .mutiara-sumber {
                font-size: 0.75em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-content">
                <div class="masjid-name"><?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas'); ?></div>
                <div class="masjid-location"><?php echo htmlspecialchars($profil_data['MASJID_CITY'] ?? 'Serpong - Tangerang Selatan'); ?></div>
            </div>
            
            <!-- MUTIARA KATA SLIDESHOW -->
            <div class="mutiara-slideshow" id="mutiaraSlideshow">
                <?php if (count($aktif_mutiara) > 0): ?>
                    <?php foreach($aktif_mutiara as $index => $row): ?>
                        <div class="mutiara-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <div class="mutiara-text">"<?php echo htmlspecialchars($row['teks'] ?? ''); ?>"</div>
                            <?php if (isset($row['sumber']) && $row['sumber']): ?>
                                <div class="mutiara-sumber">~ <?php echo htmlspecialchars($row['sumber']); ?> ~</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default mutiara jika tidak ada data -->
                    <?php 
                    $default_mutiara = [
                        ["teks" => "Sebaik-baik manusia adalah yang paling bermanfaat bagi orang lain", "sumber" => "HR. Ahmad"],
                        ["teks" => "Barangsiapa beriman kepada Allah dan hari akhir, maka hendaklah ia memuliakan tetangganya", "sumber" => "HR. Bukhari"],
                        ["teks" => "Orang yang paling dicintai Allah adalah yang paling bermanfaat bagi manusia", "sumber" => "HR. Thabrani"],
                        ["teks" => "Sesungguhnya Allah menyukai apabila seseorang dari kamu mengerjakan suatu pekerjaan, maka ia melakukannya dengan tekun", "sumber" => "HR. Abu Ya'la"],
                        ["teks" => "Tidak sempurna iman seseorang di antara kamu sehingga ia mencintai saudaranya sebagaimana ia mencintai dirinya sendiri", "sumber" => "HR. Bukhari dan Muslim"]
                    ];
                    ?>
                    <?php foreach($default_mutiara as $index => $row): ?>
                        <div class="mutiara-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <div class="mutiara-text">"<?php echo htmlspecialchars($row['teks']); ?>"</div>
                            <div class="mutiara-sumber">~ <?php echo htmlspecialchars($row['sumber']); ?> ~</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- GALERI FOTO -->
        <div class="gallery-section">
            <h2>📷 Galeri Kegiatan Masjid</h2>
            <div class="gallery-grid">
                <!-- MULTI-PHOTO SLIDESHOW 3 FOTO SEKALIGUS -->
                <div class="multi-slideshow-container" id="multiSlideshowContainer">
                    <div class="multi-slideshow-wrapper" id="multiSlideshowWrapper">
                        <?php if (!empty($image_groups)): ?>
                            <?php foreach($image_groups as $group_index => $group): ?>
                                <div class="multi-slideshow-slide <?php echo $group_index === 0 ? 'active' : ''; ?>" 
                                     data-slide-index="<?php echo $group_index; ?>"
                                     id="slide-<?php echo $group_index; ?>">
                                    <?php foreach($group as $photo_index => $row): 
                                        $image_path = 'uploads/images/' . ($row['gambar'] ?? '');
                                        $has_image = file_exists($image_path) && !empty($row['gambar']);
                                        $default_image = 'assets/images/default-gallery.jpg';
                                        $actual_image = $has_image ? $image_path : $default_image;
                                        $image_title = htmlspecialchars($row['judul'] ?? 'Foto Kegiatan', ENT_QUOTES);
                                        $image_id = 'img-' . $group_index . '-' . $photo_index;
                                    ?>
                                        <div class="multi-photo-item" data-image-id="<?php echo $image_id; ?>">
                                            <img src="<?php echo $actual_image; ?>" 
                                                 alt="<?php echo $image_title; ?>"
                                                 id="<?php echo $image_id; ?>"
                                                 data-src="<?php echo $actual_image; ?>"
                                                 data-title="<?php echo $image_title; ?>"
                                                 data-slide-index="<?php echo $group_index; ?>"
                                                 data-photo-index="<?php echo $photo_index; ?>"
                                                 onclick="openCurrentImage(this)">
                                            <div class="multi-photo-overlay">
                                                <div class="multi-photo-title"><?php echo $image_title; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Default jika tidak ada gambar -->
                            <div class="multi-slideshow-slide active" data-slide-index="0" id="slide-0">
                                <?php for ($i = 0; $i < 3; $i++): 
                                    $image_id = 'img-0-' . $i;
                                ?>
                                    <div class="multi-photo-item" data-image-id="<?php echo $image_id; ?>">
                                        <img src="assets/images/default-gallery.jpg" 
                                             alt="Belum ada galeri"
                                             id="<?php echo $image_id; ?>"
                                             data-src="assets/images/default-gallery.jpg"
                                             data-title="Belum ada galeri"
                                             data-slide-index="0"
                                             data-photo-index="<?php echo $i; ?>"
                                             onclick="openCurrentImage(this)">
                                        <div class="multi-photo-overlay">
                                            <div class="multi-photo-title">Belum ada galeri</div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <?php if (count($image_groups) > 1): ?>
                    <div class="multi-slideshow-nav">
                        <button onclick="prevMultiSlide()">‹</button>
                        <button onclick="nextMultiSlide()">›</button>
                    </div>
                    
                    <!-- Indicators -->
                    <div class="multi-slideshow-indicators" id="multiSlideshowIndicators">
                        <?php for ($i = 0; $i < count($image_groups); $i++): ?>
                        <span class="multi-slideshow-indicator <?php echo $i === 0 ? 'active' : ''; ?>" 
                              data-slide="<?php echo $i; ?>"
                              onclick="goToMultiSlide(<?php echo $i; ?>)"></span>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- QR AMAL ONLINE -->
                <div class="qr-container">
                    <img src="assets/images/qr-amal.jpg" 
                         alt="QR Code Amal Online"
                         data-src="assets/images/qr-amal.jpg"
                         data-title="Scan untuk Amal Online"
                         onclick="openSpecificImage('assets/images/qr-amal.jpg', 'Scan untuk Amal Online')">
                    <div class="qr-overlay">
                        <div class="qr-title">Amal Online</div>
                        <div class="qr-desc">Scan QR Code untuk beramal ke <?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- LANDSCAPE PHOTOS ABOVE 4-MENU GRID -->
        <div class="landscape-photos-section">
            <div class="landscape-grid">
                
                <!-- Foto 1: Di atas Pengumuman dan Waktu Sholat -->
                <div class="landscape-photo-container">
                    <img src="assets/images/jadwal-khotbah.jpg" 
                         alt="Jadwal Khotbah Jumat"
                         onclick="openSpecificImage('assets/images/jadwal-khotbah.jpg', 'Jadwal Khotbah Jumat')">
                    <div class="landscape-overlay">
                        <div class="landscape-title">Jadwal Khotbah Jumat</div>
                        <div class="landscape-desc">Informasi jadwal khotib Jumat bulan ini</div>
                    </div>
                </div>
                
                <!-- Foto 2: Di atas Keuangan dan Kontak -->
                <div class="landscape-photo-container">
                    <img src="assets/images/jadwal-takjil.jpg" 
                         alt="Jadwal Takjil Ramadhan"
                         onclick="openSpecificImage('assets/images/jadwal-takjil.jpg', 'Jadwal Takjil Ramadhan')">
                    <div class="landscape-overlay">
                        <div class="landscape-title">Jadwal Takjil Ramadhan</div>
                        <div class="landscape-desc">Distribusi takjil gratis selama bulan suci</div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- FOUR MENU GRID -->
        <div class="menu-section">
            <div class="menu-grid">
                <!-- PENGUMUMAN -->
                <div class="menu-column">
                    <h3>📢 Pengumuman Terbaru</h3>
                    <div class="pengumuman-container">
                        <div class="pengumuman-scroll" id="pengumumanScroll">
                            <?php if (!empty($pengumuman_aktif)): 
                                $counter = 0;
                                foreach($pengumuman_aktif as $row): 
                                    if ($counter >= 15) break; // Tampilkan lebih banyak untuk scroll
                                    $icon = (isset($row['penting']) && $row['penting'] == 1) ? '⚠️ ' : '📌 ';
                            ?>
                                <div class="pengumuman-item">
                                    <div class="pengumuman-judul"><?php echo $icon . htmlspecialchars($row['judul'] ?? ''); ?></div>
                                    <div class="pengumuman-isi"><?php echo mb_substr(htmlspecialchars($row['isi'] ?? ''), 0, 120); ?>...</div>
                                </div>
                            <?php 
                                    $counter++;
                                endforeach; 
                            else: ?>
                                <div class="pengumuman-item">
                                    <div class="pengumuman-judul">Selamat Datang</div>
                                    <div class="pengumuman-isi">Website resmi Masjid <?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas'); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (count($pengumuman_aktif) > 8): ?>
                        <div class="scroll-indicator">
                            <span class="scroll-hint">↑↓ Scroll untuk melihat lebih banyak</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- WAKTU & SHOLAT -->
                <div class="menu-column">
                    <h3>🕒 Waktu Sholat</h3>
                    <div class="time-sholat-compact">
                        <div class="live-clock-compact">
                            <div id="liveTime"><?php echo date('H:i:s'); ?></div>
                            <div class="clock-info">
                                <span id="liveDate"><?php echo date('d/m/Y'); ?></span>
                                <span><?php echo htmlspecialchars($profil_data['MASJID_TIMEZONE'] ?? 'Asia/Jakarta'); ?></span>
                            </div>
                        </div>
                        <div class="jadwal-grid" id="jadwalGrid">
                            <?php if (!empty($jadwal_aktif)): 
                                foreach($jadwal_aktif as $index => $row): 
                                    $waktu = isset($row['waktu']) ? date('H:i', strtotime($row['waktu'])) : '00:00';
                                    $urutan_val = isset($row['urutan']) ? $row['urutan'] : $index;
                            ?>
                                <div class="jadwal-item" data-urutan="<?php echo $urutan_val; ?>" data-waktu="<?php echo $waktu; ?>">
                                    <div class="sholat-name"><?php echo htmlspecialchars($row['nama'] ?? ''); ?></div>
                                    <div class="waktu"><?php echo $waktu; ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php else: 
                                $default_jadwal = [
                                    ['nama' => 'Subuh', 'waktu' => '04:30'],
                                    ['nama' => 'Dzuhur', 'waktu' => '12:00'],
                                    ['nama' => 'Ashar', 'waktu' => '15:30'],
                                    ['nama' => 'Maghrib', 'waktu' => '18:00'],
                                    ['nama' => 'Isya', 'waktu' => '19:30']
                                ];
                                foreach($default_jadwal as $index => $row): ?>
                                <div class="jadwal-item" data-urutan="<?php echo $index; ?>" data-waktu="<?php echo $row['waktu']; ?>">
                                    <div class="sholat-name"><?php echo htmlspecialchars($row['nama']); ?></div>
                                    <div class="waktu"><?php echo $row['waktu']; ?></div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- KEUANGAN -->
                <div class="menu-column">
                    <h3>💰 Keuangan Masjid</h3>
                    <div class="keuangan-detail">
                        <div class="keuangan-item">
                            <span class="keuangan-label">Pemasukan:</span>
                            <span class="keuangan-value pemasukan">Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></span>
                        </div>
                        <div class="keuangan-item">
                            <span class="keuangan-label">Pengeluaran:</span>
                            <span class="keuangan-value pengeluaran">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></span>
                        </div>
                        <div class="keuangan-item saldo">
                            <span class="keuangan-label">Saldo:</span>
                            <span class="keuangan-value">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></span>
                        </div>
                        
                        <button class="rincian-toggle" onclick="toggleRincian('pemasukan')">📥 Rincian Pemasukan</button>
                        <div class="rincian-list" id="rincianPemasukan">
                            <?php 
                            $pemasukan = array_filter($keuangan_aktif, function($item) {
                                return isset($item['jenis']) && $item['jenis'] == 'pemasukan';
                            });
                            usort($pemasukan, function($a, $b) {
                                $time_a = isset($a['tanggal']) ? strtotime($a['tanggal']) : 0;
                                $time_b = isset($b['tanggal']) ? strtotime($b['tanggal']) : 0;
                                return $time_b - $time_a;
                            });
                            
                            if (!empty($pemasukan)): 
                                $counter = 0;
                                foreach($pemasukan as $row): 
                                    if ($counter >= 5) break;
                                    $keterangan = isset($row['keterangan']) ? $row['keterangan'] : 'Tanpa Keterangan';
                                    $jumlah = isset($row['jumlah']) ? $row['jumlah'] : 0;
                                    $tanggal = isset($row['tanggal']) ? date('d/m', strtotime($row['tanggal'])) : '';
                            ?>
                                <div class="rincian-item">
                                    <span><?php echo htmlspecialchars($keterangan); ?>
                                        <?php if ($tanggal): ?> <small>(<?php echo $tanggal; ?>)</small><?php endif; ?>
                                    </span>
                                    <span>Rp <?php echo number_format($jumlah, 0, ',', '.'); ?></span>
                                </div>
                            <?php 
                                    $counter++;
                                endforeach; 
                            else: ?>
                                <div class="rincian-item">Belum ada data pemasukan</div>
                            <?php endif; ?>
                        </div>
                        
                        <button class="rincian-toggle" onclick="toggleRincian('pengeluaran')">📤 Rincian Pengeluaran</button>
                        <div class="rincian-list" id="rincianPengeluaran">
                            <?php 
                            $pengeluaran = array_filter($keuangan_aktif, function($item) {
                                return isset($item['jenis']) && $item['jenis'] == 'pengeluaran';
                            });
                            usort($pengeluaran, function($a, $b) {
                                $time_a = isset($a['tanggal']) ? strtotime($a['tanggal']) : 0;
                                $time_b = isset($b['tanggal']) ? strtotime($b['tanggal']) : 0;
                                return $time_b - $time_a;
                            });
                            
                            if (!empty($pengeluaran)): 
                                $counter = 0;
                                foreach($pengeluaran as $row): 
                                    if ($counter >= 5) break;
                                    $keterangan = isset($row['keterangan']) ? $row['keterangan'] : 'Tanpa Keterangan';
                                    $jumlah = isset($row['jumlah']) ? $row['jumlah'] : 0;
                                    $tanggal = isset($row['tanggal']) ? date('d/m', strtotime($row['tanggal'])) : '';
                            ?>
                                <div class="rincian-item">
                                    <span><?php echo htmlspecialchars($keterangan); ?>
                                        <?php if ($tanggal): ?> <small>(<?php echo $tanggal; ?>)</small><?php endif; ?>
                                    </span>
                                    <span>Rp <?php echo number_format($jumlah, 0, ',', '.'); ?></span>
                                </div>
                            <?php 
                                    $counter++;
                                endforeach; 
                            else: ?>
                                <div class="rincian-item">Belum ada data pengeluaran</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- PROFIL & KONTAK -->
                <div class="menu-column">
                    <h3>📋 Profil & Kontak</h3>
                    <div class="profil-kontak-container">
                        <div class="profil-kontak-scroll" id="profilKontakScroll">
                            <div class="profil-kontak-full">
                                <div class="profil-item">
                                    <span class="profil-label">Nama Masjid:</span>
                                    <span class="profil-value"><?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas'); ?></span>
                                </div>
                                <div class="profil-item">
                                    <span class="profil-label">Lokasi:</span>
                                    <span class="profil-value"><?php echo htmlspecialchars($profil_data['MASJID_CITY'] ?? 'Serpong - Tangerang Selatan'); ?></span>
                                </div>
                                <div class="profil-item">
                                    <span class="profil-label">Zona Waktu:</span>
                                    <span class="profil-value"><?php echo htmlspecialchars($profil_data['MASJID_TIMEZONE'] ?? 'Asia/Jakarta'); ?></span>
                                </div>
                                <?php if (!empty($profil_data['MASJID_ADDRESS'])): ?>
                                <div class="profil-item">
                                    <span class="profil-label">Alamat:</span>
                                    <span class="profil-value"><?php echo htmlspecialchars($profil_data['MASJID_ADDRESS']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($profil_data['MASJID_DESCRIPTION'])): ?>
                                <div class="profil-item">
                                    <span class="profil-label">Deskripsi:</span>
                                    <span class="profil-value"><?php echo htmlspecialchars($profil_data['MASJID_DESCRIPTION']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="profil-item">
                                    <span class="profil-label">Telepon:</span>
                                    <span class="profil-value">
                                        <?php if (!empty($profil_data['MASJID_PHONE'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($profil_data['MASJID_PHONE']); ?>" class="wa-link"><?php echo htmlspecialchars($profil_data['MASJID_PHONE']); ?></a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="profil-item">
                                    <span class="profil-label">WhatsApp:</span>
                                    <span class="profil-value">
                                        <?php if (!empty($profil_data['MASJID_PHONE'])): 
                                            $phone_clean = preg_replace('/[^0-9]/', '', $profil_data['MASJID_PHONE']);
                                        ?>
                                            <a href="https://wa.me/<?php echo $phone_clean; ?>" target="_blank" class="wa-link">Chat Sekarang</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="profil-item">
                                    <span class="profil-label">Email:</span>
                                    <span class="profil-value">
                                        <?php if (!empty($profil_data['MASJID_EMAIL'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($profil_data['MASJID_EMAIL']); ?>" class="email-link"><?php echo htmlspecialchars($profil_data['MASJID_EMAIL']); ?></a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <!-- DATA PENGURUS DKM -->
                                <?php if (!empty($profil_data['KETUA_DKM_NAME'])): ?>
                                <div class="profil-item">
                                    <span class="profil-label">Ketua DKM:</span>
                                    <span class="profil-value">
                                        <?php echo htmlspecialchars($profil_data['KETUA_DKM_NAME']); ?>
                                        <?php if (!empty($profil_data['KETUA_DKM_PHONE'])): ?>
                                            <br><small><a href="tel:<?php echo htmlspecialchars($profil_data['KETUA_DKM_PHONE']); ?>" class="wa-link"><?php echo htmlspecialchars($profil_data['KETUA_DKM_PHONE']); ?></a></small>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($profil_data['SEKRETARIS_DKM_NAME'])): ?>
                                <div class="profil-item">
                                    <span class="profil-label">Sekretaris DKM:</span>
                                    <span class="profil-value">
                                        <?php echo htmlspecialchars($profil_data['SEKRETARIS_DKM_NAME']); ?>
                                        <?php if (!empty($profil_data['SEKRETARIS_DKM_PHONE'])): ?>
                                            <br><small><a href="tel:<?php echo htmlspecialchars($profil_data['SEKRETARIS_DKM_PHONE']); ?>" class="wa-link"><?php echo htmlspecialchars($profil_data['SEKRETARIS_DKM_PHONE']); ?></a></small>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($profil_data['BENDAHARA_DKM_NAME'])): ?>
                                <div class="profil-item">
                                    <span class="profil-label">Bendahara DKM:</span>
                                    <span class="profil-value">
                                        <?php echo htmlspecialchars($profil_data['BENDAHARA_DKM_NAME']); ?>
                                        <?php if (!empty($profil_data['BENDAHARA_DKM_PHONE'])): ?>
                                            <br><small><a href="tel:<?php echo htmlspecialchars($profil_data['BENDAHARA_DKM_PHONE']); ?>" class="wa-link"><?php echo htmlspecialchars($profil_data['BENDAHARA_DKM_PHONE']); ?></a></small>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($profil_data['KETUA_DKM_NAME']) || !empty($profil_data['SEKRETARIS_DKM_NAME']) || !empty($profil_data['BENDAHARA_DKM_NAME'])): ?>
                        <div class="scroll-indicator">
                            <span class="scroll-hint">↑↓ Scroll untuk melihat semua data</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-version">
                    <?php echo htmlspecialchars($profil_data['SITE_NAME'] ?? 'Masjid Al-Ikhlas'); ?> © <?php echo date('Y'); ?>
                </div>
                <div class="footer-developer">
                    <?php echo htmlspecialchars($profil_data['DEVELOPER_NAME'] ?? 'by hasan dan para muslim'); ?>
                </div>
                <div class="footer-info">
                    Versi <?php echo htmlspecialchars($profil_data['APP_VERSION'] ?? '1.0.0'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- IMAGE MODAL -->
    <div id="imageModal" class="image-modal">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        // ================================================
        // CORE FUNCTIONS
        // ================================================
        
        // Live Clock
        function updateLiveClock() {
            const now = new Date();
            document.getElementById('liveTime').textContent = now.toLocaleTimeString('id-ID', {hour12: false});
            document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID');
        }
        setInterval(updateLiveClock, 1000);

        // Highlight Next Prayer
        function highlightNextPrayer() {
            const jadwalItems = document.querySelectorAll('.jadwal-item');
            const now = new Date();
            const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            
            let foundNext = false;
            jadwalItems.forEach(item => {
                const waktuElement = item.querySelector('.waktu');
                if (waktuElement) {
                    const waktuSholat = waktuElement.textContent;
                    item.classList.remove('berikutnya');
                    if (waktuSholat > currentTime && !foundNext) {
                        item.classList.add('berikutnya');
                        foundNext = true;
                    }
                }
            });
            
            // Jika tidak ada sholat berikutnya (sudah malam), highlight sholat pertama besok
            if (!foundNext && jadwalItems.length > 0) {
                jadwalItems[0].classList.add('berikutnya');
            }
        }
        
        // Inisialisasi
        highlightNextPrayer();
        setInterval(highlightNextPrayer, 60000);
        
        // Toggle Rincian Keuangan
        function toggleRincian(jenis) {
            const element = document.getElementById('rincian' + jenis.charAt(0).toUpperCase() + jenis.slice(1));
            element.style.display = element.style.display === 'block' ? 'none' : 'block';
        }
        
        // ================================================
        // ZOOM GAMBAR
        // ================================================
        
        // Fungsi untuk membuka gambar yang sedang aktif dilihat
        function openCurrentImage(clickedImg) {
            const imageSrc = clickedImg.getAttribute('data-src') || clickedImg.src;
            const imageTitle = clickedImg.getAttribute('data-title') || clickedImg.alt;
            openSpecificImage(imageSrc, imageTitle);
        }
        
        // Fungsi untuk membuka gambar spesifik
        function openSpecificImage(imageSrc, imageTitle) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            modalImg.src = imageSrc;
            modalImg.alt = imageTitle;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal dengan ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Close modal dengan klik di luar gambar
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // ================================================
        // MUTIARA KATA SLIDESHOW
        // ================================================
        
        let mutiaraCurrentSlide = 0;
        let mutiaraInterval;
        
        function initMutiaraSlideshow() {
            const slides = document.querySelectorAll('.mutiara-slide');
            if (slides.length <= 1) return;
            
            mutiaraInterval = setInterval(nextMutiaraSlide, 5000);
        }
        
        function showMutiaraSlide(index) {
            const slides = document.querySelectorAll('.mutiara-slide');
            
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            
            mutiaraCurrentSlide = index;
        }
        
        function nextMutiaraSlide() {
            const slides = document.querySelectorAll('.mutiara-slide');
            const nextIndex = (mutiaraCurrentSlide + 1) % slides.length;
            showMutiaraSlide(nextIndex);
        }
        
        // ================================================
        // MULTI-PHOTO SLIDESHOW (3 foto sekaligus)
        // ================================================
        
        let multiCurrentSlide = 0;
        let multiInterval;
        
        function initMultiSlideshow() {
            const slides = document.querySelectorAll('.multi-slideshow-slide');
            if (slides.length <= 1) return;
            
            multiInterval = setInterval(nextMultiSlide, 5000);
            
            const indicators = document.querySelectorAll('.multi-slideshow-indicator');
            indicators.forEach(indicator => {
                indicator.addEventListener('click', function() {
                    const slideIndex = parseInt(this.getAttribute('data-slide'));
                    goToMultiSlide(slideIndex);
                });
            });
        }
        
        function showMultiSlide(index) {
            const slides = document.querySelectorAll('.multi-slideshow-slide');
            const indicators = document.querySelectorAll('.multi-slideshow-indicator');
            
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            
            indicators.forEach(indicator => {
                indicator.classList.remove('active');
            });
            if (indicators[index]) {
                indicators[index].classList.add('active');
            }
            
            multiCurrentSlide = index;
        }
        
        function nextMultiSlide() {
            const slides = document.querySelectorAll('.multi-slideshow-slide');
            const nextIndex = (multiCurrentSlide + 1) % slides.length;
            showMultiSlide(nextIndex);
        }
        
        function prevMultiSlide() {
            const slides = document.querySelectorAll('.multi-slideshow-slide');
            const prevIndex = (multiCurrentSlide - 1 + slides.length) % slides.length;
            showMultiSlide(prevIndex);
        }
        
        function goToMultiSlide(index) {
            showMultiSlide(index);
            clearInterval(multiInterval);
            multiInterval = setInterval(nextMultiSlide, 5000);
        }
        
        // Pause slideshow on hover
        const multiSlideshowContainer = document.getElementById('multiSlideshowContainer');
        if (multiSlideshowContainer) {
            multiSlideshowContainer.addEventListener('mouseenter', function() {
                clearInterval(multiInterval);
            });
            
            multiSlideshowContainer.addEventListener('mouseleave', function() {
                clearInterval(multiInterval);
                multiInterval = setInterval(nextMultiSlide, 5000);
            });
        }
        
        // Pause mutiara slideshow on hover
        const mutiaraSlideshow = document.getElementById('mutiaraSlideshow');
        if (mutiaraSlideshow) {
            mutiaraSlideshow.addEventListener('mouseenter', function() {
                clearInterval(mutiaraInterval);
            });
            
            mutiaraSlideshow.addEventListener('mouseleave', function() {
                clearInterval(mutiaraInterval);
                mutiaraInterval = setInterval(nextMutiaraSlide, 5000);
            });
        }
        
        // Setup untuk landscape photos
        const landscapePhotos = document.querySelectorAll('.landscape-photo-container img');
        if (landscapePhotos.length > 0) {
            landscapePhotos.forEach(photo => {
                photo.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const src = this.src;
                    const alt = this.alt;
                    openSpecificImage(src, alt);
                });
            });
        }
        
        // ================================================
        // SMOOTH SCROLL UNTUK PENGUMUMAN DAN PROFIL
        // ================================================
        
        // Smooth scroll untuk area scroll
        function initSmoothScroll() {
            const scrollAreas = document.querySelectorAll('.pengumuman-scroll, .profil-kontak-scroll');
            
            scrollAreas.forEach(area => {
                area.addEventListener('wheel', function(e) {
                    // Hentikan scroll default
                    if (this.scrollHeight > this.clientHeight) {
                        e.preventDefault();
                        this.scrollTop += e.deltaY;
                    }
                });
            });
        }
        
        // ================================================
        // INITIALIZE ON LOAD
        // ================================================
        
        document.addEventListener('DOMContentLoaded', function() {
            initMutiaraSlideshow();
            initMultiSlideshow();
            initSmoothScroll();
            
            // Setup event listeners untuk semua gambar di galeri
            const galleryImages = document.querySelectorAll('.multi-photo-item img');
            galleryImages.forEach(img => {
                img.onclick = null;
                img.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    openCurrentImage(this);
                }, false);
            });
            
            // Setup untuk QR code
            const qrImage = document.querySelector('.qr-container img');
            if (qrImage) {
                qrImage.onclick = null;
                qrImage.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const src = this.getAttribute('data-src') || this.src;
                    const title = this.getAttribute('data-title') || this.alt;
                    openSpecificImage(src, title);
                });
            }
        });
    </script>
</body>
</html>
