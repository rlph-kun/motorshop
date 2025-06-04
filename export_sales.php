<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
include '../../includes/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = $search ? "AND p.product_name LIKE '%$search%'" : "";

// Get sales data
$sales = mysqli_query($conn, "
    SELECT p.product_name, s.quantity, s.sale_date, p.price
    FROM sales_log s
    JOIN inventory p ON s.product_id = p.id
    WHERE 1=1 $search_condition
    ORDER BY s.sale_date DESC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$headers = ['Product Name', 'Quantity Sold', 'Sale Date', 'Sale Time', 'Total Amount'];
$sheet->fromArray($headers, NULL, 'A1');

// Style header row (bold, white text, blue background)
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '4F81BD', // Excel blue
        ],
    ],
];
$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

// Add data rows
$rowNum = 2;
while ($row = mysqli_fetch_assoc($sales)) {
    $sale_date = date('M d, Y', strtotime($row['sale_date']));
    $sale_time = date('g:i a', strtotime($row['sale_date']));
    $total_amount = $row['quantity'] * $row['price'];
    $sheet->setCellValue('A' . $rowNum, $row['product_name']);
    $sheet->setCellValue('B' . $rowNum, $row['quantity']);
    $sheet->setCellValue('C' . $rowNum, $sale_date);
    $sheet->setCellValue('D' . $rowNum, $sale_time);
    $sheet->setCellValue('E' . $rowNum, number_format($total_amount, 2));
    $rowNum++;
}

// Auto-size columns
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Center-align all cell content (headers and data)
$sheet->getStyle('A1:E' . ($rowNum - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Add borders to all cells
$lastRow = $rowNum - 1;
$sheet->getStyle('A1:E' . $lastRow)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
]);

// Output as .xlsx file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="sales_history_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit(); 