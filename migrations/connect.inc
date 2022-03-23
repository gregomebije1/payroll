<?php
//include_once "{$_SESSION['company']}";
require_once("config_profile_security.inc");

function connect() {
  global $dbserver, $dbusername, $dbpassword, $database;
  
  $con = mysql_connect($dbserver, $dbusername, $dbpassword) 
     or die("Cannot connect to database server " . mysqli_error($con));
    mysql_select_db($database, $con) 
     or die("Cannot select database" . mysqli_error($con));
  return $con;
}
?>