<?php
/*
* This is an example setting file for ODIN. This file is always skipped when ODIN loads the settings
* Save this file as XYZ.inc.php and modify the values in order to add a new OC instance to ODIN.
*
*/

//instance name
$ocInstanceName = "My OC Instance";
$ocUrl = "http://mydomain/OpenClinica/";

//database credentials
$dbhost = "localhost";
$dbuser = "mydatabaseusername";
$dbpass = "mydatabasepassword";
$db = "openclinica";

//webservices
$ocWsInstanceURL = "http://mydomain/OpenClinica-ws/";

//Allow ODIN to import directly to this OC instance?
$allowImport = false;


?>