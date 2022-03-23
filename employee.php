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
$arr = array('PAYE', 'HOUSING', 'UTILITY', 'MEAL', 'ENTERTAINMENT', 'TRANSPORT');

$temp = get_user_perm($_SESSION['uid'], $con);
if (!(in_array('Employee', $temp) || in_array('Administrator', $temp))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
  print_header('Employee List', 'employee.php', 'Back to Main Menu', $con);
} else {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
  if (!user_type($_SESSION['uid'], 'Administrator', $con)
  || (user_type($_SESSION['uid'], 'Accountant', $con))
  ){
    echo msg_box("Access Denied", 'employee.php', 'Back');
    exit;
  }
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose an Employee", 'employee.php', 'Back');
    exit;
  }
  echo msg_box("Are you sure you want to delete " . 
    get_value('employee', 'firstname', 'id', $_REQUEST['id'], $con)
     . " ?" , "employee.php?action=confirm_delete&id={$_REQUEST['id']}", 
    'Continue to Delete');
   exit;
}


if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
if (!user_type($_SESSION['uid'], 'Administrator', $con)
  || (user_type($_SESSION['uid'], 'Accountant', $con))
  ){
    echo msg_box("Access Denied", 'employee.php', 'Back');
    exit;
  }
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose an Employee", 'employee.php', 'Back');
    exit;
  }
  $sql="select * from employee where id={$_REQUEST['id']}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result) <= 0) {
    echo msg_box("This Employee does not exist in the database", 
     'employee.php', 'OK');
    exit;
  }
  $sql="delete from employee where id={$_REQUEST['id']}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));

  $sql="delete from employee_di where employee_id={$_REQUEST['id']}";
  mysqli_query($con, $sql) or die(mysqli_error($con));
  
  echo msg_box("Employee has been deleted", 'employee.php', 'OK');
  exit;
}


if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Employee')) {
if (!user_type($_SESSION['uid'], 'Administrator', $con)
  || (user_type($_SESSION['uid'], 'Accountant', $con))
  || (user_type($_SESSION['uid'], 'Basic', $con))
  ){
    echo msg_box("Access Denied", 'employee.php', 'Back');
    exit;
  }
  
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose an Employee", 'employee.php', 'Back');
    exit;
  }
  /* Make sure salary structure exist for this Grade Level */
  $sql="select * from grade_level where id={$_REQUEST['gl_id']}";
  $result = mysqli_query($con, $sql) or mysqli_error($con);
  if (mysqli_num_rows($result) <= 0) {
    echo msg_box("No salary structure defined found for Grade Level " 
     . get_value('grade_level', 'name', 'id', $_REQUEST['gl_id'], $con), 
     'employee.php', 'Back');
    exit;
  }

  /* Make sure the basic salary column is not empty and is a number*/
  $row = mysqli_fetch_array($result);
  if (!is_numeric($row['basic_salary'])) {
    echo msg_box("No basic salary defined for Grade Level " 
      . get_value('grade_level', 'name', 'id', $_REQUEST['gl_id'], $con),
      'employee.php', 'Back');
    exit;
  }

  if (!empty($_FILES['passport']['name'])) {
    upload_file('passport', 'employee.php?action=Add'); 
    $sql="update employee set passport='{$_FILES['passport']['name']}'
     where id={$_REQUEST['id']}";
    mysqli_query($con, $sql) or die(mysqli_error($con));
  }
	
  $sql="update employee set "; 

  $sql1="show columns from employee";
  $result1 = mysqli_query($con, $sql1) or die(mysqli_error($con));
  while($row1 = mysqli_fetch_array($result1)) {
    if ($row1[0] == 'id')
      continue;
    else if (empty($_FILES['passport']['name']) && ($row1[0] == 'passport'))
      continue;
    else if ((!empty($_FILES['passport']['name'])) && ($row1[0] == 'passport'))
      $sql .= "$row1[0] = '{$_FILES[$row1[0]]['name']}', ";
    else $sql .= "$row1[0]='{$_REQUEST[$row1[0]]}', ";
  }
  
  $sql = substr($sql, 0, -2);
  $sql .= " where id={$_REQUEST['id']}";
  mysqli_query($con, $sql) or die(mysqli_error($con));

  echo msg_box("Employee details have been changed",'employee.php','Continue');
  exit;
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Employee')) {
  if (!user_type($_SESSION['uid'], 'Administrator', $con)
  || (user_type($_SESSION['uid'], 'Accountant', $con))
  ){
    echo msg_box("Access Denied", 'employee.php', 'Back');
    exit;
  }
 if (empty($_REQUEST['firstname']) || empty($_REQUEST['lastname']) || 
    empty($_REQUEST['gl_id']) || empty($_REQUEST['bank_account_number'])) {
    echo msg_box("Please make sure the following<br />
      Fistname, Lastname, Grade Level Bank Account Number are entered", 
      'employee.php?action=Add', 'Back');
    exit;
  }

  $sql = "select * from employee where firstname='{$_REQUEST['firstname']}' 
    and middlename='{$_REQUEST['middlename']}'
    and lastname='{$_REQUEST['lastname']}'";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result) > 0) {
    echo msg_box("There is already another Employee with the same Names<b>
    Please choose enter another set of names",'employee.php?action=Add','Back');
    exit;
  }

  $sql="select * from grade_level where id={$_REQUEST['gl_id']}";
  $result = mysqli_query($con, $sql) or mysqli_error($con);
  if (mysqli_num_rows($result) <= 0) {
    echo msg_box("No salary structure found for Grade Level " 
     . get_value('grade_level', 'name', 'id', $_REQUEST['gl_id'], $con), 
     'employee.php', 'Back');
    exit;
  }
  $row = mysqli_fetch_array($result);
  if (empty($row['basic_salary'])) {
    echo msg_box("No basic salary defined for Grade Level {$_REQUEST['gl_id']} 
      in the salary structure" , 'employee.php', 'Back');
    exit;
  }
  if (!empty($_FILES['passport']['name'])) 
    upload_file('passport', 'employee.php?action=Add'); 

  $sql="insert into employee(";

  //Lets generate the table names for our insert statement
  $sql1="show columns from employee";
  $result1 = mysqli_query($con, $sql1) or die(mysqli_error($con));
  while($row1 = mysqli_fetch_array($result1)) {
    if ($row1[0] == 'id')
      continue;
    $sql .= "$row1[0], ";
  }
  $sql = substr($sql, 0, -2);
  $sql .= ") values(";

  //Lets get the values to insert 
  $sql1="show columns from employee";
  $result1 = mysqli_query($con, $sql1) or die(mysqli_error($con));
  while($row1 = mysqli_fetch_array($result1)) {
    if ($row1[0] == 'id')
      continue;
    if ($row1[0] == 'passport')
      $sql .= "'{$_FILES[$row1[0]]['name']}', ";
    else 
      $sql .= "'{$_REQUEST[$row1[0]]}', ";
  }
  $sql = substr($sql, 0, -2);
  $sql .= ")";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  $employee_id = mysqli_insert_id($con);
  
  $sql = "select * from di";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row = mysqli_fetch_array($result)) {
    $sql="insert into employee_di(employee_id, di, amount) values ('{$employee_id}', '{$row['id']}', '0')";
	mysqli_query($con, $sql) or die(mysqli_error($con));
  }
  
  echo msg_box("{$_REQUEST['firstname']} {$_REQUEST['middlename']} 
    {$_REQUEST['lastname']} successfully added", 
    'employee.php?action=Add', 'Back');
	
  exit;
} if (isset($_REQUEST['action']) && 
  (($_REQUEST['action'] == 'Add') 
  || ($_REQUEST['action'] == 'Edit') || ($_REQUEST['action'] == 'View'))) {


  if (($_REQUEST['action'] != 'Add') && (!isset($_REQUEST['id']))){
    echo msg_box('Please choose an Employee to edit or view', 
      'employee.php', 'Back');
    exit;
  }
  $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
  if ($_REQUEST['action'] == 'Add')
    $id = 0;
	 
  if (isset($_REQUEST['action']) &&
    (($_REQUEST['action'] == 'Edit') || ($_REQUEST['action'] == 'View'))) {
    $menu = array('employee.php' => 'Employee', 
      'employee_allowance_deduction.php?type=Allowances' => 'Allowances', 
      'employee_allowance_deduction.php?type=Deductions' => 'Deductions', 
	  'employee_payee.php?a=b'=>'Payee');
    tabs($id, $menu, 'Employee');
  }

  //Make sure a deduction exist
  if (mysqli_num_rows(
   mysqli_query($con, "select * from di where type='Deductions'")) <= 0) {
     echo msg_box("No Deductions has been defined<br> 
       Please add a deduction before adding an Employee", 
       'di.php?type=Deductions', 'Deductions');
     exit;
  }
	
  //Make sure an allowance exist
  if (mysqli_num_rows(
    mysqli_query($con, "select * from di where type='Allowances'")) <= 0) {
      echo msg_box("No Allowances has been defined<br> 
        Please add an allowance before adding an employee", 
        'di.php?type=Allowances', 'Allowances');
      exit;
  }

  //Make sure Salary Structure Exist
  if (mysqli_num_rows(mysqli_query($con, "select * from grade_level")) <= 0) {
    echo msg_box("Grade Level has not been defined<br> 
      Please enter Grade Level", 'grade_level.php', 'Grade Level');
    exit;
  }

  //Make sure Department exists
  if (mysqli_num_rows(mysqli_query($con, "select * from department")) <= 0) {
    echo msg_box("No Department has been defined<br> 
      Please enter department information", 'department.php', 'Department');
    exit;
  }

  //Make sure Bank exists
  if (mysqli_num_rows(mysqli_query($con, "select * from bank")) <= 0) {
    echo msg_box("No Bank has been defined<br> 
     Please enter bank information", 'bank.php', 'Bank');
    exit;
  }

  make_form($_REQUEST['action'], $id, 'employee','passport', 'Employee');
  echo "
     <tr>
    <td>";
  if ($_REQUEST['action'] != 'View') {
    if($_REQUEST['action'] == 'Edit') { 
      echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
    }
    echo "<input name='action' type='submit' value='"; 
    echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
    echo " Employee'>";
  }
  echo "
    <input name=\"action\" type=\"submit\" value=\"Cancel\">
    </td>
   </tr>
  </table>";
  exit;
}  
if (!isset($_REQUEST['action']) || ($_REQUEST['action'] == 'Cancel') || ($_REQUEST['action'] == 'Print') || ($_REQUEST['action'] == 'Search')) {
	echo "
	  <div class='class1' style='text-align:center; font-size:1.5em;'>EMPLOYEE</div>
	  <div class='class1'>";
  
    if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
      echo "";
    } else {
      echo "
	   <form name='form1' action='employee.php' method='post'>
		<select name='action' onChange='document.form1.submit();'>
		 <option value=''>Choose option</option>
		 <option value='Add'>Add</option>
		 <option value='Delete'>Delete</option>
		 <option value='Print'>Print</option>
		</select>
		 ";
    }
    echo "
	  <span style='float:right;'>Search
		<input type='text' name='search'>
		<input type='submit' name='action' value='Search'>
	  </span>
	  </div>
	  <table border='0' id=\"large\" class='even'>
	  <thead>
	   <tr>
		<th class='even'style='width:1px;'></th>
		<th class='even'>Lastname</th>
		<th class='even'>Middlename</th>
		<th class='even'>Firstname</th>
		<th class='even'>Branch</th>
		<th class='even'>Location</th>
		<th class='even'>Grade Level</th>
		<th class='even'>Bank</th>
		<th class='even'>Status</th>
	   </tr>
	  </thead>";
   
   if (isset($_REQUEST['count'])) {
     $count = $_REQUEST['count'];
     $count += 20;
   } else 
     $count = 0;
   
   $sql="select e.id, e.firstname, e.lastname, e.middlename, 
     b.name, gl.name as 'gl', bb.name as 'bank', e.status, l.name as 'location'
     from employee e join (location l, branch b, grade_level gl, bank bb) on 
     (e.branch_id = b.id and l.id = e.location_id and gl.id = e.gl_id
       and bb.id = e.bank_id) ";
   /*
	$sql="select e.id, e.firstname, e.lastname, e.middlename, 
	 b.name, gl.name as 'gl', bb.name as 'bank', e.status, l.name as 'location'
	 from employee e join (location l, branch b, grade_level gl, bank bb) on
	 (e.location_id = l.id and l.branch_id = b.id and gl.id = e.gl_id
	  and bb.id = e.bank_id) ";
	*/ 
   $sql3 = "";
   if (isset($_REQUEST['action']) && ($_REQUEST['action']== 'Search')){
     $sql3 = " e.firstname LIKE '%{$_REQUEST['search']}%'
      or e.lastname        LIKE '%{$_REQUEST['search']}%'
      or e.middlename      LIKE '%{$_REQUEST['search']}%'
      or l.name            LIKE '%{$_REQUEST['search']}%'
      or b.name            LIKE '%{$_REQUEST['search']}%'
      or gl.name           LIKE '%{$_REQUEST['search']}%'
      or bb.name           LIKE '%{$_REQUEST['search']}%'
      or e.status          LIKE '%{$_REQUEST['search']}%'";
   }
   if (!empty($sql3)) {
     $sql .= " where $sql3 order by e.id ";
   } else 
     $sql .= " order by e.id ";

   //Dont limit output if this is a search	 
   if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Search')) 
     $sql .= "";
   else 
     $sql .= " limit $count, 20 ";

   
   $result = mysqli_query($con, $sql);
   if (mysqli_num_rows($result) <= 0) {
     echo "<p style='text-align:center;'><h4>No Employee Found</h4></p>";
     exit;
   }
 
   echo "<tbody>";
   $result = mysqli_query($con, $sql) or die(mysqli_error($con));
   while ($row = mysqli_fetch_array($result)) {
     echo "
     <tr>
     <td style='width:1px;'>
      <input type='radio' name='id' value='{$row['id']}'>
     </td>
     <td><a href='employee.php?action=Edit&id={$row['id']}'>
      {$row['lastname']}</a></td>
     <td>{$row['middlename']}</td>
     <td>{$row['firstname']}</td>
     <td>{$row['name']}</td>
     <td>{$row['location']}</td>
     <td>{$row['gl']}</td>
     <td>{$row['bank']}</td>";
     if ($row['status'] == 'Disable') 
       echo "<td style='background:red;'>{$row['status']}</td>";
     else 
       echo "<td>{$row['status']}</td>";
     echo "
   </tr>";
   } 
   echo "</tbody></table>";

   $sql="select count(*) as 'count' from employee";
   $result = mysqli_query($con, $sql) or die(mysqli_error($con));
   $row = mysqli_fetch_array($result);
	
   echo "<p class='class1' style='text-align:center;'>
      Page " . (($count/20) + 1);

   if (isset($_REQUEST['search'])) 
     echo "";
   else if (($row['count'] > 20) && ($row['count'] > ($count+20))) {
     echo "<a href='employee.php?count=$count'>More>></a>";
   } else 
    echo "";

   echo "</p>";
   echo "</form>";   
}
?>
