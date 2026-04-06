# Use Case Descriptions for Zakatku Project

## Aktor
- **Pengguna (User)**: Pengguna biasa yang dapat mendaftar, login, dan mengelola kalkulasi zakat.
- **Administrator (Admin)**: Pengguna dengan role admin yang dapat mengelola pengguna lain, data zakat, dan harga emas.

## Use Case 1: Register
- **Aktor**: Pengguna
- **Deskripsi**: Pengguna mendaftar akun baru di aplikasi.
- **Precondition**: Pengguna belum memiliki akun.
- **Postcondition**: Akun pengguna dibuat dan dapat login.
- **Basic Flow**:
  1. Pengguna mengakses halaman registrasi.
  2. Pengguna mengisi nama, email, password.
  3. Sistem memvalidasi data dan membuat akun.
  4. Sistem mengirim respons berhasil.
- **Alternative Flow**: Jika email sudah ada, sistem menampilkan error.
- **Exception**: Jika data tidak valid, sistem menampilkan pesan error.

## Use Case 2: Login
- **Aktor**: Pengguna, Admin
- **Deskripsi**: Aktor login ke aplikasi menggunakan email dan password.
- **Precondition**: Aktor memiliki akun.
- **Postcondition**: Aktor terautentikasi dan mendapat token.
- **Basic Flow**:
  1. Aktor mengirim email dan password.
  2. Sistem memverifikasi kredensial.
  3. Jika valid, sistem mengeluarkan token autentikasi.
- **Alternative Flow**: Jika role admin, akses fitur admin.
- **Exception**: Jika kredensial salah, sistem menampilkan error.

## Use Case 3: Calculate Zakat Mal
- **Aktor**: Pengguna
- **Deskripsi**: Pengguna menghitung zakat mal berdasarkan pendapatan tahunan.
- **Precondition**: Pengguna sudah login.
- **Postcondition**: Kalkulasi zakat disimpan dan ditampilkan.
- **Basic Flow**:
  1. Pengguna memilih jenis zakat mal.
  2. Pengguna memasukkan pendapatan.
  3. Sistem menghitung nisab berdasarkan harga emas.
  4. Sistem menghitung jumlah zakat jika eligible.
  5. Sistem menyimpan kalkulasi.
- **Alternative Flow**: Jika tidak eligible, zakat = 0.
- **Exception**: Jika data tidak valid, error.

## Use Case 4: Calculate Zakat Profesi
- **Aktor**: Pengguna
- **Deskripsi**: Pengguna menghitung zakat profesi berdasarkan gaji bulanan/tahunan.
- **Precondition**: Pengguna sudah login.
- **Postcondition**: Kalkulasi zakat disimpan.
- **Basic Flow**:
  1. Pengguna memilih jenis zakat profesi.
  2. Pengguna memasukkan gaji, tipe (kotor/bersih), periode.
  3. Sistem menghitung nisab.
  4. Sistem menghitung zakat.
  5. Sistem menyimpan.
- **Alternative Flow**: Sama seperti zakat mal.
- **Exception**: Sama.

## Use Case 5: View Zakat History
- **Aktor**: Pengguna
- **Deskripsi**: Pengguna melihat riwayat kalkulasi zakat.
- **Precondition**: Pengguna sudah login.
- **Postcondition**: Riwayat ditampilkan.
- **Basic Flow**:
  1. Pengguna meminta history.
  2. Sistem mengambil data dari database.
  3. Sistem menampilkan list kalkulasi.
- **Alternative Flow**: Jika kosong, tampilkan pesan.
- **Exception**: Jika tidak terautentikasi, error.

## Use Case 6: View Gold Price
- **Aktor**: Pengguna
- **Deskripsi**: Pengguna melihat harga emas terkini.
- **Precondition**: Pengguna sudah login.
- **Postcondition**: Harga emas ditampilkan.
- **Basic Flow**:
  1. Pengguna meminta gold price.
  2. Sistem mengambil dari cache atau database.
  3. Sistem menampilkan harga.
- **Alternative Flow**: Jika tidak ada data, tampilkan default.
- **Exception**: Error jika gagal ambil data.

## Use Case 7: Manage Users (Admin)
- **Aktor**: Admin
- **Deskripsi**: Admin melihat dan menghapus pengguna.
- **Precondition**: Admin sudah login.
- **Postcondition**: Data pengguna diperbarui.
- **Basic Flow**:
  1. Admin meminta list users.
  2. Sistem menampilkan list.
  3. Admin memilih hapus user.
  4. Sistem menghapus user dan data terkait.
- **Alternative Flow**: -
- **Exception**: Jika bukan admin, akses ditolak.

## Use Case 8: Update Gold Price (Admin)
- **Aktor**: Admin
- **Deskripsi**: Admin mengupdate harga emas.
- **Precondition**: Admin sudah login.
- **Postcondition**: Harga emas diperbarui.
- **Basic Flow**:
  1. Admin mengirim harga baru.
  2. Sistem menyimpan ke database dan cache.
- **Alternative Flow**: -
- **Exception**: Jika data invalid, error.

## Use Case 9: View Admin Dashboard
- **Aktor**: Admin
- **Deskripsi**: Admin melihat dashboard dengan statistik.
- **Precondition**: Admin sudah login.
- **Postcondition**: Data dashboard ditampilkan.
- **Basic Flow**:
  1. Admin meminta dashboard.
  2. Sistem menghitung total users, zakat, dll.
  3. Sistem menampilkan.
- **Alternative Flow**: -
- **Exception**: -

## Use Case 10: Export Data (Admin)
- **Aktor**: Admin
- **Deskripsi**: Admin mengekspor data zakat ke CSV.
- **Precondition**: Admin sudah login.
- **Postcondition**: File CSV dihasilkan.
- **Basic Flow**:
  1. Admin meminta export.
  2. Sistem mengambil data zakat.
  3. Sistem generate CSV.
- **Alternative Flow**: -
- **Exception**: Jika gagal, error.