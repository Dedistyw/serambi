# 🕌 SERAMBI - Sistem Informasi Masjid

Sistem informasi masjid berbasis file JSON yang mudah diinstal dan dikelola.

## ✨ Fitur Utama

- ✅ **Tanpa Database** - Menggunakan file JSON untuk penyimpanan data
- ✅ **Admin Panel** - Dashboard lengkap untuk mengelola semua konten
- ✅ **Responsif** - Tampilan optimal di desktop dan mobile
- ✅ **Galeri Foto** - Slideshow dengan zoom gambar
- ✅ **Pengumuman** - Sistem pengumuman dengan tanggal berlaku
- ✅ **Jadwal Sholat** - Menampilkan jadwal sholat sinkron API dan highlight sholat berikutnya
- ✅ **Keuangan** - Pencatatan pemasukan dan pengeluaran
- ✅ **Mutiara Kata** - Kutipan Islami acak di header
- ✅ **Kontak Masjid** - Informasi kontak dengan link WhatsApp
- ✅ **Live Clock** - Jam digital dengan waktu server

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- Ekstensi PHP: JSON, GD (untuk gambar), Fileinfo
- Permission folder: uploads/ (755)
- Web server: Apache/Nginx

## 🚀 Instalasi

### Cara 1: Manual
1. Download semua file proyek
2. Upload ke server web Anda
3. Atur permission folder:
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/data/
   chmod 755 uploads/images/
   chmod 755 assets/
   chmod 755 assets/images/
   sudo chown -R www-data:www-data /var/www/html/serambi/
   sudo chmod -R 755 /var/www/html/serambi/
   sudo chmod -R 775 /var/www/html/serambi/uploads/
   sudo chmod -R 775 /var/www/html/serambi/assets/

4. login pertama admin/admin123 , setelah berhasil segera ubah password nya

### Cara otomatis
1. ketik alaman https://domain.anda.com.serambi/setup.php
2. ikutin panduan yang ditampilkan.

software ini dikembangkan oleh HASAN & para Muslim didunia,
kalian bisa berkontribusi di proyek ini bersama kami, membangun pemuda yang tangguh , mengutamakan akhlak.
