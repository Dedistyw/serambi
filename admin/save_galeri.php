<?php
require_once 'auth_check.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = $_POST['judul'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    
    // File upload handling
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $fileName = uniqid() . '_' . basename($_FILES['gambar']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check file size (max 10MB)
        if($_FILES['gambar']['size'] > 10 * 1024 * 1024) {
            $_SESSION['error'] = "File terlalu besar! Maksimal 10MB.";
            header('Location: galeri.php');
            exit;
        }
        
        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if(!in_array($fileExtension, $allowedTypes)) {
            $_SESSION['error'] = "Hanya file JPG, JPEG, PNG, GIF, WEBP yang diizinkan!";
            header('Location: galeri.php');
            exit;
        }
        
        // Upload file
        if(move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFile)) {
            // Save to database
            $result = query("INSERT INTO galeri (judul, gambar, deskripsi) VALUES ('$judul', '$fileName', '$deskripsi')");
            
            if($result) {
                $_SESSION['success'] = "Foto berhasil diupload!";
            } else {
                $_SESSION['error'] = "Gagal menyimpan data foto!";
                // Delete uploaded file if database failed
                unlink($targetFile);
            }
        } else {
            $_SESSION['error'] = "Gagal upload file!";
        }
    } else {
        $_SESSION['error'] = "Pilih file foto!";
    }
}

header('Location: galeri.php');
exit;
?>
