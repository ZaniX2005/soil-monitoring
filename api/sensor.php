<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_POST['api_key'] ?? '');
$deviceKey = trim($_POST['device_key'] ?? '');

$raw = file_get_contents('php://input');
if ($raw) {
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $deviceKey = trim((string)($json['device_key'] ?? $deviceKey));
        $apiKey = (string)($json['api_key'] ?? $apiKey);
        $_POST = array_merge($_POST, $json);
    }
}

if ($deviceKey === '' || $apiKey === '') {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'device_key dan api_key wajib diisi']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, device_key FROM devices WHERE device_key = ?");
$stmt->execute([$deviceKey]);
$device = $stmt->fetch();

if (!$device || !hash_equals($device['device_key'], $apiKey)) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'API key tidak valid']);
    exit;
}

$adc = (int)($_POST['adc_value'] ?? 0);
$moisture = (float)($_POST['moisture_percent'] ?? 0);
$fuzzy = trim((string)($_POST['fuzzy_status'] ?? 'Tidak diketahui'));
$watering = (float)($_POST['watering_seconds'] ?? 0);

if ($moisture < 0 || $moisture > 100) {
    http_response_code(422);
    echo json_encode(['success'=>false,'message'=>'moisture_percent harus 0-100']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO sensor_readings
    (device_id, moisture_percent, adc_value, fuzzy_status, watering_seconds)
    VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$device['id'], $moisture, $adc, $fuzzy, $watering]);

$pdo->prepare("UPDATE devices SET last_seen = NOW() WHERE id = ?")->execute([$device['id']]);

echo json_encode(['success'=>true,'message'=>'Data tersimpan','id'=>(int)$pdo->lastInsertId()]);
