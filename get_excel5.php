<?php
  require_once 'Spreadsheet/Excel/Writer.php';

  $workbook = new Spreadsheet_Excel_Writer('test.xls');
  //$workbook->send('test.xls');
  $worksheet =& $workbook->addWorkSheet();
  
  $worksheet->setColumn(0,0,100);
  $worksheet->write(0,0,"99 Bottles Of Beer On The Wall- The Complete Lyrics");
  // repeat only the first row
  $worksheet->repeatRows(0);
  for ($i = 99; $i > 0; $i--)
  {
      if ($i > 1) {
          $next = $i - 1;
      }
      else {
          $next = "no more";
      }
      $worksheet->write(100 - $i,0,"$i Bottles of beer on the wall, $i bottles of beer, ".
                                   "take one down, pass it around, ".
                                   "$next bottles of beer on the wall.");
  }
  
 

  $workbook->close();
?> 