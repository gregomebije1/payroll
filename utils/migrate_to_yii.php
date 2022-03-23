<?php

$d = dir("../data");
$con = mysqli_connect("localhost", "root", "", "profile_security2");

$sql = array();
$sql[] = "RENAME TABLE org_info TO entity";

$sql[] = "ALTER TABLE audit_trail CHANGE staff_id user_id INTEGER NOT NULL";
$sql[] = "ALTER TABLE audit_trail DROP COLUMN dt";
$sql[] = "ALTER TABLE audit_trail DROP COLUMN ot";
$sql[] = "ALTER TABLE audit_trail DROP COLUMN dt2";
$sql[] = "ALTER TABLE audit_trail CHANGE descr description TEXT NOT NULL";
$sql[] = "ALTER TABLE audit_trail ADD COLUMN created_at INTEGER NULL";
$sql[] = "ALTER TABLE audit_trail ADD COLUMN updated_at INTEGER NULL";
$sql[] = "RENAME TABLE audit_trail TO audit_trail_1";
$sql[] = "ALTER TABLE `audit_trail_1`
			ADD CONSTRAINT `fk_audit_trail_1_user_id` FOREIGN KEY (`user_id`) 
			REFERENCES `user` (`id`);";
			
$sql[] = "ALTER TABLE bank CHANGE name name VARCHAR(100) NOT NULL";
$sql[] = "RENAME TABLE bank TO bank_1";

$sql[] = "ALTER TABLE branch CHANGE name name VARCHAR(100) NOT NULL";
$sql[] = "RENAME TABLE branch TO branch_1";

$sql[] = "ALTER TABLE department CHANGE name name VARCHAR(100) NOT NULL";
$sql[] = "RENAME TABLE department TO department_1";

$sql[] = "ALTER TABLE di CHANGE name name VARCHAR(100) NOT NULL";
$sql[] = "ALTER TABLE di CHANGE type type ENUM('Deductions', 'Allowances) VARCHAR(100) NOT NULL";
$sql[] = "RENAME TABLE di TO deductions_allowances_1";

$sql[] = "ALTER TABLE grade_level CHANGE name name VARCHAR(100) NOT NULL";
$sql[] = "ALTER TABLE grade_level CHANGE basic_salary basic_salary VARCHAR(100) NOT NULL";
$sql[] = "RENAME TABLE grade_level TO grade_level_1";

$sql[] = "ALTER TABLE location CHANGE name name VARCHAR(100) NOT NULL";
$sql[] = "RENAME TABLE location TO location_1";
$sql[] = "ALTER TABLE `location_1`
			ADD CONSTRAINT `fk_location_1_branch_id` FOREIGN KEY (`branch_id`) 
			REFERENCES `branch` (`id`);";

$sql[] = "RENAME TABLE employee TO employee_1";
$sql[] = "ALTER TABLE employee_1 CHANGE gl_id grade_level_id INTEGER NOT NULL";
$sql[] = "ALTER TABLE employee_1 CHANGE department_id department_id INTEGER NOT NULL";
$sql[] = "ALTER TABLE employee_1 CHANGE bank_id bank_id INTEGER NOT NULL";
$sql[] = "ALTER TABLE employee_1 DROP COLUMN branch_id";
$sql[] = "ALTER TABLE employee_1 CHANGE location_id location_id INTEGER NOT NULL";

$sql[] = "RENAME TABLE employee_di TO employee_deductions_allowances_1";
$sql[] = "ALTER TABLE employee_deductions_allowances_1 CHANGE employee_id employee_id INTEGER NOT NULL";
$sql[] = "ALTER TABLE employee_deductions_allowances_1 CHANGE di deductions_allowances_id INTEGER NOT NULL";

$sql[] = "ALTER TABLE payee_item CHANGE name name VARCHAR(100) NOT NULL";

foreach ($sql as $id => $query) {
	mysqli_query($con, $query) or die(mysqli_error($con));
	echo "{$sql}\n";		
}
		
exit;
$ignore = [".", "..", "2_.sql", "_.sql"];
while (false !== ($entry = $d->read())) {
	if (in_array($entry, $ignore)) {
		continue;
	}
   
    $data = explode("_", $entry);
	$month = explode(".", $data[1]);
	
	$sql = "CREATE TABLE `payroll_1_{$data[0]}_{$month[0]}` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `employee_id` int(11) NOT NULL,
		  `payroll_date` date NOT NULL,
		  `basic_salary` varchar(100) NOT NULL,
		  `created_at` INTEGER NULL,
		  `updated_at` INTEGER NULL,
		  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1";
	echo "{$sql}\n";
	mysqli_query($con, $sql) or die(mysqli_error($con));
	
	$sql = "CREATE TABLE `payroll_di_1_{$data[0]}_{$month[0]}` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `payroll_id` int(11) NOT NULL,
		  `di` int(11) NOT NULL,
		  `amount` VARCHAR(100) NOT NULL,
		  `created_at` INTEGER NULL,
		  `updated_at` INTEGER NULL,
		  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1";
	echo "{$sql}\n";
	mysqli_query($con, $sql) or die(mysqli_error($con));
	
	
	$sql = "ALTER TABLE `payroll_1_{$data[0]}_{$month[0]}`
			ADD CONSTRAINT `fk_payroll_1_{$data[0]}_{$month[0]}_employee_id` FOREIGN KEY (`employee_id`) 
			REFERENCES `employee` (`id`)";
	mysqli_query($con, $sql) or die(mysqli_error($con));
	echo "{$sql}\n";
	
	load_data($con, "../data/{$entry}");
	
	$sql = "ALTER TABLE `payroll_di_1_{$data[0]}_{$month[0]}`
			ADD CONSTRAINT `fk_payroll_di_1_{$data[0]}_{$month[0]}_payroll_id` FOREIGN KEY (`payroll_id`) 
			REFERENCES `payroll` (`id`)";
	mysqli_query($con, $sql) or die(mysqli_error($con));
	echo "{$sql}\n";
	
	$sql = "ALTER TABLE `payroll_di_1_{$data[0]}_{$month[0]}` CHANGE di deductions_allowances_id INTEGER NOT NULL";
	mysqli_query($con, $sql) or die(mysqli_error($con));
	
	$sql = "ALTER TABLE `payroll_di_1_{$data[0]}_{$month[0]}`
			ADD CONSTRAINT `fk_payroll_di_1_{$data[0]}_{$month[0]}_deductions_allowances_id` FOREIGN KEY (`deductions_allowances_id`) 
			REFERENCES `deductions_allowances` (`id`)";
	mysqli_query($con, $sql) or die(mysqli_error($con));
	echo "{$sql}\n";
	
	

}
$d->close();

function load_data($con, $file) {
	if (file_exists($file)) {
		//$lines = file($file);  
		//Had this error when I used the file() command.
		//Fatal error: Allowed memory size of 33554432 bytes exhausted (tried to allocate 35 bytes) in //C:\xampp\htdocs\accounting\backup_restore.inc on line 229
		$handle = fopen($file, "r") or die("Couldn't get handle");
    		  
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
				$table_name = "";
				
				if (substr($line, 0, 6) == 'INSERT') {
	      
					$table_name = substr($line, 12, strpos(substr($line, 12), "("));
					var_dump($table_name);
					
					$line = substr_replace($line, $table_name, 12, strpos(substr($line, 12), "("));;
					var_dump($line);
				}
				if (!empty($line)) {
					
					if ($table_name == "payroll")
						$line = str_replace("payroll", "black", "<body text='%body%'>");
					echo "{$line}\n";
					/*
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
					*/
				}
			}
		}
		fclose($handle);
	}
}
