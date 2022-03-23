<?php
//include_once "{$_SESSION['company']}";
require_once("config_profile_security.inc");

function connect() {
  global $dbserver, $dbusername, $dbpassword, $database;
  
  $con = mysqli_connect($dbserver, $dbusername, $dbpassword, $database);
  return $con;
}
?>