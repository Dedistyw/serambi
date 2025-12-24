<?php
/**
 * Pencatatan Keuangan Masjid
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

Auth::requireLogin();

$page_title = 'Keuangan Masjid';
$success_msg = '';
$error_msg = '';

// Proses CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$bulan = $_GET['bulan'] ?? date('Y-m');

// Load data keuangan
$keuangan_data = getJSONData('keuangan');

// Proses tambah/edit transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'tambah' || $post_action === 'edit') {
        $jenis = sanitize($_POST['jenis'] ?? '');
        $jumlah = str_replace(['.', ','], '', $_POST['jumlah'] ?? '0');
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        
        // Validasi
        if (empty($jenis) || !in_array($jenis, ['pemasukan', 'pengeluaran'])) {
            $error_msg = 'Jenis transaksi tidak valid';
        } elseif (!is_numeric($jumlah) || $jumlah <= 0) {
            $error_msg = 'Jumlah harus angka positif';
        } elseif (empty($keterangan)) {
            $error_msg = 'Keterangan harus diisi';
        } elseif (!validateDate($tanggal)) {
            $error_msg = 'Tanggal tidak valid';
        } else {
            $jumlah = floatval($jumlah);
            
            if ($post_action === 'edit' && $id) {
                // Edit transaksi
                $found = false;
                foreach ($keuangan_data as $key => $item) {
                    if ($item['id'] == $id) {
                        $keuangan_data[$key] = [
                            'id' => $id,
                            'jenis' => $jenis,
                            'jumlah' => $jumlah,
                            'keterangan' => $keterangan,
                            'tanggal' => $tanggal,
                            'aktif' => 1,
                            'created_at' => $item['created_at'],
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        $found = true;
                        logActivity('KEUANGAN_EDIT', "ID: {$id}, Jenis: {$jenis}, Jumlah: {$jumlah}");
                        break;
                    }
                }
                if ($found) {
                    $success_msg = 'Transaksi berhasil diupdate';
                } else {
                    $error_msg = 'Transaksi tidak ditemukan';
                }
            } else {
                // Tambah transaksi baru
                $new_transaksi = [
                    'id' => generateId(),
                    'jenis' => $jenis,
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                    'tanggal' => $tanggal,
                    'aktif' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $keuangan_data[] = $new_transaksi;
                logActivity('KEUANGAN_TAMBAH', "Jenis: {$jenis}, Jumlah: {$jumlah}, Keterangan: {$keterangan}");
                $success_msg = 'Transaksi berhasil ditambahkan';
            }
            
            if (empty($error_msg) && saveJSONData('keuangan', $keuangan_data)) {
                redirect('keuangan.php', $success_msg);
            } elseif (empty($error_msg)) {
                $error_msg = 'Gagal menyimpan transaksi';
            }
        }
    }
}

// Hapus transaksi
if ($action === 'hapus' && $id) {
    if (checkCSRFToken($_GET['token'] ?? '')) {
        $found = false;
        foreach ($keuangan_data as $key => $item) {
            if ($item['id'] == $id) {
                unset($keuangan_data[$key]);
                $keuangan_data = array_values($keuangan_data);
                
                if (saveJSONData('keuangan', $keuangan_data)) {
                    logActivity('KEUANGAN_HAPUS', "ID: {$id}");
                    redirect('keuangan.php', 'Transaksi berhasil dihapus');
                } else {
                    $error_msg = 'Gagal menghapus transaksi';
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $error_msg = 'Transaksi tidak ditemukan';
        }
    } else {
        $error_msg = 'Token CSRF tidak valid';
    }
}

// Get data for edit
$edit_data = [];
if ($action === 'edit' && $id) {
    foreach ($keuangan_data as $item) {
        if ($item['id'] == $id) {
            $edit_data = $item;
            break;
        }
    }
    if (empty($edit_data)) {
        $error_msg = 'Transaksi tidak ditemukan';
        $action = '';
    }
}

// Hitung statistik
$total_pemasukan = 0;
$total_pengeluaran = 0;
$pemasukan_bulan_ini = 0;
$pengeluaran_bulan_ini = 0;

foreach ($keuangan_data as $item) {
    if (isset($item['aktif']) && $item['aktif'] != 1) continue;
    
    $jumlah = isset($item['jumlah']) ? floatval($item['jumlah']) : 0;
    
    if ($item['jenis'] == 'pemasukan') {
        $total_pemasukan += $jumlah;
        if (isset($item['tanggal']) && substr($item['tanggal'], 0, 7) == $bulan) {
            $pemasukan_bulan_ini += $jumlah;
        }
    } elseif ($item['jenis'] == 'pengeluaran') {
        $total_pengeluaran += $jumlah;
        if (isset($item['tanggal']) && substr($item['tanggal'], 0, 7) == $bulan) {
            $pengeluaran_bulan_ini += $jumlah;
        }
    }
}

$saldo = $total_pemasukan - $total_pengeluaran;
$saldo_bulan_ini = $pemasukan_bulan_ini - $pengeluaran_bulan_ini;

// Filter data berdasarkan bulan dan jenis
$filtered_data = [];
foreach ($keuangan_data as $item) {
    if (isset($item['aktif']) && $item['aktif'] != 1) continue;
    
    // Filter by bulan
    if (isset($item['tanggal']) && substr($item['tanggal'], 0, 7) != $bulan) {
        continue;
    }
    
    // Filter by jenis
    if ($filter !== 'all' && isset($item['jenis']) && $item['jenis'] != $filter) {
        continue;
    }
    
    $filtered_data[] = $item;
}

// Urutkan berdasarkan tanggal terbaru
usort($filtered_data, function($a, $b) {
    $time_a = strtotime($a['tanggal'] ?? 0);
    $time_b = strtotime($b['tanggal'] ?? 0);
    return $time_b - $time_a;
});

include 'header.php';
?>

<div class="content-area">
    <?php if ($error_msg): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'tambah' || $action === 'edit'): ?>
        <!-- Form Tambah/Edit Transaksi -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-money-bill-wave"></i>
                    <?php echo $action === 'edit' ? 'Edit Transaksi Keuangan' : 'Tambah Transaksi Keuangan'; ?>
                </h3>
                <a href="keuangan.php" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jenis" class="form-label">Jenis Transaksi *</label>
                                <select id="jenis" name="jenis" class="form-control" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="pemasukan" 
                                        <?php echo isset($edit_data['jenis']) && $edit_data['jenis'] == 'pemasukan' ? 'selected' : ''; ?>>
                                        Pemasukan / Pendapatan
                                    </option>
                                    <option value="pengeluaran"
                                        <?php echo isset($edit_data['jenis']) && $edit_data['jenis'] == 'pengeluaran' ? 'selected' : ''; ?>>
                                        Pengeluaran / Belanja
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="jumlah" class="form-label">Jumlah (Rp) *</label>
                                <input type="text" 
                                       id="jumlah" 
                                       name="jumlah" 
                                       class="form-control" 
                                       value="<?php echo isset($edit_data['jumlah']) ? number_format($edit_data['jumlah'], 0, ',', '.') : ''; ?>"
                                       required
                                       placeholder="Contoh: 500000"
                                       oninput="formatNumber(this)">
                                <div class="form-text">
                                    Tanpa tanda titik atau koma
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="keterangan" class="form-label">Keterangan *</label>
                                <input type="text" 
                                       id="keterangan" 
                                       name="keterangan" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($edit_data['keterangan'] ?? ''); ?>"
                                       required
                                       placeholder="Contoh: Sumbangan jamaah, Beli sound system">
                            </div>
                            
                            <div class="form-group">
                                <label for="tanggal" class="form-label">Tanggal *</label>
                                <input type="date" 
                                       id="tanggal" 
                                       name="tanggal" 
                                       class="form-control" 
                                       value="<?php echo $edit_data['tanggal'] ?? date('Y-m-d'); ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'edit' ? 'Update Transaksi' : 'Simpan Transaksi'; ?>
                        </button>
                        <a href="keuangan.php" class="btn btn-light">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Dashboard Keuangan -->
        <!-- Statistik Cards -->
        <div class="row" style="margin-bottom: 25px;">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card bg-primary">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rp <?php echo number_format($saldo, 0, ',', '.'); ?></h3>
                        <p>Saldo Total</p>
                        <small>Seluruh waktu</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card bg-success">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></h3>
                        <p>Total Pemasukan</p>
                        <small>Rp <?php echo number_format($pemasukan_bulan_ini, 0, ',', '.'); ?> bulan ini</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card bg-danger">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></h3>
                        <p>Total Pengeluaran</p>
                        <small>Rp <?php echo number_format($pengeluaran_bulan_ini, 0, ',', '.'); ?> bulan ini</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card bg-info">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rp <?php echo number_format($saldo_bulan_ini, 0, ',', '.'); ?></h3>
                        <p>Saldo Bulan Ini</p>
                        <small><?php echo date('F Y', strtotime($bulan)); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter dan Kontrol -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Daftar Transaksi Keuangan
                </h3>
                <div class="card-actions">
                    <a href="?action=tambah" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Transaksi
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Controls -->
                <div class="filter-controls">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="bulan" class="form-label">Bulan</label>
                            <input type="month" 
                                   id="bulan" 
                                   name="bulan" 
                                   class="form-control" 
                                   value="<?php echo $bulan; ?>"
                                   onchange="this.form.submit()">
                        </div>
                        <div class="col-md-4">
                            <label for="filter" class="form-label">Jenis Transaksi</label>
                            <select id="filter" name="filter" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                                <option value="pemasukan" <?php echo $filter === 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                                <option value="pengeluaran" <?php echo $filter === 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="keuangan.php" class="btn btn-light">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($filtered_data)): ?>
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        Tidak ada transaksi untuk bulan <?php echo date('F Y', strtotime($bulan)); ?>.
                        <a href="?action=tambah" class="alert-link">Tambah transaksi pertama</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive mt-4">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th width="120">Tanggal</th>
                                    <th>Keterangan</th>
                                    <th width="150" class="text-end">Jumlah</th>
                                    <th width="120">Jenis</th>
                                    <th width="150" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered_data as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div style="font-size: 0.9em;">
                                                <?php echo isset($item['tanggal']) ? date('d/m/Y', strtotime($item['tanggal'])) : '-'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500;">
                                                <?php echo htmlspecialchars($item['keterangan'] ?? ''); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo isset($item['created_at']) ? 
                                                    date('H:i', strtotime($item['created_at'])) : ''; ?>
                                            </small>
                                        </td>
                                        <td class="text-end" style="font-weight: bold; font-family: 'Courier New', monospace;">
                                            <?php if (isset($item['jenis']) && $item['jenis'] == 'pemasukan'): ?>
                                                <span style="color: #28a745;">
                                                    + Rp <?php echo number_format($item['jumlah'] ?? 0, 0, ',', '.'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #dc3545;">
                                                    - Rp <?php echo number_format($item['jumlah'] ?? 0, 0, ',', '.'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($item['jenis']) && $item['jenis'] == 'pemasukan'): ?>
                                                <span class="badge badge-success">Pemasukan</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Pengeluaran</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="?action=edit&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" 
                                                   onclick="confirmDelete('<?php echo $item['id']; ?>', '<?php echo htmlspecialchars($item['keterangan'] ?? ''); ?>')"
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8f9fa; font-weight: bold;">
                                    <td colspan="3" class="text-end">Total <?php echo date('F Y', strtotime($bulan)); ?>:</td>
                                    <td class="text-end">
                                        <div style="font-family: 'Courier New', monospace;">
                                            <div style="color: #28a745;">
                                                Pemasukan: Rp <?php echo number_format($pemasukan_bulan_ini, 0, ',', '.'); ?>
                                            </div>
                                            <div style="color: #dc3545;">
                                                Pengeluaran: Rp <?php echo number_format($pengeluaran_bulan_ini, 0, ',', '.'); ?>
                                            </div>
                                            <div style="color: #007bff; margin-top: 5px; border-top: 1px solid #dee2e6; padding-top: 5px;">
                                                Saldo: Rp <?php echo number_format($saldo_bulan_ini, 0, ',', '.'); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Export Options -->
                    <div class="export-options mt-4">
                        <div class="alert alert-warning">
                            <i class="fas fa-download"></i>
                            <strong>Ekspor Data:</strong>
                            <div style="margin-top: 10px;">
                                <a href="javascript:void(0);" onclick="exportToCSV()" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a href="javascript:void(0);" onclick="printReport()" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-print"></i> Print Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chart (Simple) -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Grafik Keuangan Bulan <?php echo date('F Y', strtotime($bulan)); ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id, keterangan) {
    if (confirm(`Apakah Anda yakin ingin menghapus transaksi:\n"${keterangan}"?`)) {
        const token = '<?php echo generateCSRF(); ?>';
        window.location.href = `?action=hapus&id=${id}&token=${token}`;
    }
}

// Format number input
function formatNumber(input) {
    // Hapus semua karakter selain angka
    let value = input.value.replace(/[^\d]/g, '');
    
    // Format dengan titik sebagai pemisah ribuan
    if (value.length > 0) {
        value = parseInt(value).toLocaleString('id-ID');
    }
    
    input.value = value;
}

// Export to CSV
function exportToCSV() {
    const bulan = '<?php echo $bulan; ?>';
    const filter = '<?php echo $filter; ?>';
    
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Laporan Keuangan Masjid\n";
    csvContent += "Periode: " + bulan + "\n";
    csvContent += "Jenis: " + (filter === 'all' ? 'Semua' : filter) + "\n\n";
    csvContent += "No,Tanggal,Keterangan,Jenis,Jumlah (Rp)\n";
    
    <?php foreach ($filtered_data as $index => $item): ?>
        csvContent += "<?php echo ($index + 1) . ',' . 
            date('d/m/Y', strtotime($item['tanggal'] ?? '')) . ',' . 
            str_replace(',', ' ', htmlspecialchars($item['keterangan'] ?? '')) . ',' . 
            ($item['jenis'] == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran') . ',' . 
            number_format($item['jumlah'] ?? 0, 0, ',', '.') . "\n"; ?>";
    <?php endforeach; ?>
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "laporan-keuangan-<?php echo $bulan; ?>.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print report
function printReport() {
    const printContent = `
        <html>
        <head>
            <title>Laporan Keuangan Masjid</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #2E8B57; text-align: center; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .pemasukan { color: green; }
                .pengeluaran { color: red; }
                .summary { margin-top: 30px; padding: 15px; background: #f8f9fa; }
            </style>
        </head>
        <body>
            <h1>Laporan Keuangan Masjid</h1>
            <p><strong>Masjid:</strong> <?php echo htmlspecialchars(getConstant('SITE_NAME', 'Masjid')); ?></p>
            <p><strong>Periode:</strong> <?php echo date('F Y', strtotime($bulan)); ?></p>
            <p><strong>Jenis:</strong> <?php echo $filter === 'all' ? 'Semua Transaksi' : ($filter === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'); ?></p>
            
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Jenis</th>
                        <th>Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered_data as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo isset($item['tanggal']) ? date('d/m/Y', strtotime($item['tanggal'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($item['keterangan'] ?? ''); ?></td>
                        <td><?php echo $item['jenis'] == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'; ?></td>
                        <td class="<?php echo $item['jenis'] == 'pemasukan' ? 'pemasukan' : 'pengeluaran'; ?>">
                            <?php echo number_format($item['jumlah'] ?? 0, 0, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <h3>Ringkasan</h3>
                <p><strong>Total Pemasukan:</strong> Rp <?php echo number_format($pemasukan_bulan_ini, 0, ',', '.'); ?></p>
                <p><strong>Total Pengeluaran:</strong> Rp <?php echo number_format($pengeluaran_bulan_ini, 0, ',', '.'); ?></p>
                <p><strong>Saldo Bulan Ini:</strong> Rp <?php echo number_format($saldo_bulan_ini, 0, ',', '.'); ?></p>
                <p><strong>Saldo Total:</strong> Rp <?php echo number_format($saldo, 0, ',', '.'); ?></p>
            </div>
            
            <p style="margin-top: 30px; font-size: 0.9em; color: #666;">
                Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?><br>
                Oleh: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
            </p>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// Chart.js untuk grafik keuangan
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financeChart');
    if (ctx) {
        // Hitung total per hari dalam bulan ini
        const daysInMonth = new Date(<?php echo date('Y', strtotime($bulan)); ?>, <?php echo date('m', strtotime($bulan)); ?>, 0).getDate();
        const pemasukanData = new Array(daysInMonth).fill(0);
        const pengeluaranData = new Array(daysInMonth).fill(0);
        
        <?php 
        // Hitung per hari
        $daily_pemasukan = [];
        $daily_pengeluaran = [];
        foreach ($filtered_data as $item) {
            if (isset($item['tanggal'])) {
                $day = date('j', strtotime($item['tanggal']));
                $jumlah = isset($item['jumlah']) ? floatval($item['jumlah']) : 0;
                
                if ($item['jenis'] == 'pemasukan') {
                    if (!isset($daily_pemasukan[$day])) $daily_pemasukan[$day] = 0;
                    $daily_pemasukan[$day] += $jumlah;
                } else {
                    if (!isset($daily_pengeluaran[$day])) $daily_pengeluaran[$day] = 0;
                    $daily_pengeluaran[$day] += $jumlah;
                }
            }
        }
        ?>
        
        // Isi data ke array JavaScript
        <?php foreach ($daily_pemasukan as $day => $jumlah): ?>
            pemasukanData[<?php echo $day - 1; ?>] = <?php echo $jumlah; ?>;
        <?php endforeach; ?>
        
        <?php foreach ($daily_pengeluaran as $day => $jumlah): ?>
            pengeluaranData[<?php echo $day - 1; ?>] = <?php echo $jumlah; ?>;
        <?php endforeach; ?>
        
        // Buat labels hari
        const labels = [];
        for (let i = 1; i <= daysInMonth; i++) {
            labels.push(i);
        }
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: pemasukanData,
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pengeluaran',
                        data: pengeluaranData,
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.filter-controls {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    margin-bottom: 20px;
}

.export-options {
    padding: 15px;
    background: #fff3cd;
    border-radius: 8px;
    border: 1px solid #ffeaa7;
}

.export-options .btn {
    margin-right: 10px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
    height: 100%;
}

.stat-card.bg-primary {
    border-left: 5px solid #007bff;
}

.stat-card.bg-success {
    border-left: 5px solid #28a745;
}

.stat-card.bg-danger {
    border-left: 5px solid #dc3545;
}

.stat-card.bg-info {
    border-left: 5px solid #17a2b8;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
}

.bg-primary .stat-icon {
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.bg-success .stat-icon {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.bg-danger .stat-icon {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.bg-info .stat-icon {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
}

.stat-content h3 {
    font-size: 1.5em;
    margin: 0;
    color: #2c3e50;
}

.stat-content p {
    margin: 5px 0;
    color: #6c757d;
    font-weight: 500;
}

.stat-content small {
    color: #95a5a6;
    font-size: 0.85em;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
    display: flex;
    gap: 10px;
}

.chart-container {
    position: relative;
}

@media (max-width: 768px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
    
    .stat-icon {
        margin-bottom: 10px;
    }
}
</style>

<?php include 'footer.php'; ?>
