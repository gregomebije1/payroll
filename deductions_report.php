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
require_once("config_profile_security.inc");

if (!(user_type($_SESSION['uid'], 'Administrator', $con)
 || user_type($_SESSION['uid'], 'Accounts', $con))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}

if(isset($_REQUEST['command']) && ($_REQUEST['command'] =="Print")) {
    print_header('List Of Deductions', 'deductions_report.php', 'Back', $con);
} else {
  main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Generate')) {
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
       'deductions_report.php', 'Back');
    exit;
  }	 

  if (!isset($_REQUEST['command'])) {
    echo "<span style='position:absolute;top:0px;left:100px;'>
        <a style='cursor:hand;' onclick='window.open(\"deductions_report.php?action=Process&type={$_REQUEST['type']}&bank_id={$_REQUEST['bank_id']}&year={$_SESSION['year_id']}&month={$_SESSION['month_id']}&branch_id={$_REQUEST['branch_id']}&location_id={$_REQUEST['location_id']}&command=Print\", \"smallwin\", 
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
       
      <tr style='text-align:center;'><td colspan='5'>BRANCH:";
	  
  if ($_REQUEST["branch_id"] == "All")
    echo "All";
  else 
    echo get_value('branch','name','id',$_REQUEST['branch_id'], $con);

  echo "</td></tr>
      <tr style='text-align:center;'><td colspan='5'>
        LOCATION: ";
  if ($_REQUEST["location_id"] == "0")
    echo "All"; 
  else 
    echo get_value('branch','name','id',$_REQUEST['location_id'], $con);

  echo "</td></tr> 

      <tr><td>&nbsp;</td></tr>
      <tr style='text-align:center;'>
        <td colspan='5'>BANK: 
  "; 
  if ($_REQUEST['bank_id'] == 'All')
    echo "All";
  else 
    echo get_value('bank', 'name', 'id', $_REQUEST['bank_id'], $con);
  echo "
       </td></tr>

  ";
  
  //***Current changes
  if ($_REQUEST['type'] == "All")
    $sql = "select * from di where type='Deductions'";
  else 
    $sql = "select * from di where id={$_REQUEST['type']}";
  //echo "{$sql}<br />";
  
  $result_types = mysqli_query($con, $sql) or die(mysqli_error($con));
  while ($row = mysqli_fetch_array($result_types)) {
	  $types[$row["id"]] = $row["name"];
  }
  
  foreach ($types as $type_id => $type_value) {
  
  //Display the name of the deduction 
  //echo strtoupper(get_value('di', 'name', 'id', $_REQUEST['type'], $con));
    ?>
	<tr style='text-align:center; border-top:1px solid black;'><td colspan='5'><h2><?php echo $type_value;?> DEDUCTION</b></h2></tr>
      <tr style='text-align:center;'><td colspan='5'>
       <h3>Pay Period: MONTH ENDING:
	   <?php echo get_month_name($_SESSION['month_id']) . ", {$_SESSION['year_id']}" ?>
	   </h3></td></tr>
	   </table>
	   <table>
	    <tr>
	     <th>Pay Roll No.</th>
	     <th>Month/Year</th>
	     <th>Name</th>
	     <th>Ledger Folio</th>
	     <th>Amount</th>
       </tr>
   
    
    <?php 
    //DETERMINE WHICH LOCATION TO PROCESS
    if ($_REQUEST['location_id'] == '0') {
      $sql="select * from location ";
	  
	  if ($_REQUEST["branch_id"] != "All")
	    $sql .= " where branch_id={$_REQUEST['branch_id']}";
	
    } else {
      $sql="select * from location where 
	    id={$_REQUEST['location_id']} ";
		
	  if ($_REQUEST["branch_id"] != "All")
	    $sql .= " and branch_id={$_REQUEST['branch_id']}";
    }
    $html = "<span style='font-weight:bold;'>Payroll has not been prepared for this date for the following:</span><br />";  
    $result_l = mysqli_query($con, $sql) or die(mysqli_error($con));
	$total = 0;
    while($row_l = mysqli_fetch_array($result_l)) {

      //DETERMINE WHICH EMPLOYEES TO PROCESS
      if ($_REQUEST['bank_id'] == 'All') { 
        $sql="SELECT * from employee where status='Enable'";
		
		if ($_REQUEST["branch_id"] != "All")
		  $sql .= " and branch_id= {$_REQUEST['branch_id']}";
	  
	    $sql .= " and location_id={$row_l['id']} order by id";
		
      } else  {
        $sql="SELECT * from employee where bank_id={$_REQUEST['bank_id']} 
	      and status='Enable' ";
		  
		if ($_REQUEST["branch_id"] != "All")
		  $sql .= " and branch_id={$_REQUEST['branch_id']}";
		  
	    $sql = " and location_id={$row_l['id']} order by id";
		 
      }
      
      $result = mysqli_query($con, $sql) or die(mysqli_error($con));
      if(mysqli_num_rows($result) <= 0) //No employee found
        continue;
  
      //Get employee data
      while($row = mysqli_fetch_array($result)) {
		  
        //Get payroll data for this employee
        $sql="select * from payroll where employee_id={$row['id']} and payroll_date='$date'";
        $result3 = mysqli_query($con, $sql) or die(mysqli_error($con));
		
        if (mysqli_num_rows($result3) == 0) {  //No payroll data found
          $html .= "{$row['firstname']} {$row['lastname']} <br>";
		  continue;
        } 
		
        $row3 = mysqli_fetch_array($result3);
        
        //Get payroll deductions for this employee
        $sql="select di, amount from payroll_di where payroll_id={$row3['id']} and di={$type_id}";
		$result5 = mysqli_query($con, $sql) or die(mysqli_error($con));
	    $row5 = mysqli_fetch_array($result5);
		
		//Only display if there are deductions
		if ($row5['amount'] > 0) {
		  echo "
			 <tr>
			  <td style='border-bottom: 1px solid #ddd;'>&nbsp;</td>
			  <td style='border-bottom: 1px solid #ddd'>&nbsp;</td>
			  <td style='border-bottom: 1px solid #ddd'>{$row['firstname']} {$row['lastname']}</td>
			  <td style='border-bottom: 1px solid #ddd'>&nbsp;</td> 
			  <td style='border-bottom: 1px solid #ddd'> " . number_format($row5['amount'], 2) . "</td></tr>";
			
        
	      $total += $row5['amount'];		  
		}
      }
    }
	echo "<tr><td style='height:1em;'>&nbsp;</td></tr>
	      <tr style='text-align:center;'>
           <td colspan='3'><h2>ORIGINAL</h2></td>
           <td>Carried forward </td>
           <td>=N=" . number_format($total, 2) . "</td></tr>";
  }
  echo "<tr><td colspan='3'>{$html}</td></tr>";
  exit;  
  }  
?>
<table> 
 <tr class="class1">
  <td colspan='4'><h3>Deductions</h3></td>
 </tr>
 <form action="deductions_report.php" method="post" name="form1" id="form1">
 <tr>
 <?php 
 $sql = "select * from bank";
 $result = mysqli_query($con, $sql) or die(mysqli_error($con));
 if (mysqli_num_rows($result) <= 0) {
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
   echo selectfield(array("All"=>"All") + $type, 'type', '');
  ?>
  </td>
 </tr>
 <tr>
  <td>Bank</td>
   <td>
   <?php
   echo selectfield(array("All"=>"All") + my_query("select * from bank", 'id', 'name'), 
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
	 <option value='All'>All</option>
     <?php
	 $sql = "select * from branch order by id";
     $result = mysqli_query($con, $sql) or die(mysqli_error($con));
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
     <input name="action" type="submit" value="Generate">
     <!--<input name='action' type='submit' value='Cancel'>-->
    </td>
   </tr>
   </form>
  </table>
