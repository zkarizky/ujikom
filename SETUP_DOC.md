# Dokumentasi Kebutuhan dan Instalasi Proyek Zakatku

Dokumen ini berisi rincian seluruh perangkat lunak (software), paket (packages), serta panduan langkah demi langkah proses instalasi dan konfigurasi proyek Zakatku.

---

## 1. Kebutuhan Perangkat Lunak Inti (Software)

Pastikan lingkungan server lokal Anda (Laragon / XAMPP / atau stand-alone) sudah memiliki fitur berikut:

1. **PHP**: Versi Minimal `>= 8.2`
2. **Composer**: Aplikasi pengelola *package* PHP.
3. **Node.js (berserta npm)**: Minimum disarankan versi LTS.
4. **MySQL / MariaDB**: Server *database*.

---

## 2. Daftar Package dan Dependensi

Walaupun dependensi ini sudah tercatat otomatis di `composer.json` dan `package.json`, berikut adalah daftar dan cara instalasi manual paket-paket utamanya jika Anda harus memasukkannya ulang satu persatu:

### A. Dependensi Backend (Laravel / PHP)
Perintah ini dijalankan dengan Composer untuk menginstall *package* backend utama:

```bash
# Instalasi Laravel Core 12.0
composer require laravel/framework:^12.0

# Instalasi Laravel Sanctum untuk Keamanan/API Auth
composer require laravel/sanctum:^4.3

# Instalasi Package pembaca/pembuat file Excel
composer require maatwebsite/excel:^1.1

# Lingkungan Konsol Tinker
composer require laravel/tinker:^2.10.1
```

### B. Dependensi Frontend (Node.js)
Perintah ini dijalankan dengan NPM (`npm`) untuk membangun aset *frontend* menggunakan Tailwind CSS v4 dan Vite:

```bash
# Menginstall TailwindCSS versi 4 dan plugin Vite miliknya
npm install -D tailwindcss@^4.0.0 @tailwindcss/vite@^4.0.0

# Menginstall plugin Vite Laravel
npm install -D laravel-vite-plugin@^2.0.0

# Menginstall HTTP Client Axios dan concurrently
npm install -D axios@^1.11.0 concurrently@^9.0.1
```

> **Catatan Penting**: Karena semua package di atas sudah tercatat rapi di dalam proyek, Anda **tidak wajib** menjalankan satu persatu perintah di atas. Cukup gunakan perintah instalasi sekaligus pada Panduan Setup di bawah ini.

---

## 3. Langkah-Langkah Setup Proyek

Berikut adalah *script* langkah demi langkah untuk mengkonfigurasi dan menjalankan proyek pertama kali di komputer Anda.

### Cara Praktis (Satu Perintah)
Developer secara khusus sudah membuat perintah gabungan di dalam `composer.json` untuk mengeksekusi semua setup otomatis. Cukup ketik:

```bash
composer run setup
```

### Cara Manual (Selangkah demi Selangkah)

Jika *Script Praktis* mengalami masalah, atau Anda ingin melihat alur *setup* secara mandiri, jalankan baris perintah ini secara berurutan:

**Langkah 1: Unduh semua dependensi PHP dari Composer**
```bash
composer install
```

**Langkah 2: Buat file konfigurasi Environment**
Duplikat template environment dan atur profil *environment* lokal Anda.
```bash
cp .env.example .env
```
*(Windows/CMD/PowerShell: dapat diubah manual atau menggunakan `copy .env.example .env`)*

**Langkah 3: Konfigurasi Database**
1. Buka file `.env`.
2. Pastikan letak koneksi database sesuai dengan Laragon/MySQL Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=zakatku
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Buat database kosong bernama `zakatku` di PHPMyAdmin atau HeidiSQL bawaan Laragon Anda.

**Langkah 4: Enkripsi Keamanan (Generate Key)**
Hasilkan kunci rahasia (*App Key*) otomatis untuk mendeskripsi data.
```bash
php artisan key:generate
```

**Langkah 5: Strukturisasi Database**
Jalankan file *migration* untuk membongkar tabel secara otomatis ke dalam database Anda.
```bash
php artisan migrate
```

**Langkah 6: Unduh & Konfigurasi Frontend dependensi**
Instal dependensi JavaScript/CSS dengan `npm`.
```bash
npm install
```

**Langkah 7: Kompilasi Frontend Asset**
Anda perlu me-*render* pertama kali aset Vite/Tailwind untuk mode produksi, atau menjalankan engine webnya.
```bash
# Untuk di-build siap produksi:
npm run build
```

---

## 4. Menjalankan Server Web (Mulai Aplikasi)

Apabila Anda tidak menggunakan virtual host bawaan dari `.test` Laragon, jalankan gabungan server Backend dan Vite secara rentak dengan spesifikasi *concurrently*:

```bash
composer run dev
```
Perintah ini akan menjalankan server Laravel, memantau *queue*, dan otomatis me-*refresh* *build* Vite di satu layar *command prompt* secara bersamaan.
Buka link yang disediakan di terminal (Biasanya `http://localhost:8000`) di browser Anda.

