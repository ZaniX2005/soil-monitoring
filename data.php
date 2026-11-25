<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_login();

$rows = $pdo->query("SELECT r.*, DATE_FORMAT(r.created_at,'%d/%m/%Y') date, DATE_FORMAT(r.created_at,'%H:%i:%s') time
                     FROM sensor_readings r ORDER BY r.created_at DESC, r.id DESC LIMIT 500")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Data Sensor — Soil Monitor</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<div class="app">
<aside class="sidebar">
<div class="logo"><span>SM</span><div><b>Soil Monitor</b><small>ESP32 • Fuzzy</small></div></div>
<nav><a href="index.php">Dashboard</a><a class="active" href="data.php">Data Sensor</a><a href="export.php">Export Excel</a></nav>
<div class="side-bottom"><a class="logout" href="logout.php">Keluar</a></div>
</aside>
<main class="main">
<header class="topbar"><div><div class="eyebrow">REKAMAN SENSOR</div><h1>Data Sensor</h1></div>
<div class="userbox"><span><?=htmlspecialchars(current_user())?></span><a href="logout.php">Logout</a></div></header>
<section class="card table-card">
<div class="card-head"><div><h2>Riwayat Kelembapan</h2><p>Urutan terbaru berada di bagian atas.</p></div>
<a class="btn primary" href="export.php">⬇ Export Excel</a></div>
<div class="table-wrap"><table><thead><tr><th>No</th><th>Tanggal</th><th>Jam</th><th>ADC</th><th>Kelembapan</th><th>Fuzzy</th><th>Penyiraman</th><th>Aksi</th></tr></thead>
<tbody>
<?php foreach($rows as $i=>$r): ?>
<tr id="row-<?=$r['id']?>">
<td><?=$i+1?></td><td><?=htmlspecialchars($r['date'])?></td><td><?=htmlspecialchars($r['time'])?></td>
<td><?=$r['adc_value']?></td><td><b><?=$r['moisture_percent']?>%</b></td>
<td><span class="tag"><?=htmlspecialchars($r['fuzzy_status'])?></span></td><td><?=$r['watering_seconds']?> detik</td>
<td><button class="btn danger small" onclick="hapus(<?=$r['id']?>)">Hapus</button></td>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="8">Belum ada data sensor.</td></tr><?php endif; ?>
</tbody></table></div></section>
</main></div>
<script>
async function hapus(id){
 if(!confirm('Hapus data ini?')) return;
 const fd=new FormData(); fd.append('id',id);
 const r=await fetch('api/delete.php',{method:'POST',body:fd});
 const d=await r.json();
 if(d.success) document.getElementById('row-'+id)?.remove();
}
</script>
</body></html>
