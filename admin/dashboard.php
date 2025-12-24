<?php
/**
 * Dashboard Admin - Statistik dan Overview
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
Auth::requireLogin();

$page_title = 'Dashboard';

// Ambil data statistik
$pengumuman_data = getJSONData('pengumuman');
$mutiara_data = getJSONData('mutiara_kata');
$galeri_data = getJSONData('galeri');
$keuangan_data = getJSONData('keuangan');

// AMBIL DAN UPDATE DATA PENGUNJUNG DARI JSON
$visitors_data = getJSONData('visitors');
if (empty($visitors_data)) {
    // Jika belum ada data, inisialisasi
    $visitors_data = [
        'total_visitors' => 0,
        'today_visitors' => 0,
        'unique_visitors' => 0,
        'visitors_by_day' => [],
        'visitors_by_ip' => [],
        'last_reset' => date('Y-m-d'),
        'last_visit' => null,
        'last_ip' => null
    ];
}

// Update pengunjung jika belum tercatat hari ini
$today = date('Y-m-d');
$current_ip = $_SERVER['REMOTE_ADDR'];

// Reset data hari ini jika sudah berganti hari
if ($visitors_data['last_reset'] != $today) {
    $visitors_data['today_visitors'] = 0;
    $visitors_data['visitors_by_ip'] = [];
    $visitors_data['last_reset'] = $today;
}

// Hitung pengunjung unik berdasarkan IP
if (!isset($visitors_data['visitors_by_ip'][$today])) {
    $visitors_data['visitors_by_ip'][$today] = [];
}

if (!in_array($current_ip, $visitors_data['visitors_by_ip'][$today])) {
    // Tambah pengunjung unik hari ini
    $visitors_data['visitors_by_ip'][$today][] = $current_ip;
    $visitors_data['today_visitors'] = count($visitors_data['visitors_by_ip'][$today]);
    
    // Tambah ke total pengunjung
    $visitors_data['total_visitors']++;
    
    // Tambah ke pengunjung unik total
    if (!in_array($current_ip, $visitors_data['visitors_by_day'])) {
        $visitors_data['unique_visitors']++;
        $visitors_data['visitors_by_day'][] = $current_ip;
    }
    
    // Update last visit
    $visitors_data['last_visit'] = date('Y-m-d H:i:s');
    $visitors_data['last_ip'] = $current_ip;
    
    // Simpan data pengunjung
    saveJSONData('visitors', $visitors_data);
    
    // Log aktivitas
    logActivity('VISITOR_COUNT', "Pengunjung baru: $current_ip");
}

// Hitung statistik dari data pengunjung
$total_visitors = $visitors_data['total_visitors'] ?? 0;
$today_visitors = $visitors_data['today_visitors'] ?? 0;
$unique_visitors = $visitors_data['unique_visitors'] ?? 0;

// Hitung statistik lainnya
$total_pengumuman = count($pengumuman_data);
$active_pengumuman = count(array_filter($pengumuman_data, function($item) {
    return isset($item['aktif']) && $item['aktif'] == 1;
}));

$total_mutiara = count($mutiara_data);
$active_mutiara = count(array_filter($mutiara_data, function($item) {
    return isset($item['aktif']) && $item['aktif'] == 1;
}));

$total_galeri = count($galeri_data);
$active_galeri = count(array_filter($galeri_data, function($item) {
    return isset($item['aktif']) && $item['aktif'] == 1;
}));

// Hitung keuangan
$total_pemasukan = 0;
$total_pengeluaran = 0;
foreach ($keuangan_data as $item) {
    if (isset($item['aktif']) && $item['aktif'] != 1) continue;
    if (isset($item['jenis'])) {
        $jumlah = isset($item['jumlah']) ? floatval($item['jumlah']) : 0;
        if ($item['jenis'] == 'pemasukan') {
            $total_pemasukan += $jumlah;
        } elseif ($item['jenis'] == 'pengeluaran') {
            $total_pengeluaran += $jumlah;
        }
    }
}
$saldo = $total_pemasukan - $total_pengeluaran;

// Format keuangan dengan satuan yang sesuai
function formatMoney($amount) {
    if ($amount >= 1000000000) {
        return 'Rp ' . number_format($amount / 1000000000, 1, ',', '.') . ' M';
    } elseif ($amount >= 1000000) {
        return 'Rp ' . number_format($amount / 1000000, 1, ',', '.') . ' Jt';
    } elseif ($amount >= 1000) {
        return 'Rp ' . number_format($amount / 1000, 1, ',', '.') . ' K';
    } else {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

// Hitung statistik pengunjung tambahan
$visitors_by_day = $visitors_data['visitors_by_ip'] ?? [];
$last_7_days = array_slice($visitors_by_day, -7, 7, true);
$avg_daily = 0;
$max_daily = 0;
$total_last_7 = 0;

foreach ($last_7_days as $day => $ips) {
    $day_count = is_array($ips) ? count($ips) : 0;
    $total_last_7 += $day_count;
    if ($day_count > $max_daily) {
        $max_daily = $day_count;
    }
}
if (count($last_7_days) > 0) {
    $avg_daily = round($total_last_7 / count($last_7_days), 1);
}

// Pengumuman terbaru (5 terakhir)
$pengumuman_terbaru = array_slice(
    array_filter($pengumuman_data, function($item) {
        return isset($item['aktif']) && $item['aktif'] == 1;
    }),
    0, 5
);

// Aktivitas terbaru (ambil 15 untuk scroll)
$activity_log = [];
$log_file = '../uploads/data/activity.log';
if (file_exists($log_file)) {
    $log_content = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $activity_log = array_slice(array_reverse($log_content), 0, 15);
}

include 'header.php';
?>

<div class="dashboard-container">
    <!-- BARIS 1: Total Pengumuman, Mutiara, Galeri, Keuangan -->
    <div class="stats-row-main">
        <!-- KARTU PENGUMUMAN -->
        <div class="stat-card-large bg-success">
            <div class="card-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo $total_pengumuman; ?></div>
                <div class="card-title">Total Pengumuman</div>
                <div class="card-subtitle"><?php echo $active_pengumuman; ?> aktif</div>
            </div>
        </div>
        
        <!-- KARTU MUTIARA KATA -->
        <div class="stat-card-large bg-info">
            <div class="card-icon">
                <i class="fas fa-quote-right"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo $total_mutiara; ?></div>
                <div class="card-title">Mutiara Kata</div>
                <div class="card-subtitle"><?php echo $active_mutiara; ?> aktif</div>
            </div>
        </div>
        
        <!-- KARTU GALERI -->
        <div class="stat-card-large bg-warning">
            <div class="card-icon">
                <i class="fas fa-images"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo $total_galeri; ?></div>
                <div class="card-title">Foto Galeri</div>
                <div class="card-subtitle"><?php echo $active_galeri; ?> aktif</div>
            </div>
        </div>
        
        <!-- KARTU KEUANGAN -->
        <div class="stat-card-large bg-primary">
            <div class="card-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo formatMoney($saldo); ?></div>
                <div class="card-title">Saldo Keuangan</div>
                <div class="card-subtitle"><?php echo formatMoney($total_pemasukan); ?> masuk</div>
            </div>
        </div>
    </div>
    
    <!-- BARIS 2: Data Pengunjung -->
    <div class="stats-row-main">
        <!-- KARTU PENGUNJUNG HARI INI -->
        <div class="stat-card-large bg-danger">
            <div class="card-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo number_format($today_visitors); ?></div>
                <div class="card-title">Pengunjung Hari Ini</div>
                <div class="card-subtitle"><?php echo count($visitors_data['visitors_by_ip'][$today] ?? []); ?> IP unik</div>
            </div>
        </div>
        
        <!-- KARTU TOTAL PENGUNJUNG -->
        <div class="stat-card-large bg-secondary">
            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo number_format($total_visitors); ?></div>
                <div class="card-title">Total Pengunjung</div>
                <div class="card-subtitle"><?php echo number_format($unique_visitors); ?> pengunjung unik</div>
            </div>
        </div>
        
        <!-- KARTU RATA-RATA PENGUNJUNG -->
        <div class="stat-card-large bg-dark">
            <div class="card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo number_format($avg_daily, 1); ?></div>
                <div class="card-title">Rata-rata per Hari</div>
                <div class="card-subtitle">7 hari terakhir</div>
            </div>
        </div>
        
        <!-- KARTU REKOR PENGUNJUNG -->
        <div class="stat-card-large bg-success">
            <div class="card-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="card-content">
                <div class="card-number"><?php echo number_format($max_daily); ?></div>
                <div class="card-title">Rekor Harian</div>
                <div class="card-subtitle">Pengunjung terbanyak dalam 1 hari</div>
            </div>
        </div>
    </div>
    
    <!-- DETAIL STATISTIK PENGUNJUNG -->
    <div class="section-title">
        <h3><i class="fas fa-chart-bar"></i> Detail Statistik Pengunjung</h3>
    </div>
    
    <div class="visitor-details-grid">
        <div class="detail-card">
            <div class="detail-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="detail-info">
                <div class="detail-value"><?php echo number_format($unique_visitors); ?></div>
                <div class="detail-label">Pengunjung Unik</div>
                <div class="detail-desc">Berdasarkan IP address</div>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="detail-info">
                <div class="detail-value"><?php echo number_format($total_last_7); ?></div>
                <div class="detail-label">7 Hari Terakhir</div>
                <div class="detail-desc">Total pengunjung minggu ini</div>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">
                <i class="fas fa-network-wired"></i>
            </div>
            <div class="detail-info">
                <div class="detail-value"><?php echo htmlspecialchars(substr($visitors_data['last_ip'] ?? '-', 0, 15)); ?></div>
                <div class="detail-label">IP Terakhir</div>
                <div class="detail-desc">Pengunjung terakhir</div>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="detail-info">
                <div class="detail-value"><?php echo date('H:i', strtotime($visitors_data['last_visit'] ?? '00:00:00')); ?></div>
                <div class="detail-label">Kunjungan Terakhir</div>
                <div class="detail-desc"><?php echo date('d/m/Y', strtotime($visitors_data['last_visit'] ?? date('Y-m-d'))); ?></div>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="detail-info">
                <div class="detail-value"><?php echo date('d/m', strtotime($visitors_data['last_reset'] ?? date('Y-m-d'))); ?></div>
                <div class="detail-label">Reset Terakhir</div>
                <div class="detail-desc">Reset counter harian</div>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">
                <i class="fas fa-history"></i>
            </div>
            <div class="detail-info">
                <div class="detail-value"><?php echo count($visitors_by_day); ?></div>
                <div class="detail-label">Hari Terdata</div>
                <div class="detail-desc">Total hari dengan data</div>
            </div>
        </div>
    </div>
    
    <!-- KONTEN BAWAH: Pengumuman & Aktivitas -->
    <div class="content-row">
        <!-- PENGUMUMAN TERBARU -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-bullhorn"></i> Pengumuman Terbaru</h3>
                <a href="pengumuman.php" class="btn-view-all">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if (!empty($pengumuman_terbaru)): ?>
                    <div class="announcement-container">
                        <?php foreach ($pengumuman_terbaru as $item): ?>
                            <div class="announcement-item">
                                <div class="announcement-status">
                                    <?php if (isset($item['aktif']) && $item['aktif'] == 1): ?>
                                        <span class="status-badge active">Aktif</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Nonaktif</span>
                                    <?php endif; ?>
                                    <?php if (isset($item['penting']) && $item['penting'] == 1): ?>
                                        <span class="important-badge">PENTING</span>
                                    <?php endif; ?>
                                </div>
                                <div class="announcement-content">
                                    <h4><?php echo htmlspecialchars($item['judul'] ?? 'Tanpa Judul'); ?></h4>
                                    <div class="announcement-meta">
                                        <span class="meta-item">
                                            <i class="far fa-calendar"></i>
                                            <?php 
                                            if (isset($item['created_at'])) {
                                                echo date('d M Y', strtotime($item['created_at']));
                                            } else {
                                                echo 'Tanggal tidak tersedia';
                                            }
                                            ?>
                                        </span>
                                        <?php if (isset($item['tanggal_berlaku']) && $item['tanggal_berlaku']): ?>
                                            <span class="meta-item">
                                                <i class="far fa-clock"></i>
                                                Berlaku: <?php echo date('d M Y', strtotime($item['tanggal_berlaku'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <p>Belum ada pengumuman</p>
                        <a href="pengumuman.php?action=tambah" class="btn-add">+ Tambah Pengumuman</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- AKTIVITAS TERBARU -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Aktivitas Terbaru</h3>
                <span class="badge-count"><?php echo count($activity_log); ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($activity_log)): ?>
                    <div class="activity-scroll-wrapper">
                        <div class="activity-container">
                            <?php 
                            $counter = 0;
                            foreach ($activity_log as $log): 
                                $counter++;
                                if (preg_match('/^\[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\]\s*(.*)$/', $log, $matches)) {
                                    $timestamp = $matches[1];
                                    $ip = $matches[2];
                                    $user = $matches[3];
                                    $action = $matches[4];
                                    $details = $matches[5];
                                    
                                    // Tentukan icon berdasarkan action
                                    $icon = 'fa-info-circle';
                                    $color = 'info';
                                    
                                    if (strpos($action, 'LOGIN') !== false) $icon = 'fa-sign-in-alt';
                                    if (strpos($action, 'LOGOUT') !== false) $icon = 'fa-sign-out-alt';
                                    if (strpos($action, 'TAMBAH') !== false) $icon = 'fa-plus-circle';
                                    if (strpos($action, 'EDIT') !== false) $icon = 'fa-edit';
                                    if (strpos($action, 'HAPUS') !== false) $icon = 'fa-trash-alt';
                                    if (strpos($action, 'UPLOAD') !== false) $icon = 'fa-upload';
                                    if (strpos($action, 'VISITOR') !== false) $icon = 'fa-user-plus';
                                    if (strpos($action, 'ERROR') !== false) {
                                        $icon = 'fa-exclamation-triangle';
                                        $color = 'danger';
                                    }
                                    if (strpos($action, 'SUCCESS') !== false) {
                                        $color = 'success';
                                    }
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon activity-<?php echo $color; ?>">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-header">
                                        <span class="activity-user"><?php echo htmlspecialchars($user); ?></span>
                                        <span class="activity-action"><?php echo htmlspecialchars($action); ?></span>
                                        <span class="activity-time"><?php echo $timestamp; ?></span>
                                    </div>
                                    <div class="activity-message">
                                        <?php echo htmlspecialchars($details); ?>
                                        <span class="activity-ip">(<?php echo $ip; ?>)</span>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($activity_log) > 5): ?>
                        <div class="scroll-hint">
                            <i class="fas fa-chevron-down"></i>
                            Scroll untuk melihat <?php echo count($activity_log) - 5; ?> aktivitas lainnya
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <p>Belum ada catatan aktivitas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* DASHBOARD CONTAINER */
.dashboard-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* STATS ROW - 4 KOLOM BESAR */
.stats-row-main {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

/* STAT CARD BESAR */
.stat-card-large {
    background: white;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e1e5e9;
    min-height: 130px;
}

.stat-card-large:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #d0d7e0;
}

/* Warna kartu */
.bg-success { border-left: 5px solid #28a745; }
.bg-info { border-left: 5px solid #17a2b8; }
.bg-warning { border-left: 5px solid #ffc107; }
.bg-primary { border-left: 5px solid #2E8B57; }
.bg-danger { border-left: 5px solid #dc3545; }
.bg-secondary { border-left: 5px solid #6c757d; }
.bg-dark { border-left: 5px solid #343a40; }

/* Icon kartu */
.card-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2em;
    flex-shrink: 0;
}

.bg-success .card-icon { background: rgba(40, 167, 69, 0.15); color: #28a745; }
.bg-info .card-icon { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
.bg-warning .card-icon { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
.bg-primary .card-icon { background: rgba(46, 139, 87, 0.15); color: #2E8B57; }
.bg-danger .card-icon { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
.bg-secondary .card-icon { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.bg-dark .card-icon { background: rgba(52, 58, 64, 0.15); color: #343a40; }

/* Konten kartu */
.card-content {
    flex: 1;
    min-width: 0;
}

.card-number {
    font-size: 2.2em;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
    line-height: 1;
}

.card-title {
    font-size: 1.1em;
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-subtitle {
    font-size: 0.9em;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* SECTION TITLE */
.section-title {
    margin: 30px 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.section-title h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.4em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title h3 i {
    color: #2E8B57;
}

/* VISITOR DETAILS GRID */
.visitor-details-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.detail-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    border: 1px solid #e1e5e9;
    transition: all 0.3s;
}

.detail-card:hover {
    background: white;
    border-color: #d0d7e0;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.detail-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(46, 139, 87, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
    color: #2E8B57;
    margin-bottom: 15px;
}

.detail-info {
    min-width: 0;
}

.detail-value {
    font-size: 1.8em;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.detail-label {
    font-size: 0.95em;
    font-weight: 600;
    color: #495057;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.detail-desc {
    font-size: 0.8em;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* CONTENT ROW */
.content-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-top: 20px;
}

.content-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
}

/* CARD HEADER */
.card-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e1e5e9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 1.3em;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-view-all {
    background: #2E8B57;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-view-all:hover {
    background: #267a4e;
    transform: translateY(-2px);
}

.badge-count {
    background: #6c757d;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: 600;
}

/* CARD BODY */
.card-body {
    padding: 20px;
}

/* ANNOUNCEMENT CONTAINER */
.announcement-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.announcement-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    transition: all 0.3s;
}

.announcement-item:hover {
    background: white;
    border-color: #d0d7e0;
}

.announcement-status {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.status-badge.active {
    background: #28a745;
    color: white;
}

.status-badge.inactive {
    background: #6c757d;
    color: white;
}

.important-badge {
    background: #dc3545;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.announcement-content h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 1.1em;
    line-height: 1.4;
}

.announcement-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85em;
    color: #6c757d;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* ACTIVITY SCROLL WRAPPER */
.activity-scroll-wrapper {
    position: relative;
}

.activity-container {
    max-height: 350px;
    overflow-y: auto;
    padding-right: 10px;
}

.activity-container::-webkit-scrollbar {
    width: 6px;
}

.activity-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.activity-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.activity-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* ACTIVITY ITEM */
.activity-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #e1e5e9;
    transition: all 0.3s;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: #f8f9fa;
    border-radius: 8px;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2em;
    flex-shrink: 0;
}

.activity-info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
.activity-success { background: rgba(40, 167, 69, 0.1); color: #28a745; }
.activity-danger { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
.activity-warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }

.activity-details {
    flex: 1;
    min-width: 0;
}

.activity-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
    flex-wrap: wrap;
}

.activity-user {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.95em;
}

.activity-action {
    background: #e9ecef;
    color: #495057;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 600;
}

.activity-time {
    margin-left: auto;
    color: #95a5a6;
    font-size: 0.85em;
    white-space: nowrap;
}

.activity-message {
    color: #6c757d;
    font-size: 0.9em;
    line-height: 1.5;
}

.activity-ip {
    color: #95a5a6;
    font-size: 0.85em;
    margin-left: 5px;
}

/* SCROLL HINT */
.scroll-hint {
    text-align: center;
    padding: 15px;
    color: #6c757d;
    font-size: 0.9em;
    border-top: 1px dashed #dee2e6;
    margin-top: 10px;
}

.scroll-hint i {
    margin-right: 8px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-5px);
    }
    60% {
        transform: translateY(-3px);
    }
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3em;
    margin-bottom: 15px;
    color: #adb5bd;
}

.empty-state p {
    margin: 0 0 20px 0;
    font-size: 1.1em;
}

.btn-add {
    display: inline-block;
    background: #2E8B57;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-add:hover {
    background: #267a4e;
    transform: translateY(-2px);
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .stats-row-main {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .visitor-details-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .content-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-row-main {
        grid-template-columns: 1fr;
    }
    
    .visitor-details-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .card-number {
        font-size: 1.8em;
    }
    
    .detail-value {
        font-size: 1.5em;
    }
    
    .announcement-meta {
        flex-direction: column;
        gap: 5px;
    }
}

@media (max-width: 576px) {
    .visitor-details-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .activity-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .activity-time {
        margin-left: 0;
    }
}
</style>

<script>
// Auto refresh untuk statistik pengunjung (setiap 30 detik)
setTimeout(function() {
    window.location.reload();
}, 30000);
</script>

<?php include 'footer.php'; ?>
