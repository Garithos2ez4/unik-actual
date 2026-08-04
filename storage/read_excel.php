<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$file = 'storage/falabella/TecladoCreationTemplate-Express.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Subir plantilla');
if (!$sheet) { echo 'Hoja no encontrada'; exit; }
foreach (range('A', 'Z') as $col) {
    $val = $sheet->getCell($col . '3')->getValue();
    $val4 = $sheet->getCell($col . '4')->getValue();
    if ($val) {
        echo "$col => $val | $val4\n";
    }
}
foreach (range('A', 'Z') as $c1) {
    foreach (range('A', 'Z') as $c2) {
        $col = $c1 . $c2;
        $val = $sheet->getCell($col . '3')->getValue();
        $val4 = $sheet->getCell($col . '4')->getValue();
        if ($val3 || $val4) {
            $row3[$col] = $val3;
            $row4[$col] = $val4;
        }
        if ($col == 'BZ') break 2;
    }
}
foreach ($row3 as $col => $val) {
    echo $col . ' => ' . trim((string)$val) . ' | ' . trim((string)$row4[$col]) . "\n";
}
