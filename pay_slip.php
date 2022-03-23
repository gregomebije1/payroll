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
if(isset($_REQUEST['command']) && ($_REQUEST['command'] =="Print")) {
  print_header('Payment Slip', 'pay_slip.php', 'Back', $con);
} else {
  main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
  
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
  $date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";
  if (empty($date)) {
    echo msg_box('Please enter date', 'pay_slip.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['branch_id'])) {
    echo msg_box('Please choose a Branch', 'pay_slip.php', 'Back');
    exit;
  }
  if ($_REQUEST['branch_id'] == '0') {
    echo msg_box("Please choose a Branch", 'pay_slip.php', 'Back');
	exit;
  }
  if (!isset($_REQUEST['location_id'])) {
    echo msg_box('Please choose a Location', 'pay_slip.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['employee_id'])) {
    echo msg_box('Please choose an Employee', 'pay_slip.php', 'Back');
    exit;
  }
  $sql="select * from payroll where payroll_date='$date'"; 
  $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result2) <= 0) {
    echo msg_box("Payroll has not been prepared for this date", 
     'pay_slip.php', 'Back');
    exit;
  } 
  
  //DETERMINE WHICH LOCATION TO PROCESS
  if ($_REQUEST['location_id'] == '0')  {  //User chose 'All'
    $location_sql = "location_id != 0";
    $location_name = "All";
  } else {
    $location_sql = "location_id = {$_REQUEST['location_id']}";
    $location_name = get_value("location","name","id", $_REQUEST['location_id'], $con);
  }

  //DETERMINE WHICH EMPLOYEE TO PROCESS
  if ($_REQUEST['employee_id'] == 0) {
    //Processes all the employee
    $sql="select * from employee where status='Enable'
       and branch_id={$_REQUEST['branch_id']} and $location_sql order by id";
  } else  {
    //Process one employee
    $sql="select * from employee where id={$_REQUEST['employee_id']}
       and status='Enable' and branch_id={$_REQUEST['branch_id']} 
       and $location_sql order by id";
  }
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result) == 0) {
    msg_box('Employee does not exist', 'pay_slip.php', 'Back');
    exit;
  }
  $font_size = '0.9em';
  
  if(isset($_REQUEST['command']) && ($_REQUEST['command'] =="Print"))
    $font_size='0.7em';
  
  //PROCESS EMPLOYEE
  while($row = mysqli_fetch_array($result)) {

    //PROCESS PAYROLL OF EMPLOYEE
    echo "<table style='font-size: $font_size;' text-align:center;'>";
    $sql="select * from payroll where employee_id={$row['id']} 
      and payroll_date='$date'";
    $resultx = mysqli_query($con, $sql) or die(mysqli_error($con));
    if (mysqli_num_rows($resultx) <= 0) {
      echo msg_box("Payroll has not been prepared for 
        {$row['firstname']} {$row['lastname']}", 
        'pay_slip.php', 'Back');
      continue;
    }
    echo "
      <tr style='text-align:center;'><td style='font-weight:bold;'>Pay Slip</td></tr>";
    if (!isset($_REQUEST['command'])) {
      echo "<span style='position:absolute;top:0px;left:100px;'>
        <a style='cursor:hand;' onclick='window.open(\"pay_slip.php?action=Generate&employee_id={$_REQUEST['employee_id']}&year={$_SESSION['year_id']}&month={$_SESSION['month_id']}&branch_id={$_REQUEST['branch_id']}&location_id={$_REQUEST['location_id']}&command=Print\", \"smallwin\", 
	    \"width=1200,height=400,status=yes,resizable=yes,menubar=yes,toolbar=yes,scrollbars=yes\");'>
	    <img src='images/icon_printer.gif'></a>
	   </span>
	   ";
    }
    echo "
	 <tr>
        <tr style='text-align:center;'><td>" 
      . get_value('org_info', 'name', 'id', '1', $con) . "</td></tr>
      <tr style='text-align:center;'><td>BRANCH:";
	  
    echo ($_REQUEST['branch_id'] == '0') ? '' : get_value('branch','name','id',$_REQUEST['branch_id'], $con);
	
	echo "</td></tr>
      <tr style='text-align:center;'><td>LOCATION: $location_name </td></tr>

      <tr style='text-align:center;'><td>PAY PERIOD: " 
      . get_month_name($_SESSION['month_id']) . ", {$_SESSION['year_id']} </td></tr>
      <tr><td>&nbsp;</td></tr>
	 
	</tr>
      <tr>
       <td>
        <table style='font-size:$font_size;'>
         <tr>
	  <td>Name:</td><td>{$row['firstname']} {$row['lastname']}</td>
          <td>Department:</td><td>" 
           . get_value('department', 'name','id',$row['department_id'], $con) 
           . "</td>
         </tr>
         <tr>
          <td>Bank</td><td>" 
          . get_value('bank', 'name', 'id', $row['bank_id'], $con). "</td>
          <td>Account No:</td><td>{$row['bank_account_number']}</td>
         </tr>
        </table>
       </td>
      </tr>
      <tr>
       <td>
	<table style='font-size:$font_size;'>
	 <tr>
	  <th>Pay Item</th>
	  <th>Earning</th>
	  <th>Deduction</th>
	 </tr>
    ";
    //PROCESS PAYROLL DATA
    $sql="select * from payroll where employee_id={$row['id']} 
     and payroll_date ='$date'";
    $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
    $row3 = mysqli_fetch_array($result3);
    $earnings = 0;
    $deductions = 0;

    echo "
     <tr>
      <td>SALARY</td>
      <td>
    ";
    $earnings += $row3['basic_salary'];

    //DISPLAY BASIC SALARY
    echo number_format($row3['basic_salary'], 2);
    echo "</td>
      <td>&nbsp;</td>
      </tr>
    ";
 
    $sql="select * from payroll_di join di on payroll_di.di = di.id
      where payroll_di.payroll_id={$row3['id']}";
    $result4 = mysqli_query($con, $sql) or die(mysqli_error($con));
    while($row4 = mysqli_fetch_array($result4)) {
      echo "<tr><td>{$row4['name']}</td>";
      if ($row4['type'] == 'Deductions') { 
	    $value = $row4['amount'];
		  
        //CALCULATE AND DISPLAY DEDUCTIONS 
        $deductions += $value;
        echo "
          <td>&nbsp;</td>
	      <td>" . number_format($value, 2) . "</td>";
      } else {

        //CALCULATE AND DISPLAY ALLOWANCES
        $earnings += $row4['amount'];
        echo "
          <td>" . number_format($row4['amount'], 2) . "</td>
	  <td>&nbsp;</td>
        ";
      }
    }
    echo"  <tr><td>&nbsp;</td></tr>
     <tr>
      <th>Total</th>
      <th>" . number_format($earnings, 2) . "</th>
      <th>" . number_format($deductions, 2) . "</th>
     </tr>
     <tr>
      <th>Net Pay</th>
      <th>" . number_format($earnings - $deductions, 2) . "</th>
      <td>&nbsp;</td>
     </tr>
     </table>
     </td>
     </tr>
     </table><br /><br />";
  } 
}  else {
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3>Pay Slip</h3></td>
   </tr>
   <form name='form1' action="pay_slip.php" method="post">
   <tr><td>Date</td><td><?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?></td></tr>
   <tr>
    <td>Branch</td>
    <td>
     <select name="branch_id" onchange="get_location('0');">
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
     <select name="location_id" id="location_id" 
       onchange="get_employees('All');">
     </select>
    </td>
   </tr>
   <tr>
    <td>Employee</td>
    <td>
     <select name="employee_id" id="employee_id">
      <option value='0'>All</option>
     </select>
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
  <?php }  ?>
