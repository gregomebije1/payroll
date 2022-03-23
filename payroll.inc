<?php

function is_deduction_or_allowance($id, $con) {
  $sql ="select * from di where id={$id} 
   and (name='PAYE' or name='HOUSING' or name='UTILITY' or name='MEAL' or name='ENTERTAINMENT' or name='TRANSPORT')";
  return (mysqli_num_rows(mysqli_query($con, $sql)) > 0);
}

function get_payee_items($group, $con) {
  $payee_items = array();
  $sql="select * from payee_item where payee_item_group='$group'";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row = mysqli_fetch_array($result)) {
    $payee_items[$row['name']] = array($row['payee_item_type'], $row['amount']);
  }
  return $payee_items;
}

function get_benefits_in_kind($employee_id, $con) {
  $allowances = array();
  $sql = "select d.name, edi.amount from employee_di edi join di d 
    on edi.di = d.id where edi.employee_id={$employee_id} and
	(d.name = 'Staff Accommodation' or d.name='Dressing Allowance' or d.name = 'Recharge Card Allowance')";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row = mysqli_fetch_array($result)) {
    $allowances[$row['name']] = $row['amount'];
  }
  return $allowances;
}
function get_leave_allowance($employee_id, $con) {
  $sql = "select edi.amount from employee_di edi join di d 
    on edi.di = d.id where d.name = 'Leave Allowance' and edi.employee_id={$employee_id}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  $row = mysqli_fetch_array($result);
  return $row['amount'];
}
function get_salary($employee_id, $con) {
  $sql ="select gl.basic_salary from employee e join grade_level gl 
     on e.gl_id = gl.id where e.id={$employee_id}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  $row = mysqli_fetch_array($result);
  return $row['basic_salary'];
}
function calculate_paye_basic($basic_name, $emp_id, $con) { 
  $amount = 0;
	
  if($basic_name == 'PAYE')
    $amount = calculate_paye($emp_id, $con);
  else {
    $salary = get_salary($emp_id, $con);
	$payee_items = get_payee_items('paye', $con);
    
	foreach($payee_items as $payee_item => $percent_or_value) {
      $temp = $percent_or_value[0] == 'percentage' ? $percent_or_value[1]/100 : $percent_or_value[1];
	  if ($payee_item == $basic_name) {
	    $amount = $salary * $temp;
		break;
	  }
	}
  }
  return $amount;
}

function calculate_paye($emp_id, $con) {
   
  $salary = get_salary($emp_id, $con);
  $annual_salary = $salary * 12;
  
  
  $allowance = get_leave_allowance($emp_id, $con); 
  $annual_allowance = $allowance * 12;
  
  $payee_items = get_payee_items('paye', $con);
  $benefits_in_kind = get_benefits_in_kind($emp_id, $con);
  
  $relief_items = get_payee_items('reliefs', $con);
  $exemptions_items = get_payee_items('exemptions', $con);
  
  $total_benefits = 0;
  $total_payee = 0;
  $total_reliefs_exemptions = 0;
  $basic = 0;
  $housing = 0;
  $transport  = 0;
  $paye_per_annum = 0;
  
  
  foreach($payee_items as $payee_item => $percent_or_value) {
    $temp = $percent_or_value[0] == 'percentage' ? $percent_or_value[1]/100 : $percent_or_value[1];
	 if ($payee_item == 'BASIC')
	   $basic = $annual_salary * $temp;
	 else if ($payee_item == 'HOUSING')
	   $housing = $annual_salary * $temp;
	 else if ($payee_item == 'TRANSPORT')
	   $transport = $annual_salary * $temp;
	   
     //echo number_format($annual_salary * $temp);
     $total_payee += ($annual_salary * $temp); 
  }
  
  foreach($benefits_in_kind as $benefit => $amount) {
 	 $total_benefits += $amount;
  }
  $gross_income = $total_payee + $annual_allowance + $total_benefits;
  
  foreach($relief_items as $relief_item => $percent_or_value) {
    if ($relief_item == 'PERSONAL ALLOWANCE')
	   $temp = ($gross_income * ($percent_or_value[1]/100)) + 200000;
	 else {
	   $temp = $percent_or_value[0] == 'percentage' ? $percent_or_value[1]/100 : $percent_or_value[1];
	 }
	 $total_reliefs_exemptions += $temp;
  }
  
  foreach($exemptions_items as $exemption_item => $percent_or_value) {
    if ($exemption_item == 'NHF') 
	   $temp = ($percent_or_value[1]/100) * $basic;
	 else if ($exemption_item == 'PENSION') 
	     $temp = ($percent_or_value[1]/100) * ($basic + $housing + $transport);
	 else 
	   $temp = $percent_or_value[0] == 'percentage' ? $percent_or_value[1]/100 : $percent_or_value[1];
	 
	 $total_reliefs_exemptions += $temp;
  }
  $taxable_income = $gross_income - $total_reliefs_exemptions;
  
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
     if ($value[0] == '7') {
	   $bal = $taxable_income;
     }   
     $bal = $bal - $value[1];
     if (($key + 1) <= count($arr))
       $next_bal = $bal - $arr[$key + 1][1]; //Next balance
     else
       $next_bal = 0;

     if (($value[0] == 7) && ($bal <= 0)) {
       $paye_per_annum += ($taxable_income * ($value[0]/100));
	   $bal = 0;
     } else if ($next_bal <= 0) {
       $paye_per_annum += (($bal + $value[1])* ($value[0]/100));
	   $bal = 0;
     } else {
       $paye_per_annum += ($value[1] * ($value[0]/100));
     }
   }
   if (($salary >= 24500) && ($salary < 30000))
     return (1/100) * $salary;
   else if ($paye_per_annum < 0)
     return (1/100) * $salary;
   else
     return $paye_per_annum/12;
  
   //abs($paye_per_annum/12);
   //Effective Tax Rate: $paye_per_annum/$gross_income)*(100/1)
}
?>
