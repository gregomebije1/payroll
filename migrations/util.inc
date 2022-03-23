<?php
require_once("connect.inc");

function my_redirect($url, $text) {
 echo " 
   <html>
    <body>
    <form name='x' action='$url' method='post'>
     $text
    </form>
    <script language='javascript'>
      document.x.submit();
    </script>
    </body>
   </html>
  ";
}

function audit_trail($uid, $desc, $others, $con) {
  #use current_date instead of curr_date_time to allow searching by sd and ed
  $result = mysql_query("insert into audit_trail
   (dt, staff_id, descr, ot, dt2) values(NOW(), '$uid', '$desc', '$others', CURDATE())", $con) 
   or die("Cannot execute SQL audit_trail " . mysqli_error($con));
}

function msg_box($msg, $url, $urlname) {
  return "<table>
             <tr><td><h3>$msg <a style='color:orange; text-decoration:none'
             href='$url'>$urlname</a></h3></td></tr></table>";
}

function user_type($uid, $perm, $con) {
  $sql="select * from permissions p left join 
    (user u, user_permissions up) on (u.id = up.uid and up.pid = p.id) 
    where u.id = '$uid' and p.name='$perm'";
  $result = mysqli_query($con, $sql) or 
  die ("Cannot execute SQL query" . mysqli_error($con));
  return mysqli_num_rows($result) > 0;
}

function get_user_perm($uid, $con) {
  $sql="select p.name as 'name' from permissions p left join 
    (user u, user_permissions up) on (u.id = up.uid and up.pid = p.id) 
    where u.id = '$uid'";
  $result = mysql_query($sql) or die(mysqli_error($con));
  $temp = array();
  while($row = mysqli_fetch_array($result))
    $temp[] = $row['name'];
  return $temp;
}

function get_user($uid, $con) {
  $result = mysql_query("select firstname, lastname from user where id=$uid", $con);
  $row = mysqli_fetch_array($result);
  return $row['firstname'].' '.$row['lastname'];
}
function my_query() {
  $temp = array();
  $arg_list = func_get_args();
  $result = mysql_query($arg_list[0]) or die(mysqli_error($con));
  while($row = mysqli_fetch_array($result)) {
    if (func_num_args() > 3) {
      $temp[$row[$arg_list[1]]] = 
       $row[$arg_list[2]] . " " . $row[$arg_list[3]];
    } else {
      $temp[$row[$arg_list[1]]] = $row[$arg_list[2]];
    }
  }
  return $temp;
}

function get_values($table, $field1, $field2, $field3) {
  $a = array();
  $sql="select $field1 from $table where $field2=$field3";
  
  $result = mysql_query($sql);
  while($row = mysqli_fetch_array($result)) { 
    $a[$field2] = $row[$field1];
  }
  return $a;
}
function get_value($table, $field1, $field2, $field3, $con) {
  $sql="select $field1 from $table where $field2=$field3";
  //echo "$sql<br>";
  $result = mysql_query($sql);
  $row = mysqli_fetch_array($result);
  return $row[$field1];
}

?>
