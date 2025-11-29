# 📖 Serambi Berkah - Panduan Instalasi Lengkap

## Persiapan

### Requirements System
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi  
- Apache Web Server dengan mod_rewrite
- Extension PHP: mysqli, gd, mbstring

## 🛠️ Instalasi

### Method 1: Auto Install (Recommended)

1. **Upload Files**
   - Extract folder `serambi/` ke web server
   - Contoh: `/var/www/html/serambi/`

2. **Run Setup Wizard**
   - Buka browser: `http://domain-anda/serambi/setup.php`
   - Ikuti 3 step instalasi

3. **Selesai!**
   - Website: `http://domain-anda/serambi/`
   - Admin: `http://domain-anda/serambi/admin/login.php`

### Method 2: Manual Install

1. **Buat database** `serambi_db`

2. **Copy config:**
   ```bash
   cp .env.example .env
