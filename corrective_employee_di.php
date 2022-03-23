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
require_once "payroll.inc";

$con = connect();
  
$di = array();
$sql = "select * from di";
$result = mysqli_query($con, $sql) or die(mysqli_error($con));
while($row = mysqli_fetch_array($result))
  $di[] = $row['id'];


$emp = array();
$sql="select * from employee";
$result = mysqli_query($con, $sql) or die(mysqli_error($con));
while($row = mysqli_fetch_array($result))
  $emp[$row['id']] = "{$row['firstname']} {$row['lastname']}";

$sn = 1;
foreach($emp as $emp_id => $emp_name) {
  foreach ($di as $id => $di_id) {
    $sql="insert into employee_di(employee_id, di, amount) values('$emp_id', '{$di_id}', '0')"; 
    mysqli_query($con, $sql) or die(mysqli_error($con));
  }
  echo "Finished Processing $sn {$emp_name}<br>";  
  $sn++;
  
}
  
  