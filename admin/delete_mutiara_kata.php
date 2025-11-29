<?php
require_once 'auth_check.php';

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $delete = query("DELETE FROM mutiara_kata WHERE id = $id");
    
    if($delete) {
        $_SESSION['success'] = "Mutiara kata berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus mutiara kata!";
    }
} else {
    $_SESSION['error'] = "ID tidak valid!";
}

header('Location: mutiara_kata.php');
exit;
?>
