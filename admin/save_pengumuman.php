<?php
require_once 'auth_check.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = $_POST['judul'] ?? '';
    $isi = $_POST['isi'] ?? '';
    $penting = isset($_POST['penting']) ? 1 : 0;
    
    if(!empty($judul) && !empty($isi)) {
        $result = query("INSERT INTO pengumuman (judul, isi, penting) VALUES ('$judul', '$isi', $penting)");
        
        if($result) {
            $_SESSION['success'] = "Pengumuman berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan pengumuman!";
        }
    } else {
        $_SESSION['error'] = "Judul dan isi pengumuman harus diisi!";
    }
}

header('Location: pengumuman.php');
exit;
?>
