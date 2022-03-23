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
require_once "backup_restore.inc";

$con = connect();
if (!(user_type($_SESSION['uid'], 'Administrator', $con)
 || user_type($_SESSION['uid'], 'Accounts', $con))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}
main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);

if (isset($_REQUEST['action']) && 
  (($_REQUEST['action'] == 'Delete') || ($_REQUEST['action'] == 'Process'))) {

  
  //INPUT VALIDATION CODE
  $date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";
  if (empty($date)) {
    echo msg_box('Please enter date', 'process_payroll.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['branch_id'])) {
    echo msg_box('Please choose a Branch', 'process_payroll.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['location_id'])) {
    echo msg_box('Please choose a Location', 'process_payroll.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['employee_id'])) {
    echo msg_box('Please choose an Employee', 'process_payroll.php', 'Back');
    exit;
  }
      
  $emp_no = 0;
  	
  //Avoid doing SQL queries within a loop
  //A common mistake is placing a SQL query inside of a loop. 
  //This results in multiple round trips to the database, and significantly slower scripts. 
  //You can change the loop to build a single SQL query and insert all of your users at once.  

  //Determine which branch to process
  if ($_REQUEST['branch_id'] == '0') { //User chose 'All'
    $branch_sql = "branch_id != 0";
    $branch_name = "All"; 
  } else {
    $branch_sql = "branch_id = {$_REQUEST['branch_id']}";
    $branch_name = get_value("branch","name","id",$_REQUEST['branch_id'], $con);
  } 
	  
  //Determine which location to process  
  if ($_REQUEST['location_id'] == '0')  {  //User chose 'All'
    $location_sql = " id != 0";
	$location_sql2 = " location_id != 0";
    $location_name = "All"; 
  } else {
    $location_sql = " id = {$_REQUEST['location_id']}";
	$location_sql2 = " location_id ={$_REQUEST['location_id']} ";
    $location_name = get_value("location","name","id",$_REQUEST['location_id'], 
       $con);
  }

  echo "<h5>{$_REQUEST['action']}ing Payroll for Date: $date
     Branch: $branch_name Location: $location_name</h5>";
  
  $sql="select * from location where $location_sql and $branch_sql";
  //echo "$sql<br>";
  
    //Get all the Locations and store in an array 
  $location = array();
  $result_l = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row_l = mysqli_fetch_array($result_l))
    $location[$row_l['id']]=$row_l['name'];
	

  //Determine which employee to process 
  if ($_REQUEST['employee_id'] == 0) {
    $employee_sql = " id != 0";
  } else  {
    $employee_sql = " id={$_REQUEST['employee_id']} ";
  }
  
  ////Lets get all the required employee for each of the locations above
  $employee = array();
  foreach($location as $row_l_id => $row_l_name) {
    $sql="SELECT * from employee where $employee_sql and status='Enable' and $branch_sql and location_id=$row_l_id order by id";
    //echo "{$sql}<br>";

	   $result = mysqli_query($con, $sql) or die(mysqli_error($con));
     while($row = mysqli_fetch_array($result)) 
	     $employee[$row['id']] = array("{$row['firstname']} {$row['middlename']} {$row['lastname']}", $row['gl_id']);
  }
  
  $employee_payroll = array();
  foreach($employee as $emp_id => $emp) {
    $temp = array();
    $sql="select id from payroll where employee_id=$emp_id and payroll_date='$date'";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    while($r = mysqli_fetch_array($result))
      $employee_payroll[$emp_id] = $r['id'];
  }
  
  foreach($employee_payroll as $emp_id => $payroll_id) {
    //Delete any payroll previously prepared on this data for this employee
	   //$sql="delete from payroll_di where payroll_id in (select id from payroll where employee_id=$emp_id and payroll_date='$date')";
	   $sql="delete from payroll_di where payroll_id = $payroll_id";
	   mysqli_query($con, $sql) or die(mysqli_error($con));
	  
    $sql="delete from payroll where employee_id=$emp_id and payroll_date='$date'";
	  //echo "$sql<br>";
    mysqli_query($con, $sql) or die(mysqli_error($con));
	
  }
  foreach($employee as $emp_id => $emp) {
  
    if($_REQUEST['action'] == 'Delete') //Nothing to do. 
      echo "Undoing payroll for {$$emp[0]}<br>"; 

    if ($_REQUEST['action'] == 'Process') {
      //$gl = get_value('grade_level','basic_salary', 'id', $employee[$emp_id][1], $con);
      $gl = calculate_paye_basic('BASIC', $emp_id, $con);
      $sql ="insert into payroll(employee_id, payroll_date, basic_salary) values ($emp_id, '$date', '$gl')";
      mysqli_query($con, $sql) or die(mysqli_error($con));
    
      $payroll_id = mysqli_insert_id($con);
      $employee_payroll[$emp_id] = $payroll_id;
      $emp_no = $emp_no + 1;
      echo "Payroll proceessed for $emp_no {$employee[$emp_id][0]} <br>";
    }
  }
  
  $payroll_di_sql = "insert into payroll_di(payroll_id, di, amount) value ";
  foreach($employee_payroll as $emp_id => $payroll_id) {
    $sql="select * from employee_di where employee_id=$emp_id";
    $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  
      while($row3 = mysqli_fetch_array($result3))  {
	    $sql = "select * from di where id={$row3['di']}";
		$result_di = mysqli_query($con, $sql) or die(mysqli_error($con));
		$row_di = mysqli_fetch_array($result_di);
	    if ($row_di['name'] == 'PAYE')
		  $value = calculate_paye_basic('PAYE', $emp_id, $con);
		else if ($row_di['name'] == 'HOUSING')
		  $value = calculate_paye_basic('HOUSING', $emp_id, $con);
		else if ($row_di['name'] == 'TRANSPORT')
		  $value = calculate_paye_basic('TRANSPORT', $emp_id, $con);
		else if ($row_di['name'] == 'UTILITY')
		  $value = calculate_paye_basic('UTILITY', $emp_id, $con);
		else if ($row_di['name'] == 'MEAL')
		  $value = calculate_paye_basic('MEAL', $emp_id, $con);
		else if ($row_di['name'] == 'ENTERTAINMENT')
		  $value = calculate_paye_basic('ENTERTAINMENT', $emp_id, $con);
		else 
		  $value = $row3['amount'];
        $sql = "insert into payroll_di(payroll_id, di, amount) values ($payroll_id, {$row3['di']}, '{$value}')";
		mysqli_query($con, $sql) or die(mysqli_error($con));
	  }
  }
  
  save_temporary_tables($_SESSION['year_id'], $_SESSION['month_id']);
  echo msg_box("Finished processing $emp_no",'process_payroll.php','Continue');
  exit;  
}  
else {
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3>Process Payroll</h3></td>
   </tr>
   <form name='form1' id='form1' action="process_payroll.php" method="post">

   <?php 
   if (mysqli_num_rows(mysqli_query($con, "select * from bank")) <= 0) {
     echo msg_box("Please add a bank", 'bank.php', 'Continue to Bank');
     exit;
   }
   ?>
   <tr><td>Date</td><td><?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?></td></tr>
   <tr>
    <td>Branch</td>
    <td>
     <select name="branch_id" onchange="get_location('All');">
      <option value='0'>All</option>
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
   <select name="location_id" id="location_id" onchange="get_employees('All');">
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
   <tr><td>&nbsp;</td></tr>
   <tr>
    <td>&nbsp;</td>
    <td>
     <input name="action" type="submit" value="Process">
     <!--<input name='action' type='submit' value='Delete'>-->
    </td>
   </tr>
   </form>
  </table>
<?php } ?>
