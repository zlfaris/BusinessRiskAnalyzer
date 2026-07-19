# Business Risk Analyzer — Tactile Intelligence

Platform analisis kelayakan dan penilaian risiko bisnis berbasis Kecerdasan Buatan (AI) dengan standar penilaian ala *Venture Capitalist Consultant*. Aplikasi ini dirancang khusus untuk membantu calon pemilik usaha memetakan ancaman struktural, menghitung *runway* keuangan, dan menyusun rencana aksi taktis sebelum modal ditanamkan.

## 🛠️ Tech Stack (Susunan Teknologi)

Aplikasi ini dibangun dengan arsitektur yang sangat ringan, cepat, dan efisien tanpa menggunakan framework pihak ketiga:
- **Backend Core**: PHP Native 
- **Frontend State**: Alpine.js 
- **UI Styling**: CSS
- **Database**: MySQL

---

## 🚀 Fitur & Komponen UI Sistem

Sesuai dengan alur sistem pada dashboard utama, berikut adalah modul inti yang tersedia:

### 1. Sistem Autentikasi Terintegrasi
- Halaman masuk (*Welcome Back*) dan pendaftaran akun yang bersih.
- Validasi instan di sisi klien menggunakan Alpine.js untuk format email dan kekuatan kata sandi (minimal 8 karakter).

### 2. Form Wizard Profil & Data Keuangan Bisnis
Pemisahan formulir input cerdas untuk mempermudah pengalaman pengguna:
- **Profil Bisnis**: Menangkap Nama Bisnis (min. 5 karakter), Kategori Bisnis, Target Pasar Utama, dan Tingkat Kejenuhan Pasar.
- **Data Keuangan (IDR)**: Menangkap metrik finansial krusial seperti **CapEx (Modal Awal)**, **OpEx (Biaya Operasional/Bulan)**, dan **Harga Jual Rata-rata**.

### 3. Dashboard Hasil Analisis AI (Bento Grid Layout)
Begitu analisis dibuat, sistem langsung merender panel informasi dinamis:
- **Skor Viabilitas & Label Status**: Grafik radial dinamis yang menunjukkan nilai kelayakan (0-100) disertai label tegas (*Bahaya Kritis – Stop & Pikirkan Ulang*, *Waspada*, atau *Layak Lanjut*).
- **Kalkulator Otomatis Break-Even Point (BEP)**: Narasi instan yang menghitung secara eksak jumlah porsi/unit minimum yang wajib dijual per bulan dan per hari untuk sekadar bertahan hidup.
- **Audit Risiko 4-Pilar**: Ulasan naratif mendalam dan deklaratif yang membedah akar masalah pada aspek *Risiko Keuangan*, *Risiko Pasar*, *Risiko Operasional (90 hari pertama)*, dan *Risiko Hukum/Perizinan*.
- **Grid Analisis SWOT**: Pemetaan ringkas kekuatan (S), kelemahan (W), peluang (O), dan ancaman (T) internal-eksternal secara terpisah.
- **Strategi Eksekusi Utama (Master Plan)**: Panel petunjuk taktis berupa paragraf prosa mengalir tentang langkah nyata pertama yang harus dieksekusi oleh pemilik bisnis.

### 4. Manajemen Riwayat & Pencarian (Sidebar)
- Panel kiri melacak riwayat analisis terdahulu lengkap dengan tag kategori dan tanggal pembuatan.
- Dilengkapi fitur penyaringan pencarian dinamis (*Real-time Search Filter* via Alpine.js) berbasis nama bisnis atau kategori.

---

## 📁 Struktur Folder Proyek

```text
BusinessRiskAnalyzer/
├── config/
│   ├── Database.php          # Koneksi database aman menggunakan PDO MySQL
│   ├── GeminiClient.php      # Klien API utama untuk Google Gemini
│   └── GroqClient.php        # Klien API cadangan untuk Groq Cloud (Failover)
├── database/
│   └── schema.sql            # Skema SQL untuk tabel users dan analyses
├── public/
│   ├── assets/
│   │   ├── css/style.css     # File CSS Murni / Kustom buatan sendiri
│   │   └── js/app.js         # Logika State Alpine.js & Client-Side AI Failover
│   ├── index.php             # Gerbang routing (Front Controller)
│   └── index.html            # File view utama aplikasi (Dashboard Layout)
├── src/
│   ├── controllers/          # AnalysisController.php & AuthController.php
│   ├── models/               # Model Database (User.php & Analysis.php)
│   └── helpers/              # Helper sistem seperti Validator.php
├── .env                      # File rahasia API Key & Kredensial DB (Ignored)
└── .gitignore                # Pembatas keamanan berkas Git
