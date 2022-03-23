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
    print_header('Payroll Report', 'payroll_report.php', 'Back', $con);
} else {
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
    print_header('Payroll Report','payroll_report.php', 'Back', $con);
  } else {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  }  

  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
    $date = "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01";
    if (empty($date)) {
      echo msg_box('Please enter correct start and end date',
        'payroll_report.php', 'Back');
      exit;
    }
    if (!isset($_REQUEST['branch_id'])) {
	  echo msg_box('Please choose a Branch', 'payroll_report', 'Back');
      exit;
    }
    if (!isset($_REQUEST['location_id'])) {
	  echo msg_box('Please choose a Location', 'payroll_report.php', 'Back');
      exit;
    }
    if (!isset($_REQUEST['bank_id'])) {
	  echo msg_box('Please choose a Bank', 'payroll_report.php', 'Back');
      exit;
    }
    if ($_REQUEST['branch_id'] == 0) {
	  echo msg_box('Please select a Brach', 'payroll_report.php', 'Back');
	  exit;
	}
	
	$message1 = "Branch:<i>";
    $message1 .= ($_REQUEST['branch_id'] == '0') ? 'All' : get_value('branch', 'name', 'id', $_REQUEST['branch_id'], $con);
    $message1 .= ",</i> Location:<i>";
    $message1 .= ($_REQUEST['location_id'] == '0') ? 'All' : get_value('location', 'name', 'id', $_REQUEST['location_id'], $con);
    $message1 .= "</i>, Bank:<i>";
    $message1 .= ($_REQUEST['bank_id'] == '0') ? 'All' : get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con);
    $message1 .= "</i>";
  
    $sql="select * from payroll where payroll_date = '$date'";
    $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
    if (mysqli_num_rows($result2) <= 0) {
      echo msg_box("Payroll has not been prepared for this date $date", 
        'payroll_report.php', 'Back');
	  //echo "I am here<br>";
      exit;
    }
	
    ?>
     <a href='payroll_report.php'><h3>Back</h3></a>
      <!--<span style='position:absolute;top:100px;left:800px;'>
      <a style='cursor:hand;' onclick='window.open(\"payroll_report_excel.php?&bank_id=<?php echo $_REQUEST['bank_id']?>&year=<?php echo $_SESSION['year_id']?>&month=<?php echo $_SESSION['month_id']?>&branch_id=<?php echo $_REQUEST['branch_id']?>&location_id=<?php echo $_REQUEST['location_id']?>\", \"smallwin\", 
	    \"width=1200,height=400,status=yes,resizable=yes,menubar=yes,toolbar=yes,scrollbars=yes\");'>
	    <img src='images/icxls.gif'></a>
	   </span>
	   -->
	   <a target='_blank' href='report/download-xls.php?date=<?=$date?>&bank_id=<?php echo $_REQUEST['bank_id']?>&year=<?php echo $_SESSION['year_id']?>&month=<?php echo $_SESSION['month_id']?>&branch_id=<?php echo $_REQUEST['branch_id']?>&location_id=<?php echo $_REQUEST['location_id']?>'><h3>Download</h3></a>
	 <?php
	echo "
      <table>
       <caption><h2>Payroll Report</h2></caption>
        <tr><th style='text-align:center;'>" 
         . get_value('org_info', 'name', 'id', '1', $con) . "</th></tr>

	<tr><th style='text-align:center;'>" 
         . get_value('org_info', 'address', 'id', '1', $con) . "</th></tr>

	 <tr><th style='text-align:center;'>MONTH ENDING: " 
         . get_month_name($_SESSION['month_id']).", {$_SESSION['year_id']} </th></tr>

	 <tr><th style='text-align:center;'>BRANCH: " 
         . get_value('branch', 'name', 'id', $_REQUEST['branch_id'], $con)
         . "</th></tr>";
		 
	
	echo "<tr><th style='text-align:center;'>LOCATION: ";
	echo ($_REQUEST['location_id'] == '0') ? "All" : get_value('location', 'name', 'id', $_REQUEST['location_id'], $con);
    echo "</th></tr>";
	
	echo "<tr><th style='text-align:center;'>BANK: ";
	 	
    if($_REQUEST['bank_id'] == '0')
      echo "All";
    else {
      echo get_value('bank','name','id', $_REQUEST['bank_id'], $con)
       . "</th></tr>";
    }
    
	echo "
	  <hr>
	  <tr>
	   <td>		
	    <table style='font-size:0.8em;' text-align:center;'>
    ";
	
    //Calculate the number of deductions
    $sql="select count(*) as 'count' from di where 
      type='Deductions' order by id";
    $resultd = mysqli_query($con, $sql) or die(mysqli_error($con));
    $rowd = mysqli_fetch_array($resultd);
    $no_of_deductions = $rowd['count'];
	
	  
    //Calculate the number of allowances
    $sql="select count(*) as 'count' from di 
      where type='Allowances' order by id";
    $resulta = mysqli_query($con, $sql) or die(mysqli_error($con));
    $rowa = mysqli_fetch_array($resulta);
    $no_of_allowances = $rowa['count'];

	$not_prepared_payroll = false;
	$end_message = "<br /><br /><b>As at this date Payroll was not prepared for the following:</b><br />";
	$heading =  "
	    
         <tr>
	      <th>S/N</th>
          <th>NAMES</th>
          <th style='background-color:#ddf;'>BASIC SALARY</th>
          <th style='text-align:center;' colspan='$no_of_allowances'>ALLOWANCES</th>
          <th style='background-color: #ddf;'>GROSS SALARY</th>
          <th style='text-align:center;' colspan='$no_of_deductions'>DEDUCTIONS</th>
          <th style='background-color: #ddf;'>TOTAL NET SALARY</th>
         </tr>
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
	  
	  $heading .= "<th>". strtoupper($row2['name'])."</th>";
	}
	$heading .= "<th style='background-color:#ddf;'>&nbsp;</th>"; //Gross Salary
	$total['gross_salary'] = 0;
	
	//List all the deductions
	$sql="select * from di where type='Deductions' order by id";
	$result1 = mysqli_query($con, $sql) or die(mysqli_error($con));
	while($row1 = mysqli_fetch_array($result1)) {
	  $name = "{$row1['id']}_Deductions";
	  $total[$name] = 0;
	  $heading .= "<th>" . strtoupper($row1['name']) . "</th>";
	}

	$heading .= "<th style='background-color:#ddf;'>&nbsp;</th></tr>"; //Net Salary
	$total['net_pay'] = 0;
	
	
	//Avoid doing SQL queries within a loop
    //A common mistake is placing a SQL query inside of a loop. 
	//This results in multiple round trips to the database, and significantly slower scripts. 
	//You can change the loop to build a single SQL query and insert all of your users at once.
		
	//DETERMINE AND DISPLAY THE LOCATION
    if ($_REQUEST['location_id'] == '0') {
	  $sql="select * from location where 
	   branch_id={$_REQUEST['branch_id']}";
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
	  $t = $no_of_allowances + $no_of_deductions + 3;
  
	  //DETERMINE WHICH EMPLOYEES TO PROCESS
	  if ($_REQUEST['bank_id'] == '0') { 
	    $sql="SELECT * from employee where status='Enable' and branch_id= 
	    {$_REQUEST['branch_id']} and location_id={$row_l_id} order by id";
	  } else  {
	    $sql="SELECT * from employee where bank_id={$_REQUEST['bank_id']} 
	     and status='Enable' and branch_id={$_REQUEST['branch_id']}
	     and location_id={$row_l_id} order by id";
	  }
      //GET THE EMPLOYEES
      $result = mysqli_query($con, $sql) or die(mysqli_error($con));
	  
	  //LETS GET ALL EMPLOYEES AND STORE IN AN ARRAY??
      while($row = mysqli_fetch_array($result)) 
	    $employee[$row['id']] = array($row_l_id, "{$row['firstname']} {$row['middlename']} {$row['lastname']}");
	}
	//Check if any employee was found
    if (count($employee) == 0) {
      echo msg_box("No employee found for $message1", "payroll_report.php", "Continue");
	  exit;
    }
  
	
	//****Get all the payroll for this employee
	$payroll = array();
	foreach($employee as $employee_id => $employee_value) {
	  //GET PAYROLL DATA FOR EMPLOYEES
      $sql="select * from payroll where employee_id=$employee_id and payroll_date = '$date'";
	  //echo "$sql<br>";
	  
      $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
	  
      if (mysqli_num_rows($result3) == 0) {
	    $not_prepared_payroll = true;
        $end_message .= $employee_value[1] .  "<br>";
      }
	  while($row3 = mysqli_fetch_array($result3))
	    $payroll[$row3['id']] = array($employee_id, $row3['basic_salary']);
	}
	
	if (count($payroll) == 0) {
      echo msg_box("No payroll record found for $message1", "payroll_report.php", "Continue");
	  exit;
    }
  
	
	$sn = 1; //Serial Number
	$count = 1;
	echo $heading;  //Display heading
	
	$payroll_di = array();
	$sql="select * from payroll_di where 
                payroll_id in (". implode(",", array_keys($payroll)) . ") and di in (" . implode(",", array_keys($di)) . ")";
	$result = mysqli_query($con, $sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result)) {
	  $key = "{$row['payroll_id']}_{$row['di']}";
	  $payroll_di[$key] = $row['amount'];
	}
	
	foreach($location as $loc_id => $loc_name) {
	  echo "<tr><td colspan='$t'><b>$loc_name</b></td></tr>";
	  $count++;
	  foreach($employee as $emp_id => $emp_value) {
	    if ($emp_value[0] == $loc_id) {
	      foreach ($payroll as $pay_id => $pay_value) {
	        if ($pay_value[0] == $emp_id) {
                    $deductions = 0;
                    $allowances = 0;
			  
                    if ($sn == 23) {
			echo "<tr style='text-align:center;'>
		         <td style='border:1px solid black'>&nbsp;</td>
                         <td style='border:1px solid black'>TOTAL</td>";
				 
                        foreach($total as $name => $value) {
                        if (($name == 'basic_salary') || ($name == 'gross_salary')
                            || ($name == 'net_pay')) {
                            echo "<td style=' border:1px solid black;background-color:#dff;'>"; 
                        } else 
                            echo "<td style='border:1px solid black;'><b>"; 
                            echo number_format($value,2) . "</b></td>";
                        }
			echo "</tr>";
			
			echo "<tr style='page-break-after:always; orphans:2; widows:2'><td>&nbsp;</td></tr>";
			echo $heading;
			
			//B/F
			echo "<tr style='text-align:center;'>
                            <td style='border:1px solid black'>&nbsp;</td>
                            <td style='border:1px solid black'>Balance B/F</td>";
			
                        foreach($total as $name => $value) {
                            if (($name == 'basic_salary') || ($name == 'gross_salary')
                                || ($name == 'net_pay')) {
                                echo "<td style=' border:1px solid black;background-color:#dff;'>"; 
                            } else 
                                echo "<td style='border:1px solid black;'><b>"; 
                                echo number_format($value,2) . "</b></td>";
                        }
			echo "</tr>";
			$count = 0;  //Restart the counter
                    }
			  
                    if (($sn > 23) && ($count >= 40)){
			echo "<tr style='text-align:center;'>
		         <td style='border:1px solid black'>&nbsp;</td>
                        <td style='border:1px solid black'>TOTAL</td>";
				 
			foreach($total as $name => $value) {
                            if (($name == 'basic_salary') || ($name == 'gross_salary')
                                || ($name == 'net_pay')) {
                                echo "<td style=' border:1px solid black;background-color:#dff;'>"; 
                            } else 
                                echo "<td style='border:1px solid black;'><b>"; 
                            echo number_format($value,2) . "</b></td>";
                        }
			echo "</tr>";
			echo "<tr style='page-break-after:always;'><td>&nbsp;</td></tr>";
			echo $heading;
			  
			  //B/F
			echo "<tr style='text-align:center;'>
                            <td style='border:1px solid black'>&nbsp;</td>
                            <td style='border:1px solid black'>Balance B/F</td>";
                        
			 foreach($total as $name => $value) {
                            if (($name == 'basic_salary') || ($name == 'gross_salary')
                                || ($name == 'net_pay')) {
                                echo "<td style=' border:1px solid black;background-color:#dff;'>"; 
                            } else 
                                echo "<td style='border:1px solid black;'><b>"; 
                                echo number_format($value,2) . "</b></td>";
                        }
			echo "</tr>";
			$count = 0;
		    }
			
			  
                    //DISPLAY EMPLOYEE NAME
                    echo "
                    <tr style='text-align:center;'>
                     <td style='border:1px solid black;'>$sn</td>
                     <td style='border:1px solid black;'>{$emp_value[1]}</td>
                    ";
                    $sn++;
                    $count++;

                    if (strlen($emp_value[1]) > 14) //If the length of the name is greater than 15, count as another line
                        $count++;

                        $total['basic_salary'] += $pay_value[1];

                        //DISPLAY EMPLOYEE BASIC SALARYx
                        echo "<td style='border: 1px solid black; background-color:#ddf;'>" 
                        . number_format($pay_value[1], 2) . "</td>";

                        foreach($di as $di_id => $di_value) 
                            if ($di_value[0] == 'Allowances') {
                                $name = "{$di_id}_Allowances";
                                $key = "{$pay_id}_{$di_id}";

                                $value = 0;
                                if(array_key_exists($key, $payroll_di))
                                    $value = $payroll_di[$key];

                                $total[$name] += $value;
                                $allowances += $value;
                                echo "  <td style='border:1px solid black;'> " . number_format($value, 2) . "</td>";
                            }

                            //CALCULATE AND DISPLAY GROSS SALARY
                            $gross_salary = $pay_value[1] + $allowances;
                            $total['gross_salary'] += $gross_salary;
                            echo "<td style='border: 1px solid black; background:#dff;'>". number_format($gross_salary, 2) . "</td>";

                            foreach($di as $di_id => $di_value) 
                                if ($di_value[0] == 'Deductions') {
                                    $name = "{$di_id}_Deductions";
                                    $key = "{$pay_id}_{$di_id}";

                                    $value = 0;
                                    $url = "0";
                                    /*
                                      if ($di_id == 1) { //Calculate PAYE
                                            $value = calculate_paye($emp_id, $con);
                                            $url = "<a style='color:blue;text-decoration:underline;' href='employee_payee.php?&id={$emp_id}&action=List'>" . number_format($value, 2) . "</a>";
                                      } else {
                                            $value = $payroll_di[$key];
                                            $url = number_format($value, 2);
                                      }
                                    */
                                    if(array_key_exists($key, $payroll_di))
                                        $value = $payroll_di[$key];
                                    $total[$name] += $value;
                                    $deductions += $value;

                                    echo "  <td style='border:1px solid black;'>" . number_format($value, 2) . "</td>";
                                }

                                //CALCULATE AND DISPLAY NET PAY		
                                $net_pay = $gross_salary - $deductions;
                                echo "<td style='border:1px solid black; background-color:#ddf;'>" . number_format($net_pay, 2) . "</td>";
                                $total['net_pay'] += $net_pay;

                                echo "</tr>";
                            }
                        }
                    }
                }
            }
	   
            echo "<tr><td>&nbsp;</td></tr>";
             echo "<tr style='text-align:center;'>
                  <td style='border:1px solid black'>&nbsp;</td>
              <td style='border:1px solid black'>TOTAL</td>";
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
	   <tr><td>&nbsp;</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
	    <td>
		 <table>
		  <tr>
    ";
	if (isset($_POST['prepared_by']))
	  echo "<td><b>Prepared By:</b> {$_POST['prepared_by']}</td>";
	  
	if (isset($_POST['checked_by']))
	  echo "<td><b>Checked By:</b> {$_POST['checked_by']}</td>";
	
	if (isset($_POST['approved_by']))
	  echo "<td><b>Approved By:</b> {$_POST['approved_by']}</td>";
	
	echo "</tr>
	    </table>
	   </td>
	  </tr>";
	  
	echo "<tr><td>";
    
	echo ($not_prepared_payroll == false) ? "" : $end_message;
	echo "</td></tr></table>";
	exit;
  } 
}
?>
<table>  
 <tr class="class1">
  <td style='text-align:center;' colspan="4"><h3>PAYROLL REPORT</h3></td>
 </tr>
 <form action="payroll_report.php" method="post" name="form1" id="form1">
 <?php 
 if (mysqli_num_rows(mysqli_query($con, "select * from bank order by id")) <= 0) {
  echo msg_box("Please add a bank", 'bank.php', 'Continue to Bank');
  exit;
 }
 ?>
 <tr>
  <td>Date:</td><td><?php echo "{$_SESSION['year_id']}-{$_SESSION['month_id']}-01"; ?></td>
  <td>Prepared By:</td><td><input name='prepared_by' type='text' size='30'></td>
 </tr>
 <tr>
  <td>Branch:</td>
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
  <td>Checked By:</td>
  <td><input name='checked_by' type='text' size='30'></td>
 </tr>
 
 <tr>
  <td>Location:</td>
  <td>
   <select name="location_id" id="location_id">
    <option value='-1'>&nbsp;</option>
   </select>
  </td>
  <td>Approved By:</td>
  <td><input name='approved_by' type='text' size='30'></td>
  </tr>
  
  <tr>
   <td>Bank:</td>
   <td>
   <?php
    echo selectfield(
     array('0'=>'All') + my_query("select * from bank", 'id', 'name'), 
      'bank_id', '');
   ?>
   </td>
   </tr>
   
   <tr>
    <td colspan='4'>
 	 <table>
	  <tr style='text-align:center;'>
	   <td>
        <input name="action" type="submit" value="Generate">
        <input name='action' type='submit' value='Cancel'>
       </td>
	  </tr>
	 </table>
	</td>
   </tr>
   </form>
  </table>
  
