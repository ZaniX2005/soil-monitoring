<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_login();

$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die("PhpSpreadsheet belum terpasang. Buka Terminal Laragon di folder project lalu jalankan: composer install");
}
require $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$rows = $pdo->query("SELECT r.id, d.device_name, r.adc_value, r.moisture_percent, r.fuzzy_status, r.watering_seconds,
                     DATE_FORMAT(r.created_at,'%d/%m/%Y') date, DATE_FORMAT(r.created_at,'%H:%i:%s') time
                     FROM sensor_readings r JOIN devices d ON d.id=r.device_id
                     ORDER BY r.created_at ASC, r.id ASC")->fetchAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Kelembapan');

$headers = ['No','Tanggal','Jam','Perangkat','Nilai ADC','Kelembapan (%)','Status Fuzzy','Durasi Penyiraman (detik)'];
$sheet->fromArray($headers, null, 'A1');

foreach($rows as $i=>$r){
    $sheet->fromArray([
        $i+1, $r['date'], $r['time'], $r['device_name'], (int)$r['adc_value'],
        (float)$r['moisture_percent'], $r['fuzzy_status'], (float)$r['watering_seconds']
    ], null, 'A'.($i+2));
}

$sheet->freezePane('A2');
$sheet->getAutoFilter()->setRange('A1:H'.max(1,count($rows)+1));
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
$sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0F172A');
$sheet->getStyle('A1:H1')->getFont()->getColor()->setARGB('FFFFFF');
$sheet->getStyle('A1:H'.max(1,count($rows)+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
foreach(range('A','H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$filename = 'data_kelembapan_'.date('Y-m-d_H-i-s').'.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;
