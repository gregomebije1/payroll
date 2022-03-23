<?php
require_once 'Spreadsheet/Excel/Writer.php';
$workbook = new Spreadsheet_Excel_Writer();

$format_bold =& $workbook->addFormat();
$format_bold->setBold();

$format_title =& $workbook->addFormat();
$format_title->setBold();
$format_title->setColor('yellow');
$format_title->setPattern(1);
$format_title->setFgColor('blue');
// let's merge
$format_title->setAlign('merge');

$worksheet =& $workbook->addWorksheet();
$worksheet->write(0, 0, "Quarterly Profits for Dotcom.Com", $format_title);
// Couple of empty cells to make it look better
$worksheet->write(0, 1, "", $format_title);
$worksheet->write(0, 2, "", $format_title);
$worksheet->write(1, 0, "Quarter", $format_bold);
$worksheet->write(1, 1, "Profit", $format_bold);
$worksheet->write(2, 0, "Q1");
$worksheet->write(2, 1, 0);
$worksheet->write(3, 0, "Q2");
$worksheet->write(3, 1, 0);

$workbook->send('test.xls');
$workbook->close();
?>