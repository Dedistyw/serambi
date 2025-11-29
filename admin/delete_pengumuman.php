<?php
require_once 'auth_check.php';

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $delete = query("DELETE FROM pengumuman WHERE id = $id");
    
    if($delete) {
        $_SESSION['success'] = "Pengumuman berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus pengumuman!";
    }
} else {
    $_SESSION['error'] = "ID tidak valid!";
}

header('Location: pengumuman.php');
exit;
?>
