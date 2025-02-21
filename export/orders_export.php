<?php
require '../vendor/autoload.php';
require_once '../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Créer un nouveau document Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// En-têtes
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Client');
$sheet->setCellValue('C1', 'Fichier STL');
$sheet->setCellValue('D1', 'Couleur');
$sheet->setCellValue('E1', 'Dimensions');
$sheet->setCellValue('F1', 'Type de filament');
$sheet->setCellValue('G1', 'Statut');
$sheet->setCellValue('H1', 'Date de création');

// Récupérer les données
$sql = "SELECT * FROM orders ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Remplir les données
$row = 2;
foreach ($orders as $order) {
    $sheet->setCellValue('A' . $row, $order['id']);
    $sheet->setCellValue('B' . $row, $order['client_name']);
    $sheet->setCellValue('C' . $row, $order['stl_file']);
    $sheet->setCellValue('D' . $row, $order['color']);
    $sheet->setCellValue('E' . $row, $order['dimensions']);
    $sheet->setCellValue('F' . $row, $order['filament_type']);
    $sheet->setCellValue('G' . $row, $order['status']);
    $sheet->setCellValue('H' . $row, $order['created_at']);
    $row++;
}

// Auto-dimensionner les colonnes
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Créer le fichier Excel
$writer = new Xlsx($spreadsheet);

// En-têtes HTTP pour le téléchargement
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="commandes_impression3d.xlsx"');
header('Cache-Control: max-age=0');

// Sauvegarder le fichier
$writer->save('php://output');
exit; 