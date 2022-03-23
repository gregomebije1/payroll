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
if (!user_type($_SESSION['uid'], 'Administrator', $con)) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
 print_header('Department List', 'department.php', 'Back to Main Menu', $con);
} else {
  main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
    if (empty($_REQUEST['id'])) {
	  echo msg_box("Please choose a department", 'department.php', 'Back');
	  exit;
	}
	$sql="select * from employee where department_id={$_REQUEST['id']}";
	if (mysqli_num_rows(mysqli_query($con, $sql)) > 0) {
	  echo msg_box("***DELETION DENIED***<br>
	   There are employees still attached to this department.<br>
	   Delete those employees before deleting this department", 
	   "department.php", "Continue to Delete");
	  exit;
	}
	   
	echo msg_box("***WARNING***<br>
	  Are you sure you want to delete " . 
	  get_value('department', 'name', 'id', $_REQUEST['id'], $con)
	  . " Department?" , 
	   "department.php?action=confirm_delete&id={$_REQUEST['id']}", 
	   'Continue to Delete');
	   exit;
  }
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
    if (empty($_REQUEST['id'])) {
	  echo msg_box("Please choose a department", 'department.php', 'Back');
	  exit;
	}
	$sql="delete from department where id={$_REQUEST['id']}";
	$result = mysqli_query($con, $sql) or die(mysqli_error($con));
	
	echo msg_box("Department has been deleted", 'department.php', 'Continue');
	exit;
  }
  
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Department')) {
    if (empty($_REQUEST['name']))  {
       echo msg_box('Please enter Department Name', 'department.php?action=Form', 'Back');
       exit;
    }
    $sql="select * from department where name='{$_REQUEST['name']}'";
	$result = mysqli_query($con, $sql) or die(mysql_errror());
	$row = mysqli_fetch_array($result);
	if (mysqli_num_rows($result) > 0) {
	  echo msg_box("There is already an existing Department 
	   with the same name as '{$_REQUEST['name']}'<br>
	   Please choose another name", 'department.php', 'Back');
	  exit;
	}
    $sql="insert into department (name)values('{$_REQUEST['name']}')";
    mysqli_query($con, $sql) or die(mysqli_error($con));
	
	echo msg_box("Successfully added", 'department.php', 'Continue');
	exit;

  } else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Department')) {
	if (empty($_REQUEST['id'])) {
	  echo msg_box('Please choose a department', 'department.php', 'Back');
	}
	$sql="select * from department where name='{$_REQUEST['name']}'";
	$result = mysqli_query($con, $sql) or die(mysql_errror());
	if (mysqli_num_rows($result) > 0) {
	  echo msg_box("There is already an existing Department 
	   with the same name as '{$_REQUEST['name']}'<br>
	   Please choose another name", 'department.php', 'Back');
	  exit;
	}
	$sql="update department set name='{$_REQUEST['name']}' where id={$_REQUEST['id']}";
    mysqli_query($con, $sql) or die(mysqli_error($con));
    echo msg_box("Successfully changed", 'department.php', 'OK');
	exit;
  } else if (isset($_REQUEST['action']) && 
   (($_REQUEST['action'] == 'Add') || ($_REQUEST['action'] == 'Edit') || 
    ($_REQUEST['action'] == 'View'))) {
   if ($_REQUEST['action'] != 'Add') {
     if (!isset($_REQUEST['id'])) {
	   echo msg_box("Please choose a department", 'department.php', 'Back');
	   exit;
	  }
   }
   $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
   $sql = "select * from department where id=$id";
   $result = mysqli_query($con, $sql);
   $row = mysqli_fetch_array($result);
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3><?php echo $_REQUEST['action']; ?> Department</h3></td>
   </tr>
   <form action="department.php" method="post">
   <tr>
    <td>Name</td>
    <td>
     <input type="text" name="name" value='<?php echo $row['name']; ?>'></td>
   </tr>
   <tr>
    <td>
    <?php  
    if ($_REQUEST['action'] != 'View') {
      if($_REQUEST['action'] == 'Edit') { 
       echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
      }
      echo "<input name='action' type='submit' value='"; 
      echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
      echo " Department'>";
    }
    ?>
    <input name="action" type="submit" value="Cancel">
    </td>
   </tr>
  </table>
  <?php
  exit;
  } 
  if (!isset($_REQUEST['action']) || ($_REQUEST['action'] == 'Cancel')
    || ($_REQUEST['action'] == 'Print')) {
  ?>
  <table>
   <tr class='class1'>
   <?php 
   if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
         echo "<td></td>";
   } else {
     echo "
    <td>
    <form name='form1' action='department.php' method='post'>
     <select name='action' onChange='document.form1.submit();'>
      <option value=''>Choose option</option>
      <option value='Add'>Add</option>
      <option value='View'>View</option>
      <option value='Edit'>Edit</option>
      <option value='Delete'>Delete</option>
      <option value='Print'>Print</option>
     </select>
    </td>
     ";
    }
    ?>
    <td colspan='7' style='text-align:center;'><h3>Department</h3></td>
   </tr>
   <tr>
    <th></th>
    <th>Name</th>
   </tr>
   <?php
   $result = mysqli_query($con, "select * from department");
   while($row = mysqli_fetch_array($result)) {
   ?>
    <tr class='class3'>
     <td><input type='radio' name='id' value='<?php echo $row['id']?>'></td>
     <td><?php echo $row['name']?></td>
    </tr>
    <?php
    }
    echo '</form></table>';
     main_footer();
}
?>
