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

$temp = get_user_perm($_SESSION['uid'], $con);
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Print')) {
  print_header('User List', 'users.php', '', $con);
} else {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete')) {
    if (empty($_REQUEST['id'])) {
	  echo msg_box("Please choose a user", 'users.php', 'Back');
       exit;
    }
	if ($_REQUEST['id'] == $_SESSION['uid']) {
      echo msg_box("Deletion denied<br>
       You cannot delete this user while logged in ", 'users.php', 'Back');
      exit;
    }
	if ((!user_type($_SESSION['uid'], 'Administrator', $con)) && ($_SESSION['uid'] != $_REQUEST['id'])) {
      echo msg_box("Deletion denied<br>
       Security Alert***You cannot delete someone elses account ", 'users.php', 'Back');
      exit;
    }
    echo msg_box("Are you sure you want to delete " . 
     get_value('user', 'name', 'id', $_REQUEST['id'], $con)
     . " User?" , 
     "users.php?action=confirm_delete&id={$_REQUEST['id']}", 
     'Continue to Delete');
     exit;
  }
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm_delete')) {
    if (empty($_REQUEST['id'])) {
      echo msg_box("Please choose a User", 'users.php', 'Back');
      exit;
    }
	if ($_REQUEST['id'] == $_SESSION['uid']) {
	  echo msg_box("Deletion denied<br>
       You cannot delete this user while logged in ", 'users.php', 'Back');
      exit;
    }
	if ((!user_type($_SESSION['uid'], 'Administrator', $con)) && ($_SESSION['uid'] != $_REQUEST['id'])) {
      echo msg_box("Deletion denied<br>
       Security Alert***You cannot delete someone elses account ", 'users.php', 'Back');
      exit;
    }
    $sql="select * from user where id={$_REQUEST['id']}";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    if (mysqli_num_rows($result) <= 0) {
      echo msg_box("User does not exist in the database", 'users.php', 'OK');
      exit;
    }
    $sql="delete from user where id={$_REQUEST['id']}";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
	
    mysqli_query($con, "DELETE FROM user_permissions where uid=". $_REQUEST['id']) 
     or die(mysqli_error($con));
	
    echo msg_box("User has been deleted", 'users.php', 'OK');
    exit;
  }
  
  //Change Password UI
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Change Password')) {
  ?>
  <form name='form1' action="users.php" method="post">
   <table> 
    <tr class='class1'>
     <td colspan='3'><h3><?php echo $_REQUEST['action']; ?> User</h3></td>
    </tr>
    <tr>
     <td>Password</td>
     <td><input type="password" name="password1"></td>
	</tr>
	 
    <tr>
     <td>Retype Password</td>
     <td><input type="password" name="password2"></td>
	</tr>
    <?php echo "<input type='hidden' name='id' value='{$_REQUEST['id']}'>"; ?>	
	<tr><td><input type='submit' name='action' value='Update Password'></td></tr>
   </table>
   
  </form>
  <?php
  }
  
  //Change Password
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Password')) {
    if (empty($_REQUEST['id'])) {
      echo msg_box("Please choose a user", 'users.php', 'Back');
      exit;
    }
	if ((!user_type($_SESSION['uid'], 'Administrator', $con)) && ($_SESSION['uid'] != $_REQUEST['id'])) {
      echo msg_box("Security Alert: You cannot change someone elses password ", 'users.php', 'Back');
      exit;
    }
    if (empty($_REQUEST['password1']) || empty($_REQUEST['password2'])) {
      echo msg_box('Please enter correct passwords', "users.php", 'Back');
      exit;
    }
    if ($_REQUEST['password1'] != $_REQUEST['password2']) {
      echo msg_box('Passwords are not equal', "users.php", 'Back');
      exit;
    }
    
    $sql="update user set passwd = sha1('" . $_REQUEST['password1'] . "')  where id=" . $_REQUEST['id'];
    mysqli_query($con, $sql) or die(mysqli_error($con));
	
	echo msg_box("Password Changed", 'users.php', 'Continue');
	exit;
  }
	
	
  //Update User Details
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update User')) {
    if (empty($_REQUEST['id'])) {
      echo msg_box("Please choose a user", 'users.php', 'Back');
       exit;
    }
	
	
	//Change users firstname and lastname
    
    $sql="update user set firstname='{$_REQUEST['firstname']}', 
       lastname='{$_REQUEST['lastname']}' where id={$_REQUEST['id']}";
    mysqli_query($con, $sql) or die(mysqli_error($con));
 
    //First delete all the permissions for this user
    $sql="delete from user_permissions where uid={$_REQUEST['id']}";
    mysqli_query($con, $sql) or die(mysqli_error($con));
    
	//echo "{$_REQUEST['u_permissions_members']}<br />";
	
    if (!empty($_REQUEST['u_permissions_members'])) {
	  //Then loop through the permissions
	  $data = explode("|", $_REQUEST['u_permissions_members']);
	  
	  foreach ($data as $pid) {
        //There should be a more efficient way
        $sql="insert into user_permissions(uid, pid) values ({$_REQUEST['id']}, $pid)";
        mysqli_query($con, $sql) or die(mysqli_error($con));
      }
    }
	
    echo msg_box("User details have been changed", 'users.php', 'Continue');
    exit;
  }
  
  if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Add User')) {
    if (empty($_REQUEST['username']) || empty($_REQUEST['firstname']) 
      || empty($_REQUEST['lastname'])) {
      echo msg_box("Please fill out the form", 'users.php?action=Add', 'Back');
      exit;
    }
    if (empty($_REQUEST['password1']) || empty($_REQUEST['password2'])) {
      echo msg_box('Please enter the passwords','users.php?action=Add', 'Back');
        exit;
    }
    if ($_REQUEST['password1'] != $_REQUEST['password2']) {
      echo msg_box('Passwords are not equal', 'users.php?action=Add', 'Back');
       exit;
    }
	
    $sql = "select * from user where name='{$_REQUEST['username']}'";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    if (mysqli_num_rows($result) > 0) {
      echo msg_box("There is already another user with the same username<b>
       Please choose another user", 'users.php?action=Add', 'Back');
      exit;
    }
	
    $sql="insert into user(name, passwd, entity_id, firstname, lastname)
       values('{$_REQUEST['username']}', sha1('{$_REQUEST['password1']}'), 
       '1', '{$_REQUEST['firstname']}', '{$_REQUEST['lastname']}')";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    $uid = mysqli_insert_id();
    
    $data = explode("|", $_REQUEST['u_permissions_members']);
	foreach ($data as $pid) {
      $sql="insert into user_permissions(uid, pid) values ($uid, $pid)";
	  mysqli_query($con, $sql) or die(mysqli_error($con));
    }
    echo msg_box("{$_REQUEST['username']} inserted", 'users.php', 'Continue');
	
 } 
 
 if (isset($_REQUEST['action']) &&  (($_REQUEST['action'] == 'Add')  || ($_REQUEST['action'] == 'Edit'))) {
   //Only admin can access this part of the module
   if (!user_type($_SESSION['uid'], 'Administrator', $con)) {
       echo msg_box('Only Administrator can do that!', 
        'users.php', 'Back');
       exit;
   }
   $id = 0;
   if ($_REQUEST['action'] != 'Add') {
     $id = $_REQUEST['id'];
     if (empty($_REQUEST['id'])) {
       echo msg_box("Please choose a user", 'users.php', 'Back');
       exit;
     }
   }
   $sql="select * from user where id = $id";
	 $result = mysqli_query($con, $sql) or die(mysqli_error($con));
     $row = mysqli_fetch_array($result);
   ?>
   <form name='form1' action="users.php" method="post">
   <table> 
    <tr class='class1'>
     <td colspan='3'><h3><?php echo $_REQUEST['action']; ?> User</h3></td>
    </tr>
    <tr>
     <td>Username</td>
     <td><input type="text" name="username"
     <?php 
     if (($_REQUEST['action'] == 'Edit') || ($_REQUEST['action'] == 'View')) 
       echo "value = '{$row['name']}' disabled='disabled'>";
     else 
       echo ">";
     
     ?>
	 </td></tr>
	 <?php
	 if ($_REQUEST['action'] == 'Add') {
	 ?>
    <tr>
     <td>Password</td>
     <td><input type="password" name="password1"></td>
	</tr>
	 
    <tr>
     <td>Retype Password</td>
     <td><input type="password" name="password2"></td>
	</tr>
	<?php
	}
	?>
	<tr>
     <td>Firstname</td>
     <td>
      <input type="text" name="firstname" value='<?php echo $row['firstname']; ?>'>
     
     <?php
	 if ($_REQUEST['action'] == 'Edit') {
	   echo "<a href='users.php?action=Change Password&id={$_REQUEST['id']}'>Change Password</td>";
	 }
	?>
	
    </tr>
    <tr>
     <td>Lastname</td>
     <td>
      <input type="text" name="lastname" value='<?php echo $row['lastname']; ?>'>
     </td>
    </tr>
    <tr class='class1'><td>To</td><td>From</td></tr>  
	<tr style='display:inline;'>
	 <td colspan='2' style='width:50em;'>
      <table style='table-layout:fixed;'>
       <tr>
        <td>
	     <?php
	      if (($_REQUEST['action'] == 'Add')||($_REQUEST['action'] == 'Edit')) {
         //Get all permissions
          $sql="select * from permissions ";
		 
          if($_REQUEST['action'] == 'Edit') {
            $sql .= " where id 
             not in (select pid from user_permissions 
             where uid={$_REQUEST['id']})";
          }
          $result = mysqli_query($con, $sql) or die(mysqli_error($con));
		  
          echo "<select name='pid' size='10' id='pid'>";
			
          while ($row = mysqli_fetch_array($result)) {
            echo "<option value='{$row['id']}'>{$row['name']}</option>";
          }
          echo "
             </select>
	         </td>
			<td>
			 <table style='border: solid black 0.0em;'>
			  <tr>
				   <td>
				<a name='adds' id='adds' onClick='transfer();'>
					 <img src='images/next.gif'></a>
			   </td>
				  </tr>
				  <tr>
				   <td>
				<a name='dels' id='dels' onClick='transfer2();'>
					 <img src='images/prev.gif'></a>
			   </td>
				  </tr>
			 </table>
			</td>
			";
	    }
	?>
	<td>
     <select size='10' id='u_permissions' name='u_permissions'>
	 <?php 
	 if($_REQUEST['action'] == 'Edit'){
	   $sql ="select p.id, p.name as 'permission_name' from permissions p join user_permissions up on 
	     (up.pid = p.id) where up.uid={$_REQUEST['id']}";
	   $result = mysqli_query($con, $sql) or die(mysqli_error($con));
	   while ($row = mysqli_fetch_array($result)) {
	     echo "<option value='{$row['id']}'>{$row['permission_name']}</option>";
	   } 
	 }
	 ?>
      </select>
	   <script language='javascript' src='accounting.js'>get_permissions_in_s_permissions();</script>
      <input type='hidden' name='u_permissions_members'>
    </td>
   </tr>
  </table>
 </td>
</tr>
   
     <?php  
     if($_REQUEST['action'] == 'Edit') { 
         echo "<input name='id' type='hidden' value='{$_REQUEST['id']}'>";
     }
     echo "<tr><td><input name='action' type='submit' value='"; 
     echo $_REQUEST['action'] == 'Edit' ? 'Update' : 'Add';
     echo " User'></td>";
     
	 if ($_REQUEST['action'] == 'Edit') {
	   echo "<td><input name='action' type='submit' value='Delete'></td>";
	 }
	 ?>
	 </tr>
   </table>
   </form>
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
	     if (user_type($_SESSION['uid'], 'Administrator', $con))
		   echo "<td><a href='users.php?action=Add'>Add</a></td>";
	  }
      ?> 
     </select>
    </td>
    <td colspan='4' style='text-align:center;'><h3>Users List</h3></td>
   </tr>
   <tr>
    <th>Username</th>
    <th>Firstname</th>
    <th>Lastname</th>
    <th>Permission</th>
   </tr>
   <?php
   //Admin privilege means you can access all users
   if (in_array('Administrator', get_user_perm($_SESSION['uid'], $con)))
     $sql="select * from user";
   else
     $sql="select * from user where id={$_SESSION['uid']}";
	 
   $result = mysqli_query($con, $sql) or die(mysqli_error($con));
   while ($row = mysqli_fetch_array($result)) {
     echo "
      <tr class='class3'>";
	  
	  if (in_array('Administrator', get_user_perm($_SESSION['uid'], $con)))
        echo "<td><a href='users.php?id={$row['id']}&action=Edit'>{$row['name']}</td>";
	  else
	   echo "<td><a href='users.php?id={$row['id']}&action=Change Password'>{$row['name']}</td>";
	   
	  echo "
       <td>{$row['firstname']}</td>
       <td>{$row['lastname']}</td>
	   <td>";
	
	 $sql="select up.id, p.name from user_permissions up join permissions p on (up.pid = p.id)
	   where up.uid={$row['id']}";
	 $result2 = mysqli_query($con, $sql) or die(mysqli_error($con));
	 while($row2 = mysqli_fetch_array($result2))
	   echo "{$row2['name']}, ";
	 echo "</td>
     </tr>";
   }
   echo "</table>";
   main_footer();
}
?>
