<?php
require_once 'Spreadsheet/Excel/Writer.php';
require_once "ui.inc";
require_once "util.inc";
require_once "connect.inc";

$con = connect();

// Creating a workbook
$workbook = new Spreadsheet_Excel_Writer();

// sending HTTP headers
$workbook->send('test.xls');


$date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";

$format_bold =& $workbook->addFormat();
$format_bold->setBold();


// Creating a worksheet
$worksheet =& $workbook->addWorksheet("Bank Schedule for $date");

if ($_REQUEST['bank_id'] == 0)
  $bank_name = "Bank";
else 
  $bank_name = get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con);

// This is our title
$worksheet->write(0, 0, "$bank_name SCHEDULE", $format_bold);
$worksheet->write(1, 0, get_value('org_info', 'name', 'id', '1', $con));
$worksheet->write(2, 0, get_value('org_info', 'address', 'id', '1', $con));

if ($_REQUEST['bank_id'] == '0') 
  $worksheet->write(3, 0, "Bank: All");
else 
  $worksheet->write(3, 0, "Bank: " . get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con));
  
$worksheet->write(4, 0, "Pay Period: MONTH ENDING: " . get_month_name($_SESSION['month_id']) . ", {$_SESSION['year_id']}");
$worksheet->write(5, 0, "BRANCH: " . get_value('branch', 'name', 'id', $_REQUEST['branch_id'], $con));

$worksheet->write(6, 0, "");
$worksheet->write(7, 0, "S/N",  $format_bold);
$worksheet->write(7, 1, "NAMES",  $format_bold);
$worksheet->write(7, 2, "ACCOUNT NUMBERS",  $format_bold);
$worksheet->write(7, 3, "NET SALARIES",  $format_bold);

$worksheet->repeatRows(7);


//DETERMINE AND DISPLAY THE LOCATION
if ($_REQUEST['location_id'] == '0') {
  $sql="select * from location where branch_id={$_REQUEST['branch_id']}";
} else {
  $sql="select * from location where id={$_REQUEST['location_id']} and branch_id={$_REQUEST['branch_id']}";
}
  
$total = 0;	
$sn = 1; //Serial Number
$count = 1; //Count after initial 15 rows

$row_count = 8;
$result_l = mysqli_query($con, $sql) or die(mysqli_error($con));

while($row_l = mysqli_fetch_array($result_l)) {

   //DETERMINE WHICH EMPLOYEES TO PROCESS
    if ($_REQUEST['bank_id'] == '0') {
	  
      $sql="SELECT * from employee where status='Enable' and branch_id=
       {$_REQUEST['branch_id']} and location_id={$row_l['id']} order by id";
	} else  {
      
	  $sql="SELECT * from employee where bank_id={$_REQUEST['bank_id']}
       and status='Enable' and branch_id={$_REQUEST['branch_id']}
       and location_id={$row_l['id']} order by id";
	}
	
	//GET THE EMPLOYEES
	$result = mysqli_query($con, $sql) or die(mysqli_error($con));
	if(mysqli_num_rows($result) <= 0)
	    continue;
		
	$worksheet->write($row_count, 0, $row_l['name']);
    $branch_total = 0;
    while($row = mysqli_fetch_array($result)) {
      //CHECK IF PAYROLL HAS BEEN PREPARED FOR THIS DATE
      $sql="select * from payroll where employee_id={$row['id']} 
        and payroll_date='$date'";
	  $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
      if (mysqli_num_rows($result3) == 0) {
         echo msg_box("Payroll has not been prepared for this date
          for this Employee", "bank_schedule.php", "Bank");
         exit;
      } 
	  
	  
	  if ($sn == 17) {
	    
		$worksheet->write(++$row_count, 0, '');
		$worksheet->write($row_count, 1, 'TOTAL', $format_bold);
		$worksheet->write($row_count, 2, '');
		$worksheet->write($row_count, 3, number_format($total,2),  $format_bold);
        $worksheet->write($row_count, 4, '');
		
		//Echo Heading
		$worksheet->write(++$row_count, 0, "");
        $worksheet->write(++$row_count, 0, "S/N", $format_bold);
        $worksheet->write($row_count, 1, "NAMES", $format_bold);
        $worksheet->write($row_count, 2, "ACCOUNT NUMBERS", $format_bold);
        $worksheet->write($row_count, 3, "NET SALARIES", $format_bold);
		
	    //B/F
		
		$worksheet->write(++$row_count, 0, "");
		$worksheet->write($row_count, 1, "Balance B/F", $format_bold);
		$worksheet->write($row_count, 2, "");
		$worksheet->write($row_count, 3, number_format($total,2), $format_bold);
		$worksheet->write($row_count, 4, "");
		
		$count -= 17;  //Restart the counter
			  
	  } else if (($count % 20) == 0){
	    $worksheet->write(++$row_count, 0, '');
		$worksheet->write($row_count, 1, 'TOTAL', $format_bold);
		$worksheet->write($row_count, 2, '');
		$worksheet->write($row_count, 3, number_format($total,2), $format_bold);
		$worksheet->write($row_count, 4, '');
        
		
		//Echo Heading
		$worksheet->write(++$row_count, 0, "");
        $worksheet->write(++$row_count, 0, "S/N", $format_bold);
        $worksheet->write($row_count, 1, "NAMES", $format_bold);
        $worksheet->write($row_count, 2, "ACCOUNT NUMBERS", $format_bold);
        $worksheet->write($row_count, 3, "NET SALARIES", $format_bold);
				
		//B/F
		$worksheet->write(++$row_count, 0, "");
		$worksheet->write($row_count, 1, "Balance B/F", $format_bold);
		$worksheet->write($row_count, 2, "");
		$worksheet->write($row_count, 3, number_format($total,2), $format_bold);
		$worksheet->write($row_count, 4, "");
		
		      
	  }
	  
	  $worksheet->write(++$row_count, 0, $sn);
	  $worksheet->write($row_count, 1, "{$row['firstname']} {$row['middlename']} {$row['lastname']}");
	  $worksheet->write($row_count, 2, "{$row['bank_account_number']}");
      
	  ++$sn;
	  ++$count;
	  
	  
      $row3 = mysqli_fetch_array($result3);
	  
      $earnings = 0;
      $deductions = 0;
	  $earnings += $row3['basic_salary'];
	  
	    
	  $sql="select * from di where type='Allowances' order by id";
	  $result6 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  while($row6 = mysqli_fetch_array($result6)) {
	    $sql="select amount from payroll_di where 
          payroll_id={$row3['id']} and di={$row6['id']}";
	    $result7 = mysqli_query($con, $sql) or die(mysqli_error($con));
		
	    $row7 = mysqli_fetch_array($result7);
	    $earnings += $row7['amount'];
	  }
	  $sql="select * from di where type='Deductions' order by id";
	  $result4 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  while($row4 = mysqli_fetch_array($result4)) {
	    $sql="select amount from payroll_di where 
          payroll_id={$row3['id']} and di={$row4['id']}";
	    $result5 = mysqli_query($con, $sql) or die(mysqli_error($con));

	    $row5 = mysqli_fetch_array($result5);
		$deductions += $row5['amount'];    
	  }
	  
      $total += ($earnings - $deductions);
      $branch_total += ($earnings - $deductions);
	  $worksheet->write($row_count, 3, number_format($earnings - $deductions, 2));
    }
	$worksheet->write(++$row_count, 0, '');
    $worksheet->write($row_count, 1, '');
	$worksheet->write($row_count, 2, '');
    $worksheet->write($row_count, 3, number_format($branch_total,2), $format_bold); 
    $worksheet->write($row_count, 4, '');
    
	$row_count++;
 }
 
$worksheet->write(++$row_count, 0, '');
$worksheet->write($row_count, 1, 'TOTAL', $format_bold);
$worksheet->write($row_count, 2, '');
$worksheet->write($row_count, 3, number_format($total,2), $format_bold); 

// Let's send the file
$workbook->close();
?>
