<?php
require_once 'auth_check.php';

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $delete = query("DELETE FROM jadwal WHERE id = $id");
    
    if($delete) {
        $_SESSION['success'] = "Jadwal berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus jadwal!";
    }
} else {
    $_SESSION['error'] = "ID tidak valid!";
}

header('Location: jadwal.php');
exit;
?>
