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
 print_header('Bank List', 'bank.php', 'Back to Main Menu', $con);
} else {
  main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
    if (empty($_REQUEST['id'])) {
	  echo msg_box("Please choose a bank", 'bank.php', 'Back');
	  exit;
	}
	$sql="select * from employee where bank_id={$_REQUEST['id']} and status='Enable'";
	if (mysqli_num_rows(mysqli_query($con, $sql)) > 0) {
	  echo msg_box("***DELETION DENIED***<br>
	   There are employees still attached to this Bank.<br>
	   Delete those employees before deleting this Bank", 
	   "bank.php", "Continue to Delete");
	  exit;
	}
	   
	echo msg_box("***WARNING***<br>
	  Are you sure you want to delete " . 
	  get_value('bank', 'name', 'id', $_REQUEST['id'], $con)
	  . " Bank?" , 
	   "bank.php?action=confirm_delete&id={$_REQUEST['id']}", 
	   'Continue to Delete');
	   exit;
  }
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
    if (empty($_REQUEST['id'])) {
	  echo msg_box("Please choose a bank", 'bank.php', 'Back');
	  exit;
	}
	$sql="delete from bank where id={$_REQUEST['id']}";
	$result = mysqli_query($con, $sql) or die(mysqli_error($con));
	
	echo msg_box("Bank has been deleted", 'bank.php', 'Continue');
	exit;
  }
  
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Bank')) {
    if (empty($_REQUEST['name']))  {
       echo msg_box('Please enter Bank Name', 'bank.php?action=Form', 'Back');
       exit;
    }
    $sql="select * from bank where name='{$_REQUEST['name']}'";
	$result = mysqli_query($con, $sql) or die(mysql_errror());
	$row = mysqli_fetch_array($result);
	if (mysqli_num_rows($result) > 0) {
	  echo msg_box("There is already an existing Bank 
	   with the same name as '{$_REQUEST['name']}'<br>
	   Please choose another name", 'bank.php', 'Back');
	  exit;
	}
    $sql="insert into bank (name, address)values('{$_REQUEST['name']}', '{$_REQUEST['address']}')";
    mysqli_query($con, $sql) or die(mysqli_error($con));
	
	echo msg_box("Successfully added", 'bank.php', 'Continue');
	exit;

  } else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Bank')) {
	if (empty($_REQUEST['id'])) {
	  echo msg_box('Please choose a bank', 'bank.php', 'Back');
	}
	/*
	$sql="select * from bank where name='{$_REQUEST['name']}'";
	$result = mysqli_query($con, $sql) or die(mysql_errror());
	if (mysqli_num_rows($result) <= 0) {
	  echo msg_box("There is no existing Bank 
	   with the same name as '{$_REQUEST['name']}'<br>
	   Please choose another name", 'bank.php', 'Back');
	  exit;
	}
	*/
	$sql="update bank set name='{$_REQUEST['name']}', 
	 address='{$_REQUEST['address']}' where id={$_REQUEST['id']}";
	
    mysqli_query($con, $sql) or die(mysqli_error($con));
    echo msg_box("Successfully changed", 'bank.php', 'OK');
	exit;
  } else if (isset($_REQUEST['action']) && 
   (($_REQUEST['action'] == 'Add') || ($_REQUEST['action'] == 'Edit') || 
    ($_REQUEST['action'] == 'View'))) {
   if ($_REQUEST['action'] != 'Add') {
     if (!isset($_REQUEST['id'])) {
	   echo msg_box("Please choose a bank", 'bank.php', 'Back');
	   exit;
	  }
   }
   $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
   $sql = "select * from bank where id=$id";
   $result = mysqli_query($con, $sql);
   $row = mysqli_fetch_array($result);
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3><?php echo $_REQUEST['action']; ?> Bank</h3></td>
   </tr>
   <form action="bank.php" method="post">
   <tr>
    <td>Name</td>
    <td>
     <input type="text" name="name" value='<?php echo $row['name']; ?>'></td>
   </tr>
   <tr>
    <td>Address</td>
    <td><textarea name='address'><?php echo $row['name'];?></textarea></td>
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
      echo " Bank'>";
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
    <form name='form1' action='bank.php' method='post'>
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
    <td colspan='7' style='text-align:center;'><h3>Bank</h3></td>
   </tr>
   <tr>
    <th></th>
    <th>Name</th>
   </tr>
   <?php
   $result = mysqli_query($con, "select * from bank");
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
