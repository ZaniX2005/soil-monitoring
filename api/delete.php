<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false]); exit;
}
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'ID tidak valid']); exit;
}
$stmt = $pdo->prepare("DELETE FROM sensor_readings WHERE id=?");
$stmt->execute([$id]);
echo json_encode(['success'=>true,'deleted'=>$stmt->rowCount()]);
