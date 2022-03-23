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
 print_header('Location List', 'location.php', 'Back to Main Menu', $con);
} else {
  main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose a location", 'location.php', 'Back');
    exit;
  }
  $sql="select * from employee 
    where location_id={$_REQUEST['id']} and status='Enable'";
  if (mysqli_num_rows(mysqli_query($con, $sql)) > 0) {
    echo msg_box("***DELETION DENIED***<br>
     There are employees still attached to this Location.<br>
     Delete those employees before deleting this Location", 
     "location.php", "Continue to Delete");
     exit;
  }
  echo msg_box("***WARNING***<br>
   Are you sure you want to delete " . 
   get_value('location', 'name', 'id', $_REQUEST['id'], $con)
   . " Location?" , 
   "location.php?action=confirm_delete&id={$_REQUEST['id']}", 
   'Continue to Delete');
   exit;
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose a location", 'location.php', 'Back');
    exit;
  }
  $sql="delete from location where id={$_REQUEST['id']}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
	
  echo msg_box("Location has been deleted", 'location.php', 'Continue');
  exit;
}
  
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Location')) {
  if (empty($_REQUEST['name']))  {
    echo msg_box('Please enter Location Name', 'location.php?action=Add', 
    'Back');
    exit;
  }
  $sql="select * from location where name='{$_REQUEST['name']}' 
    and branch_id={$_REQUEST['branch_id']}";
  $result = mysqli_query($con, $sql) or die(mysql_errror());
  $row = mysqli_fetch_array($result);
  if (mysqli_num_rows($result) > 0) {
    echo msg_box("There is already an existing Location 
     with the same name as '{$_REQUEST['name']}'<br>
     Please choose another name", 'location.php', 'Back');
    exit;
  }
  $sql="insert into location (branch_id, name)
    values({$_REQUEST['branch_id']}, '{$_REQUEST['name']}')";
  mysqli_query($con, $sql) or die(mysqli_error($con));
	
  echo msg_box("Successfully added", 'location.php', 'Continue');
  exit;
} 
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Location')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box('Please choose a location', 'location.php', 'Back');
  }
  $sql="update location set name='{$_REQUEST['name']}', 
    branch_id={$_REQUEST['branch_id']} where id={$_REQUEST['id']}";
	
  mysqli_query($con, $sql) or die(mysqli_error($con));
  echo msg_box("Successfully changed", 'location.php', 'OK');
  exit;
} 
if (isset($_REQUEST['action']) && 
  (($_REQUEST['action'] == 'Add') || ($_REQUEST['action'] == 'Edit') || 
   ($_REQUEST['action'] == 'View'))) {
  if ($_REQUEST['action'] != 'Add') {
    if (!isset($_REQUEST['id'])) {
      echo msg_box("Please choose a location", 'location.php', 'Back');
      exit;
    }
  }
  $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
  $sql = "select * from location where id=$id";
  $result = mysqli_query($con, $sql);
  $row = mysqli_fetch_array($result);
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3><?php echo $_REQUEST['action']; ?> Location</h3></td>
   </tr>
   <form action="location.php" method="post">
   <tr>
    <td>Branch</td>
    <td>
     <?php echo selectfield(my_query("select * from branch", 'id', 'name'), 
       'branch_id', $row['branch_id']);?>
    </td>
   </tr>
   <tr>
    <td>Name</td>
    <td>
     <input type="text" name="name" value='<?php echo $row['name']; ?>' 
      size='50'></td>
   </tr>
   <tr>
    <td>
    <?php  
    if($_REQUEST['action'] == 'Edit') { 
       echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
    }
    echo "<input name='action' type='submit' value='"; 
    echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
    echo " Location'>";
	
    if($_REQUEST['action'] == 'Edit') 
       echo "<input name='action' type='submit' value='Delete'>";
	else 
      echo "<input name='action' type='submit' value='Cancel'>";
	echo "
    </td>
   </tr>
  </table>";
  
  exit;
  } 
  if (!isset($_REQUEST['action']) || ($_REQUEST['action'] == 'Cancel')
    || ($_REQUEST['action'] == 'Search')
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
    <a href='location.php?action=Add'>Add</a>
    </td>
     ";
    }
    ?>
    <td colspan='2'>
	 <span><h3>Location</h3></span>
	 <span style='float:right;'>Search
	 <form method='POST' action='location.php'>
      <input type='text' name='search'>
      <input type='submit' name='action' value='Search'>
	 </form>
    </span>
	</td>

   </tr>
   <tr>
    <th>Branch</th>
    <th>Location</th>
   </tr>
   <?php
   $sql= "select b.id, b.name as 'branch', l.name, l.id from location l join branch b 
     on l.branch_id = b.id ";
   
   $sql3 = "";
   if (isset($_REQUEST['action']) && ($_REQUEST['action']== 'Search')){
     $sql3 = " b.name LIKE '%{$_REQUEST['search']}%'
      or l.name LIKE '%{$_REQUEST['search']}%'";
   }
   
   if (!empty($sql3)) {
     $sql .= " where $sql3 order by b.name ";
   } else 
     $sql .= " order by b.name";

   $result = mysqli_query($con, $sql) or die(mysqli_error($con));
   while($row = mysqli_fetch_array($result)) {
    ?>
    <tr class='class3'>
     <td><?php echo $row['branch']; ?></td>
     <td><a href='location.php?action=Edit&id=<?php echo $row['id']; ?>'><?php echo $row['name']; ?></a></td>
    </tr>
    <?php
    }
    ?>
	</table>
    <?php echo main_footer(); 
}
?>
