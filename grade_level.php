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
 print_header('Grade Level List', 'grade_level.php', 'Back to Main Menu', $con);
} else {
  main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
    if (empty($_REQUEST['id'])) {
	  echo msg_box("Please choose a Grade Level", 'grade_level.php', 'Back');
	  exit;
	}
	$sql="select * from employee where gl_id={$_REQUEST['id']}";
	if (mysqli_num_rows(mysqli_query($con, $sql)) > 0) {
	  echo msg_box("***DELETION DENIED***<br>
	   There are employees still assigned to this Grade Level.<br>
	   Delete those employees before deleting this Grade Level", 
	   "grade_level.php", "Continue to Delete");
	  exit;
	}
	   
	echo msg_box("***WARNING***<br>
	  Are you sure you want to delete " . 
	  get_value('grade_level', 'name', 'id', $_REQUEST['id'], $con)
	  . " Grade Level?" , 
	   "grade_level.php?action=confirm_delete&id={$_REQUEST['id']}", 
	   'Continue to Delete');
	   exit;
  }
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
    if (empty($_REQUEST['id'])) {
	  echo msg_box("Please choose a Grade Level", 'grade_level.php', 'Back');
	  exit;
	}
	$sql="delete from grade_level where id={$_REQUEST['id']}";
	$result = mysqli_query($con, $sql) or die(mysqli_error($con));
	
	echo msg_box("Grade Level has been deleted", 'grade_level.php', 'Continue');
	exit;
  }
  
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Grade_Level')) {
    if (empty($_REQUEST['name']) || empty($_REQUEST['basic_salary']))  {
       echo msg_box('Please enter Grade Level Name/Basic Salary', 'grade_level.php?action=Form', 'Back');
       exit;
    }
    $sql="select * from grade_level where name='{$_REQUEST['name']}'";
	$result = mysqli_query($con, $sql) or die(mysql_errror());
	$row = mysqli_fetch_array($result);
	if (mysqli_num_rows($result) > 0) {
	  echo msg_box("There is already an existing Grade Level 
	   with the same name as '{$_REQUEST['name']}'<br>
	   Please choose another name", 'grade_level.php', 'Back');
	  exit;
	}
    $sql="insert into grade_level (name, basic_salary)values('{$_REQUEST['name']}', '{$_REQUEST['basic_salary']}')";
    mysqli_query($con, $sql) or die(mysqli_error($con));
	
	echo msg_box("Successfully added", 'grade_level.php', 'Continue');
	exit;

  } else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Grade_Level')) {
	if (empty($_REQUEST['id'])) {
	  echo msg_box('Please choose a Grade Level', 'grade_level.php', 'Back');
	}
	
	$sql="update grade_level set name='{$_REQUEST['name']}', basic_salary='{$_REQUEST['basic_salary']}' where id={$_REQUEST['id']}";
    mysqli_query($con, $sql) or die(mysqli_error($con));
    echo msg_box("Successfully changed", 'grade_level.php', 'OK');
	exit;
  } else if (isset($_REQUEST['action']) && 
   (($_REQUEST['action'] == 'Add') || ($_REQUEST['action'] == 'Edit') || 
    ($_REQUEST['action'] == 'View'))) {
   if ($_REQUEST['action'] != 'Add') {
     if (!isset($_REQUEST['id'])) {
	   echo msg_box("Please choose a grade level", 'grade_level.php', 'Back');
	   exit;
	  }
   }
   $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
   $sql = "select * from grade_level where id=$id";
   $result = mysqli_query($con, $sql);
   $row = mysqli_fetch_array($result);
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3><?php echo $_REQUEST['action']; ?> Salary Structure</h3></td>
   </tr>
   <form action="grade_level.php" method="post">
   <tr>
    <td>Grade Level</td>
    <td>
     <input type="text" name="name" size='30' value='<?php echo $row['name']; ?>'></td>
   </tr>
   <tr>
    <td>Basic Salary</td>
    <td>
     <input type="text" name="basic_salary" size='30' value='<?php echo $row['basic_salary']; ?>'></td>
   </tr>
   <tr>
    <td colspan='2'>
    <?php  
    if($_REQUEST['action'] == 'Edit') { 
      echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
    }
    echo "<input name='action' type='submit' value='"; 
    echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
    echo " Grade_Level'>";
	
	if($_REQUEST['action'] == 'Edit') { 
	  ?>
	  <input name="action" type="submit" value="Delete">
	<?php
	}
	?>
    </td>
   </tr>
   </form>
  </table>
  <?php
  exit;
  } 
  if (!isset($_REQUEST['action']) || ($_REQUEST['action'] == 'Cancel')
    || ($_REQUEST['action'] == 'Print')) {
  ?>
  
  <table>
   <tr class='class1'>
    <td colspan='2'>
	 <table>
	  <tr class='class1'>
   <?php 
   if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
         echo "<td></td>";
   } else {
     echo "<td>
	   <a href='grade_level.php?action=Add'>Add</a>
	   |<a href='change_grade_level_multiple_employees.php'>Grade Level/Multiple Employees
	   |<a href='grade_level.php?action=Print'>Print</a></td> ";
    }
    ?>
	   <td><h3>Branch</h3></td>
      </tr>
	 </table>
	</td>
   </tr>
   <tr>
    <th>Grade Level</th>
	<th>Basic Salary</th>
   </tr>
   <?php
   $result = mysqli_query($con, "select * from grade_level order by name");
   while($row = mysqli_fetch_array($result)) {
   ?>
    <tr class='class3'>
     <td><a href='grade_level.php?action=Edit&id=<?php echo $row['id'];?>'><?php echo $row['name'];?></a></td>
	 <td><?php echo number_format($row['basic_salary'], 2); ?></td>
    </tr>
    <?php
    }
    echo '</table>';
     main_footer();
}
?>
