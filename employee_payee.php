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
  || (user_type($_SESSION['uid'], 'Records', $con)))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}


if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
  print_header('Employee Payee', 'employee_payee.php', 'Back to Main Menu', $con);
} else {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'List')) {
  $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
  $menu = array('employee.php' => 'Employee', 
	  'employee_allowance_deduction.php?type=Allowances' => 'Allowances', 
	  'employee_allowance_deduction.php?type=Deductions' => 'Deductions',
	  'employee_payee.php?a=b'=>'Payee');
  tabs($id, $menu, 'Payee');
    
  $firstname = get_value('employee', 'firstname', 'id', $_REQUEST['id'], $con);
  $lastname = get_value('employee', 'lastname', 'id', $_REQUEST['id'], $con);
   
  $salary = get_salary($_REQUEST['id'], $con);
  $annual_salary = $salary * 12;
  
  $allowance = get_leave_allowance($_REQUEST['id'], $con); 
  $annual_allowance = $allowance * 12;
  
  $payee_items = get_payee_items('paye', $con);
  $benefits_in_kind = get_benefits_in_kind($_REQUEST['id'], $con);
  
  $relief_items = get_payee_items('reliefs', $con);
  $exemptions_items = get_payee_items('exemptions', $con);
  
  $total_benefits = 0;
  $total_payee = 0;
  $total_reliefs_exemptions = 0;
  $basic = 0;
  $housing = 0;
  $transport  = 0;
  $paye_per_annum = 0;
  
  ?>
  <table> 
   <tr class='class1'>
    <td colspan='4'><h3><?php echo "$firstname $lastname - PAYEE"; ?></h3></td>
   </tr>
   <tr>
    <th></th>
	<th>Percentage</th>
	<th>Monthly</th>
	<th>Annual</th>
   </tr>
   <tr>
    <td>SALARY</td>
	<td></td>
	<td><?php echo number_format($salary, 2); ?></td>
	<td><?php echo number_format($annual_salary, 2); ?></td>
   </tr>
   <tr>
	<td>LEAVE ALLOWANCE</td>
	<td></td>
	<td><?php echo number_format($allowance, 2); ?></td>
	<td><?php echo number_format($annual_allowance, 2); ?></td>
   </tr>
   
   <tr>
    <th colspan='4' >PAYE</th>
   </tr>
   
   <?php
   foreach($payee_items as $payee_item => $percent_or_value) {
   
     echo "<tr>
	   <td>{$payee_item}</td>";
	 echo "<td>" . $percent_or_value[1];
	 echo ($percent_or_value[0] == 'percentage') ? '%' : '' . " </td>";
	 echo "
	   <td></td>
	   <td>";
	 $temp = $percent_or_value[0] == 'percentage' ? $percent_or_value[1]/100 : $percent_or_value[1];
	 if ($payee_item == 'BASIC')
	   $basic = $annual_salary * $temp;
	 else if ($payee_item == 'HOUSING')
	   $housing = $annual_salary * $temp;
	 else if ($payee_item == 'TRANSPORT')
	   $transport = $annual_salary * $temp;
	   
     echo number_format($annual_salary * $temp);
     $total_payee += ($annual_salary * $temp);
      echo "<td>
	  </tr>";
	 
  }
  ?>
  <tr>
   <td></td>
   <td style='font-weight:bold;'>100%</td>
   <td></td>
  </tr>
  
  <tr>
   <td>LEAVE ALLOWANCE (10% of annual basic)</td>
   <td></td><td></td>
   <td><?php echo number_format($annual_allowance, 2); ?></td>
  </tr>
  
  <tr>
   <th colspan='4'>BENEFITS IN KIND</th>
  </tr>
  
  <?php
   foreach($benefits_in_kind as $benefit => $amount) {
     echo "<tr>
	   <td>{$benefit}</td>
	   <td>" . number_format($amount, 2) . "</td>
	   <td></td>
	  </tr>";
	 $total_benefits += $amount;
  }
  ?>
  <tr>
   <td></td>
   <td></td>
   <td></td>
   <td><?php echo number_format($total_benefits, 2); ?></td>
  </tr>
  <tr>
   <th colspan='3'>GROSS INCOME</th>
   <th>
   <?php
   $gross_income = $total_payee + $annual_allowance + $total_benefits;
   echo number_format($gross_income, 2);
   ?>
   </th>
  </tr>   
  
  <tr><td colspan='4'></td></tr>
  
  <tr><th colspan='4'>RELIEFS</th></tr>
  <?php
   foreach($relief_items as $relief_item => $percent_or_value) {
     echo "<tr>
	   <td>{$relief_item}</td>";
	 echo "<td>" . $percent_or_value[1];
	 echo ($percent_or_value[0] == 'percentage') ? '%' : '' . " </td>";
	 echo "
	   <td>";
	 if ($relief_item == 'PERSONAL ALLOWANCE')
	   $temp = ($gross_income * ($percent_or_value[1]/100)) + 200000;
	 else {
	   $temp = $percent_or_value[0] == 'percentage' ? $percent_or_value[1]/100 : $percent_or_value[1];
	 }
	 $total_reliefs_exemptions += $temp;
     echo number_format($temp, 2) . "<td>
	  </tr>";
  }
  ?>
  <tr><th colspan='4'>EXEMPTIONS</th></tr>
  <?php
   foreach($exemptions_items as $exemption_item => $percent_or_value) {
     echo "<tr>
	   <td>{$exemption_item}</td>";
	 echo "<td>" . $percent_or_value[1];
	 echo ($percent_or_value[0] == 'percentage') ? '%' : '' . " </td>";
	 echo "
	   <td> ";
	 if ($exemption_item == 'NHF') 
	   $temp = ($percent_or_value[1]/100) * $basic;
	 else if ($exemption_item == 'PENSION') 
	     $temp = ($percent_or_value[1]/100) * ($basic + $housing + $transport);
	 else 
	   $temp = $percent_or_value[0] == 'percentage' ? $percent_or_value[1]/100 : $percent_or_value[1];
	 
	 $total_reliefs_exemptions += $temp;
     echo number_format($temp, 2) . "<td>
	  </tr>";
  }
  ?>
  <tr>
   <th>TAX FREE PAY</th>
   <th></th>
   <th></th>
   <th><?php echo number_format($total_reliefs_exemptions, 2); ?></th>
  </tr>
  <tr>
   <th>TAXABLE INCOME</th>
   <th></th>
   <th></th>
   <th>
    <?php 
	  $taxable_income = $gross_income - $total_reliefs_exemptions;
      echo number_format($taxable_income, 2); 
	?>
   </th>
  <tr><th colspan='4'>PAYE</th></tr>
  
  <?php
   //If taxable income is negative use 1%
   if ($taxable_income <= 0)
     $annual_paye = (1/100) * $taxable_income;
   
   $arr = array(1 => array('7','300000'), 
                2 => array('11', '300000'), 
				3 => array('15', '500000',),
				4 => array('19', '500000',),
				5 => array('21', '1600000',),
				6 => array('24', '3200000'));
   $bal = 0;
   foreach($arr as $key => $value) {
     if ($value[0] == '24')
	  echo "<tr><td></td><td>" . number_format($taxable_income, 2) . "</td><td></td><td></td></tr>";
	
     echo "<tr>";
     if ($value[0] == '7') {
	   $bal = $taxable_income;
      echo "<td>1ST</td>";
	 } else
	   echo "<td>NEXT</td>";
	   
	  
	 //echo "before: n={$next_bal} b={$bal} v={$value[1]}<Br>";
	 
	 $bal = $bal - $value[1];
	 
	 //echo "after: n={$next_bal} b={$bal} v={$value[1]}<Br>";
	 
	 if (($key + 1) <= count($arr))
	   $next_bal = $bal - $arr[$key + 1][1]; //Next balance
	 else
	   $next_bal = 0;
	   
	 if (($value[0] == 7) && ($bal <= 0)) {
	   $paye_per_annum += ($taxable_income * ($value[0]/100));
	  echo "
	    <td>" . number_format($taxable_income, 2) . "</td>
        <td>" . $value[0] . "%</td> 
	    <td>" . number_format($taxable_income * ($value[0]/100), 2) . "</td>
       </tr>";
	   $bal = 0;
	 } else if ($next_bal <= 0) {
	   $paye_per_annum += (($bal + $value[1])* ($value[0]/100));
	   
	   echo "
	    <td>" . number_format($bal + $value[1], 2) . "</td>
        <td>" . $value[0] . "%</td> 
	    <td>" . number_format(($bal + $value[1])* ($value[0]/100), 2) . "</td>
       </tr>";
	   $bal = 0;
     } else {
	   $paye_per_annum += ($value[1] * ($value[0]/100));
       echo "	 
	     <td>" . number_format($value[1], 2) . "</td>
         <td>" . $value[0] . "%</td> 
	    <td>" . number_format($value[1] * ($value[0]/100), 2) . "</td>
       </tr>";
     }
   }
  ?>
  <tr>
   <th>PAYE PER ANNUM</th>
   <th></th>
   <th></th>
   <th>
   <?php 
    echo number_format($paye_per_annum, 2); 
    if ($paye_per_annum < 0)
	 echo "&nbsp;&nbsp;<span style='color:red;'>" . number_format((1/100) * $annual_salary, 2) . "</span>";
   ?>
   </th>
  </tr>
  <tr>
   <th>PER MONTH</th>
   <th></th>
   <th></th>
   <th>
   <?php 
    echo number_format($paye_per_annum/12, 2); 
	if ($paye_per_annum < 0)
	 echo "&nbsp;&nbsp;<span style='color:red;'>" . number_format((1/100) * $salary, 2) . "</span>";
	 ?>
	</th>
  </tr>
  
  <tr>
   <td>Effective Tax Rate</td>
   <td>
    <?php 
     if ($gross_income != 0)
        echo number_format(abs(($paye_per_annum/$gross_income)*(100/1)), 2) . "%"; 
	   if ($paye_per_annum < 0)
	     echo "&nbsp;&nbsp;<span style='color:red;'>1%</span>";
	?>
    </td>
  </tr>
  
 </table>
<?php
   
 } 
?>

