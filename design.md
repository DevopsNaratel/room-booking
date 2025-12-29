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
  - `auth`
  - `role:admin`
  - `role:mahasiswa`

## 📝 Logging

- **File-based logs:** API requests and important actions are logged to the application log (storage/logs/laravel.log).
- **Middleware:** `ApiLoggingMiddleware` records trace id, user, method, URL, status, duration and attaches `X-Trace-Id` to responses.
- **Controller events:** Key actions (booking created/updated/deleted, booking approved/rejected, room CRUD) emit structured Log::info events for easy filtering.

- **Loki-ready JSON logs:** Added `loki` daily log channel (storage/logs/laravel-json.log) which writes compact JSON lines suitable for Grafana Loki ingestion. Set `LOG_CHANNEL=loki` in production to enable.
 
- **Kubernetes + Loki:** The `loki` channel now writes compact JSON to `stderr` (recommended for container logs). Set `LOG_CHANNEL=loki` in production so Kubernetes/P omtail can scrape container logs and send them to Grafana Loki.

### Promtail example (kubernetes)
Add a `scrape_config` that tails container stdout/stderr and parses JSON lines. Example pipeline stages:

```yaml
scrape_configs:
  - job_name: room-booking
    static_configs:
      - targets: ['localhost']
        labels:
          job: room-booking
          __path__: /var/log/containers/*room-booking-*.log
    pipeline_stages:
      - json:
          expressions:
            event: event
            trace_id: trace_id
      - labels:
          event: 
          trace_id:
```

This parses `event` and `trace_id` from each JSON log line and promotes them to labels for efficient queries.



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