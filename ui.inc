<?php 

include_once 'util.inc';
include_once 'connect.inc';

function get_month_name($month_id) {
  $arr = array('1'=>"January", '2'=>"February", '3'=>"March", '4'=>"April", '5'=>"May", '6'=>"June", '7'=>"July", '8'=>"August", 
    '9'=>"September", '10'=>"October", '11'=>"November", '12'=>"December");
 return $arr[$month_id];
}
function get_months($name = "month") {
  $arr = array('1'=>"January", '2'=>"February", '3'=>"March", '4'=>"April", '5'=>"May", '6'=>"June", '7'=>"July", '8'=>"August", 
    '9'=>"September", '10'=>"October", '11'=>"November", '12'=>"December");
 echo selectfield($arr, $name, '');
}
function get_years($name = "year") {
 $arr = array(2011=>"2011", 2012=>"2012", 2013=>"2013", 2014=>"2014", 2015=>"2015", 2016=>"2016", 2017=>"2017");
 echo selectfield($arr, $name, '2012');
}
function upload_file($filename, $url) {
  if ($_FILES[$filename]['error'] != 4) {  
  //Lets upload the file
    if ($_FILES[$filename]['error'] > 0) {
      switch($_FILES[$filename]['error']) {
        case 1: echo msg_box('File exceeded upload max_filesize', 
          $url, 'OK'); break;
        case 2: echo msg_box('File exceeded max_file_size', 
          $url, 'OK'); break;
        case 3: echo msg_box('File only partially uploaded', 
          $url, 'OK'); break;
      }
      exit;
    } elseif ($_FILES[$filename]['type']
      != ('image/jpeg' || 'image/gif' || 'image/png')) {
      echo msg_box('Prolem: file is not an image', $url, 'OK');
      exit;
    } else {
	  $upfile = "images/". $_FILES[$filename]['name'];
      if(is_uploaded_file($_FILES[$filename]['tmp_name'])) {
        if(!move_uploaded_file($_FILES[$filename]['tmp_name'], $upfile)) {
          echo msg_box('Problem: Could not move file to destination directory', $url, 'OK');
          exit;
        }
      } else {
        echo msg_box("Problem: Possible file upload attack. Filename: " .
          $_FILES[$filename]['name'], $url, 'OK');
        exit;
      }
    }
  }
}
    	
function tabs($id, $menu, $active) {
  echo "
  <div id='tabs'>
  <ul>";
    foreach($menu as $url => $name) {
	  echo "<li";
	  if ($name == $active)
	    echo " id='selected'";
		
	  if ($name == "Employee")
	    echo "><a href='$url?id=$id&action=Edit'>$name</a></li>\n";
	  else 
	    echo "><a href='$url&id=$id&action=List'>$name</a></li>\n";
      echo "<!---->";
	  //<!-- these comments between li's solve a problem in IE that prevents spaces appearing between list items that appear on different lines in the source
    } 
   echo "
   </ul>
   </div>";
}

function tr($arr) {
  $output = "<tr>";
  foreach($arr as $td) {
    $output .= "<td>$td</td>";
  }
  $output .= "</tr>";
  return $output;
}
function textfield() {
  $numargs = func_num_args();
  if ($numargs < 2) die("textfield second argument is missing");
  
  $output = "<input type='text' ";
  $arg_list = func_get_args();
  for ($i = 0; $i < $numargs; $i+=2) {
    $output .= " {$arg_list[$i]}='{$arg_list[$i+1]}' ";
  }
  $output .= ">";
  return $output;
}
function textarea($name, $value, $rows='5', $cols='30') {
  $numargs = func_num_args();
  if ($numargs < 2) die("textarea second argument is missing");
  return "<textarea name='$name' rows='$rows' cols='$cols'>$value</textarea>";
}

function selectfield($arr, $name, $default_id, $style="display:inline;", $script='') {
  $output= "<select name='$name' id='$name' style='$style' onchange='$script'>";
  foreach($arr as $id => $value) {
    if ($id == $default_id)
      $output .= "<option value='$id' selected='selected'>$value</option>";
    else
      $output .= "<option value='$id'>$value</option>";
  }
  $output .="</select>";
  return $output;
}
function get_table_data($table, $con) {  
  $sql="SELECT * FROM $table";
  $result = mysqli_query($con, $sql);
  echo "<select name='{$table}_id' id='{$table}_id'>";
  echo "<!--<option>--Select--</option>-->";
  while ($row = mysqli_fetch_array($result)) {
    echo "<option value='{$row['id']}'>{$row['name']} </option>";
  } 
  echo "</select>";
}


function is_table_empty($table, $con) {
  $sql="select * from $table";
  $result = mysqli_query($con, $sql);
  if (mysqli_num_rows($result) <= 0) 
    return true;
   else 
     return false;
}	 
function filled_out($form_vars) {
  // test that each variable has a value
  foreach ($form_vars as $key => $value) {
     if (!isset($key) || ($value == '')) 
        return false;
  } 
  return true;
}

function valid_email($address) {
  // check an email address is possibly valid
  if (ereg("^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$", $address))
    return true;
  else 
    return false;
}
function input_name($label) {
  
  $label = ucwords(str_replace('textarea', '', $label));
  $label = ucwords(str_replace('select', '', $label));
  $label = ucwords(str_replace('_', ' ', $label));
  
  //$label = ucwords($label);
  return $label;
}

function make_form($action, $id, $table, $file_name='', $option='') {
  $con = connect();
  $sql = "select * from $table where id=$id";
  $result = mysqli_query($con, $sql) or die(mysqli_error($con));
  $penalties = "";
  $row = mysqli_fetch_array($result); 
  echo "
   <table> 
    <tr class='class1'>
     <td colspan='4'><h3>$action " . ucwords($option) . "</h3></td>
    </tr>
    <form action='$table.php' method='post' name='form1' id='form1'";
  if (!empty($file_name))
    echo "enctype='multipart/form-data'";
  echo ">
    <tr>
     <td>
      <table>
   ";
   $sql="show columns from $table";
   $result1 = mysqli_query($con, $sql) or die(mysqli_error($con));
  
   while($row1 = mysqli_fetch_array($result1)) {
     if (($row1[0] == 'id')|| ($row1[0] == 'passport'))
       continue;
	  echo "
       <tr>
        <td>";
     if ($row1[0] == 'gl_id')
       echo "Grade Level";
     else if ($row1[0] == 'bank_id')
       echo "Bank";
     else if ($row1[0] == 'branch_id')
       echo "Branch";
     else if ($row1[0] == 'location_id')
       echo "Location";
     else if ($row1[0] == 'department_id')
       echo "Department";
     else if ($row1[0] == 'penalties')
       echo "";
     else echo input_name($row1[0]);
       echo "</td><td>";
     if ($row1[1] == 'text') {
       echo "<textarea rows='5' cols='30' name='{$row1[0]}'>{$row[$row1[0]]}</textarea>";
     } else if ($row1[1] == 'date') {
       $date = empty($row[$row1[0]]) ? date('Y-m-d') : $row[$row1[0]];
       echo "<input type='text' name='{$row1[0]}' size='10' maxlength='10' 
        value='$date'>";
     } else if ($row1[1] == "enum('Male','Female')") {
       echo selectfield(array('Male'=>'Male', 'Female'=>'Female'), 
         'gender_id', $row[$row1[0]]);
     } else if ($row1[1] == "enum('Deductions','Allowances')") {
       if ($option == 'Deductions'){
         echo selectfield(array('Deductions'=>'Deductions', 
         'Allowances'=>'Allowances'), 'type', $row[$row1[0]]);
       } else if ($option == 'Allowances') {
         echo selectfield(array('Allowances'=>'Allowances', 
           'Deductions'=>'Deductions'), 'type', $row[$row1[0]]);
       } 
     } else if ($row1[0] == "gl_id") {
       echo selectfield(my_query("select * from grade_level", "id", "name"),
           'gl_id', $row[$row1[0]]);
     } else if ($row1[0] == "department_id") {
       echo selectfield(my_query("select * from department order by name", "id", "name"), 
         'department_id', $row[$row1[0]]);
     } else if ($row1[0] == "bank_id") {
       echo selectfield(my_query("select * from bank order by name", "id", "name"), 
       'bank_id', $row[$row1[0]]);
     } else if ($row1[0] == "branch_id") {
       //$arr = array("0"=>"&nbsp;");
       $arr = my_query("select * from branch order by name", "id", "name");
       echo selectfield($arr, 'branch_id', $row[$row1[0]], 
        'display:inline;','get_location(0);');
     } else if ($row1[0] == "location_id") {
		if ($action == "Add")
	     $sql = "select * from location where id=0"; //Do not display any location
	   else
	     $sql = "select * from location where branch_id={$row['branch_id']} order by name";
	   
       echo selectfield(my_query($sql, "id", "name"), 'location_id', $row[$row1[0]]);
     } else if ($row1[0] == "status") {
       echo selectfield(array('Enable'=>'Enable', 'Disable'=>'Disable'), 
       'status', $row[$row1[0]]);
     } else if ($row1[0] == "penalties") {
       $penalties = $row[$row1[0]];
       
     } else {
       echo "<input type='text' name='{$row1[0]}' 
        value='{$row[$row1[0]]}'>";
       echo "</td></tr>";
     }
   } 
   echo "</table></td><td style='vertical-align:top;'>
    <table >
   "; 
   if (!empty($file_name)) {
    echo "
     <tr>
      <td>$file_name</td>
      <td><input type='file' name='$file_name' 
        value='{$row[$file_name]}'></td>	
       </tr>
    ";
   }

   if (($_REQUEST['action'] == 'Edit') || ($_REQUEST['action'] == 'View')) {
     if(!empty($file_name)) {
       echo "<tr><td>&nbsp;</td><td><img src='images/{$row['passport']}' 
       width='200' height='200'></td></tr>";
     }
   }
   echo "</table></td></tr>";
}


function generate_menu($heading, $menu) {  
  echo "
  <li>{$heading}</li>
  <li>
   <ul>";
    foreach($menu as $url => $name) {
      echo "<li><a href=\"$url\">$name</a></li>\n";
    } 
   echo "
   </ul>
  </li>";
} 
function print_header($title, $url, $urltext, $con) { 
  $result = mysqli_query($con, "SELECT * FROM org_info") 
    or die("Cannot execute SQL query at print_header" . mysqli_error($con));
  $row = mysqli_fetch_array($result);
  echo "
    <html>
      <head>
       <title>{$title}</title>
       <style>
       a:hover, a:active, a:visited, a:link {
         text-decoration:none;
         color:red;
       }
	   
       .h table td {
        font: 78%/1.5 arial, helvetica, serif;
        text-align: left;
        padding: 0;
        /*margin: 2em; */
       }
	   th {
	    font: 78%/1.5 arial, helvetica, serif;
        text-align: left;
        /*padding: 0; */
        padding: 0.1em 1em;
	font-weight:bold;
        border: 1px solid #999;
	    }
       .h td {
        font: 78%/1.5 arial, helvetica, serif;
        text-align: left;
        padding: 0;
       }
       .s {
        font: 78%/1.5 arial, helvetica, serif;
        text-align: center;
        padding: 0;
        border-width:1px; border-style:solid;
       }
	   table .class1 {
		border-bottom: #ffffff 0.1em solid;
	    /*background-color:#d6e8ff;*/
        background-color:#ebf3ff;
        font-weight:bold;
       }
	  table {
       color:black;
       background-color: white;
       border-width:1px;
       text-align: left;
       padding: 0;
       width:100%;
       table-layout: float;
       border: #d6e8ff 0.1em solid;
       border-collapse:collapse;
     }
	 
	 table .report {
		border-collapse: collapse;
		/*table-layout: fixed;*/
		width: 100%;
		
	}
	
	table .report td, th{
		border: 1px solid #999;
		padding: 0.1em 1em;
	}
	table .report th{
		border: 1px solid #999;
		padding: 0.1em 1em;
	    text-align: center;
		font: 100%/1.5 arial, helvetica, serif;
		font-weight:bold;
	}
	.alternative {
		background-color: #ddf;
	}
	
       </style>
      </head>
      <body style='text-align:center;' >
       <!--<a href='{$url}'>{$urltext}</a>-->
	   <!--
       <table class='s' align='center' width='70%' 
        cellpadding='0' cellspacing='0'> 
         <tr><td><h2>{$row['name']}</h2></td></tr>
         <tr><td>{$row['address']}</td></tr>
         <tr><td>{$row['phone']}</td></tr>
         <tr><td>{$row['email']} {$row['web']}</td></tr>
         <tr><td>&nbsp;</td></tr>
       </table>
	   -->
       <table class='h' align='center' rules='rows' border='1'
        style='
          border-width:1px; 
          border-style:solid;'
         border='0' width='70%' cellspacing='0' cellpadding='0'>
         <tr>
          <td style='background-color:#ebf3ff' colspan='8'>
          <!--<h3>{$title}</h3>-->
          </td>
         </tr>";
}
function main_menu($uid, $name, $con) { 
  $result = mysqli_query($con, "SELECT * FROM org_info") 
    or die("Cannot execute SQL query at print_header" . mysqli_error($con));
  $row = mysqli_fetch_array($result);

 if (isset($_SESSION['firstname'])) {
   $firstname = $_SESSION['firstname'];
   $lastname = $_SESSION['lastname'];
 } else {
   $firstname = "";
   $lastname = "";
 }
 echo "
 <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
  'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
  <html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
   <head>
    <title>PayPro</title>
    <script type='text/javascript' src='jquery-1.6.2.js'> </script>
    <script type='text/javascript' src='payroll.js'> </script>
	<script language='javascript' src='util.js'></script>
	
    <script type='text/javascript' src='custom.js'> </script>
    <script type='text/javascript' 
      src='jquery.tablesorter/jquery.tablesorter.js'></script>
    <link rel='stylesheet' type='text/css' href='payroll.css'>
   </head>
   <body>";
   
	   echo "
       <div class='header'>
        <p>PayPro</p><p>{$row['name']}</p><p>Welcome - $firstname $lastname 
        <p>{$_SESSION['year_id']} " . get_month_name($_SESSION['month_id']) . "</p>
        
		    <a style='float:right;color:red;' href='index.php?action=logout'> Sign Out</a>
       </div>
       <div class='bar'></div>
       <div class='navigation'>
        <ul>
	    ";

  $temp = get_user_perm($uid, $con);
  $arr = array(0 => 'Employee', 1=>'Pay Slip', 2=>'Payroll History', 3=>'Administrator', 4=>'Choose Year and Month');
  
  generate_menu("Employee", gen_sub_menu($temp, $arr));
  
  $arr = array(0 => 'Process Payroll', 1 => 'Payroll Report', 2 => 'Payroll Summary Report', 3 => 'Bank Schedule',  4 => 'Deductions Report', 5 => 'Administrator');
  generate_menu("Payroll", gen_sub_menu($temp, $arr));
 
 
  $arr = array(0 => 'Allowances',  1 => 'Deductions',  2 => 'Org Info',  3 => 'Payee Item', 4 => 'Bank',  5 => 'Department',  6 => 'Grade Level', 
	7 => 'Branch', 8 => 'Location', 9 => 'Backup Restore', 10 => 'Administrator');
  $menu = gen_sub_menu($temp, $arr);
  $menu['users.php'] = 'Users';
  
  generate_menu("SetUp", $menu);
  
   echo "
    </ul>
   </div>
   <div class='content'>";
}
function main_footer() {
  echo "
     </div> <!-- End of content -->
    </div> <!-- End of container -->
   </body>
  </html>";
}
function gen_sub_menu($temp, $arr) {
  $menu = array();
  if (array_intersect($temp, $arr)) {
    foreach($arr as $x) {
	 if ($x == 'Administrator')
	   continue;
	 if (in_array($x, $arr)) {
	   $menu[strtolower(str_replace(" ", "_", $x)) .".php"] = $x;
	 }
	}
  }
  //natsort($menu);
  return $menu;
} 
?>
