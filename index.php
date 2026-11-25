<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_login();

$device = $pdo->query("SELECT * FROM devices ORDER BY id LIMIT 1")->fetch();
$last = $pdo->query("SELECT * FROM sensor_readings ORDER BY created_at DESC, id DESC LIMIT 1")->fetch();

$count = (int)$pdo->query("SELECT COUNT(*) FROM sensor_readings")->fetchColumn();
$avg = (float)($pdo->query("SELECT COALESCE(AVG(moisture_percent),0) FROM sensor_readings")->fetchColumn());
$today = (int)$pdo->query("SELECT COUNT(*) FROM sensor_readings WHERE DATE(created_at)=CURDATE()")->fetchColumn();

$active = false;
if ($device && !empty($device['last_seen'])) {
    $active = (time() - strtotime($device['last_seen'])) <= 30;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Soil Monitor</title>
<link rel="stylesheet" href="assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app">
<aside class="sidebar">
  <div class="logo"><span>SM</span><div><b>Soil Monitor</b><small>ESP32 • Fuzzy</small></div></div>
  <nav>
    <a class="active" href="index.php">Dashboard</a>
    <a href="data.php">Data Sensor</a>
    <a href="export.php">Export Excel</a>
  </nav>
  <div class="side-bottom">
    <div class="device-mini">
      <span class="dot <?=$active?'online':'offline'?>"></span>
      <div><small>ESP32</small><strong><?=$active?'Aktif':'Tidak Aktif'?></strong></div>
    </div>
    <a class="logout" href="logout.php">Keluar</a>
  </div>
</aside>

<main class="main">
<header class="topbar">
  <div><div class="eyebrow">MONITORING REAL-TIME</div><h1>Dashboard</h1></div>
  <div class="userbox"><span><?=htmlspecialchars(current_user())?></span><a href="logout.php">Logout</a></div>
</header>

<section class="cards">
  <div class="card stat">
    <div class="stat-icon">💧</div><div><span>Kelembapan terakhir</span><strong id="latestMoisture"><?=number_format((float)($last['moisture_percent']??0),1)?>%</strong></div>
  </div>
  <div class="card stat">
    <div class="stat-icon">📡</div><div><span>Status ESP32</span><strong id="deviceStatus"><?=$active?'Aktif':'Tidak Aktif'?></strong><small id="lastSeen"><?=htmlspecialchars($device['last_seen'] ?? 'Belum ada data')?></small></div>
  </div>
  <div class="card stat">
    <div class="stat-icon">📊</div><div><span>Total data</span><strong id="totalData"><?=$count?></strong><small>rekaman tersimpan</small></div>
  </div>
  <div class="card stat">
    <div class="stat-icon">📅</div><div><span>Data hari ini</span><strong id="todayData"><?=$today?></strong><small>rekaman</small></div>
  </div>
</section>

<section class="grid-2">
  <div class="card chart-card">
    <div class="card-head"><div><h2>Grafik Kelembapan</h2><p>Data sensor terbaru</p></div><span class="live-pill"><i></i> LIVE</span></div>
    <canvas id="moistureChart"></canvas>
  </div>
  <div class="card current-card">
    <div class="card-head"><div><h2>Status Tanah</h2><p>Hasil Fuzzy Tsukamoto</p></div></div>
    <div class="gauge"><div class="gauge-inner"><strong id="gaugeValue"><?=number_format((float)($last['moisture_percent']??0),0)?>%</strong><span>Kelembapan</span></div></div>
    <div class="fuzzy-box"><span>Status</span><b id="fuzzyStatus"><?=htmlspecialchars($last['fuzzy_status'] ?? 'Belum ada data')?></b><small>Durasi penyiraman: <b id="watering"><?=number_format((float)($last['watering_seconds']??0),1)?> detik</b></small></div>
  </div>
</section>

<section class="card table-card">
  <div class="card-head">
    <div><h2>Data Sensor Terbaru</h2><p>Data tersusun dari terbaru ke terlama</p></div>
    <a class="btn outline" href="data.php">Lihat semua data</a>
  </div>
  <div class="table-wrap">
  <table><thead><tr><th>No</th><th>Tanggal</th><th>Jam</th><th>ADC</th><th>Kelembapan</th><th>Fuzzy</th><th>Penyiraman</th></tr></thead>
  <tbody id="latestRows"><tr><td colspan="7">Memuat data…</td></tr></tbody></table>
  </div>
</section>
</main>
</div>
<script>
const chartCtx = document.getElementById('moistureChart');
let chart;
async function loadDashboard(){
  const r = await fetch('api/readings.php?limit=20');
  const d = await r.json();
  const rows = d.readings || [];
  document.getElementById('totalData').textContent = d.stats.total;
  document.getElementById('todayData').textContent = d.stats.today;
  document.getElementById('latestMoisture').textContent = (d.latest?.moisture_percent ?? 0) + '%';
  document.getElementById('gaugeValue').textContent = Math.round(d.latest?.moisture_percent ?? 0) + '%';
  document.getElementById('fuzzyStatus').textContent = d.latest?.fuzzy_status ?? 'Belum ada data';
  document.getElementById('watering').textContent = (d.latest?.watering_seconds ?? 0) + ' detik';
  document.getElementById('deviceStatus').textContent = d.device.active ? 'Aktif' : 'Tidak Aktif';
  document.getElementById('lastSeen').textContent = d.device.last_seen ?? 'Belum ada data';

  const body = document.getElementById('latestRows');
  body.innerHTML = rows.slice(0,8).map((x,i)=>`<tr>
    <td>${i+1}</td><td>${x.date}</td><td>${x.time}</td><td>${x.adc_value}</td>
    <td><b>${x.moisture_percent}%</b></td><td><span class="tag">${x.fuzzy_status}</span></td><td>${x.watering_seconds} detik</td>
  </tr>`).join('') || '<tr><td colspan="7">Belum ada data.</td></tr>';

  const labels = [...rows].reverse().map(x=>x.time);
  const values = [...rows].reverse().map(x=>Number(x.moisture_percent));
  if(chart) chart.destroy();
  chart = new Chart(chartCtx,{type:'line',data:{labels,datasets:[{label:'Kelembapan (%)',data:values,tension:.35,fill:true}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{min:0,max:100}},plugins:{legend:{display:false}}}});
}
loadDashboard();
setInterval(loadDashboard, 5000);
</script>
</body>
</html>
