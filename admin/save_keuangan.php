<?php
require_once 'auth_check.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis = $_POST['jenis'] ?? '';
    $jumlah = $_POST['jumlah'] ?? 0;
    $keterangan = $_POST['keterangan'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    
    if(!empty($jenis) && $jumlah > 0 && !empty($keterangan)) {
        $result = query("INSERT INTO keuangan (jenis, jumlah, keterangan, tanggal) VALUES ('$jenis', $jumlah, '$keterangan', '$tanggal')");
        
        if($result) {
            $_SESSION['success'] = "Transaksi berhasil disimpan!";
        } else {
            $_SESSION['error'] = "Gagal menyimpan transaksi!";
        }
    } else {
        $_SESSION['error'] = "Semua field harus diisi dengan benar!";
    }
}

header('Location: keuangan.php');
exit;
?>
