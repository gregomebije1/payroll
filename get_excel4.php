<?php
require_once 'Spreadsheet/Excel/Writer.php';

$workbook = new Spreadsheet_Excel_Writer();
$worksheet =& $workbook->addWorksheet();
// we can set set all properties on instantiation
$upper_right_side_brick =& $workbook->addFormat(array('right' => 5, 'top' => 5, 'size' => 15,
                                                      'pattern' => 1, 'bordercolor' => 'blue',
                                                      'fgcolor' => 'red'));
// or set all properties one by one
$upper_left_side_brick =& $workbook->addFormat();
$upper_left_side_brick->setLeft(5);
$upper_left_side_brick->setTop(5);
$upper_left_side_brick->setSize(15);
$upper_left_side_brick->setPattern(1);
$upper_left_side_brick->setBorderColor('blue');
$upper_left_side_brick->setFgColor('red');

$lower_right_side_brick =& $workbook->addFormat(array('right' => 5, 'bottom' => 5, 'size' => 15,
                                                      'pattern' => 1, 'bordercolor' => 'blue',
                                                      'fgcolor' => 'red'));
$lower_left_side_brick =& $workbook->addFormat(array('left' => 5, 'bottom' => 5, 'size' => 15,
                                                     'pattern' => 1, 'bordercolor' => 'blue',
                                                     'fgcolor' => 'red'));

$worksheet->setColumn(0, 20, 6);

// Sky
$sky =& $workbook->addFormat(array('fgcolor' => 'cyan', 'pattern' => 1, 'size' => 15));
for ($i = 0; $i <= 10; $i++)
{
    for ($j = 0; $j < 20; $j++) {
        $worksheet->writeBlank($i, $j, $sky);
    }
}

// Cloud
$cloud =& $workbook->addFormat(array('fgcolor' => 'white', 'pattern' => 1, 'size' => 15));
$worksheet->writeBlank(5, 7, $cloud);
$worksheet->writeBlank(4, 8, $cloud);
$worksheet->writeBlank(5, 8, $cloud);
$worksheet->writeBlank(6, 8, $cloud);
$worksheet->writeBlank(4, 9, $cloud);
$worksheet->writeBlank(5, 9, $cloud);
$worksheet->writeBlank(5, 10, $cloud);

// Bricks
for ($j = 0; $j < 20; $j++)
{
    for ($i = 5; $i <= 11; $i++)
    {
        if (($i + $j)%2 == 1) // right side of brick
        {
            $worksheet->writeBlank(2*$i, $j, $upper_right_side_brick);
            $worksheet->writeBlank(2*$i + 1, $j, $lower_right_side_brick);
        }
        else // left side of brick
        {
            $worksheet->writeBlank(2*$i, $j, $upper_left_side_brick);
            $worksheet->writeBlank(2*$i + 1, $j, $lower_left_side_brick);
        }
    }
}

// hide gridlines so they don't mess with our Excel art.
$worksheet->hideGridLines();

$workbook->send('bricks.xls');
$workbook->close();
?> 
