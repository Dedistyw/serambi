<?php
require_once 'auth_check.php';

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $delete = query("DELETE FROM keuangan WHERE id = $id");
    
    if($delete) {
        $_SESSION['success'] = "Transaksi berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus transaksi!";
    }
} else {
    $_SESSION['error'] = "ID tidak valid!";
}

header('Location: keuangan.php');
exit;
?>
