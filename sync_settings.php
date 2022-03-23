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

main_menu($_SESSION['uid'],
  $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Update Sync Settings')) {
  if (filled_out($HTTP_POST_VARS)) {
    if($_REQUEST['s_password1'] != $_REQUEST['s_password2']) {
	  echo msg_box_hotel('Passwords are not equal', 'sync_settings.php', 'Back to sync settings');
	  exit;
	}
    $result = mysqli_query($con, "select * from sync_settings where id=1", $con);
    if(mysqli_num_rows($result) > 0) {
      mysqli_query($con, "UPDATE sync_settings set s_host='{$_REQUEST['s_host']}', 
	   s_path = '{$_REQUEST['s_path']}', s_username = '{$_REQUEST['s_username']}', 
	   s_password = '{$_REQUEST['s_password1']}' where id=1", $con);
	} else {
	  mysqli_query($con, "INSERT INTO sync_settings (s_host, s_path, s_username, s_password)
	    VALUES('{$_REQUEST['s_host']}', '{$_REQUEST['s_path']}', '{$_REQUEST['s_username']}', '{$_REQUEST['s_password1']}')", $con);
	}
  } else {
    echo msg_box_hotel("Please enter correct values for sync settings", "sync_settings.php", "Back to Sync Settings");
	exit;
  }
}
$result = mysqli_query($con, "SELECT * FROM sync_settings", $con);
$row = mysqli_fetch_array($result);
?>
  <table>
   <tr class='class1'>
       <td colspan="4">
        <h3>FTP Sync Inforamtion</h3>
        <form action="sync_settings.php" method="post">
       </td>
      </tr>
      <tr>
      <tr>
       <td>Hostname(IP Address)</td>
       <td><input type="text" name="s_host" size="50" value="<?php echo $row['s_host']?>"></td>
      </tr>
      <tr>
       <td>path information</td>
       <td><input type="text" name="s_path" size="50" value="<?php echo $row['s_path']?>"></td>
      </tr>
       <td>Username</td>
	   <td><input type="text" name="s_username" size="50" value="<?php echo $row['s_username']?>"></td>
      </tr>
      <tr>
       <td>Password</td>
	   <td><input type="password" name="s_password1" size="50" value="<?php echo $row['s_password']?>"></td>
      </tr>
      <tr>
       <td>Re-nter Password</td>
	   <td><input type="password" name="s_password2" size="50" value="<?php echo $row['s_password']?>"></td>
      </tr>
	   <td><input type="submit" name="action" value="Update Sync Settings"></td>
      </tr>
     </form>
    </table>
<?
  main_footer();
?>
