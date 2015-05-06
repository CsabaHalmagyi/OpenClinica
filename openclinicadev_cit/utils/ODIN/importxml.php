<?php


require_once "classes/OpenClinicaSoapWebService.php";

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';

is_logged_in();
require($_SESSION['settingsfile']);
?>

<?php 

if (isset($allowImport) && $allowImport){
//connect to webservices
$client = new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION['user_name'], $_SESSION['passwd']);


$xmlName = "import_".$_SESSION['importid'].".xml";
//$xmlName = "variable.xml";

$ODMxml=file_get_contents("savedxmls/".htmlspecialchars($_SESSION['user_name']).'/'.$xmlName);
//$ODMxml->load("savedxmls/".$xmlName);
//$xmlstring = $ODMxml->saveXML();
$xmlstring = $ODMxml;

//file_put_contents('savedxmls/variable.xml',$xmlstring);
//send data import request through webservices
$import = $client->dataImport($xmlstring);

echo '<br/><br/>';
//echo $client->__getLastRequest();
echo '<br/><br/>';
if ($import->xpath('//v1:result')[0]=="Success") echo '<p><span class="success">Import finished successfully!</span></p>';
if ($import->xpath('//v1:result')[0]=="Fail") {
	echo '<p><span class="error">Import failed due to the following error(s):</span><br/>';
	echo '<span class="error">'.$import->xpath('//v1:error')[0] . '</span></p>';
}

echo '<p><br/><a href="index.php?import_session=reset" class="easyui-linkbutton" data-options="iconCls:\'icon-blank\'">Start a new import</a><br/><br/></p>';

}
else{
	echo '<span class="error">You are not allowed to import directly to this instance!</span><br/>';
}
require_once 'includes/html_bottom.inc.php';

?>