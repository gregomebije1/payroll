<?php
session_start(); 

if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
require_once 'util.inc';
require_once 'ui.inc';
require_once "backup_restore.inc";

$con = connect();

error_reporting(E_ALL);

if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'OK')) {
  
  if((!isset($_REQUEST['year_id'])) || (!isset($_REQUEST['month_id']))) {
    my_redirect('choose_year_and_month.php', '');
    exit;
  }

  //Save currently running session
  save_temporary_tables($_SESSION['year_id'], $_SESSION['month_id']);
  truncate_temporary_tables($con);

  $_SESSION['year_id'] = $_REQUEST['year_id'];
  $_SESSION['month_id'] = $_REQUEST['month_id'];

  //Open a new session for this user  
  open_session($_SESSION['year_id'], $_SESSION['month_id'], $con);
  
  my_redirect('employee.php', '');
}

main_menu($_SESSION['uid'], '', $con);
?>
   <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
    'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
  <html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
   <head>
   <title>HyperBooks</title>
    <!-- For Table sorter -->
   
   
  <link href="jquery-ui.css" rel="stylesheet"> 
 <style>
  body { font-size: 62.5%; }
  label, input { display:block; }
  input.text { margin-bottom:12px; width:95%; padding: .4em; }
  fieldset { padding:0; border:0; margin-top:25px; }
  h1 { font-size: 1.2em; margin: .6em 0; }
  div#users-contain { width: 350px; margin: 20px 0; }
  div#users-contain table { 
   margin: 1em 0; border-collapse: collapse; width: 100%; }
  div#users-contain table td, div#users-contain table th { 
   border: 1px solid #eee; padding: .6em 10px; text-align: left; }
 .ui-dialog .ui-state-error { padding: .3em; }
 .validateTips { border: 1px solid transparent; padding: 0.3em; }
 </style>

 <script src="external/jquery/jquery.js"></script>
 <script src="jquery-ui.js"></script>
 <script>
  $(function() {
  
  var counter = $("#counter");
  
  // a workaround for a flaw in the 
  //demo system (http://dev.jqueryui.com/ticket/4375), ignore!
  //$( "#dialog:ui-dialog" ).dialog( "destroy" );
		
  var year_id1 = $( "#year_id1" ), 
      entity_id1 = $("#entity_id1"); 
   allFields = $( [] ).add( year_id1 ).add( entity_id1),
   tips = $( ".validateTips" );

   function updateTips( t ) {
     tips
       .text( t )
       .addClass( "ui-state-highlight" );
     setTimeout(function() {
      tips.removeClass( "ui-state-highlight", 1500 );
     }, 500 );
   }

   function ifValid(o) {
     if (o.val() == 0) {
       o.addClass("ui-state-error" );
       updateTips("Please choose an option: ");
       return false;
     } else 
       return true;
   }

   $( "#dialog-form" ).dialog({
     autoOpen: true,
     height: 200,
     width: 500,
     modal: true,
     buttons: {
       "OK": function() {
         var bValid = true;
	 allFields.removeClass( "ui-state-error" );
	 bValid = bValid && ifValid(entity_id1) && ifValid(year_id1);
	 
         if (bValid) {
           document.form1.submit();
           //$( this ).dialog( "close" );
         }
       },
       Cancel: function() {
         $( this ).dialog( "close" );
           location.href='index.php?action=logout'; 
       },
     },
     close: function() {
       allFields.val( "" ).removeClass( "ui-state-error" );
       location.href='employee.php';
     }
 });
 });
 </script>
 </head>
 <body>
 <div id="dialog-form" title="PayPro" >
  <p class="validateTips">Please make a choice</p>
   <form name='form1' method='post' action='choose_year_and_month.php'>
    <table border="0">
     <tr class='class1'><td colspan='2' 
   style='text-align:center; font-size:2em; font-weight:normal;'>
  </td></tr>

<tr>
    <td>Year</td>
    <td>
     <select name='year_id' id='year_id'>
     <?php
       $arr = array('2012', '2013', '2014', '2015','2016','2017',);
       foreach ($arr as $id => $value)
         echo "<option value='{$value}'>{$value}</option>"; 
     ?>
     </select>
    </td>
  </tr>
  <tr>
    <td>Month</td>
    <td>
     <select name='month_id' id='month_id'>
     <?php
       $arr = array('January', 'February', 'March', 'April','May', 'June', 'July', 'August', 
        'September', 'October', 'November', 'December');
       for($i = 0; $i <= 12; $i++) 
         echo "<option value='" . ($i + 1) . "'>{$arr[$i]}</option>"; 
     ?>
     </select>
    </td>
  </tr>
  <input type='hidden' name='action' value='OK'/>
 </form>
 </table>
 </div>
</body>
</html>
