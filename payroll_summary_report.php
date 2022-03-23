<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
error_reporting(E_ALL);

require_once "ui.inc";
require_once "util.inc";
require_once "connect.inc";
require_once "payroll.inc";


$con = connect();
if (!(user_type($_SESSION['uid'], 'Administrator', $con)
 || user_type($_SESSION['uid'], 'Accounts', $con))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}

if(isset($_REQUEST['action']) && ($_REQUEST['action'] =="Print")) {
    print_header('Payroll Summary Report', 
     'payroll_summary_report.php', 'Back', $con);
} else {
 if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
   print_header('Payroll Summary Report', 'payroll_summary_report.php', 
    'Back', $con);
 } else {
  main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
 }  

 if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
  $date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";
  if (empty($date)) {
    echo msg_box('Please enter correct date', 
     'payroll_summary_report.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['branch_id'])) {
    echo msg_box('Please choose a Branch', 'payroll_summary_report.php','Back');
    exit;
  }
  if ($_REQUEST['branch_id'] == '0') {
    echo msg_box("Please choose a Branch", "payroll_summary_report.php", "Back");
	exit;
  }
  if (!isset($_REQUEST['location_id'])) {
   echo msg_box('Please choose a Location','payroll_summary_report.php','Back');
   exit;
  }
  if (!isset($_REQUEST['bank_id'])) {
    echo msg_box('Please choose a Bank', 'payroll_summary_report.php', 'Back');
    exit;
  }

  $sql="select * from payroll where payroll_date = '$date'";
  $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result2) == 0) {
    echo msg_box("Payroll has not been prepared for this date $date", 
     'payroll_summary_report.php', 'Back');
	exit;
  }
  echo "
    <a href='payroll_summary_report.php'><h3>Back</h3></a><br />
     <table> 
      <caption><h2>Payroll Summary Report</h2></caption>
      <tr><td style='text-align:center;'>" 
       . get_value('org_info', 'name', 'id', '1', $con) . "</td></tr>

       <tr><td style='text-align:center;'>"
       . get_value('org_info', 'address', 'id', '1', $con) . "</td></tr>

	  <tr><td style='text-align:center;'>MONTH ENDING: " 
       . get_month_name($_SESSION['month_id']).", {$_SESSION['year_id']} </td></tr>

       <tr><td style='text-align:center;'>BRANCH" . get_value('branch', 'name', 'id', $_REQUEST['branch_id'], $con);
  
  echo  "</td></tr> <tr><td style='text-align:center;'>LOCATION: ";
  echo ($_REQUEST['location_id'] == '0') ? "All" : get_value('location', 'name', 'id', $_REQUEST['location_id'], $con);
  
  echo "</td></tr> <tr><td style='text-align:center;'>BANK: ";
  echo get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con). "</td></tr>";
  
  echo "<tr><td>&nbsp;</td></tr>
       <tr>
        <td>		
	 <table style='font-size:0.8em;' text-align:center;'>
  ";
  //CALCULATE THE NUMBER OF DEDUCTIONS
  $sql="select count(*) as 'count' from di where type='Deductions' order by id";
  $resultd = mysqli_query($con, $sql) or die(mysqli_error($con));
  $rowd = mysqli_fetch_array($resultd);
  $no_of_deductions = $rowd['count'];
	  
  //CALCULATE THE NUMBER OF ALLOWANCES 
  $sql="select count(*) as 'count' from di where type='Allowances' order by id";
  $resulta = mysqli_query($con, $sql) or die(mysqli_error($con));
  $rowa = mysqli_fetch_array($resulta);
  $no_of_allowances = $rowa['count'];

  echo "
   <tr>
    <th>BASIC SALARY</th>
    <th style='text-align:center;' colspan='$no_of_allowances'>ALLOWANCES</th>
    <th>GROSS SALARY</th>
    <th style='text-align:center;' colspan='$no_of_deductions'>DEDUCTIONS</th>
    <th style='text-align:center;'>TOTAL NET SALARY</th>
   </tr>
   <tr>
    <th>&nbsp;</th>
  ";
  $total = array('basic_salary'=>0);

  //DISPLAY ALL THE ALLOWANCES
  $sql="select * from di where type='Allowances' order by id";
  $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row2 = mysqli_fetch_array($result2)) {
    $name = "{$row2['id']}_Allowances";
    $total[$name] = 0;
    echo "<th>" . strtoupper($row2['name']) . "</th>";
  }
  echo   "<th>&nbsp;</th>"; //DISPLAY GROSS SALARY
  $total['gross_salary'] = 0;
	   
  //DISPLAY ALL THE DEDUCTIONS
  $sql="select * from di where type='Deductions' order by id";
  $result1 = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row1 = mysqli_fetch_array($result1)) {
    $name = "{$row1['id']}_Deductions";
    $total[$name] = 0;
    echo "  <th>" . strtoupper($row1['name']) . "</th>";
  }
  echo "<th>&nbsp;</th></tr>"; //DISPLAY NET SALARY
  $total['net_pay'] = 0;

  //DETERMINE AND DISPLAY THE LOCATION
  if ($_REQUEST['location_id'] == '0') {
    $sql="select * from location where
      branch_id={$_REQUEST['branch_id']}";
  } else {
    $sql="select * from location where
        id={$_REQUEST['location_id']} and branch_id={$_REQUEST['branch_id']}";
  }
  $result_l = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row_l = mysqli_fetch_array($result_l)) {
    $t = $no_of_allowances + $no_of_deductions + 3;
    echo "<tr><td colspan='$t'><b>{$row_l['name']}</b></td></tr>";

    //DETERMINE WHICH EMPLOYEES TO PROCESS
    $sql="SELECT * from employee where bank_id={$_REQUEST['bank_id']}
          and status='Enable' and branch_id={$_REQUEST['branch_id']}
          and location_id={$row_l['id']} order by id";
	
    // GET THE EMPLOYEES
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    while($row = mysqli_fetch_array($result)) {
  
      //GET PAYROLL DATA FOR EMPLOYEES
      $sql="select * from payroll where employee_id={$row['id']} 
      and payroll_date = '$date'";
      $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
      if (mysqli_num_rows($result3) == 0) {
	    continue;
		/*
        echo msg_box("Payroll has not be prepared for this date
         for this Employee", "payroll_summary_report.php", "Back");
        exit; 
		*/
      } 
      while($row3 = mysqli_fetch_array($result3)) {
        $deductions = 0;
        $allowances = 0;
	  
        $total['basic_salary'] += $row3['basic_salary'];
		
        //CALCULATE EMPLOYEE ALLOWANCES
        $sql="select * from di where type='Allowances' order by id";
        $result6 = mysqli_query($con, $sql) or die(mysqli_error($con));
        while($row6 = mysqli_fetch_array($result6)) {
          $name = "{$row6['id']}_Allowances";
          $sql="select amount from payroll_di where payroll_id={$row3['id']} 
           and di={$row6['id']}";
	  $result7 = mysqli_query($con, $sql) or die(mysqli_error($con));
		
	  $row7 = mysqli_fetch_array($result7);
	  $total[$name] += $row7['amount'];
		
	  $allowances += $row7['amount'];
        }

        $gross_salary = $row3['basic_salary'] + $allowances;
        $total['gross_salary'] += $gross_salary;	  

        //CALCULATE EMPLOYEE DEDUCTIONS
        $sql="select * from di where type='Deductions' order by id";
        $result4 = mysqli_query($con, $sql) or die(mysqli_error($con));
        while($row4 = mysqli_fetch_array($result4)) {
          $sql="select di, amount from payroll_di where payroll_id={$row3['id']} and di={$row4['id']}";
          $result5 = mysqli_query($con, $sql) or die(mysqli_error($con));
          $row5 = mysqli_fetch_array($result5);
		  $value = $row5['amount'];
			
          $deductions += $value;
          $name = "{$row4['id']}_Deductions";
	      $total[$name] += $value;
		
        }
        //CALCULATE NET PAY
        $net_pay = $gross_salary - $deductions;
        $total['net_pay'] += $net_pay;
      } // end of employee payroll data
    } // end of employee loop
  } //end the location loop

  echo "<tr>";
  foreach($total as $name => $value) {
    echo "<td style='border:1px solid black; text-align:center;'>" 
    . number_format($value,2) . "</td>";
  }
  echo "</tr>
    </table>
    </td>
   </tr>    
  ";
  exit;  
  }  
}
?>
<table> 
 <tr class="class1">
  <td colspan="4"><h3>Payroll Summary Report</h3></td>
 </tr>
 <form action="payroll_summary_report.php" method="post" 
  name="form1" id="form1">
 <?php 
 if (mysqli_num_rows(mysqli_query($con, "select * from bank")) <= 0) {
   echo msg_box("Please add a bank", 'bank.php', 'Continue to Bank');
   exit;
 }
 ?>
 <tr><td>Date</td><td><?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?></td> </tr>
 <tr>
  <td>Branch</td>
  <td>
   <select name="branch_id" onchange="get_location('All');">
   <option value='0'></option>
    <?php
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
    <option value='0'>All</option>
   </select>
  </td>
 </tr>
 <tr>
  <td>Bank</td>
  <td>
  <?php
   echo selectfield(my_query("select * from bank", 'id', 'name'), 
     'bank_id', '');
  ?>
  </td>
 </tr>
 <tr>
  <td>
   <input name="action" type="submit" value="Generate">
   <!--<input name='action' type='submit' value='Cancel'>-->
  </td>
 </tr>
 </form>
 </table>
