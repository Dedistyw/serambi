<?php
require_once 'auth_check.php';

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get file info before delete
    $result = query("SELECT gambar FROM galeri WHERE id = $id");
    if($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $filePath = '../uploads/' . $row['gambar'];
        
        // Delete from database
        $delete = query("DELETE FROM galeri WHERE id = $id");
        
        if($delete) {
            // Delete physical file
            if(file_exists($filePath)) {
                unlink($filePath);
            }
            $_SESSION['success'] = "Foto berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus foto dari database!";
        }
    } else {
        $_SESSION['error'] = "Foto tidak ditemukan!";
    }
} else {
    $_SESSION['error'] = "ID tidak valid!";
}

header('Location: galeri.php');
exit;
?>
