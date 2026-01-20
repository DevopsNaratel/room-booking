---

# 📋 Standarisasi Logging JSON (Grafana Optimized)

Dokumentasi ini adalah panduan bagi developer untuk menghasilkan log yang terstandarisasi. Standar ini dirancang agar log kita dapat diproses secara otomatis oleh **Loki** dan divisualisasikan secara akurat pada dashboard **Apps Activity Log**.

---

## 🏗️ 1. Core Fields (Wajib)

Setiap entri log harus berupa objek JSON satu baris dengan field berikut:

| Field | Tipe | Deskripsi |
| --- | --- | --- |
| `timestamp` | ISO8601 | Waktu UTC saat log dibuat. |
| `level` | String | `info`, `warn`, `error`. |
| `message` | String | Deskripsi event. |
| `path` | String | Endpoint API atau Komponen internal. |
| `requestId` | UUID | Trace ID untuk tracking request. |
| `method` | String | `GET`, `POST`, atau `INTERNAL`. |

---

## 🚀 2. Implementasi & Contoh Log

### A. Logging Request (Info)

Digunakan untuk mencatat traffic masuk. Pastikan `level` diisi `info`.

```json
{
  "timestamp": "2026-01-15T14:00:01.123Z",
  "level": "info",
  "requestId": "a3b2-c4d5-e6f7",
  "method": "POST",
  "path": "/api/v1/orders",
  "message": "Incoming request to create order",
  "attributes": {
    "user_id": 1024,
    "source": "mobile-app"
  }
}

```

---

### B. Logging Query Database

Dashboard memiliki panel khusus untuk memantau performa query. Agar terbaca, Anda **WAJIB** menggunakan pesan `"Executed query"`.

```json
{
  "timestamp": "2026-01-15T14:05:10.456Z",
  "level": "info",
  "requestId": "a3b2-c4d5-e6f7",
  "method": "INTERNAL",
  "path": "Repository/OrderStore",
  "message": "Executed query",
  "attributes": {
    "query": "SELECT id FROM boards WHERE id = $1 AND user_id = $2",
    "duration": 3,
    "rows": 1
  }
}

```

---

### C. Logging Error vs Warning (Level-Based)

Anda harus membedakan penggunaan `level` berdasarkan keparahan kejadian.

#### 1. Level: `warn` (Warning)

Gunakan level ini untuk kejadian yang **tidak menghentikan sistem**, tetapi memerlukan perhatian (misal: performa melambat, validasi bisnis gagal, atau *threshold* hampir tercapai).

* **Logic:** Gunakan saat terjadi *slow response* atau *retries*.
* **Contoh Output:**

```json
{
  "timestamp": "2026-01-15T14:10:00.123Z",
  "level": "warn",
  "requestId": "w1-x2-y3",
  "method": "GET",
  "path": "/api/v1/checkout",
  "message": "Slow query detected",
  "metrics": {
    "executionTimeMs": 1200,
    "thresholdMs": 1000
  }
}

```

#### 2. Level: `error` (Error)

Gunakan level ini untuk **kegagalan fatal** yang menyebabkan request gagal atau sistem berhenti berfungsi (misal: koneksi database terputus, 5xx status codes, atau *unhandled exceptions*).

* **Logic:** Gunakan di dalam blok `catch` atau saat integrasi pihak ketiga mati total.
* **Contoh Output:**

```json
{
  "timestamp": "2026-01-15T14:10:05.999Z",
  "level": "error",
  "requestId": "e1-r2-r3",
  "method": "POST",
  "path": "/api/v1/payment",
  "message": "Payment Gateway Connection Refused",
  "error": {
    "code": "PG_503",
    "details": "Downstream service unavailable",
    "stack": "Error: Connect ETIMEDOUT 10.20.30.40:443"
  }
}

```

---