<?php
/*
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
error_reporting(E_ALL);
*/

require_once "ui.inc";
require_once "util.inc";
require_once 'Spreadsheet/Excel/Writer.php';

// Creating a workbook
$workbook = new Spreadsheet_Excel_Writer();


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('My first worksheet');

$format_bold =& $workbook->addFormat();
$format_bold->setBold();

$format_title =& $workbook->addFormat();
$format_title->setBold();
$format_title->setColor('black');
$format_title->setPattern(1);
$format_title->setFgColor('white');

// let's merge
$format_title->setAlign('merge');

// sending HTTP headers
$workbook->send('test.xls');

$con = connect();
/*
if (!(user_type($_SESSION['uid'], 'Administrator', $con)
 || user_type($_SESSION['uid'], 'Accounts', $con))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}
*/
$date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";
$sql="select * from payroll where payroll_date = '$date'";
$result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
if (mysqli_num_rows($result2) <= 0) {
  echo msg_box("Payroll has not been prepared for this date $date", 
    'payroll_report.php', 'Back');
  exit;
}

// The actual data
/*
$worksheet->write(0, 0, 'Name');
$worksheet->write(0, 1, 'Age');
$worksheet->write(1, 0, 'John Smith');
$worksheet->write(1, 1, 30);
$worksheet->write(2, 0, 'Johann Schmidt');
$worksheet->write(2, 1, 31);
$worksheet->write(3, 0, 'Juan Herrera');
$worksheet->write(3, 1, 32);
*/

$worksheet->write(0, 0, 'Payroll Report');

$worksheet->write(1, 0, get_value('org_info', 'name', 'id', '1', $con));
$worksheet->write(2, 0, get_value('org_info', 'address', 'id', '1', $con));
$worksheet->write(3, 0, 'MONTH ENDING: ' . get_month_name($_SESSION['month_id']). ", {$_SESSION['year_id']}");
$worksheet->write(4, 0, 'BRANCH: ' . get_value('branch', 'name', 'id', $_REQUEST['branch_id'], $con));

if($_REQUEST['bank_id'] == '0')
  $bank = "All";
else 
  $bank = get_value('bank','name','id', $_REQUEST['bank_id'], $con);
  
$worksheet->write(5, 0, $bank);
$worksheet->write(6, 0, "");

    
//Calculate the number of deductions
$sql="select count(*) as 'count' from di where  type='Deductions' order by id";
$resultd = mysqli_query($con, $sql) or die(mysqli_error($con));
$rowd = mysqli_fetch_array($resultd);
$no_of_deductions = $rowd['count'];
	
	  
//Calculate the number of allowances
$sql="select count(*) as 'count' from di 
  where type='Allowances' order by id";
$resulta = mysqli_query($con, $sql) or die(mysqli_error($con));
$rowa = mysqli_fetch_array($resulta);
$no_of_allowances = $rowa['count'];

$worksheet->write(7, 0, 'S/N', $format_bold);
$worksheet->write(7, 1, 'NAMES', $format_bold);
$worksheet->write(7, 2, 'BASIC SALARY', $format_title);
$worksheet->write(7, 3, '');
$worksheet->write(7, 4, "ALLOWANCES", $format_title);
$worksheet->write(7, 5, "");

/*
$col = 4;
for($i = 0; $i < $no_of_allowances; $i++) {
  $col += $i;
  $worksheet->write(7, $col, "", $format_title);
}
++$col;
$worksheet->write(7, $col, "GROSS SALARY", $format_title);

++$col;
$worksheet->write(7, $col, "DEDUCTIONS", $format_title);
for($i = 0; $i < $no_of_deductions; $i++) {
  $col += $i;
  $worksheet->write(7, $col, "", $format_title);
}  
++$col;
$worksheet->write(7, $col, "TOTAL NET SALARY", $format_title);    

     <tr>
      <th>&nbsp;</th>
	  <th>&nbsp;</th>
      <th style='background-color:#dff;'>&nbsp;</th>
    ";
    $total = array('basic_salary'=>0);
	   
    //List all the allowances
    $sql="select * from di where type='Allowances' order by id";
    $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
    while($row2 = mysqli_fetch_array($result2)) {
      $name = "{$row2['id']}_Allowances";
      $total[$name] = 0;
      echo "<th>". strtoupper($row2['name'])."</th>";
    }
    echo "<th style='background-color:#ddf;'>&nbsp;</th>"; //Gross Salary
    $total['gross_salary'] = 0;

    //List all the deductions
    $sql="select * from di where type='Deductions' order by id";
    $result1 = mysqli_query($con, $sql) or die(mysqli_error($con));
    while($row1 = mysqli_fetch_array($result1)) {
      $name = "{$row1['id']}_Deductions";
      $total[$name] = 0;
      echo "<th>" . strtoupper($row1['name']) . "</th>";
    }

    echo   "<th style='background-color:#ddf;'>&nbsp;</th></tr>"; //Net Salary
    $total['net_pay'] = 0;
	
    //DETERMINE AND DISPLAY THE LOCATION
    if ($_REQUEST['location_id'] == '0') {
      $sql="select * from location where 
       branch_id={$_REQUEST['branch_id']}";
    } else {
      $sql="select * from location where 
        id={$_REQUEST['location_id']} and branch_id={$_REQUEST['branch_id']}";
    } 

	$sn = 1; //Serial Number
    $result_l = mysqli_query($con, $sql) or die(mysqli_error($con));
    while($row_l = mysqli_fetch_array($result_l)) {
      $t = $no_of_allowances + $no_of_deductions + 3;
      
 
      //DETERMINE WHICH EMPLOYEES TO PROCESS
      if ($_REQUEST['bank_id'] == '0') { 
	    
        $sql="SELECT * from employee where status='Enable' and branch_id= 
         {$_REQUEST['branch_id']} and location_id={$row_l['id']} order by id";
	    } else  {
	    
        $sql="SELECT * from employee where bank_id={$_REQUEST['bank_id']} 
          and status='Enable' and branch_id={$_REQUEST['branch_id']}
          and location_id={$row_l['id']} order by id";
	  }
      //echo "$sql<br>";
	   
      //GET THE EMPLOYEES
      $result = mysqli_query($con, $sql) or die(mysqli_error($con));
	  if(mysqli_num_rows($result) <= 0)
	    continue;
		
	  echo "<tr><td colspan='$t'><b>{$row_l['name']}</b></td></tr>";
	  
      while($row = mysqli_fetch_array($result)) {

        //GET PAYROLL DATA FOR EMPLOYEES
        $sql="select * from payroll where employee_id={$row['id']} 
          and payroll_date = '$date'";
        $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
        if (mysqli_num_rows($result3) == 0) {
          echo msg_box("Payroll has not been prepared for this date
           for this Employee", "payroll_report.php", "Back");
          exit;
        }
        while($row3 = mysqli_fetch_array($result3)) {
          $deductions = 0;
          $allowances = 0;
		  
          //DISPLAY EMPLOYEE NAME
	  
	  echo "
           <tr style='text-align:center;'>
		    <td style='border:1px solid black;'>$sn</td>
            <td style='border:1px solid black;'>
             {$row['firstname']} {$row['middlename']} {$row['lastname']}
            </td>
          ";
	  ++$sn;
	  $total['basic_salary'] += $row3['basic_salary'];

          //DISPLAY EMPLOYEE BASIC SALARY
	  echo "<td style='border: 1px solid black; background-color:#ddf;'>" 
             . number_format($row3['basic_salary'], 2) . "</td>";

          //DISPLAY ALLOWANCES
	  $sql="select * from di where type='Allowances' order by id";
	  $result6 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  while($row6 = mysqli_fetch_array($result6)) {

            //GET EMPLOYEE ALLOWANCES
	    $name = "{$row6['id']}_Allowances";
	    $sql="select amount from payroll_di where 
             payroll_id={$row3['id']} and di={$row6['id']}";
	    $result7 = mysqli_query($con, $sql) or die(mysqli_error($con));
		
	    $row7 = mysqli_fetch_array($result7);
	    $total[$name] += $row7['amount'];
	    $allowances += $row7['amount'];
       
            //DISPLAY EMPLOYEE ALLOWANCES 
	    echo "  <td style='border:1px solid black;'>" 
            . number_format($row7['amount'], 2) . "</td>";
	  }
		
          //CALCULATE AND DISPLAY GROSS SALARY
          $gross_salary = $row3['basic_salary'] + $allowances;
          $total['gross_salary'] += $gross_salary;
          echo "<td style='border: 1px solid black; background:#dff;'>"
            . number_format($gross_salary, 2) . "</td>";

          //DISPLAY DEDUCTIONS
	  $sql="select * from di where type='Deductions' order by id";
	  $result4 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  while($row4 = mysqli_fetch_array($result4)) {
  
            //GET EMPLOYEE ALLOWANCES
	    $name = "{$row4['id']}_Deductions";
	    $sql="select amount from payroll_di where 
            payroll_id={$row3['id']} and di={$row4['id']}";
	    $result5 = mysqli_query($con, $sql) or die(mysqli_error($con));

	    $row5 = mysqli_fetch_array($result5);
	    $deductions += $row5['amount'];
	    $total[$name] += $row5['amount'];

            //DISPLAY EMPLOYEE DEDUCTIONS
	    echo "<td style='border:1px solid black;'>"
	      .number_format($row5['amount'], 2) . "</td>";
	  }
	  //CALCULATE AND DISPLAY NET PAY		
	  $net_pay = $gross_salary - $deductions;
	  echo "<td style='border:1px solid black; background-color:#ddf;'>" 
            . number_format($net_pay, 2) . "</td>";
	  $total['net_pay'] += $net_pay;
	  echo "</tr>";
        } //end of looping through payroll data of employee 
      } // end of looping through employee data
    }// end of looping through locations 

   echo "<tr><td>&nbsp;</td></tr>";
    echo "<tr style='text-align:center;'>
	 <td style='border:1px solid black'>&nbsp;</td>
     <td style='border:1px solid black'>&nbsp;</td>";
    foreach($total as $name => $value) {
      if (($name == 'basic_salary') || ($name == 'gross_salary')
        || ($name == 'net_pay')) {
        echo "<td style=' border:1px solid black;background-color:#dff;'>"; 
      }
      else 
        echo "<td style='border:1px solid black;'><b>"; 
      echo number_format($value,2) . "</b></td>";
    } 
    echo "</tr>
         </table>
	</td>
       </tr>    
    ";
    exit;  

  } 
  
}
*/



// Let's send the file
$workbook->close();

  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3>Payroll Report</h3></td>
   </tr>
   <form action="payroll_report.php" method="post" name="form1" id="form1">
   <?php 
   if (mysqli_num_rows(mysqli_query($con, "select * from bank order by id")) <= 0) {
     echo msg_box("Please add a bank", 'bank.php', 'Continue to Bank');
     exit;
   }
   ?>
   <tr><td>Date</td><td><?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?></td></tr>
   <tr>
    <td>Branch</td>
    <td>
     <select name="branch_id" onchange="get_location('All');">
      <option value='0'></option>
      <?
      $result = mysqli_query($con, "select * from branch order by id");
      while($row = mysqli_fetch_array($result)) {
        echo "<option value='{$row['id']}'>{$row['name']}</option>";
      }
      ?>
     </select>
    </td>
   </tr>
   <tr>
    <td>Location</td>
    <td>
     <select name="location_id" id="location_id">
      <option value='-1'>&nbsp;</option>
     </select>
    </td>
   </tr>
   <tr>
    <td>Bank</td>
    <td>
     <?php
      echo selectfield(
       array('0'=>'All') + my_query("select * from bank", 'id', 'name'), 
       'bank_id', '');
     ?>
    </td>
   </tr>
   <tr>
    <td>
     <input name="action" type="submit" value="Process">
     <input name='action' type='submit' value='Cancel'>
    </td>
   </tr>
   </form>
  </table>
