<?php

require_once "classes/OpenClinicaSoapWebService.php";

require_once "classes/OpenClinicaODMFunctions.php";

require_once 'classes/PHPExcel.php';

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);

set_time_limit(60);

$inputFileName = 'uploads/data_'.$_SESSION['importid'].'_.csv';
//connect to the database
$dbh = new PDO("pgsql:dbname=$db;host=$dbhost", $dbuser, $dbpass );


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
//$ocEnrollmentDate = '2014-05-19';
//$ocPersonID = '1107-TEST-07';
//$ocGender = 'm';
//$ocDateOfBirth = '1989-12-16';


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
try {
	$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
	$objReader = PHPExcel_IOFactory::createReader($inputFileType);
	$objPHPExcel = $objReader->load($inputFileName);
} catch (Exception $e) {
	die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
			. '": ' . $e->getMessage());
}

//  Get worksheet dimensions
$sheet = $objPHPExcel->getSheet(0);
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();


//  Loop through each row of the worksheet in turn
for ($row = 2; $row <= $highestRow; $row++) {
	//  Read a row of data into an array
	$subj = $sheet->getCell('A'.$row)->getValue();
    $secondary = $sheet->getCell('B'.$row)->getValue();
    $enrollment = $sheet->getCell('C'.$row)->getValue();
    $person = $sheet->getCell('D'.$row)->getValue();
    $gender = strtolower($sheet->getCell('E'.$row)->getValue());
	$dob = $sheet->getCell('F'.$row)->getValue();
    
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

		//send a subjectCreateSubject request to the server
      	$createSubject = $client->subjectCreateSubject($ocUniqueProtocolId,
			$ocUniqueProtocolIDSiteRef, $subj, $secondary,
			$enrollment, $person, $gender, $dobFinal);
      
      	// if the import is successful
        if ($createSubject->xpath('//v1:result')[0] == 'Success') {
        $newSubjectsCount++;	 
        echo '<p>createSubject:<span class="success">' . $createSubject->xpath('//v1:result')[0] . "</span><br/>"; }
        //if the import is failed
        else {
        echo '<p>createSubject:<span class="error">' . $createSubject->xpath('//v1:error')[0] . "</span><br/>"; }
        
		
        

        //GET THE SUBJ OID HERE
        //check the site name
        if (isset($_SESSION['siteprotname']) && strlen($_SESSION['siteprotname'])>0){
        $sql= "SELECT ss.oc_oid,ss.label, s.unique_identifier FROM study_subject ss, study s 
        		WHERE ss.label= '".$subj."' 
        		AND s.study_id = ss.study_id AND s.unique_identifier='".$_SESSION['siteprotname']."'";
        
        $sth = $dbh->prepare($sql);
        $sth->execute();
        
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        
        
        }
        // if there was no site chosen, use the study instead
        else {
        $sql= "SELECT ss.oc_oid,ss.label, s.unique_identifier FROM study_subject ss, study s 
        		WHERE ss.label= '".$subj."' 
        		AND s.study_id = ss.study_id AND s.unique_identifier='".$ocUniqueProtocolId."'";
        
        
        $sql= "SELECT ss.oc_oid,ss.label, s.unique_identifier
        		      FROM study_subject ss, study s   
        		WHERE ss.label= '".$subj."'        
        		AND ss.study_id = s.study_id        
        		AND (s.parent_study_id = (          
        			select distinct s2.study_id          
        			from study s2          
        			where s2.unique_identifier='".$ocUniqueProtocolId."')          
        			or s.unique_identifier='".$ocUniqueProtocolId."' )";
        }     
        
        $sth = $dbh->prepare($sql);
        $sth->execute();
        
        $result = $sth->fetch(PDO::FETCH_ASSOC);


			//display the result
        	echo 'Subject name in xlsx: '.$subj."<br/>";
        	if (isset($result['oc_oid'])){
        		$succesCount++;
        		$subjoid = $result['oc_oid'];
        		$label = $result['label'];
        		
        		echo '<span class="success">';
        		echo 'SOID = '.$subjoid.'</span>';
        		echo ' label = '.$label.'</p>';
        	}
        	else{
        		$failCount++;
        		$subjoid = "MISSING_SUBJ_OID";
        		echo '<span class="error">';
        		echo 'SOID = '.$subjoid.'</span>';
        		echo ' label = SUBJECT NOT FOUND! </p>';
        	}
        
       
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1,$row, $subjoid);
	flush();
	ob_flush();
  
       
}//end of reading xlsx

//remove date of birth and gender columns from the data file, add subj_oid column
$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1,1, 'SUBJ_OID');
$objPHPExcel->getActiveSheet()->removeColumn('C');
$objPHPExcel->getActiveSheet()->removeColumn('C');
$objPHPExcel->getActiveSheet()->removeColumn('C');
$objPHPExcel->getActiveSheet()->removeColumn('C');
//create oid data file
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
$objWriter->save("temp/oid_".$_SESSION['importid']."_.csv");

//var_dump($subjects);

echo '<p>';
echo 'Subjects import finished.<br/><br/>';
echo 'Successful imports: '.$succesCount.'<br/>';
echo 'New subjects: '.$newSubjectsCount.'<br/>';
echo 'Errors: '.$failCount.'<br/></p>';

echo '<p><a href="schedule.php" class="easyui-linkbutton" data-options="iconCls:\'icon-large-clipart\'">Continue to scheduling events</a></p>';
/* 


 */
?>

<?php 
require_once 'includes/html_bottom.inc.php';
?>
