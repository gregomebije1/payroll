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

$con = connect();
if (!(user_type($_SESSION['uid'], 'Administrator', $con)
 || user_type($_SESSION['uid'], 'Accounts', $con))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}

if(isset($_REQUEST['command']) && ($_REQUEST['command'] =="Print")) {
    print_header('List Of Deductions', 'list_of_deductions.php', 'Back', $con);
} else {
  main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Process')) {
  $date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";
  if (empty($date)) {
    echo msg_box('Please enter dates','list_of_payroll_deductions.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['bank_id'])) {
    echo msg_box('Please choose a Bank', 'list_of_payroll_deductions.php',
     'Back');
    exit;
  }
  if (!isset($_REQUEST['branch_id'])) {
    echo msg_box('Please choose a Branch', 
        'list_of_payroll_deductions.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['location_id'])) {
    echo msg_box('Please choose a Location', 
       'list_of_payroll_deductions.php', 'Back');
    exit;
  }

  $sql="select * from payroll where payroll_date='$date'";
  $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result2) == 0) {
    echo msg_box("Payroll has not been prepared for this date",
       'list_of_deductions.php', 'Back');
    exit;
  }	 

  if (!isset($_REQUEST['command'])) {
    echo "<span style='position:absolute;top:0px;left:100px;'>
        <a style='cursor:hand;' onclick='window.open(\"list_of_deductions.php?action=Process&type={$_REQUEST['type']}&bank_id={$_REQUEST['bank_id']}&year={$_SESSION['year_id']}&month={$_SESSION['month_id']}&branch_id={$_REQUEST['branch_id']}&location_id={$_REQUEST['location_id']}&command=Print\", \"smallwin\", 
	    \"width=1200,height=400,status=yes,resizable=yes,menubar=yes,toolbar=yes,scrollbars=yes\");'>
	    <img src='images/icon_printer.gif'></a>
	   </span>
    ";
  }

  echo "
    <table>
      <tr style='text-align:center;'><td colspan='5'>
        <b> ADVICE OF DEDUCTION FROM SALARY - DETAIL SHEET</b></td></tr>
      <tr><td>&nbsp;</td></tr>
      <tr style='text-align:center;'>
        <td colspan='5'>" . get_value('org_info', 'name', 'id', '1', $con) 
         . "</td></tr>
      <tr style='text-align:center;'><td colspan='5'>" 
         . get_value('org_info', 'address', 'id', '1', $con) . "</td></tr>
       
      <tr style='text-align:center;'><td colspan='5'>BRANCH:"
      . get_value('branch','name','id',$_REQUEST['branch_id'], $con)."</td></tr>
      <tr style='text-align:center;'><td colspan='5'>
        LOCATION: " 
	  . get_value('branch','name','id',$_REQUEST['location_id'], $con)."</td></tr> 

      <tr><td>&nbsp;</td></tr>
      <tr style='text-align:center;'>
        <td colspan='5'>BANK: 
  "; 
  if ($_REQUEST['bank_id'] == '0')
    echo "All";
  else 
    echo get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con);
  echo "
       </td></tr>
       <tr style='text-align:center;'><td><b>
  ";
	  
  //Display the name of the deduction 
  echo strtoupper(get_value('di', 'name', 'id', $_REQUEST['type'], $con));
  echo "  DEDUCTION</b></td></tr>
    <tr style='text-align:center;'><td colspan=6'>
     Pay Period: MONTH ENDING: " . get_month_name($_SESSION['month_id']) 
     . ", {$_SESSION['year_id']} </td></tr>
	 </table>
	 <table border='1'>
	  <tr>
	   <th>Pay Roll No.</th>
	   <th>Month/Year</th>
	   <th>Name</th>
	   <th>Ledger Folio</th>
	   <th>Amount</th>
   </tr>
  ";
  $total = 0;
  
  //DETERMINE WHICH LOCATION TO PROCESS
  if ($_REQUEST['location_id'] == '0') {
    $sql="select * from location where 
	   branch_id={$_REQUEST['branch_id']}";
  } else {
    $sql="select * from location where 
	    id={$_REQUEST['location_id']} and branch_id={$_REQUEST['branch_id']}";
  } 
   
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
  
   
    //GET EMPLOYEES
    while($row = mysqli_fetch_array($result)) {
      //GET PAYROLL DATA FOR EMPLOYEES
      $sql="select * from payroll where employee_id={$row['id']} 
        and payroll_date='$date'";
      $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
      if (mysqli_num_rows($result3) == 0) {
        echo msg_box("Payroll has not been prepared for this date
        for this employee ", 'list_of_deductions.php', 'Back');
        exit;
      } 
      $row3 = mysqli_fetch_array($result3);
      echo "
        <tr>
	     <td>&nbsp;</td>
	     <td>&nbsp;</td>
	     <td>{$row['firstname']} {$row['lastname']}</td>
	     <td>&nbsp;</td> 
      ";
        
       //GET DEDUCTIONS 
       $sql="select * from di where id={$_REQUEST['type']}";
       $result4 = mysqli_query($con, $sql) or die(mysqli_error($con));
       while($row4 = mysqli_fetch_array($result4)) {
 
         //GET PAYROLL DEDUCTIONS FOR THIS EMPLOYEE
        $sql="select amount from payroll_di where payroll_id={$row3['id']} 
           and di={$row4['id']}";
	    $result5 = mysqli_query($con, $sql) or die(mysqli_error($con));
	    $row5 = mysqli_fetch_array($result5);		  
	    $total += $row5['amount'];
	    echo "<td>" . number_format($row5['amount'], 2) . "</td>";
      }   
      echo "</tr>";
    }
  }
  echo "<tr style='text-align:center;'>
    <td colspan='3'><h2>ORIGINAL</h2></td>";
  echo "<td>Carried forward </td>
      <td>=N=" . number_format($total, 2) . "</td></tr>";
  exit;  
  }  
?>
<table> 
 <tr class="class1">
  <td colspan='4'><h3>Deductions</h3></td>
 </tr>
 <form action="list_of_deductions.php" method="post" name="form1" id="form1">
 <tr>
 <?php 
 if (mysqli_num_rows(mysqli_query($con, "select * from bank")) <= 0) {
   echo msg_box("Please add a bank", 'bank.php', 'Continue to Bank');
   exit;
 }
 ?>
 <tr>
  <td>Type</td>
  <td>
  <?php
   $type = my_query("select * from di where type='Deductions' order by id", 
    'id', 'name');
   echo selectfield($type, 'type', '');
  ?>
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
  <tr><td>Date</td><td><?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?></td></tr>
  <tr>
   <td>Branch</td>
   <td>
    <select name="branch_id" onchange="get_location('All');">
     <option value='-1'></option>
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
    </select>
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
