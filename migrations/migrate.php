<?php
require_once("util.inc");
require_once("backup_restore.inc");

$con = connect();

for ($year = 2015; $year <= 2016; $year++) {
  for ($month = 1; $month <= 12; $month++) {
    $sql1 = array();
    $sql2 = array();
    $sqlx = array();

    $sqlx[] = "select * from payroll where payroll_date ='{$year}-{$month}-01'";
    $sql2[] = "select * from payroll where payroll_date ='{$year}-{$month}-01'";
    
    foreach($sql2 as $id => $value) {
      $result = mysqli_query($con, $value, $con) or die(mysqli_error($con));
      while ($row = mysqli_fetch_array($result)) 
        $sqlx[] = "select * from payroll_di where payroll_id={$row['id']}";
    }
    $sql_file = "data/{$year}_{$month}.sql"; //File to be used for serialization

    echo "Writing to file {$sql_file}...\n";
    store_data($sqlx, $sql_file); //Serialiaze

    echo "Finished processing {$year}-{$month}-01!!!\n\n";
  }
}
?>