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
 print_header('Location List', 'payee_item.php', 'Back to Main Menu', $con);
} else {
  main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose a payee item", 'payee_item.php', 'Back');
    exit;
  }
  $sql="select * from employee 
    where location_id={$_REQUEST['id']} and status='Enable'";
  if (mysqli_num_rows(mysqli_query($con, $sql)) > 0) {
    echo msg_box("***DELETION DENIED***<br>
     There are employees still attached to this Location.<br>
     Delete those employees before deleting this Location", 
     "payee_item.php", "Continue to Delete");
     exit;
  }
  echo msg_box("***WARNING***<br>
   Are you sure you want to delete " . 
   get_value('payee_item', 'name', 'id', $_REQUEST['id'], $con)
   . " ?" , 
   "payee_item.php?action=confirm_delete&id={$_REQUEST['id']}", 
   'Continue to Delete');
   exit;
}
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box("Please choose a location", 'payee_item.php', 'Back');
    exit;
  }
  $sql="delete from location where id={$_REQUEST['id']}";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
	
  echo msg_box("Delete Successfull", 'payee_item.php', 'Continue');
  exit;
}
  
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add Payee Item')) {
  if (empty($_REQUEST['name']))  {
    echo msg_box('Please enter Payee Item', 'payee_item.php?action=Add', 'Back');
    exit;
  }
  if (empty($_REQUEST['amount']))  {
    echo msg_box('Please enter amount', 'payee_item.php?action=Add', 'Back');
    exit;
  }
  $sql="select * from payee_item where name='{$_REQUEST['name']}'";
  $result = mysqli_query($con, $sql) or die(mysql_errror());
  $row = mysqli_fetch_array($result);
  if (mysqli_num_rows($result) > 0) {
    echo msg_box("There is already an existing payee item
     with the same name as '{$_REQUEST['name']}'<br>
     Please choose another name", 'payee_item.php', 'Back');
    exit;
  }
  $sql="insert into payee_item (name, amount, payee_item_type, payee_item_group)
    values('{$_REQUEST['name']}', '{$_REQUEST['amount']}', '{$_REQUEST['type']}', '{$_REQUEST['group']}')";
  mysqli_query($con, $sql) or die(mysqli_error($con));
	
  echo msg_box("Successfully added", 'payee_item.php', 'Continue');
  exit;
} 
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Payee Item')) {
  if (empty($_REQUEST['id'])) {
    echo msg_box('Please choose a Payee Item', 'payee_item.php', 'Back');
  }
  $sql="update payee_item set name='{$_REQUEST['name']}', 
    amount='{$_REQUEST['amount']}', payee_item_type='{$_REQUEST['type']}', payee_item_group='{$_REQUEST['group']}' where id={$_REQUEST['id']}";
	
  //echo "$sql<br>";
  mysqli_query($con, $sql) or die(mysqli_error($con));
  echo msg_box("Successfully changed", 'payee_item.php', 'OK');
  exit;
} 
if (isset($_REQUEST['action']) && 
  (($_REQUEST['action'] == 'Add') || ($_REQUEST['action'] == 'Edit') || 
   ($_REQUEST['action'] == 'View'))) {
  if ($_REQUEST['action'] != 'Add') {
    if (!isset($_REQUEST['id'])) {
      echo msg_box("Please choose a payee item", 'payee_item.php', 'Back');
      exit;
    }
  }
  $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
  $sql = "select * from payee_item where id=$id";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  $row = mysqli_fetch_array($result);
  ?>
  <table> 
   <tr class="class1">
    <td colspan="4"><h3><?php echo $_REQUEST['action']; ?>Payee Item</h3></td>
   </tr>
   <form action="payee_item.php" method="post">
   <tr>
    <td>Name</td>
    <td><input type="text" name="name" value='<?php echo $row['name']; ?>' size='50'></td>
   </tr>
   <tr>
    <td>Type</td>
	<td>
	 <select name='type'>
	  <option value='percentage'>Percentage</option>
	  <option value='value'>Value</option>
	 </select>
	</td>
   </tr>
   <tr>
    <td>Percentage/Amount</td>
    <td><input type="text" name="amount" value='<?php echo $row['amount']; ?>' size='50'></td>
   </tr>
   <tr>
    <td>Group</td>
	<td>
	 <select name='group'>
	  <option value='paye'>Payee</option>
	  <option value='reliefs'>Reliefs</option>
	  <option value='exemptions'>Exemptions</option>
	 </select>
	</td>
   </tr>
   <tr>
    <td>
    <?php  
    if($_REQUEST['action'] == 'Edit') { 
       echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
    }
    echo "<input name='action' type='submit' value='"; 
    echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
    echo " Payee Item'>";
	
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
    || ($_REQUEST['action'] == 'Print')) {
  ?>
  <table>
   <tr class='class1'>
   <?php 
   if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
         echo "<td></td>";
   } else {
     echo "
    <td colspan='2'>
    <h3><a href='payee_item.php?action=Add'>Add</a></h3></td>
     ";
    }
    ?>
   </tr>
   <tr class='class1'>
    <th>Name</th>
	<th>Amount</th>
   </tr>
   <?php
   $arr = array('paye', 'reliefs', 'exemptions');
   foreach($arr as $group) {
     echo "<tr><th colspan='2'>" . strtoupper($group) . "</th></tr>";
     $sql= "select * from payee_item where payee_item_group='{$group}'";
     $result = mysqli_query($con, $sql) or die(mysqli_error($con));
     while($row = mysqli_fetch_array($result)) {
     ?>
     <tr class='class3'>
     <td><?php echo $row['name']; ?></td>
     <td><a href='payee_item.php?action=Edit&id=<?php echo $row['id']; ?>'>
	   <?php echo $row['amount'];
             echo ($row['payee_item_type'] == 'percentage') ? '%' : ''; ?></a></td>
     </tr>
     <?php
     }
  }
  ?>
  </table>
    <?php echo main_footer(); 
}
?>
