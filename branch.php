<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
error_reporting(E_ALL);

require_once "ui.inc";
require_once "util.inc";

$con = connect();
if (!user_type($_SESSION['uid'], 'Administrator', $con)) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
  exit;
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
 print_header('Branch List', 'branch.php', 'Back to Main Menu', $con);
} else {
  main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose a Branch", 'branch.php', 'Back');
    exit;
  }
  $sql="select * from employee where branch_id={$_REQUEST['id']}";
  if (mysqli_num_rows(mysqli_query($con, $sql)) > 0) {
    echo msg_box("***DELETION DENIED***<br>
      There are employees still attached to this Branch.<br>
      Delete those employees before deleting this Branch", 
      "branch.php", "Continue to Delete");
      exit;
  }
  $sql="select * from location where branch_id={$_REQUEST['id']}";
  if (mysqli_num_rows(mysqli_query($con, $sql)) > 0) {
    echo msg_box("***DELETION DENIED***<br>
      There are location(s) still attached to this Branch.<br> 
      Delete those location(s)before deleting this Branch", 
      "branch.php", "Continue to Delete");
      exit;
  }	   
  echo msg_box("***WARNING***<br>
    Are you sure you want to delete " . 
    get_value('branch', 'name', 'id', $_REQUEST['id'], $con)
     . " Branch?" , 
    "branch.php?action=confirm_delete&id={$_REQUEST['id']}", 
    'Continue to Delete');
   exit;
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose a Branch", 'branch.php', 'Back');
    exit;
  }
  $sql="delete from branch where id={$_REQUEST['id']}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));

  echo msg_box("Branch has been deleted", 'branch.php', 'Continue');
  exit;
}
  
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Branch')) {
  if (empty($_REQUEST['name']))  {
    echo msg_box('Please enter Branch Name', 'branch.php?action=Add', 'Back');
    exit;
  }
  $sql="select * from branch where name='{$_REQUEST['name']}'";
  $result = mysqli_query($con, $sql) or die(mysql_errror());
  $row = mysqli_fetch_array($result);
  if (mysqli_num_rows($result) > 0) {
    echo msg_box("There is already an existing Branch 
      with the same name as '{$_REQUEST['name']}'<br>
      Please choose another name", 'branch.php', 'Back');
    exit;
  }
  $sql="insert into branch (name)values('{$_REQUEST['name']}')";
  mysqli_query($con, $sql) or die(mysqli_error($con));
	
  echo msg_box("Successfully added", 'branch.php', 'Continue');
  exit;

} 
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Branch')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box('Please choose a Branch', 'branch.php', 'Back');
  }
  $sql="select * from branch where name='{$_REQUEST['name']}'";
  $result = mysqli_query($con, $sql) or die(mysql_errror());
  if (mysqli_num_rows($result) > 0) {
    echo msg_box("There is already an existing Branch 
     with the same name as '{$_REQUEST['name']}'<br>
     Please choose another name", 'branch.php', 'Back');
     exit;
  }
  $sql="update branch set name='{$_REQUEST['name']}' 
    where id={$_REQUEST['id']}";
  mysqli_query($con, $sql) or die(mysqli_error($con));
  echo msg_box("Successfully changed", 'branch.php', 'OK');
  exit;
} 
if (isset($_REQUEST['action']) && 
  (($_REQUEST['action'] == 'Add') || ($_REQUEST['action'] == 'Edit') || 
  ($_REQUEST['action'] == 'View'))) {
  if ($_REQUEST['action'] != 'Add') {
    if (!isset($_REQUEST['id'])) {
      echo msg_box("Please choose a Branch", 'branch.php', 'Back');
      exit;
    }
  }
  $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
  $sql = "select * from branch where id=$id";
  $result = mysqli_query($con, $sql);
  $row = mysqli_fetch_array($result);
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3><?php echo $_REQUEST['action']; ?> Branch</h3></td>
   </tr>
   <form action="branch.php" method="post">
   <tr>
    <td>Name</td>
    <td>
     <input type="text" name="name" value='<?php echo $row['name']; ?>'></td>
   </tr>
   <tr>
    <td>
    <?php  
    if($_REQUEST['action'] == 'Edit') { 
       echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
    }
    echo "<input name='action' type='submit' value='"; 
    echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
    echo " Branch'>";
    if ($_REQUEST['action'] == 'Edit') 
	  echo "<input name='action' type='submit' value='Delete' />"; 
    else 
	  echo "<input name='action' type='submit' value='Cancel'>";
	echo "
    </td>
   </tr>
  </table>";
  
  exit;
  } 
  if (!isset($_REQUEST['action']) || ($_REQUEST['action'] == 'Cancel')
    || ($_REQUEST['action'] == 'Print')) {
  ?>
  <table>
   <tr class='class1'>
   <?php 
     echo "
    <td>
	 <table>
	  <tr class='class1'>
	   <td><a href='branch.php?action=Add'>Add</a></td>
	   <td><h3>Branch</h3></td>
      </tr>
	 </table>
	</td>
   </tr>
   <tr>
    <th>Name</th>
   </tr>";
   
   $result = mysqli_query($con, "select * from branch order by id");
   while($row = mysqli_fetch_array($result)) {
     echo "
    <tr class='class3'>
     <td><a href='branch.php?action=Edit&id={$row['id']}'>{$row['name']}</a></td>
    </tr>";
    }
    echo '</table>';
     main_footer();
}
?>
