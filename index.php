<?php
require_once 'includes/config.php';

function getConstant($key, $default = '') {
    return defined($key) ? constant($key) : $default;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getConstant('SITE_NAME', 'Masjid Al-Ikhlas'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333; 
            min-height: 100vh;
        }
        .container { max-width: 100%; margin: 0 auto; padding: 0; }
        
        /* HEADER FULL WIDTH DENGAN BACKGROUND LOGO - DIPERPANJANG */
        .header {
            position: relative;
            width: 100%;
            height: 555px;
            background: url('assets/images/logo-masjid.jpg') center/cover no-repeat;
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }
        .header-content { 
            color: white; 
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
            margin-top: 10px;
        }
        .masjid-name {
            font-size: 2.2em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .masjid-location {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        /* MUTIARA KATA - DIPINDAH KE DALAM HEADER */
        .mutiara-slider {
            position: absolute;
            bottom: 10px;
            left: 10px;
            right: 10px;
            padding: 15px 20px;
            text-align: center;
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(3px);
            border-radius: 10px;
        }
        .mutiara-text {
            color: white;
            font-size: 1em;
            font-style: italic;
            margin-bottom: 5px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
        .mutiara-sumber {
            color: rgba(255,255,255,0.9);
            font-size: 0.8em;
        }
        
        /* GALLERY SLIDESHOW SEMUA FOTO */
        .gallery-section {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            margin: 10px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .gallery-section h2 {
            color: #2E8B57;
            margin-bottom: 15px;
            text-align: center;
            font-size: 1.4em;
        }
        .gallery-slideshow {
            position: relative;
            height: 700px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
        }
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .slide.active {
            opacity: 1;
        }
        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .slide-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: 20px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
        .slide-judul {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        /* MODAL UNTUK ZOOM GAMBAR */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            margin-top: 2%;
            border-radius: 10px;
            animation: zoomIn 0.3s;
        }
        .modal-info {
            color: white;
            text-align: center;
            padding: 20px;
            max-width: 80%;
            margin: 0 auto;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        @keyframes zoomIn {
            from {transform: scale(0.8);}
            to {transform: scale(1);}
        }
        
        /* FOUR MENU GRID */
        .menu-section {
            background: rgba(255,255,255,0.95);
            padding: 15px;
            margin: 10px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .menu-column {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        .menu-column h3 {
            color: #2E8B57;
            margin-bottom: 10px;
            font-size: 0.9em;
            text-align: center;
            border-bottom: 2px solid #2E8B57;
            padding-bottom: 5px;
        }
        
        /* PENGUMUMAN DENGAN SCROLL */
        .pengumuman-compact {
            max-height: 200px;
            width: 100%;
            overflow-y: auto;
            padding-right: 10px;
        }
        .pengumuman-compact::-webkit-scrollbar {
            width: 5px;
        }
        .pengumuman-compact::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .pengumuman-compact::-webkit-scrollbar-thumb {
            background: #2E8B57;
            border-radius: 10px;
        }
        .pengumuman-item {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .pengumuman-item:last-child {
            border-bottom: none;
        }
        .pengumuman-judul {
            font-weight: bold;
            font-size: 0.8em;
            color: #2E8B57;
            margin-bottom: 3px;
        }
        .pengumuman-isi {
            font-size: 0.75em;
            color: #666;
        }
        
        /* TIME & SHOLAT DENGAN TIMEZONE */
        .live-clock-compact {
            background: linear-gradient(135deg, #2E8B57, #3CB371);
            padding: 10px;
            border-radius: 8px;
            color: white;
            text-align: center;
            margin-bottom: 10px;
        }
        #liveTime {
            font-size: 1.3em;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            margin-bottom: 5px;
        }
        .clock-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.8em;
            opacity: 0.9;
        }
        .jadwal-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
        }
        .jadwal-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 5px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .jadwal-item.berikutnya {
            display: flex;
            background: #2E8B57;
            color: white;
            border-color: #2E8B57;
            padding: 8px 5px
        }
        .sholat-name { font-weight: 500; font-size: 0.75em; }
        .waktu { font-family: 'Courier New', monospace; font-weight: bold; font-size: 0.8em; }
        
        /* KEUANGAN DETAIL */
        .keuangan-detail {
            display: grid;
            gap: 8px;
        }
        .keuangan-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .keuangan-label { font-weight: 600; color: #2E8B57; font-size: 0.75em; }
        .keuangan-value { font-weight: bold; font-family: 'Courier New', monospace; font-size: 0.75em; }
        .pemasukan { color: #28a745; }
        .pengeluaran { color: #dc3545; }
        .saldo { color: #007bff; background: #e7f3ff; }
        
        .rincian-toggle {
            background: #2E8B57;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.7em;
            cursor: pointer;
            margin-top: 5px;
            width: 100%;
        }
        .rincian-list {
            max-height: 150px;
            overflow-y: auto;
            margin-top: 5px;
            display: none;
        }
        .rincian-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.7em;
        }
        
        /* PROFIL & KONTAK DENGAN SCROLL DAN LINK WA */
        .profil-kontak-compact {
            max-height: 150px;
            overflow-y: auto;
            padding-right: 5px;
        }
        .profil-kontak-compact::-webkit-scrollbar {
            width: 5px;
        }
        .profil-kontak-compact::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .profil-kontak-compact::-webkit-scrollbar-thumb {
            background: #2E8B57;
            border-radius: 10px;
        }
        .profil-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.75em;
        }
        .profil-item:last-child {
            border-bottom: none;
        }
        .profil-label {
            font-weight: 600;
            color: #2E8B57;
        }
        .profil-value {
            text-align: right;
        }
        .wa-link {
            color: #25D366;
            text-decoration: none;
            font-weight: bold;
        }
        .wa-link:hover {
            text-decoration: underline;
        }
        
        /* FOOTER */
        .footer {
            background: rgba(255,255,255,0.95);
            padding: 15px;
            margin: 10px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            font-size: 0.8em;
            color: #666;
        }
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .footer-version {
            color: #2E8B57;
            font-weight: bold;
        }
        .footer-developer {
            color: #764ba2;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .menu-grid { grid-template-columns: repeat(2, 1fr); }
            .jadwal-grid { grid-template-columns: repeat(2, 1fr); }
            .header { height: 200px; }
            .masjid-name { font-size: 1.8em; }
            .mutiara-slider { bottom: 10px; left: 10px; right: 10px; padding: 10px 15px; }
            .footer-content { flex-direction: column; gap: 10px; }
        }
        @media (max-width: 480px) {
            .menu-grid { grid-template-columns: 1fr; }
            .jadwal-grid { grid-template-columns: 1fr; }
            .header { height: 180px; }
            .masjid-name { font-size: 1.5em; }
            .mutiara-slider { font-size: 0.9em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER FULL WIDTH DIPERPANJANG DENGAN MUTIARA KATA DI DALAMNYA -->
        <div class="header">
            <div class="header-content">
                <div class="masjid-name"><?php echo getConstant('SITE_NAME', 'Masjid Al-Ikhlas RAJAWALI'); ?></div>
                <div class="masjid-location"><?php echo getConstant('MASJID_CITY', 'Serpong - Tangerang Selatan'); ?></div>
            </div>
            
            <!-- MUTIARA KATA DIPINDAH KE DALAM HEADER -->
            <div class="mutiara-slider">
                <?php
                $mutiara = query("SELECT * FROM mutiara_kata WHERE aktif = 1 ORDER BY RAND() LIMIT 1");
                if ($mutiara && $mutiara->num_rows > 0) {
                    $row = $mutiara->fetch_assoc();
                    echo '<div class="mutiara-text">" ' . htmlspecialchars($row['teks']) . ' "</div>';
                    if ($row['sumber']) {
                        echo '<div class="mutiara-sumber">~ ' . htmlspecialchars($row['sumber']) . ' ~</div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <!-- GALLERY SLIDESHOW SEMUA FOTO -->
        <div class="gallery-section">
            <h2>📷 Galeri Kegiatan</h2>
            <div class="gallery-slideshow" id="gallerySlideshow" onclick="openModal()">
                <?php
                $galeri = query("SELECT * FROM galeri ORDER BY created_at DESC");
                if ($galeri && $galeri->num_rows > 0) {
                    $counter = 0;
                    while($row = $galeri->fetch_assoc()) {
                        $active = $counter === 0 ? 'active' : '';
                        echo '<div class="slide '.$active.'" data-index="'.$counter.'">';
                        echo '<img src="uploads/'.$row['gambar'].'" alt="'.htmlspecialchars($row['judul']).'">';
                        echo '<div class="slide-info">';
                        echo '<div class="slide-judul">'.htmlspecialchars($row['judul']).'</div>';
                        if ($row['deskripsi']) {
                            echo '<div class="slide-deskripsi">'.htmlspecialchars($row['deskripsi']).'</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                        $counter++;
                    }
                } else {
                    echo '<div class="slide active"><img src="assets/images/default-gallery.jpg" alt="Default Image"><div class="slide-info"><div class="slide-judul">Belum ada galeri</div></div></div>';
                }
                ?>
            </div>
        </div>
        
        <!-- MODAL UNTUK ZOOM GAMBAR -->
        <div id="imageModal" class="modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <img class="modal-content" id="modalImage">
            <div id="modalInfo" class="modal-info"></div>
        </div>
        
        <!-- FOUR MENU GRID -->
        <div class="menu-section">
            <div class="menu-grid">
                <!-- PENGUMUMAN DENGAN SCROLL -->
                <div class="menu-column">
                    <h3>📢 Pengumuman</h3>
                    <div class="pengumuman-compact">
                        <?php
                        $pengumuman = query("SELECT * FROM pengumuman WHERE tanggal_berlaku >= CURDATE() OR tanggal_berlaku IS NULL ORDER BY penting DESC, created_at DESC LIMIT 10");
                        if ($pengumuman && $pengumuman->num_rows > 0) {
                            while($row = $pengumuman->fetch_assoc()) {
                                echo '<div class="pengumuman-item">';
                                echo '<div class="pengumuman-judul">'.($row['penting']?'⚠️ ':'📌 ').htmlspecialchars($row['judul']).'</div>';
                                echo '<div class="pengumuman-isi">'.mb_substr(htmlspecialchars($row['isi']),0,50).'...</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="pengumuman-item">';
                            echo '<div class="pengumuman-judul">Tidak ada pengumuman</div>';
                            echo '<div class="pengumuman-isi">Belum ada pengumuman terbaru</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- WAKTU & SHOLAT -->
                <div class="menu-column">
                    <h3>🕒 Waktu & Sholat</h3>
                    <div class="time-sholat-compact">
                        <div class="live-clock-compact">
                            <div id="liveTime"><?php echo date('H:i:s'); ?></div>
                            <div class="clock-info">
                                <span id="liveDate"><?php echo date('d/m/Y'); ?></span>
                                <span><?php echo getConstant('MASJID_TIMEZONE', 'Asia/Jakarta'); ?></span>
                            </div>
                        </div>
                        <div class="jadwal-grid">
                            <?php
                            $jadwal = query("SELECT * FROM jadwal WHERE jenis = 'sholat' ORDER BY urutan");
                            if ($jadwal && $jadwal->num_rows > 0) {
                                while($row = $jadwal->fetch_assoc()) {
                                    $waktu = date('H:i', strtotime($row['waktu']));
                                    echo '<div class="jadwal-item">';
                                    echo '<div class="sholat-name">'.htmlspecialchars($row['nama']).'</div>';
                                    echo '<div class="waktu">'.$waktu.'</div>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- KEUANGAN DETAIL -->
                <div class="menu-column">
                    <h3>💰 Keuangan</h3>
                    <div class="keuangan-detail">
                        <?php
                        // Total
                        $pemasukan = query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis='pemasukan'");
                        $pengeluaran = query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis='pengeluaran'");
                        $total_pemasukan = $pemasukan ? ($pemasukan->fetch_assoc()['total'] ?? 0) : 0;
                        $total_pengeluaran = $pengeluaran ? ($pengeluaran->fetch_assoc()['total'] ?? 0) : 0;
                        $saldo = $total_pemasukan - $total_pengeluaran;
                        ?>
                        <div class="keuangan-item">
                            <span class="keuangan-label">Pemasukan:</span>
                            <span class="keuangan-value pemasukan">Rp <?php echo number_format($total_pemasukan,0,',','.'); ?></span>
                        </div>
                        <div class="keuangan-item">
                            <span class="keuangan-label">Pengeluaran:</span>
                            <span class="keuangan-value pengeluaran">Rp <?php echo number_format($total_pengeluaran,0,',','.'); ?></span>
                        </div>
                        <div class="keuangan-item saldo">
                            <span class="keuangan-label">Saldo:</span>
                            <span class="keuangan-value">Rp <?php echo number_format($saldo,0,',','.'); ?></span>
                        </div>
                        
                        <button class="rincian-toggle" onclick="toggleRincian('pemasukan')">📥 Rincian Pemasukan</button>
                        <div class="rincian-list" id="rincianPemasukan">
                            <?php
                            $rincian_pemasukan = query("SELECT * FROM keuangan WHERE jenis='pemasukan' ORDER BY tanggal DESC LIMIT 5");
                            if ($rincian_pemasukan && $rincian_pemasukan->num_rows > 0) {
                                while($row = $rincian_pemasukan->fetch_assoc()) {
                                    echo '<div class="rincian-item">';
                                    echo '<span>'.htmlspecialchars($row['keterangan']).'</span>';
                                    echo '<span>Rp '.number_format($row['jumlah'],0,',','.').'</span>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                        
                        <button class="rincian-toggle" onclick="toggleRincian('pengeluaran')">📤 Rincian Pengeluaran</button>
                        <div class="rincian-list" id="rincianPengeluaran">
                            <?php
                            $rincian_pengeluaran = query("SELECT * FROM keuangan WHERE jenis='pengeluaran' ORDER BY tanggal DESC LIMIT 5");
                            if ($rincian_pengeluaran && $rincian_pengeluaran->num_rows > 0) {
                                while($row = $rincian_pengeluaran->fetch_assoc()) {
                                    echo '<div class="rincian-item">';
                                    echo '<span>'.htmlspecialchars($row['keterangan']).'</span>';
                                    echo '<span>Rp '.number_format($row['jumlah'],0,',','.').'</span>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- PROFIL & KONTAK DENGAN SCROLL DAN LINK WA -->
                <div class="menu-column">
                    <h3>📋 Profil & Kontak</h3>
                    <div class="profil-kontak-compact">
                        <div class="profil-item">
                            <span class="profil-label">Nama:</span>
                            <span class="profil-value"><?php echo getConstant('SITE_NAME', 'Masjid'); ?></span>
                        </div>
                        <div class="profil-item">
                            <span class="profil-label">Lokasi:</span>
                            <span class="profil-value"><?php echo getConstant('MASJID_CITY', 'Jakarta'); ?></span>
                        </div>
                        <div class="profil-item">
                            <span class="profil-label">Zona Waktu:</span>
                            <span class="profil-value"><?php echo getConstant('MASJID_TIMEZONE', 'Asia/Jakarta'); ?></span>
                        </div>
                        <div class="profil-item">
                            <span class="profil-label">Telp:</span>
                            <span class="profil-value">
                                <a href="tel:<?php echo getConstant('MASJID_PHONE', '+62123456789'); ?>" class="wa-link">
                                    <?php echo getConstant('MASJID_PHONE', '+62 123-4567-89'); ?>
                                </a>
                            </span>
                        </div>
                        <div class="profil-item">
                            <span class="profil-label">WhatsApp:</span>
                            <span class="profil-value">
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getConstant('MASJID_PHONE', '+62123456789')); ?>" target="_blank" class="wa-link">
                                    Chat Sekarang
                                </a>
                            </span>
                        </div>
                        <div class="profil-item">
                            <span class="profil-label">Email:</span>
                            <span class="profil-value">
                                <a href="mailto:<?php echo getConstant('MASJID_EMAIL', 'info@masjid.com'); ?>" class="wa-link">
                                    <?php echo getConstant('MASJID_EMAIL', 'info@masjid.com'); ?>
                                </a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER DENGAN VERSI DAN DEVELOPER -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-version">
                    Versi: <?php echo getConstant('APP_VERSION', '1.0.0'); ?>
                </div>
                <div class="footer-developer">
                    Developer: <?php echo getConstant('DEVELOPER_NAME', 'Tim IT Masjid Al-Ikhlas'); ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Live Clock
        function updateLiveClock() {
            const now = new Date();
            document.getElementById('liveTime').textContent = now.toLocaleTimeString('id-ID', {hour12: false});
            document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID');
        }
        setInterval(updateLiveClock, 1000);

        // Gallery Slideshow - SEMUA FOTO
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        
        function nextSlide() {
            if (slides.length > 0) {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }
        }
        
        // Jalankan slideshow hanya jika ada lebih dari 1 slide
        if (slides.length > 1) {
            setInterval(nextSlide, 5000);
        }

        // Modal untuk zoom gambar
        function openModal() {
            const activeSlide = document.querySelector('.slide.active');
            if (activeSlide) {
                const img = activeSlide.querySelector('img');
                const judul = activeSlide.querySelector('.slide-judul').textContent;
                const deskripsi = activeSlide.querySelector('.slide-deskripsi') ? 
                    activeSlide.querySelector('.slide-deskripsi').textContent : '';
                
                document.getElementById('modalImage').src = img.src;
                document.getElementById('modalImage').alt = img.alt;
                document.getElementById('modalInfo').innerHTML = 
                    `<div class="slide-judul">${judul}</div>` +
                    (deskripsi ? `<div class="slide-deskripsi">${deskripsi}</div>` : '');
                
                document.getElementById('imageModal').style.display = 'block';
            }
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Tutup modal ketika klik di luar gambar
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Toggle Rincian Keuangan
        function toggleRincian(jenis) {
            const element = document.getElementById('rincian' + jenis.charAt(0).toUpperCase() + jenis.slice(1));
            element.style.display = element.style.display === 'block' ? 'none' : 'block';
        }

        // Highlight Next Prayer
        function highlightNextPrayer() {
            const jadwalItems = document.querySelectorAll('.jadwal-item');
            const now = new Date();
            const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            
            let foundNext = false;
            jadwalItems.forEach(item => {
                const waktuElement = item.querySelector('.waktu');
                if (waktuElement) {
                    const waktuSholat = waktuElement.textContent;
                    item.classList.remove('berikutnya');
                    if (waktuSholat > currentTime && !foundNext) {
                        item.classList.add('berikutnya');
                        foundNext = true;
                    }
                }
            });
            
            // Jika tidak ada sholat berikutnya (sudah malam), highlight sholat pertama besok
            if (!foundNext && jadwalItems.length > 0) {
                jadwalItems[0].classList.add('berikutnya');
            }
        }
        setInterval(highlightNextPrayer, 60000);
        highlightNextPrayer();
    </script>
</body>
</html>
