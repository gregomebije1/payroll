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
if (isset($_REQUEST['action']) && 
  ($_REQUEST['action'] == 'Update Org Info')) {
  
  if (empty($_REQUEST['n'])) {
    echo msg_box("Please enter the name of the organization", "org_info.php", "Back");
   exit;
  } else {
    $result = mysqli_query($con, "select * from org_info where id=1");
    if(mysqli_num_rows($result) > 0) {
	  if (!empty($_FILES['logo']['name'])) {
	  upload_file('logo', 'org_info.php'); 
	  $sql="update org_info set logo='{$_FILES['logo']['name']}' where id=1";
	  mysqli_query($con, $sql) or die(mysqli_error($con));
	}
    $sql="UPDATE org_info set name='{$_REQUEST['n']}', 
      address = '{$_REQUEST['a']}', phone = '{$_REQUEST['p']}',
      email = '{$_REQUEST['em']}', web = '{$_REQUEST['w']}'  where id=1";
    mysqli_query($con, $sql);
    } else {
      upload_file('logo', 'org_info.php'); 
      $sql="INSERT INTO org_info (name, address, email, phone, 
        web, logo) VALUES('{$_REQUEST['n']}', '{$_REQUEST['a']}', 
        '{$_REQUEST['em']}', '{$_REQUEST['p']}', '{$_REQUEST['w']}', 
        '{$_FILES['logo']['name']}')";
      mysqli_query($con, $sql);
    }
  }
}

$result = mysqli_query($con, "SELECT * FROM org_info");
$row = mysqli_fetch_array($result);
?>
  <table>
   <tr class='class1'>
       <td colspan="4">
        <h3>Organization Information</h3>
        <form enctype="multipart/form-data" 
          action="org_info.php" method="post">
       </td>
      </tr>
      <tr>
       <td>Logo Image</td>
       <td>
       <img src='images/<?php echo $row['logo'];?>' 
        width='100' height='100'></td>
      </tr>
      <tr>
       <td>Name</td>
       <td><input type="text" name="n" size="50" value="<?php echo $row['name']?>"></td>
      </tr>
      <tr>
       <td>Address</td>
       <td><textarea rows="5" cols="50" name="a"><?php echo $row['address']?></textarea></td>
      </tr>
      <tr>
       <td>Phone</td>
        <td><input type="text" name="p" size="50" value="<?php echo $row['phone']?>">
        </td>
      </tr>
      <tr>
       <td>Email</td>
       <td><input type="text" name="em" size="50" value="<?php echo $row['email']?>">
       </td>
      </tr>
      <tr>
       <td>Website</td>
       <td><input type="text" name="w" size="50" value="<?php echo $row['web']?>"></td>
      </tr>
      <tr>
       <td>Logo</td>
       <td><input type="file" name="logo"></td>
      </tr>
      <tr>
       <td><input type="submit" name="action" value="Update Org Info"></td>
      </tr>
     </form>
    </table>
<?
  main_footer();
?>
