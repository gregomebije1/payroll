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
    print_header('Payroll History', 'personal_er.php', 'Back', $con);
} else {
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
    print_header('Payroll History', 'personal_er.php', 'Back', $con);
  } else {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  }  

  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
    $sdate = $_REQUEST['start_date'];
    $edate = $_REQUEST['end_date'];
  
    if (empty($sdate) || empty($edate)) {
      echo msg_box('Please enter correct start and end date', 
       'payroll_history.php', 'Back');
      exit;
    }
    if (!isset($_REQUEST['branch_id'])) {
      echo msg_box('Please choose a Branch', 'payroll_history.php', 'Back');
      exit;
    }
	if ($_REQUEST['branch_id'] == '0') {
	  echo msg_box('Please choose a Branch', 'payroll_history.php', 'Back');
      exit;
    }
    if (!isset($_REQUEST['location_id'])) {
      echo msg_box('Please choose a Location', 'payroll_history.php', 'Back');
      exit;
    }
    if (!isset($_REQUEST['employee_id'])) {
      echo msg_box('Please choose an Employee', 'payroll_history.php', 'Back');
      exit;
    }
    $sql="select * from payroll where payroll_date between 
      '$sdate' and '$edate'";
    $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
    if (mysqli_num_rows($result2) <= 0) {
      echo msg_box("Payroll has not been prepared for this dates 
       $sdate and $edate", 'payroll_history.php', 'Back');
      exit;
    }

    //DETERMINE WHICH LOCATION TO PROCESS
    if ($_REQUEST['location_id'] == '0')  {  //User chose 'All'
      $location_sql = "location_id != 0";
      $location_name = "All";
    } else {
      $location_sql = "location_id = {$_REQUEST['location_id']}";
      $location_name = get_value("location","name","id",
      $_REQUEST['location_id'], $con);
    }

    //DETERMINE WHICH EMPLOYEE TO PROCESS
    if ($_REQUEST['employee_id'] == 0) {
	  
      $sql="select * from employee where status='Enable'
       and branch_id={$_REQUEST['branch_id']} and $location_sql order by id";
    } else  {
      $sql="select * from employee where id={$_REQUEST['employee_id']}
       and status='Enable' and branch_id={$_REQUEST['branch_id']}
       and $location_sql order by id";
	 }

    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    if (mysqli_num_rows($result) == 0) {
      msg_box('Employee does not exist', 'payslip.php', 'Back');
      exit;
    }
	echo "<a href='payroll_history.php'><h3>Back</h3></a><br />";
       
    //PROCESS EMPLOYEE
    while($row = mysqli_fetch_array($result)) {

      //CHECK IF PAYROLL HAS BEEN PROCESSED FOR THIS EMPLOYEE 
      //FOR THIS DATE
      $sql="select * from payroll where employee_id={$row['id']} 
        and payroll_date between '$sdate' and '$edate' order by payroll_date";
      $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));

      if (mysqli_num_rows($result3) == 0) {
         echo msg_box("Payroll has not been prepared for this date
          for this Employee", 'payroll_history.php', 'Back');
        exit;
      }

      echo "
       <table>
        <caption><h2>PAYROLL HISTORY</h2></caption>
        <tr><td style='text-align:center;'>"
         .get_value('org_info', 'name', 'id', '1', $con) ."</td></tr>
	<tr><td style='text-align:center;'>"
          .get_value('org_info','address','id','1',$con) . "</td></tr>
	<tr><td style='text-align:center;'>
           PERIOD: BETWEEN $sdate AND $edate</td></tr>
	<tr>
        <tr style='text-align:center;'><td>BRANCH: "
            . get_value('branch','name','id',$_REQUEST['branch_id'], $con)
            ."</td></tr>

         <tr style='text-align:center;'><td>LOCATION: $location_name 
           </td></tr>

	 <td style='text-align:center;'>
	  <table border='0'width='20%'>
	   <tr>
	    <td><b>Name:</b> 
             {$row['firstname']} {$row['middlename']} {$row['lastname']}</td>
	    <td><b>Department:</b> "
             . get_value('department','name','id',$row['department_id'],$con)
             . "</td>
           </tr>
           <tr>
	     <td><b>Bank:</b> " 
               . get_value('bank', 'name', 'id', $row['bank_id'], $con) . "</td>
	     <td><b>Account No:</b> {$row['bank_account_number']}</td>
	   </tr>
	  </table>
	 </td>
	</tr>
	<tr>
	 <td>		
	  <table style='font-size:0.8em; text-align:center;'>
      ";
      //Calculate the number of deductions
      $sql="select count(*) as 'count' from di where 
       type='Deductions' order by id";
      $resultd = mysqli_query($con, $sql) or die(mysqli_error($con));
      $rowd = mysqli_fetch_array($resultd);
      $no_of_deductions = $rowd['count'];
	  
	  
      //Calculate the number of allowances
      $sql="select count(*) as 'count' from di where 
        type='Allowances' order by id";
      $resulta = mysqli_query($con, $sql) or die(mysqli_error($con));
      $rowa = mysqli_fetch_array($resulta);
      $no_of_allowances = $rowa['count'];
      echo "
       <tr>
	<th>MONTH</th>
	<th style='background:#ddf;'>BASIC SALARY</th>
      <th style='text-align:center;' colspan='$no_of_allowances'>ALLOWANCES</th>
	<th style='background: #ddf;'>GROSS SALARY</th>
      <th style='text-align:center;' colspan='$no_of_deductions'>DEDUCTIONS</th>
	<th style='background:#ddf;'>TOTAL NET SALARY</th>
       </tr>
       <tr>
	<th>&nbsp;</th>
        <th style='background:#ddf;'>&nbsp;</th>
      ";
      $sql="select * from di where type='Allowances' order by id";
      $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
      while($row2 = mysqli_fetch_array($result2)) 
        echo "<th>" . strtoupper($row2['name']) . "</th>";
      echo "  <th style='background:#ddf;'>&nbsp;</th>"; //Gross salary

      $sql="select * from di where type='Deductions' order by id";
      $result1 = mysqli_query($con, $sql) or die(mysqli_error($con));
      while($row1 = mysqli_fetch_array($result1)) 
        echo "  <th>" . strtoupper($row1['name']) . "</th>";
      echo "</tr>";
	  
      //PROCESS PAYROLL
      while($row3 = mysqli_fetch_array($result3)) {
        $allowances = 0;
        $deductions = 0;
        echo "
	  <tr style='font-size:0.8em; text-align:center; 
           border:1px solid black;'>
	   <td style='border:1px solid black;'>{$row3['payroll_date']}</td>";
	echo "<td style='background:#ddf;'>" 
         . number_format($row3['basic_salary'], 2) . "</td>";
		
	//The Allowances
	$sql="select * from di where type='Allowances' order by id";
	$result6 = mysqli_query($con, $sql) or die(mysqli_error($con));
	while($row6 = mysqli_fetch_array($result6)) {
	  $sql="select amount from payroll_di where payroll_id={$row3['id']} 
	   and di={$row6['id']}";
	  $result7 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  $row7 = mysqli_fetch_array($result7);
		  
	  $allowances += $row7['amount'];
	  echo "  <td style='border:1px solid black;'>" 
           . number_format($row7['amount'], 2) . "</td>";
	}

        //Gross Salary
        $gross_salary = $row3['basic_salary'] + $allowances;
	echo "<td style='background:#dff; border: 1px solid black;'>" 
          . number_format($gross_salary, 2) . "</td>";
		
        //The Deductions
	$sql="select * from di where type='Deductions' order by id";
	$result4 = mysqli_query($con, $sql) or die(mysqli_error($con));
	while($row4 = mysqli_fetch_array($result4)) {
	  $sql="select amount, di from payroll_di where payroll_id={$row3['id']} and di={$row4['id']}";
	  $result5 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  $row5 = mysqli_fetch_array($result5);
	 
	  $value = $row5['amount'];
	  $deductions += $value;
	  echo "  <td style='border: 1px solid black;'>" . number_format($value, 2) . "</td>";
	}
		
	//Net Pay
	$net_pay = $gross_salary - $deductions;
	echo "    <td style='border: 1px solid black;'>" 
         . number_format($net_pay, 2) . "</td>";
	echo "</tr>";
      }
    }
    echo "</table>
      </td>
     </tr>    
    ";
    exit;  
  } 
}  
?>
<table> 
 <tr class="class1">
  <td colspan="4"><h3>Payroll History</h3></td>
 </tr>
 <form name="form1" action="payroll_history.php" method="post">
 <tr>
  <td>Start Date</td>
  <td><input type="text" name="start_date" value="<?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?>" /></td>
 </tr>
 <tr>
  <td>End Date</td>
  <td><input type="text" name="end_date" value="<?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?>" /></td>
 </tr>
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
    <select name="location_id" id="location_id"
      onchange="get_employees('0');">
     <option value='0'>All</option>
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
