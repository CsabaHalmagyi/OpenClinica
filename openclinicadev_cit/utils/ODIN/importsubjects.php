<?php

require_once "classes/OpenClinicaSoapWebService.php";

require_once "classes/OpenClinicaODMFunctions.php";

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);
unset($_SESSION['subjectOIDMap']);

set_time_limit(60);

//check the study name
if (isset($_SESSION['studyprotname']) && strlen($_SESSION['studyprotname'])>0){
 $ocUniqueProtocolId = $_SESSION['studyprotname'];   
}
else {
	echo '<p><br/>Studyname parameter is missing!</p>';
	die();
}
//check the site name
if (isset($_SESSION['siteprotname']) && strlen($_SESSION['siteprotname'])>0){
	$ocUniqueProtocolIDSiteRef = $_SESSION['siteprotname'];   
}
else {
	$ocUniqueProtocolIDSiteRef = null;
}

$ocSecondaryLabel = '';

function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

$client = new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION['user_name'], $_SESSION['passwd']);

$succesCount = 0;
$failCount = 0;
$newSubjectsCount = 0;
//  Read the excel data file

$csvdata = $_SESSION['csvdata'];
$highestRow = intval($_SESSION['csvmaxrow']);
//for storing subjectID => oid relations
$subjectOIDMap = array();

echo '<table id="importsubjectstable"><thead><tr><td>Subject</td><td>Subject status</td><td>OID</td><td>Result</td></tr></thead><tbody>';

for ($row = 1; $row < $highestRow; $row++) {
	//  Read a row of data into an array
	$subj = $csvdata[$row][0];
    $secondary = $csvdata[$row][1];
    $enrollment = $csvdata[$row][2];
    $person = $csvdata[$row][3];
    $gender = strtolower($csvdata[$row][4]);
	$dob = $csvdata[$row][5];
    
	$dobFinal="";
	$lastCase = '';
	if ($_SESSION['studyParamConf']['SPL_collectDob']=="1" || $_SESSION['studyParamConf']['SPL_collectDob']==""){
		$dobFormat = "/(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";
		//if date is UK standard format
		if (preg_match($dobFormat,$dob)){
			$dateParts = explode("/",$dob);
			
			//transform the date to YYYY-MM-DD format
			$dobFinal = $dateParts[2]."-".$dateParts[1]."-".$dateParts[0];
		}
		else {
			$dobFinal=$dob;
		}

	}
	if ($_SESSION['studyParamConf']['SPL_collectDob']=="2"){

		$dobFormat = "/(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";
		$dobFormat2 = "/^([0-9]{4})$/";
		$dobFormat3 = "/(^([0-9]{4})-([0-9]{2})-([0-9]{2})$)/";
		
		//if date is UK standard format
		if (preg_match($dobFormat,$dob)){
			$dateParts = explode("/",$dob);

			//transform the date to YYYY
			$dobFinal = $dateParts[2];

		}
		//if date is not UK standard format
		else if (preg_match($dobFormat2,$dob)){
			//create a default YYYY-01-01
			$dobFinal = $dob;
		}
		else if (preg_match($dobFormat3,$dob)){
			$dateParts = explode("-",$dob);

			$dobFinal = $dateParts[0];
			$lastCase.=5;
		}

		
	}	
	
	if ($_SESSION['studyParamConf']['SPL_collectDob']=="3"){
			//if the dob is not collected 
			$dobFinal = null;
	}	
	//if the enrollment date is empty set it to the actual day
	if($enrollment==null){
		$enrollment = Date("Y-m-d");
	}
	//if there is a value for enrollment
	else {
		//and it is UK format
		$dateFormat = "/(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";
		if (preg_match($dateFormat,$enrollment)){
			$dateParts = explode("/",$enrollment);
			
			//transform the date to YYYY-MM-DD format
			$enrollment = $dateParts[2]."-".$dateParts[1]."-".$dateParts[0];
		}
	}

		// determine whether a subject is an existing subject
		$isStudySubject = $client->subjectIsStudySubject($ocUniqueProtocolId, $ocUniqueProtocolIDSiteRef, $subj);
		$subjOID = null;
		if ($isStudySubject->xpath('//v1:result')[0]=='Success'){
			$subjOID = (string)$isStudySubject->xpath('//v1:subjectOID')[0];
			$subjectOIDMap[$subj] = array("subjID"=>$subj,"oid"=>$subjOID,"existed"=>true, "error"=>null);
			echo '<tr><td>'.$subj.'</td><td>Existing</td><td>'.$subjOID.'</td><td><span class="success">OK</span></td></tr>';
		}
		else{

			//send a subjectCreateSubject request to the server
			$createSubject = $client->subjectCreateSubject($ocUniqueProtocolId,
					$ocUniqueProtocolIDSiteRef, $subj, $secondary,
					$enrollment, $person, $gender, $dobFinal);
			
			// if the import is successful
			if ($createSubject->xpath('//v1:result')[0] == 'Success') {
				
				$isStudySubject = $client->subjectIsStudySubject($ocUniqueProtocolId, $ocUniqueProtocolIDSiteRef, $subj);
				if ($isStudySubject->xpath('//v1:result')[0]=='Success'){
					$subjOID = (string)$isStudySubject->xpath('//v1:subjectOID')[0];
					$subjectOIDMap[$subj] = array("subjID"=>$subj,"oid"=>$subjOID,"existed"=>false, "error"=>null);
					echo '<tr><td>'.$subj.'</td><td>New</td><td>'.$subjOID.'</td><td><span class="success">OK</span></td></tr>';
					$newSubjectsCount++;
						
				}
				else{
					$err = (string)$createSubject->xpath('//v1:error')[0];
					$subjectOIDMap[$subj] = array("subjID"=>$subj,"oid"=>"MISSING_SUBJECT_OID","existed"=>false, "error"=>$err);
					echo '<tr><td>'.$subj.'</td><td>Error</td><td>MISSING_SUBJECT_OID</td><td><span class="error">'.$err.'</span></td></tr>';
						
				}
			}
				//if the import is failed
			else {
				$err = (string)$createSubject->xpath('//v1:error')[0];
				$subjectOIDMap[$subj] = array("subjID"=>$subj,"oid"=>"MISSING_SUBJECT_OID","existed"=>false, "error"=>$err);
				echo '<tr><td>'.$subj.'</td><td>Error</td><td>MISSING_SUBJECT_OID</td><td><span class="error">'.$err.'</span></td></tr>';
				
				}
		}
	
        	if ($subjOID!=null){
        		$succesCount++;
        	}
        	else{
        		$failCount++;
        	}
        
       
	flush();
	ob_flush();
  
       
}//end of reading csvdata


echo '</tbody></table><br/>';
echo '<p>Subjects import finished.<br/><br/>';
echo 'Subj OIDs retrieved: '.$succesCount.'<br/>';
echo 'New subjects: '.$newSubjectsCount.'<br/>';
echo 'Errors: '.$failCount.'<br/></p>';

$_SESSION['subjectOIDMap'] = $subjectOIDMap;

echo '<br/>';

if ($succesCount>0)
	echo '<p><a href="schedule.php" class="easyui-linkbutton" data-options="iconCls:\'icon-large-clipart\'">Continue to scheduling events</a></p>';
else {
	echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';
}
/* 


 */
?>

<?php 
require_once 'includes/html_bottom.inc.php';
?>
