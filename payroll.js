function display_element(id) {
  document.getElementById(id).style.display='inline';
}
function hide_element(id) {
  document.getElementById(id).style.display='none';
}

function do_me(elem_id) {
  var id = "xxx_" + elem_id;
  var sIndex = document.getElementById(id).selectedIndex;
  var text = document.getElementById(id).options[sIndex].text;
  document.getElementById(elem_id).value=text;
  hide_element(id);
}
	 
function get_location(extra) {
  //Remove any previous values;
  document.form1.location_id.options.length = 0; 

  if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {// code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
      var json = eval (xmlhttp.responseText); 
	  
      if ((json.length > 0) && (extra != 0))  {
        newData = new Option(extra,'0',false, false);
        location_id = document.form1.location_id;
        location_id.options[location_id.options.length] = newData; 
      }
      for(i=0; i < json.length; i= i+2) {
        //Create a new Option Object
        newData = new Option(json[i+1], //text property
                             json[i],  // value property
                             false,    // The defaultSelecte property
                             false);   //The selected property
        location_id = document.form1.location_id;
        location_id.options[location_id.options.length] = newData; 
      }  
    } 
  } 
      
  var sIndex = document.form1.branch_id.selectedIndex;
  var branch_id = document.form1.branch_id.options[sIndex].value;
  url="get_location.php?branch_id="+branch_id;
  xmlhttp.open("GET",url,true);
  xmlhttp.send();
}

function get_employees(extra) {
  //Delete any previous employee
  document.form1.employee_id.options.length = 0;

  if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {// code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
      var json = eval (xmlhttp.responseText); 

      if ((json.length > 0) && (extra != 0))  {
        newData = new Option(extra,'0',false, false);
        employee_id= document.form1.employee_id;
        employee_id.options[employee_id.options.length] = newData; 
      }

      for(i=0; i < json.length; i= i+2) {
        //Create a new Option Object
        newData = new Option(json[i+1], //text property
                             json[i],  // value property
                             false,    // The defaultSelecte property
                             false);   //The selected property
        employee_id= document.form1.employee_id;
        employee_id.options[employee_id.options.length] = newData; 
      } 
    }  
  } 

  var sIndex = document.form1.location_id.selectedIndex;
  var location_id= document.form1.location_id.options[sIndex].value;
  url="get_employee.php?location_id="+location_id+"&first=empty";
  xmlhttp.open("GET",url,true);
  xmlhttp.send();
}

function get_employee2(path) {
   var sIndex = document.form1.branch_id.selectedIndex;
   var branch_id = document.form1.branch_id.options[sIndex].value;
   var host = window.location.hostname;
   var url = "get_employee2.php?branch_id=" + branch_id + "&rand=" + Math.random();
   get_objects(url, 'employee');
}  

function get_employee(path) {
   var sIndex = document.form1.branch_id.selectedIndex;
   var branch_id = document.form1.branch_id.options[sIndex].value;
   var host = window.location.hostname;
   var url = "get_employee.php?branch_id=" + branch_id + "&rand=" + Math.random();
   get_objects(url, 'employee');
}


function get_objects(url, target_id) {
   if (window.XMLHttpRequest) {
    agax = new XMLHttpRequest();
   } else if (window.ActiveXObject) {
    agax = new ActiveXObject('Microsoft.XMLHTTP');
   }
   if (agax) {
     agax.open('GET', url, true);
     agax.onreadystatechange = function () {
       if (agax.readyState == 4 && agax.status == 200) {
         var agaxText = agax.responseText;
         document.getElementById(target_id).innerHTML = agaxText;
       }};
     agax.send(null);
   } else {
    alert("Error in Connecting to server");
  }
}

