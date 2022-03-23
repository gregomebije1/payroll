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
  
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Begin Synchronization')) {
  $result1 = mysqli_query($con, "select * from sync_settings", $con);
  if (mysqli_num_rows($result1) <= 0) {
    echo msg_box_hotel("Your sync settings for connecting with external database is empty", 'sync_settings.php', 'Back to sync settings');
	exit;
  }
  $row = mysqli_fetch_array($result1);
  if (!file_exists($_REQUEST['file'])) {
    echo msg_box_hotel("The file {$_REQUEST['file']} does not exist
	  <br>Please re-run the backup/restore process<br>", 
	  'backup_restore.php', 'Back To Backup/Restore');
	exit;
  }
  $source_file = "{$_REQUEST['file']}";
  $destination_file = "{$_REQUEST['file']}";
  $fp = fopen($destination_file, 'r');

  // set up basic connection
  $conn_id = ftp_connect($row['s_host']); 

  // login with username and password
  $login_result = ftp_login($conn_id, $row['s_username'], $row['s_password']); 

  // check connection
  if ((!$conn_id) || (!$login_result)) { 
        echo "FTP connection has failed!<br>";
        echo "Attempted to connect to {$row['s_host']} for user {$row['s_username']} <br>"; 
        exit; 
    } else {
        echo "Connected to {$row['s_host']}, for user {$row['s_username']} <br>";
    }

	// change directory to public_html
   ftp_chdir($conn_id, $row['s_path']);
   echo "Changed directory to {$row['s_path']}<br>";
   ftp_pasv($conn_id, true);
   
   // try to delete $file
   if (ftp_delete($conn_id, $destination_file)) {
     echo "$destination_file deleted successful<br>";
   } else {
     echo "could not delete $destination_file<br>";
   }
   
   // upload the file
   $upload = ftp_fput($conn_id, $destination_file, $fp, FTP_BINARY); 

   // check upload status
  if (!$upload) { 
        echo "FTP upload has failed!<br>";
  } else {
        echo "Uploaded $source_file to {$row['s_host']} as $destination_file<br>";
  }

  // close the FTP stream and the file handler
  ftp_close($conn_id); 
  fclose($fp);
  
  if(!empty($_REQUEST['folder'])) 
    $url = "/{$_REQUEST['folder']}/backup_restore.php?action=Restore&file=$source_file";
  else 
    $url = "/backup_restore.php?action=Restore&file=$source_file";
  echo "$url<br>";
  
  #### Now run sync_restore.php ####
  $fp = fsockopen($row['s_host'], 80, $errno, $errstr, 30);
  if (!$fp) {
    echo "$errstr ($errno)<br />\n";
  } else {	
	$out = "GET  $url HTTP/1.1\r\n";
    $out .= "Host: {$row['s_host']}\r\n";
    $out .= "Connection: Close\r\n\r\n";
	
    fwrite($fp, $out);
	/*
	 while (!feof($fp)) {
        echo fgets($fp, 128);
    }
	*/
    fclose($fp);
  }

  echo "<h3>Database synchronized with that of foreign server</h3>";
  echo "Continue to <a href='hotel/index.php'>HomePage</a>";
  exit;
}
 
 $result2 = mysqli_query($con, "select * from sync_settings", $con);
 $row = mysqli_fetch_array($result2);
?>
  <table>
   <tr class='class1'>
       <td colspan="4">
        <h3>FTP Sync Inforamtion</h3>
        <form action="sync.php" method="post">
       </td>
      </tr>
      <tr>
      <tr>
       <td>Hostname(IP Address)</td>
       <td><input type="text" name="s_host" size="50" value="<?php echo $row['s_host']?>" disabled="disabled"></td>
      </tr>
      <tr>
       <td>path information</td>
       <td><input type="text" name="s_path" size="50" value="<?php echo $row['s_path']?>" disabled="disabled"></td>
      </tr>
       <td>Username</td>
	   <td><input type="text" name="s_username" size="50" value="<?php echo $row['s_username']?>" disabled="disabled"></td>
      </tr>
      <tr>
       <td>Password</td>
	   <td><input type="password" name="s_password1" size="50" value="<?php echo $row['s_password']?>" disabled="disabled"></td>
      </tr>
      <tr>
       <td>Re-nter Password</td>
	   <td><input type="password" name="s_password2" size="50" value="<?php echo $row['s_password']?>" disabled="disabled"> </td>
      </tr>
	  <tr>
       <td>Backup File</td>
	   <td><input type="text" name="file" size="50" value="backup.sql"></td>
      </tr>
	  <tr>
	  <td>Url folder (Leave as blank if installing in the root folder of your web server)</td>
	  <td><input type="text" name="folder" size="50"></td>
      </tr>
	  
	   <td><input type="submit" name="action" value="Begin Synchronization"></td>
      </tr>
     </form>
    </table>
<?
  main_footer();
?>
