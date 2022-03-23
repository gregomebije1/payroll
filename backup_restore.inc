<?php

require_once "util.inc";
/** This was implemented so as to allow the application to perform faster. 
    It only loads the data of the entity that is currently logged in. 
    And when the user logs out, it deletes their data from the database and stores it in a file. 
    By so doing, queries will run faster because these queries are only run against tables
    populated by the entity.
   
    It also avoids any future problems from mixing up data between entities   

 **/  
 
function open_session($year, $month, $con) {  
  load_data("data/{$year}_{$month}.sql");
}
function truncate_temporary_tables($con) {
  $tables = array('payroll', 'payroll_di');
  foreach($tables as $id => $table_name) {
    $sql="truncate table {$table_name}";
    mysqli_query($con, $sql) or die(mysqli_error($con));
  }
}

function save_temporary_tables($year, $month) {
   //Create a list of tables with associating sql queries to be used for serialization
   
   $tables = array('payroll', 'payroll_di');
   $stc = array();
   
   foreach($tables as $id => $table_name) {
     $stc[$table_name] = "select * from {$table_name}";
   }
   $stc_file = "data/{$year}_{$month}.sql"; //File to be used for serialization
   store_data($stc, $stc_file); //Serializer
}


function load_data($file) {
 if (file_exists($file)) {
  //$lines = file($file);  
  //Had this error when I used the file() command.
  //Fatal error: Allowed memory size of 33554432 bytes exhausted (tried to allocate 35 bytes) in C:\xampp\htdocs\accounting\backup_restore.inc on line 229
  $handle = fopen($file, "r") or die("Couldn't get handle");
    
    
  $con = connect();
	$un = "";
  if ($handle) {
    while (!feof($handle)) {
      $line = fgets($handle);
  
      //foreach ($lines  as $line) {
	    $end_i = substr($line, -3, 2); 
	    $end_t = substr($line, -2, 1);
	    $start_i = substr($line, 0, 6);
	    $start_t = substr($line, 0, 8);
	  
	    if ((($start_i == strtoupper('INSERT')) && ($end_i == ");")) 
	      || (($start_t == strtoupper('TRUNCATE')) &&($end_t == ";"))) { 
	    } 
	    if (substr($line, 0, 6) == 'INSERT') {
	      //$t = substr($line, 12, strpos(substr($line, 12), "(")) . "_{$entity_id}";
        $t = substr($line, 12, strpos(substr($line, 12), "("));
	      $line = substr_replace($line, $t, 12, strpos(substr($line, 12), "("));;
	    }
      if (!empty($line)) {
        $result = mysqli_query($con, $line);
  	    if (!$result) {
  	      $un = $un . $line;
  	      $endx = substr($un, -3, 2);
  	      //echo "Error Executing: $line<br>End is $endx<br><br>";		
  		    if($endx == ");") {
  		      //echo "Complete line $un <br><br>";
  		      mysqli_query($con, $un) or die(mysqli_error($con));
            
  		      //echo "Executed completed line $un<br>";
  		      $un = "";
  		    }
  	    }
      }
    }
  }
  fclose($handle);
}
}

function get_table_name($value) {
  $start = stripos($value, strtolower("from")); //Get the starting position of 'from'

  $start+= 5; //The end position of 'from'
  
  //$value .= " where";
  $end = strpos(substr($value, $start), " "); //The start position of the next space
  if ($end) {
    $end += $start;
    $sbstr = substr($value, $start, $end - $start);
  } else 
    $sbstr = substr($value, $start);
  return trim($sbstr); //Trim white spaces
}

function store_data($tables, $file) {
  $con = connect();

  $fp = fopen($file, "w");
  
  //$sql is expected to be an array of 'select' statements
  $sql="";
  foreach ($tables as $id => $sql1) {
    $result2 = mysqli_query($con, $sql1) or die(mysqli_error($con));
    $num_rows = mysqli_num_rows($result2);
    if ($num_rows == 0) 
      continue;
    else {
      while($row = mysqli_fetch_row($result2)) {
        $x = mysqli_num_fields($result2);
	      $sql .="INSERT INTO " . get_table_name($sql1) . "(";
	
      	//Get row names
      	for($j = 0; $j < $x; $j++) {
      	  if ($j == ($x - 1)) 
      	    $sql .= mysqli_fetch_field_direct($result2, $j)->name;
      	  else
      	    $sql .= mysqli_fetch_field_direct($result2, $j)->name . ", ";
      	}
      	$sql .= ") values (";
	  
      	//Get the row values
      	for($k = 0; $k < $x; $k++) {
      	  if ($k == ($x - 1))
            if (mysqli_fetch_field_direct($result2, $k)->type == 'int') 
      	      $sql .= htmlspecialchars($row[$k], ENT_QUOTES);
      	    else 
      	      $sql .= "'" . htmlspecialchars($row[$k], ENT_QUOTES) . "'";
      	  else {
      	    if (mysqli_fetch_field_direct($result2, $k)->type == 'int') 
      	      $sql .= htmlspecialchars($row[$k], ENT_QUOTES) . ", ";
      	    else 
      	      $sql .= " '" . htmlspecialchars($row[$k], ENT_QUOTES) . "', ";
      	  }
	      }
      	$sql .= ");";
      	fwrite($fp, "$sql\n");
        $sql="";
      }
    }
  }
  fclose($fp);
}