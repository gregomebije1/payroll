<?php 

session_start();
unset($_SESSION['uid']);
unset($_SESSION['firstname']);
unset($_SESSION['lastname']);
session_destroy();

include_once('../config.inc');

$back = "<a href='' onClick='history.back();'>Back</a>";  
if ($_REQUEST['action'] == 'Install') {
  if (empty($_REQUEST['dbname']) || empty($_REQUEST['dbusername'])
    || empty($_REQUEST['dbpassword1'])) {
   echo "Please enter correct Database Information details $back_link";
   exit;
  } else {   
    if ($_REQUEST['dbpassword1'] !== $_REQUEST['dbpassword2']) 
	  echo "Passwords are not equal $back_link";
    $con = mysqli_connect($_REQUEST['dbhost'],
      $_REQUEST['dbusername'],$_REQUEST['dbpassword1'], $_REQUEST['dbname']) 
      or die("Cannot connect to Database Host $back_link");

	
    ####Store the values in the config file####
    if (file_exists("../config.inc")) {
      unlink("../config.inc");
    }
    $fp = fopen("../config.inc", "w");
    $stuff="<?php\n
     \$dbserver = '{$_REQUEST['dbhost']}';\n
     \$dbusername='{$_REQUEST['dbusername']}';\n
     \$dbpassword='{$_REQUEST['dbpassword1']}';\n
     \$database= '{$_REQUEST['dbname']}';\n
     ?>";
    fwrite($fp, "$stuff\n");
    fclose($fp);
	
    echo "Database configuration stored in config.inc<br><br>";	
	
    $tables['audit_trail'] = "
     create table audit_trail(
      id int(11) auto_increment primary key, 
      dt datetime, 
      staff_id varchar(100), 
      descr varchar(100),   /* Description of the transaction */
      ot varchar(100),  /* Can contain journal ID, or room ID*/
      dt2 date 
     )";

    $tables['org_info'] = "
     create table org_info (
      id int(11) auto_increment primary key, 
      name varchar(300), 
      address text, 
      phone varchar(100), 
      email varchar(100), 
      web varchar(100),
      logo varchar(100)
     )";

    
    $tables['permissions'] = "
     create table permissions (
     id int(11) auto_increment primary key, 
     name varchar(100) 
    )";
	 
    $tables['user_permissions'] = "
     create table user_permissions (
      id int(11) auto_increment primary key, 
      uid int(11),
      pid int(11)
     )";
	
    $tables['user'] = "	
     CREATE TABLE user (
      id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name varchar(100) NOT NULL,
      passwd varchar(100) NOT NULL,
      entity_id int(11) NOT NULL,
      firstname varchar(100), 
      lastname varchar(100)
    )";
	
    $tables['grade_level'] = "
     CREATE TABLE grade_level (
      id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
      name varchar(100), 
      basic_salary varchar(100)
     )";

  $tables['payee_item'] = " 
    create table payee_item(id integer auto_increment primary key, 
      name text,
      payee_item_type varchar(100),
      payee_item_group varchar(100),  
      amount varchar(100))";
    
    $tables['employee'] = " 
     create table employee ( 
      id integer auto_increment primary key, 
      lastname varchar(100),
      firstname varchar(100),
      middlename varchar(100),
      gl_id varchar(100),
      department_id integer,
      bank_id integer,
      bank_account_number varchar(100),
	  branch_id integer(100),
    location_id varchar(100),
      marital_status varchar(100),
      date_of_birth date, 
      place_of_birth text, 
      nationality varchar(100),
      passport_no varchar(100),
      national_identity_card_no_or_residential_permit_no varchar(100), 
      local_govt_area_and_state_of_origin text,
      name_of_next_of_kin_and_relationship varchar(100),
      address_and_telephone_next_of_kin text, 
      permanent_home_or_family_address text, 
      abuja_residential_address text, 
      home_telephone_number varchar(100),
      mobile_telephone_number varchar(100),
      secondary_school_qualification text, 
      post_secondary_school_qualification text, 
      previous_employer text,
      date_of_first_appointment date, 
      starting_position varchar(100), 
	  panalities text,
      passport varchar(100), 
      status varchar(100)
     )
    ";
	
    $tables['employee_di'] = "
     create table employee_di (
      id integer auto_increment primary key, 
      employee_id integer, 
      di integer,
      amount varchar(100)
     )
    ";
	
    $tables['di'] = "
     create table di (
      id integer auto_increment primary key,
      type enum('Deductions','Allowances'),
      name varchar(100)
    )";

    $tables['payroll'] = "
     create table payroll (
      id integer auto_increment primary key, 
      employee_id integer, 
      payroll_date date, 
      basic_salary varchar(100)
    )";  

    $tables['payroll_di'] = "
     create table payroll_di (
      id integer auto_increment primary key, 
      payroll_id integer, 
      di integer,
      amount varchar(100)
    )";

    $tables['department'] = "
     create table department (
      id integer auto_increment primary key, 
      name varchar(100)
    )";

	$tables['branch'] = "
     create table branch (
      id integer auto_increment primary key, 
      name varchar(100)
    )";
	
	$tables['location'] = "
     create table location (
      id integer auto_increment primary key, 
	  branch_id integer,
      name varchar(100)
    )";

    $tables['bank'] = "
     create table bank (
      id integer auto_increment primary key, 
      name varchar(100),
      address varchar(100)
    )";
    
    foreach($tables as $name => $sql) 
     if (mysqli_query($con, $sql))
       echo " $name table successfully created<br>";
     else {
       echo "Problem creating $name table";
       exit;
    }
    unset($tables);
	
	    
    $tables[] = "insert into user_permissions(uid, pid) 
         values (1, 1)";
		 
    $tables[] = "insert into user(name, passwd, entity_id, firstname, 
         lastname) values ('admin', sha1('password'), 1, 
         'Administrator', 'Administrator')";
		 
    $tables[] = "insert into permissions(name) values('Administrator')";
    $tables[] = "insert into permissions(name) values('Accountant')";
    $tables[] = "insert into permissions(name) values('Staff')";
    $tables[] = "insert into permissions(name) values('Report Viewer')";
	
	
	$tables[] = "INSERT INTO branch (name) VALUES
		('Default')";
	
    $tables[] = "INSERT INTO `bank` (`id`, `name`, `address`) VALUES
		(1, 'United Bank Of Africa', ''),
		(2, 'Zenith Bank', ''),
		(3, 'Diamond Bank', ''),
		(4, 'Aso Savings And Loans', ''),
		(5, 'Sterling Bank', ''),
		(6, 'First Bank', ''),
		(7, 'Intercontinental Bank', ''),
		(8, 'Gurantee Trust Bank', '')";

    $tables[] = "INSERT INTO `department` (`id`, `name`) VALUES
		(1, 'Account'),
		(2, 'Admin'),
		(3, 'Litigation'),
		(4, 'Store'),
		(5, 'Library')";
		
    $tables[] = "INSERT INTO `di` (`id`, `type`, `name`) VALUES
		(1, 'Deductions', 'PAYE'),
		(2, 'Deductions', 'Pension Contribution Employee'),
		(3, 'Deductions', 'Pension Contribution Employer'),
		(4, 'Deductions', 'Zain Deduction'),
		(5, 'Allowances', 'Housing'), 
		(6, 'Allowances', 'Transportation'), 
		(7, 'Allowances', 'Meal Subsidy'), 
		(8, 'Allowances', 'Utility'), 
		(9, 'Allowances', 'Other')
		";
	
    $tables[] = "
		INSERT INTO `org_info` 
		(`id`, `name`, `address`, `phone`, `email`, `web`, `logo`) 
		VALUES (1, 'Profile Security Services Limited',
		'Abuja','', '', '', '')";
	
    foreach($tables as $sql)
    if (!mysqli_query($con, $sql)) {
      echo "Problem inserting data into table";
      exit;
    }
    unset($tables);

    echo "<h3>Installtion successfully completed</h3>";
    echo "<p><b>Login</b> as username:&nbsp;<b>admin</b>&nbsp;
	   &nbsp;password:&nbsp;<b>password</b></p>";
    echo "Continue to <a href='../index.php'>HomePage</a>";
  }
}
?>
