<?php
require_once 'auth_check.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis = $_POST['jenis'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $hari = $_POST['hari'] ?? 'setiap';
    $waktu = $_POST['waktu'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    $urutan = intval($_POST['urutan'] ?? 0);
    
    if(!empty($jenis) && !empty($nama) && !empty($waktu)) {
        $result = query("INSERT INTO jadwal (jenis, nama, hari, waktu, keterangan, urutan) VALUES ('$jenis', '$nama', '$hari', '$waktu', '$keterangan', $urutan)");
        
        if($result) {
            $_SESSION['success'] = "Jadwal berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan jadwal!";
        }
    } else {
        $_SESSION['error'] = "Jenis, nama, dan waktu harus diisi!";
    }
}

header('Location: jadwal.php');
exit;
?>
