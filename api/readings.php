<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_login();

$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));

$stmt = $pdo->prepare("SELECT r.id, r.adc_value, r.moisture_percent, r.fuzzy_status, r.watering_seconds, r.created_at,
                       DATE_FORMAT(r.created_at,'%d/%m/%Y') AS date,
                       DATE_FORMAT(r.created_at,'%H:%i:%s') AS time
                       FROM sensor_readings r
                       ORDER BY r.created_at DESC, r.id DESC LIMIT {$limit}");
$stmt->execute();
$readings = $stmt->fetchAll();

$total = (int)$pdo->query("SELECT COUNT(*) FROM sensor_readings")->fetchColumn();
$today = (int)$pdo->query("SELECT COUNT(*) FROM sensor_readings WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$latest = $readings[0] ?? null;
$device = $pdo->query("SELECT last_seen FROM devices ORDER BY id LIMIT 1")->fetch();
$active = $device && $device['last_seen'] && (time() - strtotime($device['last_seen']) <= 30);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'readings'=>$readings,
    'latest'=>$latest,
    'stats'=>['total'=>$total,'today'=>$today],
    'device'=>['active'=>$active,'last_seen'=>$device['last_seen'] ?? null]
]);
