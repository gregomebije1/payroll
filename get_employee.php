<?php
session_start();
include_once "connect.inc"; 

//$con = connect();

require_once("config_profile_security.inc");
$con = mysqli_connect($dbserver, $dbusername, $dbpassword, $database);

$outputs = array();
if(isset($_GET['location_id']) and $_GET['location_id'] != '') {
  $sql="select * from employee where location_id={$_REQUEST['location_id']}
   order by id";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con) );
  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      $outputs[]=$row['id'];
      $outputs[]="{$row['firstname']} {$row['middlename']} {$row['lastname']}";
    }
  } 
} else {
  $outputs[0] = "";
}
mysqli_close($con);

echo json_encode($outputs);
