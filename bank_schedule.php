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
ob_start();

if (!(user_type($_SESSION['uid'], 'Administrator', $con)
 || user_type($_SESSION['uid'], 'Accounts', $con))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}

if(isset($_REQUEST['command']) && ($_REQUEST['command'] =="Print")) {
    print_header('Bank Schedule', 'bank_schedule.php', 'Back', $con);
} else {
  main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
 
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
  $date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";
  if (empty($date)) {
    echo msg_box('Please enter dates', 'bank_schedule.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['branch_id'])) {
    echo msg_box('Please choose a Branch', 'bank_schedule.php', 'Back');
    exit;
  }
  if ($_REQUEST['branch_id'] == '0') {
    echo msg_box("Please choose a Branch", "bank_schedule.php", "Back");
	exit;
  }
  if (!isset($_REQUEST['location_id'])) {
    echo msg_box('Please choose a Location', 'bank_schedule.php', 'Back');
    exit;
  }
  if (!isset($_REQUEST['bank_id'])) {
    echo msg_box('Please choose a Bank ', 'bank_schedule.php', 'Back');
    exit;
  }
  $message1 = "Branch:<i>";
  $message1 .= ($_REQUEST['branch_id'] == '0') ? 'All' : get_value('branch', 'name', 'id', $_REQUEST['branch_id'], $con);
  $message1 .= ",</i> Location:<i>";
  $message1 .= ($_REQUEST['location_id'] == '0') ? 'All' : get_value('location', 'name', 'id', $_REQUEST['location_id'], $con);
  $message1 .= "</i>, Bank:<i>";
  $message1 .= ($_REQUEST['bank_id'] == '0') ? 'All' : get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con);
  $message1 .= "</i>";
  
	  
  $sql="select * from payroll where payroll_date='$date'";
  $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result2) == 0) {
    echo msg_box("Payroll has not been prepared for this date", 
     'bank_schedule.php', 'Back');
    exit;
  }	 
  if ($_REQUEST['bank_id'] == 0)
    $bank_name = "Bank";
  else 
    $bank_name = get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con);
  
  echo "
    <table border='1'>
      <caption><h2>$bank_name SCHEDULE</h2></caption>
      <tr style='text-align:center;'><td colspan='3'>" 
       . get_value('org_info', 'name', 'id', '1', $con) . "</td></tr>

      <tr style='text-align:center;'><td colspan='3'>" 
       . get_value('org_info', 'address', 'id', '1', $con) . "</td></tr>

      <tr style='text-align:center;'><td colspan='3'>BANK: ";
  
  
  if ($_REQUEST['bank_id'] == '0') 
    echo "All";
  else 
    echo get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con);
  echo "</td></tr>";

  echo "<tr style='text-align:center;'><td colspan='3'>
    Pay Period: MONTH ENDING: " 
      . get_month_name($_SESSION['month_id']) . ", {$_SESSION['year_id']} </td></tr>

     <tr style='text-align:center;'><td colspan='3'>BRANCH: " 
     . get_value('branch', 'name', 'id', $_REQUEST['branch_id'], $con)
     ."</td></tr>";

  
  if (!isset($_REQUEST['command'])) {
    echo "<span style='position:absolute;top:0px;left:100px;'>
     <a style='cursor:hand;' onclick='window.open(\"bank_schedule.php?action=Generate&bank_id={$_REQUEST['bank_id']}&year={$_SESSION['year_id']}&month={$_SESSION['month_id']}&branch_id={$_REQUEST['branch_id']}&location_id={$_REQUEST['location_id']}&command=Print\", \"smallwin\", 
	    \"width=1200,height=400,status=yes,resizable=yes,menubar=yes,toolbar=yes,scrollbars=yes\");'>
	    <img src='images/icon_printer.gif'></a>
	   </span>
    ";
  }
  /*
  echo "<span style='position:absolute;top:0px;left:120px;'>
     <a style='cursor:hand;' onclick='window.open(\"bank_schedule_excel.php?bank_id={$_REQUEST['bank_id']}&year={$_SESSION['year_id']}&month={$_SESSION['month_id']}&branch_id={$_REQUEST['branch_id']}&location_id={$_REQUEST['location_id']}&command=Print\", \"smallwin\", 
	    \"width=1200,height=400,status=yes,resizable=yes,menubar=yes,toolbar=yes,scrollbars=yes\");'>
	    <img src='images/icxls.gif'></a>
	   </span>";
  */
  echo "
    <tr><td>&nbsp;</td></tr>";
  ob_flush();
  
  $not_prepared_payroll = false;
  $end_message = "<br /><br /><b>As at this date Payroll was not prepared for the following:</b><br />";
  $heading = "
    <tr>
	  <th style='width:2em;'>S/N</th>
      <th>NAMES</th>
      <th>ACCOUNT NUMBERS</th>
      <th>NET SALARIES</th>
    </tr>";

  //Avoid doing SQL queries within a loop
  //A common mistake is placing a SQL query inside of a loop. 
  //This results in multiple round trips to the database, and significantly slower scripts. 
  //You can change the loop to build a single SQL query and insert all of your users at once.
		
  //DETERMINE AND DISPLAY THE LOCATION
  if ($_REQUEST['location_id'] == '0') {
    $sql="select * from location where branch_id={$_REQUEST['branch_id']}";
  } else {
    $sql="select * from location where
        id={$_REQUEST['location_id']} and branch_id={$_REQUEST['branch_id']}";
  }
  
  //Get all the Locations and store in an array 
  $location = array();
  $result_l = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row_l = mysqli_fetch_array($result_l))
    $location[$row_l['id']]=$row_l['name'];
  
  //Lets get all the allowances 
  $di = array();
  $sql="select * from di order by id";
  $result6 = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row6 = mysqli_fetch_array($result6))
    $di[$row6['id']] = array($row6['type'],$row6['name']);
	
  ////Lets get all the required employee
  $employee = array();
  foreach($location as $row_l_id => $row_l_name) {
    
    //DETERMINE WHICH EMPLOYEES TO PROCESS
    if ($_REQUEST['bank_id'] == '0') { 
	  $sql="SELECT * from employee where status='Enable' and branch_id= 
	  {$_REQUEST['branch_id']} and location_id={$row_l_id} order by id";
    } else  {
	  $sql="SELECT * from employee where bank_id={$_REQUEST['bank_id']} 
	   and status='Enable' and branch_id={$_REQUEST['branch_id']}
	   and location_id={$row_l_id} order by id";
    }
	//LETS GET ALL EMPLOYEES AND STORE IN AN ARRAY??
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    while($row = mysqli_fetch_array($result)) 
	  $employee[$row['id']] = array($row_l_id, "{$row['firstname']} {$row['middlename']} {$row['lastname']}", "{$row['bank_account_number']}");
  }
  
  //Check if any employee was found
  if (count($employee) == 0) {
    echo msg_box("No employee found for $message1", "bank_schedule.php", "Continue");
	exit;
  }
     
  //****Get all the payroll for this employee
  $payroll = array();
  foreach($employee as $employee_id => $employee_value) {
    //GET PAYROLL DATA FOR EMPLOYEES
    $sql="select * from payroll where employee_id=$employee_id and payroll_date = '$date'";
	
    $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
	if (mysqli_num_rows($result3) == 0) {
	    $not_prepared_payroll = true;
        $end_message .= $employee_value[1] .  "<br>";
    }
	
	while($row3 = mysqli_fetch_array($result3))
	  $payroll[$row3['id']] = array($employee_id, $row3['basic_salary']);
  }
  //Check if any payroll record was found
  if (count($payroll) == 0) {
    echo msg_box("No Payroll record found $mesage1 ", "bank_schedule.php", "Continue");
	exit;
  }
  
  $payroll_di = array();
  $sql="select * from payroll_di where 
               payroll_id in (". implode(",", array_keys($payroll)) . ") and di in (" . implode(",", array_keys($di)) . ")";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row = mysqli_fetch_array($result)) {
    $key = "{$row['payroll_id']}_{$row['di']}";
	$payroll_di[$key] = $row['amount'];
  }
  
  $total = 0;	
  $sn = 1; //Serial Number
  $count = 1; //Count after initial 15 rows
  echo $heading;
  ob_flush();
  
  
  foreach($location as $loc_id => $loc_name) {
	  echo "<tr><td colspan='3'><b>$loc_name</b></td></tr>";
	  $branch_total = 0;
	  foreach($employee as $emp_id => $emp_value) {
	    if ($emp_value[0] == $loc_id) {
	      foreach ($payroll as $pay_id => $pay_value) {
	        if ($pay_value[0] == $emp_id) {
			  if ($sn == 17) {
				echo "<tr>
						<th style='width:2em;'>&nbsp;</th>
						 <th>TOTAL</th>
						 <th>&nbsp;</th><th><b>"; 
				echo number_format($total,2) . "</b></th></tr>	
				 <tr style='page-break-after:always;'><td>&nbsp;</td></tr>";
				 
				echo $heading;
					
				//B/F
				
				echo "
				 <tr style='border:black solid 1px;'>
				  <td style='width:2em;'>&nbsp;</td>
				  <td>Balance B/F</td>
				  <td>&nbsp;</td>
				  <td><b>";
				echo number_format($total,2) . "</b></td></tr><tr><td>&nbsp;</td></tr>";
				$count -= 17;  //Restart the counter
					  
			  } else if (($count % 20) == 0){
				echo "<tr>
					   <th style='width:2em;'>&nbsp;</th>
					   <th>TOTAL</th>
					   <th>&nbsp;</th>
					   <th><b>"; 
					   
				echo number_format($total,2) . "</b></th></tr>
				 <tr style='page-break-after:always;'><td>&nbsp;</td></tr>";
				 
				echo $heading;
					  
				//B/F
				echo "
				  <tr style='border:black solid 1px;'>
					  <td style='width:2em;'>&nbsp;</td>
					  <td>BALANCE B/F</td>
					  <td>&nbsp;</td>
					  <td><b>";
				echo number_format($total,2) . "</b></td></tr>
				<tr><td>&nbsp;</td></tr>";
					  
			  }
			  
		 
			  echo "
			    <tr style='border:1px solid black;'>
				 <th style='width:2em;'>$sn</th>
			     <td>{$emp_value[1]}</td>
				 <td>{$emp_value[2]}</td>
			  ";
			  
			  ++$sn;
			  ++$count;
	
			  $deductions = 0;
			  $earnings = 0;
		 
			  ob_flush();
	
			  $earnings += $pay_value[1];
			  foreach($di as $di_id => $di_value) 
		        if ($di_value[0] == 'Allowances') {
				  $key = "{$pay_id}_{$di_id}";
				  
				  $value = 0;
				  if(array_key_exists($key, $payroll_di))
				    $value = $payroll_di[$key];
				  
			      $earnings += $value;
				}
			 
			  foreach($di as $di_id => $di_value) 
			    if ($di_value[0] == 'Deductions') {
				  $key = "{$pay_id}_{$di_id}";
				  
				  $value = 0;
				  if(array_key_exists($key, $payroll_di))
				    $value = $payroll_di[$key];
			      $deductions += $value;
				}
	
                $total += ($earnings - $deductions);
                $branch_total += ($earnings - $deductions);
                echo "<td>" . number_format($earnings - $deductions, 2) . "</td></tr>";
             }
		  }
		}
	  }
      echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><th><b>" 
       . number_format($branch_total, 2) . "</b></th></tr>";
      echo "<tr><td>&nbsp;</td></tr>";
    }
    echo "<tr><td>&nbsp;</td><td><h3>TOTAL</h3></td><td>&nbsp;</td><th><h3>" 
     . number_format($total, 2) . "</h3></th></tr>";
	 
    echo "
     <tr><td>&nbsp;</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
	    <td colspan='4'>
		 <table>
		  <tr>
    ";
	if (isset($_POST['footer_text']))
	  echo "<td style='text-align:center;'>" . nl2br($_POST['footer_text']) . "</td>";
	  
	echo "</tr>
	    </table>
	   </td>
	  </tr>";
	echo "<tr><td colspan='2'>";
    
	echo ($not_prepared_payroll == false) ? "" : $end_message;
	echo "</td></tr></table>";
	exit;  
   
} 
?>
<table border='1'> 
 <tr class="class1">
  <td colspan="4" style='text-align:center;'><h3>BANK SCHEDULE</h3></td>
 </tr>
 <form action="bank_schedule.php" method="post" name="form1" id="form1">
 <tr>
  <td valign='top' style='width:40em;'>
   <table>
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
	     <option value='-1'>&nbsp;</option>
	    </select>
	   </td>
      </tr>
      <tr>
       <td>Bank</td>
       <td>
	   <?php
	   echo selectfield(
	    array('0'=>'All') + my_query("select * from bank", 'id', 'name'), 'bank_id', '');
	   ?>
       </td>
      </tr>
	  </table>
	 </td>
	 <td style='text-align:right;'>
	  <table>
	   <tr>
	   <td>Text at footer:</td>
	   <td><textarea rows='5' cols='30' name='footer_text'></textarea></td></tr>
	  </table>
	 </td>
	</tr>
    <tr>
     <td colspan='2' style='text-align:center;'>
      <input name="action" type="submit" value="Generate">
      <!--<input name='action' type='submit' value='Cancel'>-->
     </td>
    </tr>
   </form>
   </table>
  <?php // } ?>
