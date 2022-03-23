<?php
session_start();

/***
 **Please note that the value of PAYE, HOUSING, UTILITY, MEAL, ENTERTAINMENT, TRANSPORT are not determined here
 **as such it is not displayed among the list of deductions
 **and code is implemented to prevent PAYE, HOUSING, UTILITY, MEAL, ENTERTAINMENT, TRANSPORT from being computed.
 **A different computation for PAYE, HOUSING, UTILITY, MEAL, ENTERTAINMENT, TRANSPORT can be found at payroll.inc
 **under calculate_paye(), calculate_paye_basic()
***/
/***
**Also the User is not allowed to Add/Edit/Update/delete PAYE, HOUSING, UTILITY, MEAL, ENTERTAINMENT, TRANSPORT
**/


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
$arr = array('PAYE', 'HOUSING', 'UTILITY', 'MEAL', 'ENTERTAINMENT', 'TRANSPORT');

if (!(user_type($_SESSION['uid'], 'Administrator', $con)
  || (user_type($_SESSION['uid'], 'Records', $con)))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}


if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
  print_header('Staff List', 'staff.php', 'Back to Main Menu', $con);
} else {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
if (isset($_REQUEST['action']) && 
 (($_REQUEST['action'] == 'Update Employee Allowances') || ($_REQUEST['action'] == 'Update Employee Deductions'))) {
  if (empty($_REQUEST['id'])) {
      echo msg_box("Please choose an Employee", 'employee_allowance_deduction.php', 'Back');
       exit;
    }
    $sql = "select * from di where type='{$_REQUEST['type']}'";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result)) {
	  $sql="delete from employee_di where di={$row['id']} and employee_id={$_REQUEST['id']}";
	  mysqli_query($con, $sql) or die(mysqli_error($con));
	  
	  //Only insert into the table do not add amount. This is calculated where payroll is run
	  if (in_array($row['name'], $arr)) {
	    $sql="insert into employee_di(employee_id, di, amount) values ('{$_REQUEST['id']}', '{$row['id']}', '0')";
	  } else if (is_numeric($_REQUEST[$row['id']])) {
	    $sql="insert into employee_di(employee_id, di, amount)
		values('{$_REQUEST['id']}', '{$row['id']}', '{$_REQUEST[$row['id']]}')";
	  }
	  mysqli_query($con, $sql) or die(mysqli_error($con));
	  
    }
    echo msg_box("{$_REQUEST['type']} successfully updated", 
	 "employee_allowance_deduction.php?action=List&id={$_REQUEST['id']}&type={$_REQUEST['type']}", 'Back');
     exit;
 } elseif (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'List')) {
 
   $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
   $menu = array('employee.php' => 'Employee', 
	  'employee_allowance_deduction.php?type=Allowances' => 'Allowances', 
	  'employee_allowance_deduction.php?type=Deductions' => 'Deductions',
	  'employee_payee.php?a=b'=>'Payee');
     
   if($_REQUEST['type'] == 'Allowances')
    tabs($id, $menu, 'Allowances');
   else 
     tabs($id, $menu, 'Deductions');
	 
   $firstname = get_value('employee', 'firstname', 'id', $_REQUEST['id'], $con);
   $lastname = get_value('employee', 'lastname', 'id', $_REQUEST['id'], $con);
   
   echo "
    <table> 
    <tr class='class1'>
     <td colspan='4'><h3>$firstname $lastname - {$_REQUEST['type']} </h3></td>
    </tr>
    <form action='employee_allowance_deduction.php' method='post' name='form1'>
    <tr>
	 <td>
	  <table border='1'>";  
	  
   $sql="select * from di where type='{$_REQUEST['type']}'";
   $result = mysqli_query($con, $sql) or die(mysqli_error($con));
   if (mysqli_num_rows($result) <= 0) {
	   echo msg_box("No {$_REQUEST['type']} have been defined", "di.php?type={$_REQUEST['type']}&action=Add", "Add {$_REQUEST['type']}");
	   exit;
   }
  
   while($row = mysqli_fetch_array($result)) {
     $sql="select * from employee_di where di={$row['id']} and employee_id={$_REQUEST['id']}";
	 $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
	 
	 $row2 = mysqli_fetch_array($result2);
	 echo "
	  <tr>
	   <td>{$row['name']}</td><td><input type='text' name='{$row['id']}'";
	 if (in_array($row['name'], $arr))
       echo "value='"  . number_format(calculate_paye_basic($row['name'], $_REQUEST['id'], $con), 2) . "' readonly='readonly' disabled='disabled'";
	 else 
	   echo "value='{$row2['amount']}'";
	   
	 echo "></td></tr>";
   }
   echo "</table></td></tr>
    <tr><td><input name='id' type='hidden' value='{$_REQUEST['id']}'>
	<input name='type' type='hidden' value='{$_REQUEST['type']}'>
    <input name='action' type='submit' value='Update Employee {$_REQUEST['type']}'>
	</form>
    </td>
   </tr>
  </table>";
  } 
?>
