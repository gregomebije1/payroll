function enable_element(id) {
  document.getElementById(id).disabled=""; 
}
function disable_element(id) {
  document.getElementById(id).disabled="disabled"; 
}
function display_element(id) {
  document.getElementById(id).style.display='block';
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
	 
function showResult(str, elem_id, livesearch, sql) {
  if (str.length==0) { 
    document.getElementById(elem_id).innerHTML="";
    document.getElementById(elem_id).style.border="0px";
    return;
  }
  if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {// code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
	  var json = eval (xmlhttp.responseText); 
	  /*
	  var newData = "<ul style=\"list-style-type:none; padding: 0; margin:0; border: 1px solid grey\">" + "\n";
	  var style = " font:8pt tahoma; color:#204d89; padding:0; margin: 0; cursor:pointer;";
	  for(i=0; i < json.length; i++) {
		//newData += "<li id=\"" + i + "\" style=\"" + style + "\"><a onclick=\"display(" + i + "," + elem_id + ")\">" + json[i] + "</a></li>";
		newData += "<li id=\"" + i + "\" onmouseover=\"this.style.color='red';\"  onmouseout=\"this.style.color='#204d89';\" onclick=\"do_me(" + i + ");\" style=\"" + style + "\">" + json[i] + "</li>" + "\n";
	  }
	  newData += "</ul>";
	  alert(newData);
	  */
	 
	  var newData = "<select id='xxx" + "_" + elem_id + "' name='xxx" + "_" + elem_id + "' size='" + (json.length + 2) + "' onchange=\"do_me('" + elem_id + "');\">";
	  for(i=0; i < json.length; i++) {
	    newData += "<option>" + json[i] + "</option>";
	  }
	  newData += "</select>";
	  document.getElementById(livesearch).innerHTML=newData;
	  document.getElementById(livesearch).style.border="1px solid #A5ACB2";
	}
  }
  url="livesearch.php?q="+str+"&sql="+sql;
  xmlhttp.open("GET",url,true);
  xmlhttp.send();
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

function get_permissions_in_u_permissions() {
  var element = document.form1.u_permissions;
  var value="";
  for(var i = 0; i < element.options.length; i++) {
    //if (element.options[i].selected) 
    if (i == (element.options.length - 1)) {
      value += element.options[i].value;
    } else {  
      value += element.options[i].value + "|";
    }
  }
  document.form1.u_permissions_members.value = value;
  //alert(document.form1.u_permissions_members.value);
}

function transfer() {
   //Get the subject selected
   var sIndex = document.form1.pid.selectedIndex;
   var len = document.form1.pid.options.length;
   if ((sIndex < 0) || (sIndex >= len)) {
     alert('Please choose a permission to add');
     return;
   }
   var ptext = document.form1.pid.options[sIndex].text;
   var pvalue = document.form1.pid.options[sIndex].value;

   //Create a new Option Object
   var new_pid = new Option(ptext, //The text property
                               pvalue, //The value property
                               false,   // The defaultSelected property 
                               false);  // The selected property

   //Display it in s_permissions element by appending it to the options array
   var u_permissions = document.form1.u_permissions;
   u_permissions.options[u_permissions.options.length]=new_pid;

   //Remove the subject from class_subject element
   document.form1.pid.options[sIndex] = null;
  
   get_permissions_in_u_permissions();
}


function transfer2() {
   //Get the student in the class 
   var sIndex = document.form1.u_permissions.selectedIndex;
   var len = document.form1.u_permissions.options.length;
   if ((sIndex < 0) || (sIndex >= len)) {
     alert('Please choose a subject to remove');
     return;
   }
   var ptext = document.form1.u_permissions.options[sIndex].text;
   var pvalue = document.form1.u_permissions.options[sIndex].value;

   //Create a new Option Object
   var new_p = new Option(ptext, //The text property
                          pvalue, //The value property
                               false,   // The defaultSelected property 
                               false);  // The selected property

   //Display it in class_subject element by appending it to the options array
   var pid = document.form1.pid
   pid.options[pid.options.length]=new_p;

   //Remove the student from student element
   document.form1.u_permissions.options[sIndex] = null;

   get_permissions_in_u_permissions();
}

