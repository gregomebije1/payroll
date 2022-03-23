<?php
require_once("util.inc");
require_once("backup_restore.inc");

$con = connect();

$sql = "select * from branch";
$result = mysqli_query($con, $sql) or die(mysqli_error($con));
while($row = mysqli_fetch_array($result)) 
	$branch[] = $row['id'];

echo "Finished getting all branches...\n";

foreach ($branch as $id => $branch_id) {

  $sql1 = array();
  $sql2 = array();
  $sqlx = array();

  //Get all Employees belonging to branches
  $sql1[] = "select * from employee where branch_id={$branch_id}";
  $sqlx[] = "select * from employee where branch_id={$branch_id}";

  foreach($sql1 as $id => $value) {
    $result = mysqli_query($con, $value, $con) or die(mysqli_error($con));
    while ($row = mysqli_fetch_array($result)) {
      $sqlx[] = "select * from employee_di where employee_id={$row['id']}";
      $sqlx[] = "select * from payroll where employee_id={$row['id']}";
      $sql2[] = "select * from payroll where employee_id={$row['id']}";
    }
  }
  echo "Finished getting all employee_di and payroll...\n";

  foreach($sql2 as $id => $value) {
    $result = mysqli_query($con, $value, $con) or die(mysqli_error($con));
    while ($row = mysqli_fetch_array($result)) 
      $sqlx[] = "select * from payroll_di where payroll_id={$row['id']}";
  }
  echo "Finished getting all payroll_di...\n";

  $sql_file = "data/{$branch_id}.sql"; //File to be used for serialization

  echo "Writing to file {$sql_file}...\n";
  store_data($branch_id, $sqlx, $sql_file); //Serialiaze

  echo "Finished processing Branch => {$branch_id}!!!\n\n";
}
?>