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
  || user_type($_SESSION['uid'], 'Accounts', $con)
  || user_type($_SESSION['uid'], 'Expenditure', $con))) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}
 main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);


if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'OK')) {
  $url = "di.php?type={$_REQUEST['type']}";
 
 if (is_deduction_or_allowance($_REQUEST['di'], $con)) {
    echo msg_box("You cannot Add/Edit/Delete " . implode(",", $arr), $url, 'Back');
	exit;
  }
 
  if (!is_numeric($_REQUEST['amount'])) {
    echo msg_box('Please enter correct amount', $url, 'Back');
    exit;
  }
  if (!isset($_REQUEST['branch_id'])) {
    echo msg_box('Please choose a Branch', $url, 'Back');
    exit;
  }
  if (!isset($_REQUEST['location_id'])) {
    echo msg_box('Please choose a Location', $url, 'Back');
    exit;
  }

  $branch_sql = "";
  $location_sql = "";
  
  //Determine which branch to process
  if ($_REQUEST['branch_id'] == '0')   //User chose 'All'
    $branch_sql = "branch_id != 0";
  else 
    $branch_sql = "branch_id = {$_REQUEST['branch_id']}";
	   
  //Determine which location to process  
  if ($_REQUEST['location_id'] == '0')   //User chose 'All'
    $location_sql = "location_id != 0";
  else 
    $location_sql = "location_id = {$_REQUEST['location_id']}";

  //Get all the employees at this branch at this location
  $emp = array();
  $sql="select * from employee where $branch_sql and $location_sql";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row = mysqli_fetch_array($result))
    $emp[] = $row['id'];
  
  //For each employee update the deduction or allowance amount
  foreach($emp as $id => $emp_id) {
    $sql="delete from employee_di where di={$_REQUEST['di']} and employee_id=$emp_id";
	mysqli_query($con, $sql) or die(mysqli_error($con));
	
    $sql="insert into employee_di(employee_id, di, amount) values('$emp_id', '{$_REQUEST['di']}', '{$_REQUEST['amount']}')"; 
	mysqli_query($con, $sql) or die(mysqli_error($con));
  }
  echo msg_box("Update successfully", "di.php?type={$_REQUEST['type']}", 'Continue');
  exit;
  
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {

  if (!isset($_REQUEST['id'])) {
      echo msg_box("Please choose {$_REQUEST['type']} to delete", 'di.php', 'Back');
      exit;
  }
  
  if (is_deduction_or_allowance($_REQUEST['id'], $con)) {
    echo msg_box("You cannot Add/Edit/Delete " . implode(",", $arr), $url, 'Back');
	exit;
  }
  
  $sql = "select * from payroll_di where di='{$_REQUEST['id']}'";
  if (mysqli_num_rows(mysqli_query($con, $sql)) >= 1) {
    echo msg_box("***Warning*** There are payroll records containing this {$_REQUEST['type']}<br />
	  Deleting this {$_REQUEST['type']} will delete payroll records tied to this {$_REQUEST['type']}<br />
	  Are you sure you still want to delete?", "di.php?action=Continue_Delete&type={$_REQUEST['type']}&id={$_REQUEST['id']}", 
	  "Yes Continue");
	exit;
  } 
  
  $sql = "select * from employee_di where di='{$_REQUEST['id']}'";
  if (mysqli_num_rows(mysqli_query($con, $sql)) >= 1) {
    echo msg_box("***Warning*** There are employee records containing this {$_REQUEST['type']}<br />
	  Deleting this {$_REQUEST['type']} will delete {$_REQUEST['type']} records tied to this employee<br />
	  Are you sure you still want to delete?", "di.php?action=Continue_Delete&type={$_REQUEST['type']}&id={$_REQUEST['id']}", 
	  "Yes Continue");
    exit;
  } else {
     echo msg_box("***Warning*** 
    Are you sure you still want to delete?", "di.php?action=Continue_Delete&type={$_REQUEST['type']}&id={$_REQUEST['id']}", 
    "Yes Continue");
     exit;
  }
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Continue_Delete')) {
  if (!isset($_REQUEST['id'])) {
      echo msg_box("Please choose {$_REQUEST['type']} to delete", 'di.php', 'Back');
      exit;
  }
  
  if (is_deduction_or_allowance($_REQUEST['id'], $con)) {
    echo msg_box("You cannot Add/Edit/Delete " . implode(",", $arr), $url, 'Back');
	exit;
  }
  
  $sql = "delete from employee_di where di='{$_REQUEST['id']}'";
  mysqli_query($con, $sql) or die(mysqli_error($con));
  
  $sql = "delete from payroll_di where di='{$_REQUEST['id']}'";
  mysqli_query($con, $sql) or die(mysqli_error($con));
  
  $sql="delete from di where id='{$_REQUEST['id']}'";
  mysqli_query($con, $sql) or die(mysqli_error($con));
  
  echo msg_box("Delete successfull", "di.php?type={$_REQUEST['type']}", 'Continue');
  exit;
}
	
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Payroll Deductions/Allowances')) {
  if (empty($_REQUEST['type']) || empty($_REQUEST['name'])) {
      echo msg_box("Please fill out the form", 'di.php?action=Add', 'Back');
      exit;
  }
  
  if (is_deduction_or_allowance($_REQUEST['id'], $con)) {
    echo msg_box("You cannot Add/Edit/Delete " . implode(",", $arr), $url, 'Back');
	exit;
  }
  
  $sql = "select * from di where 
      type='{$_REQUEST['type']}' and name='{$_REQUEST['name']}'";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result) > 0) {
    echo msg_box("There is already another Payroll deduction/allowance with the same name<b>
      Please choose another ", "di.php?action=Add&type={$_REQUEST['type']}", 'Back');
    exit;
  }
  $sql="update di set type='{$_REQUEST['type']}', name='{$_REQUEST['name']}' where id={$_REQUEST['id']}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  
  echo msg_box("Update successfully", "di.php?type={$_REQUEST['type']}", 'Continue');
  exit;
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Payroll Deductions/Allowances')) {
  $url = "di.php?type={$_REQUEST['type']}";
  if (empty($_REQUEST['type']) || empty($_REQUEST['name'])) {
      echo msg_box("Please fill out the form", 'di.php?action=Add', 'Back');
      exit;
  }
  
  if (in_array($_REQUEST['name'], $arr)) {
    echo msg_box("You cannot Add/Edit/Update/Delete " . implode(",", $arr), $url, 'Back');
	exit;
  }
  
  $sql = "select * from di where 
      type='{$_REQUEST['type']}' and name='{$_REQUEST['name']}'";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result) > 0) {
    echo msg_box("There is already another Payroll deduction/allowance with the same name<b>
       Please choose another ", 'di.php?action=Add', 'Back');
    exit;
  }
  $sql="insert into di(type, name)
      values('{$_REQUEST['type']}', '{$_REQUEST['name']}')";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  $di_id = mysqli_insert_id();
  
  //Get all the employees at this branch at this location
  $emp = array();
  $sql="select * from employee";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  while($row = mysqli_fetch_array($result))
    $emp[] = $row['id'];
  
  
  foreach($emp as $id => $emp_id) {
    $sql="insert into employee_di(employee_id, di, amount) values('$emp_id', '{$di_id}', '0')"; 
	mysqli_query($con, $sql) or die(mysqli_error($con));
  }
  
  echo msg_box("{$_REQUEST['type']} {$_REQUEST['name']} successfully added",
      "di.php?action=Add&type={$_REQUEST['type']}", 'Back');
  exit;
}
if (isset($_REQUEST['action']) && 
     (($_REQUEST['action'] == 'Add') || ($_REQUEST['action'] == 'Edit'))) {

  if (($_REQUEST['action'] != 'Add') && (!isset($_REQUEST['id']))){
    echo msg_box('Please choose a Deduction/Allowance to edit or view', 
      "di.php?type={$_REQUEST['type']}", 'Back');
    exit;
  }  
  $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
  
  if (is_deduction_or_allowance($id, $con)) {
    echo msg_box("You cannot Add/Edit/Delete " . implode(",", $arr), $url, 'Back');
	exit;
  }
  
  make_form($_REQUEST['action'], $id, 'di', '', $_REQUEST['type']);
	echo "
     <tr>
    <td>";
    if($_REQUEST['action'] == 'Edit') 
       echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
     
    echo "<input name='action' type='submit' value='"; 
    echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
    echo " Payroll Deductions/Allowances'>";
    if($_REQUEST['action'] == 'Edit') 
	  echo "<input name='action' type='submit' value='Delete'>";
	else 
	  echo "<input name='action' type='submit' value='Cancel'>";
    ?>
	</form>
    </td>
   </tr>
  </table>
  <?php
  exit;
 
}
 if (isset($_REQUEST['type']) && 
(($_REQUEST['type'] == 'Allowances') || ($_REQUEST['type'] == 'Deductions'))) {
  echo "<table>
   <tr>
    <td valign='top'>
	 <table>
	  <tr>
	   <td>
	    <table>
		 <tr class='class1'>
		  <td><a href='di.php?action=Add&type={$_REQUEST['type']}'>Add {$_REQUEST['type']}</a></td>
          <td style='text-align:left;'><h3>{$_REQUEST['type']}</h3></td>
		 </tr>
		</table>
	   </td>
	  </tr>
      <tr>
       <th>Name</th>
      </tr>
   ";
   $result = mysqli_query($con, "select * from di where type='{$_REQUEST['type']}'");
   while($row = mysqli_fetch_array($result)) {
     echo "
      <tr>
	  <td>";
     if (in_array($row['name'], $arr)) //Do not allow any of the $arr array to be added/edited/deleted. See top of this page.
	   echo $row['name'];
	 else
	   echo "<a href='di.php?action=Edit&type={$_REQUEST['type']}&id={$row['id']}'>{$row['name']}</a>";
	 echo "
     </td> 
      </tr>";
  }
  ?>
     </table>
    </td>
   <td valign='top'>
    <table>
	 <form name='form1' id='form1' action='di.php' method='get'>
	 <tr class='class1'><td colspan='2'><h3>Specify amount for <?php echo $_REQUEST['type']?></h3></td></tr> 
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
     <td><?php echo $_REQUEST['type']?></td>
	 <td>
      <?php
	   //Skip $arr See note at the beginning of this page.
	   $last = end($arr);
	   reset($arr);
	   $sql = "";
	   foreach($arr as $item) {
	     if ($last == $item)
		   $sql .= " name != '$item'";
	     else
		   $sql .= " name != '$item' and ";
	   }
	   echo selectfield(array('0'=>'') + my_query("select * from di where type='{$_REQUEST['type']}' and $sql", "id", "name"),'di', '');
	  ?>
     </td>
    </tr>

    <tr>
     <td>Amount</td>
     <td><input type='text' name='amount' /></td>
    </tr>
 	<tr>
     <td>
      <input name="action" type="submit" value="OK">
      <input name='action' type='submit' value='Cancel'>
	  <input name='type' type='hidden' value='<?php echo $_REQUEST['type']; ?>'>
     </td>
    </tr>
	</form>
   </table>
  </td>
 </tr>
</table>  
<?php
 exit;
}
