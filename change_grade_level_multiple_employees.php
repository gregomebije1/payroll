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
    print_header('Change grade level for multiple employees', 
	 'change_grade_level_multiple_employees.php', 'Back', $con);
} else {
  main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Change')) {
  if (!isset($_REQUEST["branch_id"])) {
	  echo msg_box("Please specify a branch", "change_grade_level_multiple_employees.php", "Back");
	  
  }  
  else  if ($_REQUEST["branch_id"] == "All") 
    $sql = "update employee set gl_id={$_REQUEST['grade_level']}";
  else if ($_REQUEST["location_id"] == "0")
    $sql = "update employee set gl_id={$_REQUEST['grade_level']} where branch_id={$_REQUEST['branch_id']}";
  else {
    $sql = "update employee set gl_id={$_REQUEST['grade_level']} where 
	  location_id={$_REQUEST['location_id']}";
  }
  $result = mysqli_query($con, $sql);
  if (!$result) {
	  echo msg_box("There was an error executing your request","change_grade_level_multiple_employees.php", "Back");
	  exit;
  } else {
	  echo msg_box("Successfully changed grade level", "change_grade_level_multiple_employees.php", "Back");
	  exit;
  }
}
?>
<table> 
 <tr class="class1">
  <td colspan='4'><h3>Change grade level for multiple employees</h3></td>
 </tr>
 <form action="change_grade_level_multiple_employees.php" method="post" name="form1" id="form1">
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
   <td>Grade Level</td>
   <td>
    <select name="grade_level">
     <?php
	 $sql = "select * from grade_level order by id";
     $result = mysqli_query($con, $sql) or die(mysqli_error($con));
     while($row = mysqli_fetch_array($result)) {
       echo "<option value='{$row['id']}'>{$row['name']}</option>";
     }
     ?>
    </select>
   </td>
  </tr>
  <tr>
    <td>
     <input name="action" type="submit" value="Change">
     <!--<input name='action' type='submit' value='Cancel'>-->
    </td>
   </tr>
   </form>
  </table>