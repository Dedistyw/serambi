<?php
require_once 'auth_check.php';

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $delete = query("DELETE FROM kontak WHERE id = $id");
    
    if($delete) {
        $_SESSION['success'] = "Kontak berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus kontak!";
    }
} else {
    $_SESSION['error'] = "ID tidak valid!";
}

header('Location: kontak.php');
exit;
?>
