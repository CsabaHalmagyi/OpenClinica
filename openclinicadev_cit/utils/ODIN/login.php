<?php
/**
 * Displays a login form.
 * 
 * @author Csaba Halmagyi
 * 
 * 
 */
require_once 'includes/connection.inc.php';
require_once 'config/odinconfig.inc.php';
is_logged_out();
//if the login form was submitted
if (count($_POST)!=0) {
	//create an empty error array
    $error = Array();
    
    //check if the posted data meets the requirements
    if ((strlen($_POST['username'])<=0)) $error[1]="Username is too short!";
    if ((strlen($_POST['password'])<=4)) $error[2]="Password is too short!";
	$settingsfile="settings/".$_POST['instance'].".inc.php";
	//load the settings file
    if (!require($settingsfile)){
    	$error[3]="Invalid OC instance! Instance settings can not be loaded!";
    	 
    }
    //if the form was filled properly
    if (count($error)==0) {
        $sha1pass=sha1($_POST['password']);
        
        try {
        $dbh = new PDO("pgsql:dbname=$db;host=$dbhost", $dbuser, $dbpass );
        }catch (PDOException $e) {
   			 echo 'Connection failed: ' . $e->getMessage();
			}
        $query = "SELECT * FROM user_account WHERE user_name='".trim($_POST['username'])."' AND status_id=1";
        
        $sth = $dbh->prepare($query);
        $sth->execute();
        
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        
        if (!$result['user_id']) {        		
        	$error[4]='Username and password combination is wrong! <br/>
        				Forgot your password? Contact '.$adminContactEmail;}
        	
        //username and password is ok
        else if ($result['passwd']==$sha1pass){
			//if the user is authorised to use web services
        	if ($result['run_webservices']){
        	$_SESSION=$result;
        	$_SESSION['settingsfile']=$settingsfile;
        	$_SESSION['importid']=uniqid();
        	
        	//delete old files if setting is enabled
        	
        	if(isset($deleteFilesOlderThan) && $deleteFilesOlderThan>0){
        		
        		removeFiles('./uploads',time()-$deleteFilesOlderThan);
        		removeFiles('./map',time()-$deleteFilesOlderThan);
        		removeFiles('./savedxmls',time()-$deleteFilesOlderThan);
        		
        	}

        	redirect("index.php");
        	}
        	else {
        		$error[5] = "User is not authorised to use webservices! Logging out.";
        	}    	      
        	}
        	else {
        		$error[4]='Username and password combination is wrong! <br/>
        				Forgot your password? Contact '.$adminContactEmail;;
        	}
    }
    
 }
 require_once 'includes/html_top.inc.php';
?>

<div id="logindiv">
<form id="loginform" class="rounded" method="post" action="login.php">
<h2>Login</h2>

     <table name="logintable">

    <tr><td><label for="name">OC Username:</label></td>
    <td><input type="text" class="input" name="username" id="username" /></td></tr>

 

<tr><td>
    <label for="password">OC Password:</label></td>
    <td><input type="password" class="input" name="password" id="password" /></td></tr>


  

    <tr><td><label for="name">OC Instance:</label></td>
<?php 
//read all the settings files
$settingFiles = array();
$dir = 'settings';
$cdir = scandir($dir);
foreach ($cdir as $key => $value)
{
	if (!in_array($value,array(".","..","example.inc.php")))
	{
		if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
		{
			$settingFiles[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
		}
		else
		{
			$settingFiles[] = $value;
		}
	}
}
?>    
    
<td><select name="instance" id="instance">
<?php 
foreach ($settingFiles as $sf){
	$handle = fopen ( $dir.'/'.$sf, "r" );
	$ocName = '';
	$val='';
	if ($handle) {
		while ( ($line = fgets ( $handle )) !== false) {
			$words = explode ( "=", $line, 2 );
			if (trim($words[0])=='$ocInstanceName'){
				$ocName = str_replace('"','',trim($words[1]));
				$ocName = str_replace(';','',$ocName);
				
				$fileval = explode('.',$sf,2);
				$val = trim($fileval[0]);
				echo '<option value="'.$val.'">'.$ocName.'</option>';
				break;
			}
			
		}
	}
}
?>	
	
</select></td></tr>

</table>
  
<input type="submit" name="Submit"  class="button" value="Login" />
</form>
</div>

<?php 
if (isset($error) && count($error)!=0){
echo '<div id="errormessages">';
	foreach ($error as $err){
		echo $err.'<br/>';
	}
echo '</div>';	
}
?>

<?php
require_once 'includes/html_bottom.inc.php';