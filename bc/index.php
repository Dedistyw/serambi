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
        
        /* HEADER FULL WIDTH DENGAN BACKGROUND LOGO */
        .header {
            position: relative;
            width: 100%;
            height: 200px;
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
        
        /* MUTIARA KATA - TANPA BACKGROUND */
        .mutiara-slider {
            padding: 15px 20px;
            text-align: center;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            margin: 10px;
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
        
        /* GALLERY SLIDESHOW 2 FOTO BESAR */
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
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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
            background: #2E8B57;
            color: white;
            border-color: #2E8B57;
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
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .menu-grid { grid-template-columns: repeat(2, 1fr); }
            .jadwal-grid { grid-template-columns: repeat(2, 1fr); }
            .header { height: 150px; }
            .masjid-name { font-size: 1.8em; }
        }
        @media (max-width: 480px) {
            .menu-grid { grid-template-columns: 1fr; }
            .jadwal-grid { grid-template-columns: 1fr; }
            .header { height: 120px; }
            .masjid-name { font-size: 1.5em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER FULL WIDTH -->
        <div class="header">
            <div class="header-content">
                <div class="masjid-name"><?php echo getConstant('SITE_NAME', 'Masjid Al-Ikhlas RAJAWALI'); ?></div>
                <div class="masjid-location"><?php echo getConstant('MASJID_CITY', 'Serpong - Tangerang Selatan'); ?></div>
            </div>
        </div>
        
        <!-- MUTIARA KATA -->
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
        
        <!-- GALLERY SLIDESHOW 2 FOTO -->
        <div class="gallery-section">
            <h2>📷 Galeri Kegiatan</h2>
            <div class="gallery-slideshow" id="gallerySlideshow">
                <?php
                $galeri = query("SELECT * FROM galeri ORDER BY created_at DESC LIMIT 2");
                if ($galeri && $galeri->num_rows > 0) {
                    $counter = 0;
                    while($row = $galeri->fetch_assoc()) {
                        $active = $counter === 0 ? 'active' : '';
                        echo '<div class="slide '.$active.'">';
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
                }
                ?>
            </div>
        </div>
        
        <!-- FOUR MENU GRID -->
        <div class="menu-section">
            <div class="menu-grid">
                <!-- PENGUMUMAN -->
                <div class="menu-column">
                    <h3>📢 Pengumuman</h3>
                    <div class="pengumuman-compact">
                        <?php
                        $pengumuman = query("SELECT * FROM pengumuman WHERE tanggal_berlaku >= CURDATE() OR tanggal_berlaku IS NULL ORDER BY penting DESC, created_at DESC LIMIT 3");
                        if ($pengumuman && $pengumuman->num_rows > 0) {
                            while($row = $pengumuman->fetch_assoc()) {
                                echo '<div class="pengumuman-item">';
                                echo '<div class="pengumuman-judul">'.($row['penting']?'⚠️ ':'📌 ').htmlspecialchars($row['judul']).'</div>';
                                echo '<div class="pengumuman-isi">'.mb_substr(htmlspecialchars($row['isi']),0,50).'...</div>';
                                echo '</div>';
                            }
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
                
                <!-- PROFIL & KONTAK -->
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
                    </div>
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

        // Gallery Slideshow
        let currentSlide = 0;
        function nextSlide() {
            const slides = document.querySelectorAll('.slide');
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }
        setInterval(nextSlide, 5000);

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
        }
        setInterval(highlightNextPrayer, 60000);
        highlightNextPrayer();
    </script>
</body>
</html>