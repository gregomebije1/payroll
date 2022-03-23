<?php 
require_once "util.inc";
require_once "connect.inc";

$con = connect();

$tables = array('hotel_info', 'sales', 'audit_trail', 'permissions', 'user_permissions', 'user',
	  'room_category', 'room', 'department', 'guest', 'guest_checked_out', 'booking', 'group_company_guests', 'account', 
	  'account_type', 'profile', 'entity');
$dir_path = 'C:\\backup';	
	
foreach($tables as $name) {
  $sql = "backup table $name to 'c:\\\backup'";
  $result = mysqli_query($con, $sql);
  echo "Table $name backed up<br>";
}
//unset($tables);


$conn_id = ftp_connect('mangital.com');
echo "Connected to mangital.com<br>";
$login_result = ftp_login($conn_id, "mangital", 't@ke1t0rle@ve1t2');
echo "Logging into mangital<br>";
  
/*Solution 1*/
/*
$file = "c:\\backup\\account.frm";
if (ftp_alloc($conn_id, filesize($file), $result)) {
  echo "Space successfully allocated on server.  Sending $file.\n";
  ftp_put($conn_id, '/public_ftp/account.frm', $file, FTP_BINARY);
} else {
  echo "Unable to allocate space on server.  Server said: $result\n";
}
$file = "c:\\backup\\account.MYD";
if (ftp_alloc($conn_id, filesize($file), $result)) {
  echo "Space successfully allocated on server.  Sending $file.\n";
  ftp_put($conn_id, '/public_ftp/account.frm', $file, FTP_BINARY);
} else {
  echo "Unable to allocate space on server.  Server said: $result\n";
}
*/
/* Solution 2 */
foreach($tables as $name) {
  $file = "c:\\backup\\{$name}.frm";
  if (ftp_alloc($conn_id, filesize($file), $result)) {
    echo "Space successfully allocated on server.  Sending $file.\n";
    ftp_put($conn_id, '/public_ftp/account.frm', $file, FTP_BINARY);
  } else {
    echo "Unable to allocate space on server.  Server said: $result\n";
  }
  $file = "c:\\backup\\{$name}.MYD";
  if (ftp_alloc($conn_id, filesize($file), $result)) {
    echo "Space successfully allocated on server.  Sending $file.\n";
    ftp_put($conn_id, '/public_ftp/account.frm', $file, FTP_BINARY);
  } else {
    echo "Unable to allocate space on server.  Server said: $result\n";
  }
}


/*
$dir = opendir($dir_path);
echo "Reading files from backup $dir_path<br>";
  
while ($filename = readdir($dir)) {
  if (($filename == '.') || ($filename == '..')) {
    continue;
  }  
  $r_filename="$dir_path\\{$filename}";
  echo "Reading $r_filename<br>";
  if (ftp_alloc($conn_id, filesize($r_filename), $result)) {
    echo "Space successfully allocated on server.  Sending $r_filename.<br>";
    ftp_put($conn_id, '/public_html/$filename', $r_filename, FTP_BINARY);
  } else {
    echo "Unable to allocate space on server.  Server said: $result<br>";
  }
}


#### Now run sync_restore.php ####
$fp = fsockopen("192.168.72.129", 80, $errno, $errstr, 30);
if (!$fp) {
  echo "$errstr ($errno)<br />\n";
} else {
  $out = "GET /hotel/sync_restore.php HTTP/1.1\r\n";
  $out .= "Host: 192.168.72.129\r\n";
  $out .= "Connection: Close\r\n\r\n";

  fwrite($fp, $out);
  while (!feof($fp)) {
	echo fgets($fp, 128);
  }
  fclose($fp);
}
ftp_close($conn_id);
*/
echo "<h3>Database backuped</h3>";
echo "Continue to <a href='../index.php'>HomePage</a>";
  
?>
