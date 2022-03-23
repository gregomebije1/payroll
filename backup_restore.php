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

if (isset($_REQUEST['action']) && 
  (($_REQUEST['action'] == 'Backup') || ($_REQUEST['action'] == 'Restore'))) {
  if (empty($_REQUEST['file'])) {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
    echo msg_box('Please enter the file to backup or restore from', 
     'backup_restore.php', 'Back To Backup/Restore');
    exit;
  }
  
  $filename = "backup.sql";  

  if ($_REQUEST['action'] == 'Restore') {
    if(empty($_FILES['file']['name'])) {
	  main_menu($_SESSION['uid'],
       $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
	  echo msg_box('Please enter the file to backup or restore from', 
       'backup_restore.php', 'Back To Backup/Restore');
      exit;
    }
    $dir = '.'; 
    $url="backup_restore.php";
    if (!empty($_FILES['file']['name'])) {
      if ($_FILES['file']['error'] != 4) {  
        //Lets upload the file
        if ($_FILES['file']['error'] > 0) {
          switch($_FILES['file']['error']) {
            case 1: echo msg_box('File exceeded upload max_filesize', 
              $url, 'OK'); break;
            case 2: echo msg_box('File exceeded max_file_size', 
              $url, 'OK'); break;
            case 3: echo msg_box('File only partially uploaded', 
              $url, 'OK'); break;
          }
          exit;
        } else {
	      $upfile = $_FILES['file']['name'];
          if(is_uploaded_file($_FILES['file']['tmp_name'])) {
            if(!move_uploaded_file($_FILES['file']['tmp_name'], $upfile)) {
              echo msg_box('Problem: Could not move file to destination
                 directory', $url, 'OK');
              exit;
            }
          } else {
            echo msg_box("Problem: Possible file upload attack. Filename: " .
              $_FILES['file']['name'], $url, 'OK');
            exit;
          } 
        } 
      }
    }
    $lines = file("{$_FILES['file']['name']}");
    $un = "";

    foreach ($lines  as $line) {
      $end_i = substr($line, -3, 2); 
      $end_t = substr($line, -2, 1);
      $start_i = substr($line, 0, 6);
      $start_t = substr($line, 0, 8);
      if ((($start_i == 'INSERT') && ($end_i == ");")) 
	  || (($start_t == 'TRUNCATE') &&($end_t == ";"))) { 
	    //echo "Executing $line <br>End is $end_i or $end_t<br>";
          ; //Do nothing
      } 
      $result = mysqli_query($con, $line, $con);
      if (!$result) {
        $un = $un . $line;
	    $endx = substr($un, -3, 2);
	    //echo "Error Executing: $line<br>End is $endx<br><br>";		
        if($endx == ");") {
          //echo "Complete line $un <br><br>";
	      mysqli_query($con, $un, $con);
	      //echo "Executed completed line $un<br>";
	      $un = "";
	    }
      }
    }
	//Authentication and display of Menu
      main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
	  
	  //Delete backup file after updating database
	  unlink($_FILES['file']['name']);
	  echo msg_box("Database has been restored from {$_FILES['file']['name']}", 'backup_restore.php', 'Continue');
	  
  } else if ($_REQUEST['action'] == 'Backup') {
    if (file_exists($filename)) {
      unlink($filename);
    }
    $fp = fopen($filename, "w+");
    $sql = "";
    $result = mysql_list_tables($database, $con);
    for ($i = 0; $i < mysqli_num_rows($result); $i++) {
      $table_name = mysql_tablename($result, $i);
      $result2 = mysqli_query($con, "select * from $table_name", $con);
      if (!$result2) 
        die('Query failed: ' . mysqli_error($con));
      $sql ="TRUNCATE table $table_name;\n";
      //echo "<br>";

      $num_rows = mysqli_num_rows($result2);
      if ($num_rows == 0) 
        continue;
      else {
        while($row = mysql_fetch_row($result2)) {
          $x = mysql_num_fields($result2);
	  $sql .="INSERT INTO $table_name(";
	  for($j = 0; $j < $x; $j++) {
	    if ($j == ($x - 1)) 
	      $sql .= mysql_field_name($result2, $j);
	    else
	      $sql .= mysql_field_name($result2, $j) . ", ";
	  }
	  $sql .= ") values (";
	  for($k = 0; $k < $x; $k++) {
	    if ($k == ($x - 1))
              if (mysql_field_type($result2, $k) == 'int') 
	        $sql .= htmlspecialchars($row[$k], ENT_QUOTES);
	      else 
	        $sql .= "'" . htmlspecialchars($row[$k], ENT_QUOTES) . "'";
		else {
		  if (mysql_field_type($result2, $k) == 'int') 
		    $sql .= htmlspecialchars($row[$k], ENT_QUOTES) . ", ";
		  else 
		  $sql .= " '" . htmlspecialchars($row[$k], ENT_QUOTES) . "', ";
	        }
	  }
	  $sql .= ");";
	  fwrite($fp, "$sql\n");
          //echo "$sql<br>";
          $sql="";
        }
      }
    }
    mysql_free_result($result);
    fclose($fp);

    header("Content-disposition:attachement; filename={$_REQUEST['file']}");
    header('Content-type: application/x-download; charset:iso-8859-1');
    header('Content-length: ' . filesize($filename)); 
    readfile($filename);
    
	//Delete backup file after backup has been made
	unlink($filename);
  }
  exit;
}
 $filename = "backup_" . date('Y_m_d_H_i') . ".sql";

 if (!user_type($_SESSION['uid'], 'Administrator', $con)) {
  main_menu($_SESSION['uid'],
    $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
  echo msg_box('Access Denied!', 'index.php?action=logout', 'Continue');
} else {
    main_menu($_SESSION['uid'],
      $_SESSION['firstname'] . " " . $_SESSION['lastname'], $con);
}
?>
 <table>
   <tr class='class1'>
    <td colspan="4">
     <h3>Backup/Restore the databse</h3>
     <form action="backup_restore.php" method="post" 
       enctype='multipart/form-data'>
    </td>
   </tr>
   <tr>
    <td>
     <fieldset>
      <legend>Backup</legend>
       <table>
        <tr>
         <td>Filename</td>
         <td><input type='text' name='file' 
          value='<?php echo $filename; ?>' size='30'></td>
         <td><input type="submit" name="action" value="Backup"></td>
        </tr>
       </table>
     </fieldset>
    </td>
   </tr>
   <tr>
    <td> 
     <fieldset>
      <legend>Restore</legend>
       <table>
        <tr>
         <td>Filename</td>
          <td><input type='file' name='file'></td>
          <td><input type="submit" name="action" value="Restore"></td>
         </tr>
        </td>
     </fieldset>  
    </td>
   </tr>
   </form>
  </table>
<?
  main_footer();
?>
