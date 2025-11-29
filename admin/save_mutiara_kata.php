<?php
require_once 'auth_check.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teks = $_POST['teks'] ?? '';
    $sumber = $_POST['sumber'] ?? '';
    $urutan = intval($_POST['urutan'] ?? 0);
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    
    if(!empty($teks) && !empty($sumber)) {
        $result = query("INSERT INTO mutiara_kata (teks, sumber, urutan, aktif) VALUES ('$teks', '$sumber', $urutan, $aktif)");
        
        if($result) {
            $_SESSION['success'] = "Mutiara kata berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan mutiara kata!";
        }
    } else {
        $_SESSION['error'] = "Teks dan sumber harus diisi!";
    }
}

header('Location: mutiara_kata.php');
exit;
?>
