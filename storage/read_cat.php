<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$file = 'storage/falabella/SuministrosTemplate-Express_2026-07.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Subir plantilla');
if ($sheet) {
    echo 'D5: ' . $sheet->getCell('D5')->getValue();
}
