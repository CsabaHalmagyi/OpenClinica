<?php
require_once 'includes/connection.inc.php';


session_start();
$_SESSION = Array();
redirect("index.php");
die();
?>