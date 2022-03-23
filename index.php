<?php
session_start(); 
error_reporting(E_ALL);

require_once("util.inc");
require_once("backup_restore.inc");
require_once("connect.inc");

$con = connect(); 

$message = "Please login";

if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'logout')) {

   //There is still a session running, so do the right thing.
  //This should be tested
  if (isset($_SESSION['year_id']) && isset($_SESSION['month_id'])) {
    save_temporary_tables($_SESSION['year_id'], $_SESSION['month_id']);
    truncate_temporary_tables($con);
  
    foreach($_SESSION as $name => $value)
      unset($name);
    
    session_destroy();
  } else 
	  session_destroy();
} 

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'login')) {
  if (empty($_REQUEST['company'])) {
    $message = "Please select a company";
  }
  if (empty($_REQUEST['u']) || empty($_REQUEST['p'])) {
  $message = "Please enter username and password";
  }
  if ( (!(empty($_REQUEST['u'])))  && (!(empty($_REQUEST['p']))) ) {
    
    $_SESSION['company'] = "config_{$_REQUEST['company']}.inc";

    //validation may be required. htmlentities
    $q = "SELECT * from user where name='{$_REQUEST['u']}' 
       and passwd=sha1('{$_REQUEST['p']}')";
    $result = mysqli_query($con, $q) or die(mysqli_error($con));
    if (mysqli_num_rows($result)) {
      $row = mysqli_fetch_array($result);
      $_SESSION['uid'] = $row['id'];  #Store a session variable 
      $_SESSION['firstname'] = $row['firstname'];   
      $_SESSION['lastname'] = $row['lastname'];
      $_SESSION['year_id'] = $_REQUEST['year_id'];
      $_SESSION['month_id'] = $_REQUEST['month_id'];
   
      truncate_temporary_tables($con); //Truncate any existing data

      open_session($_SESSION['year_id'], $_SESSION['month_id'], $con);
      my_redirect('employee.php', '');
    } else {
   $message = "Wrong username and password";
    } 
  }
} 
if (isset($_SESSION['uid']))
  my_redirect('employee.php', '');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
 <title>PayPro</title>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <link rel='stylesheet' type='text/css' href='payroll.css'>
</head>
<body class='login'>
 <form name='form1' id='form1' method='post'>
 <table>
  <tr class='class1'>
   <td colspan='2' style='text-align:center;font-size:2em;'>PROFILE GROUP LIMITED</td></tr>
   <tr class='class1'><td colspan='2' style='text-align:center; font-size:0.7em; font-weight:normal; color:red;'>
   <?php
   if (isset($message))
     echo $message;
   ?></td></tr>
  <tr class='class1'>
   <td colspan='2' style='text-align:center; font-size:0.7em; font-weight:normal;'>PayPro 2012</td>
  </tr>
  <tr>
   <td>Username</td>
   <td><input id='u' name='u' autocomplete='off' type='text' size='40'></td>
  </tr>
  <tr>
   <td>Password</td>
   <td><input id='p' name='p' autocomplete='off' type='password' size='40'></td>
  </tr>
  <tr>
   <td>Company</td>
   <td>
    <select id='company' name='company'>
   <option value='profile_security'>Profile Security</option>
   <!--
   <option value='profile_energy'>Profile Energy</option>
   <option value='profile_group'>Profile Group</option>
   <option value='profile_international'>Profile International</option>
   <option value='profile_properties'>Profile Properties</option>
  <option value='profile_technologies'>Profile Technologies</option>
-->
  </select>
   </td>
  </tr>
  <tr>
    <td>Year</td>
    <td>
     <select name='year_id' id='year_id'>
     <?php
       $arr = array('2012', '2013', '2014', '2015','2016','2017');
       foreach ($arr as $id => $value)
         echo "<option value='{$value}'>{$value}</option>"; 
     ?>
     </select>
    </td>
  </tr>
  <tr>
    <td>Month</td>
    <td>
     <select name='month_id' id='month_id'>
     <?php
       $arr = array('January', 'February', 'March', 'April','May', 'June', 'July', 'August', 
        'September', 'October', 'November', 'December');
       for($i = 0; $i <= 11; $i++) 
         echo "<option value='" . ($i + 1) . "'>{$arr[$i]}</option>"; 
     ?>
     </select>
    </td>
  </tr>
  <input type='hidden' name='action' value='login'>
  <tr>
   <td style='text-align:center;' colspan='2'>
   <input type='submit'   value='     Login      '>
   </td>
  </tr>
 </table>
 </form>
</body>
</html>
