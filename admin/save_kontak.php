<?php
require_once 'auth_check.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? '';
    $jabatan = $_POST['jabatan'] ?? '';
    $telepon = $_POST['telepon'] ?? '';
    $wa_link = $_POST['wa_link'] ?? '';
    $urutan = intval($_POST['urutan'] ?? 0);
    
    // Auto-generate WhatsApp link if empty
    if(empty($wa_link) && !empty($telepon)) {
        $clean_phone = preg_replace('/[^0-9]/', '', $telepon);
        if(substr($clean_phone, 0, 2) === '08') {
            $clean_phone = '62' . substr($clean_phone, 1);
        } else if(substr($clean_phone, 0, 1) === '8') {
            $clean_phone = '62' . $clean_phone;
        }
        $wa_link = 'https://wa.me/' . $clean_phone;
    }
    
    if(!empty($nama) && !empty($jabatan) && !empty($telepon)) {
        $result = query("INSERT INTO kontak (nama, jabatan, telepon, wa_link, urutan) VALUES ('$nama', '$jabatan', '$telepon', '$wa_link', $urutan)");
        
        if($result) {
            $_SESSION['success'] = "Kontak berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan kontak!";
        }
    } else {
        $_SESSION['error'] = "Nama, jabatan, dan telepon harus diisi!";
    }
}

header('Location: kontak.php');
exit;
?>
