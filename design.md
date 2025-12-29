# Desain Sistem Booking Ruangan Kampus


## Deskripsi Singkat
Sistem Booking Ruangan Kampus, aplikasi berbasis web yang memungkinkan mahasiswa memesan ruangan dan admin mengelola data ruangan serta menyetujui/menolak pemesanan.  
---


## 🧱 Arsitektur Sistem
- **Frontend**: Blade Template & TailwindCSS
- **Style**: Minimalist modern 
- **Backend**: Laravel 12
- **Database**: MySQL
- **Auth**: Laravel Breeze 
- **Role Management**: Laravel Gates/Policies
---


## 👥 Role Pengguna

### 1. Admin
- Mengelola data ruangan (nama ruangan, kapasitas, fasilitas, ketersediaan):
  - Tambah ruangan  
  - Edit ruangan  
  - Hapus ruangan  
- Melihat daftar pemesanan ruangan  
- Menyetujui / menolak pemesanan

### 2. Mahasiswa (User)
- Melihat daftar ruangan  
- Melihat ketersediaan ruangan  
- Melakukan pemesanan ruangan dengan menginput ruangan, tanggal, jam, dan tujuan pemesanan
- Melihat status booking (pending / approved / rejected)
---


## 🔁 Alur Sistem

### **Mahasiswa**
1. Login
2. Melihat daftar ruangan
3. Memilih ruangan
4. Mengisi form pemesanan 
5. Menunggu persetujuan admin
6. Melihat status pemesanan

### **Admin**
1. Login
2. Mengelola data ruangan
3. Melihat daftar permintaan booking
4. Approve/Reject booking
5. Sistem mengirim notifikasi ke mahasiswa  

---


## 🔐 Authorization & Access Control
- **Middleware**:
  - `auth`
  - `role:admin`
  - `role:mahasiswa`

---


## 🖥️ Rancangan Halaman (UI/UX)

### Mahasiswa
- Dashboard ruangan
- Detail ruangan + form booking  
- Riwayat booking  

### Admin
- Dashboard admin dengan statistik ruangan dan pemesanan 
- Kalender pemesanan
- CRUD ruangan  
- Daftar pemesanan dengan tombol Approve/Reject  

---


## 📦 Rancangan Model Laravel

### `User`
- hasMany(Bookings)

### `Room`
- hasMany(Bookings)

### `Booking`
- belongsTo(User)  
- belongsTo(Room)

---


## 🔔 Notifikasi
- in-app notification ketika:
  - Mahasiswa membuat booking  
  - Admin menyetujui / menolak booking  

---