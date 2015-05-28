<?php
require_once 'includes/connection.inc.php';
is_logged_in();


if (count($_GET)!=0) {
	if (isset($_GET['type'])){
		$type=$_GET['type'];
	}
	if ($type=="") die('Missing type!');
	
	if (isset($_GET['id'])){
		$id=$_GET['id'];
	}
	if ($id=="") die('Missing id!');
	
	
	if ($type=="x") {
		$fileName = "import_".$id.".xml";
		$file = 'savedxmls/'.htmlspecialchars($_SESSION['user_name']).'/'.$fileName;
	}
	else if ($type=="m"){
		$fileName = "map_".$id."_.csv";
		$file = 'map/'.htmlspecialchars($_SESSION['user_name']).'/'.$fileName;
		
	}
	else if ($type == "mtemp"){
		$fileName = "odin_mapping_template.csv";
		$file = 'templates/'.$fileName;
	}
	else if ($type == "dtemp"){
		$fileName = "odin_data_template.csv";
		$file = 'templates/'.$fileName;
	}
 
    if(!file_exists($file)) die("I'm sorry, the file doesn't seem to exist.");

    $ftype = filetype($file);
    // Get a date and timestamp
    $today = date("F j, Y, g:i a");
    $time = time();
    // Send file headers
    header("Content-type: $ftype");
    header("Content-Disposition: attachment;filename={$fileName}");
    header("Content-Transfer-Encoding: binary"); 
    header('Pragma: no-cache'); 
    header('Expires: 0');
    // Send the file contents.
    set_time_limit(0); 
    readfile($file);
	}
	else die('Missing parameters!');
?>