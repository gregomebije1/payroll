<?php
session_start();
include_once "connect.inc";
$con = connect();

$outputs = array();
if(isset($_GET['branch_id']) and $_GET['branch_id'] != '') {
  if ($_REQUEST["branch_id"] == "All")
    $sql = "select * from location order by id";
  else {
    $sql="select * from location where branch_id={$_GET['branch_id']}
    order by id";
  }
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      $outputs[]=$row['id'];
      $outputs[]=$row['name'];
    } 
  } 
} 
mysqli_close($con);

echo json_encode($outputs);
