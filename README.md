# Soil Monitor — ESP32 + Fuzzy Tsukamoto

Website monitoring kelembapan tanah berbasis PHP + MySQL untuk Laragon.

## Fitur
- Login / logout
- Dashboard modern
- Indikator ESP32 aktif/tidak aktif
- Monitoring kelembapan
- Grafik data masuk
- Tabel data tersusun terbaru → terlama
- Hapus data
- Export Excel `.xlsx`
- Kolom tanggal, jam, ADC, kelembapan, status fuzzy, durasi penyiraman
- API untuk menerima data dari ESP32
- Fuzzy Tsukamoto di ESP32

## Struktur
soil-monitoring/
  api/
  assets/
  config/
  database/
  arduino/
  composer.json
  index.php
  login.php
  logout.php
  setup.php
  data.php
  export.php

## Instalasi Laragon
1. Salin folder `soil-monitoring` ke `C:\laragon\www\`.
2. Jalankan Laragon: Start All.
3. Buka HeidiSQL/phpMyAdmin dari Laragon.
4. Import `database/schema.sql`.
5. Buka `http://localhost/soil-monitoring/setup.php`.
6. Login menggunakan:
   username: admin
   password: admin123
7. Hapus/rename `setup.php` setelah selesai.
8. Buka Terminal Laragon pada folder project:
   `cd C:\laragon\www\soil-monitoring`
   lalu:
   `composer install`

## Agar ESP32 bisa mengakses website
Jangan gunakan `localhost` pada ESP32. Gunakan IP komputer yang menjalankan Laragon.

Contoh:
`http://192.168.1.10/soil-monitoring/api/sensor.php`

Cari IP Windows dengan:
`ipconfig`

ESP32 dan laptop/PC harus berada pada jaringan yang sama.

## Indikator aktif
Website menganggap ESP32 aktif jika `last_seen` menerima data dalam 30 detik terakhir. ESP32 mengirim data setiap 5 detik.

## API
Endpoint:
POST `/api/sensor.php`

Header:
`X-API-KEY: ESP32-SOIL-001`

JSON:
{
  "device_key": "ESP32-SOIL-001",
  "api_key": "ESP32-SOIL-001",
  "adc_value": 2500,
  "moisture_percent": 50,
  "fuzzy_status": "Sedang",
  "watering_seconds": 7.5
}

## Catatan
Nilai `nilaiKering` dan `nilaiBasah` di Arduino harus dikalibrasi dari sensor yang digunakan.
