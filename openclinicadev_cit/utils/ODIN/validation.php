<?php
require_once "classes/OpenClinicaSoapWebService.php";
require_once "classes/OpenClinicaODMFunctions.php";
require_once 'classes/PHPExcel.php';

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);



$ocUniqueProtocolId = $_SESSION['studyprotname'];

//connect to webservices
$client = new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION['user_name'], $_SESSION['passwd']);
//get metadata from server
$getMetadata = $client->studyGetMetadata($ocUniqueProtocolId);
$odmMetaRaw = $getMetadata->xpath('//v1:odm');
$odmMeta = simplexml_load_string($odmMetaRaw[0]);
$odmMeta->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);

$studoid = (string)$odmMeta->Study->attributes()->OID;

//extract the site oid if a site was selected

if (isset($_SESSION['siteprotname']) && strlen($_SESSION['siteprotname'])>0){
	
	foreach($odmMeta->Study as $studies){
		
		$siteref = $ocUniqueProtocolId." - ".$_SESSION['siteprotname'];
		
		if ((string)$studies->GlobalVariables->ProtocolName == $siteref){
			$_SESSION['siteoid']=(string)$studies->attributes()->OID;
		}
	}
	
}


if (strlen($studoid)>0){
	$_SESSION['studyoid']=$studoid;
}

//get the study parameter configuration
$studyParamConf = array();

foreach ($odmMeta->Study->MetaDataVersion as $MetaDVer){
	$namespaces = $MetaDVer->getNameSpaces(true);
	$OpenClinica = $MetaDVer->children($namespaces['OpenClinica'])->StudyDetails;
	
	foreach ($OpenClinica->StudyParameterConfiguration->StudyParameterListRef as $oc){

			$subattr = $oc->attributes();
			$studyParamConf[(string)$subattr['StudyParameterListID']] = (string)$subattr['Value'];
	
}
}


//add the study parameter configuration settings to the session
$_SESSION['studyParamConf']=$studyParamConf;
echo '<p>Current study: <span class="success">'.$ocUniqueProtocolId.'</span></p>';
echo '<p>Datafile structure must be the following: <br/>';
echo 'SubjectId SecondaryId EnrollmentDate PersonId Gender DOB + followed by the data fields.<br/></p>';

//var_dump($studyParamConf);
//echo '<br/>';
//read the xls data file
$inputFileName = 'uploads/data_'.$_SESSION['importid'].'_.csv';

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

$excelData = array();
//  Read all rows
for ($row = 1; $row <= $highestRow; $row++) {
	$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
			NULL, TRUE, FALSE);	
	array_push($excelData, $rowData[0]);
		
}
//Reading file ends here
//VALIDATION STARTS HERE
$errors = [];

//check valid datafile header structure
//SubjectId
if (strtolower($excelData[0][0])!='subjectid') {
	$errors[] = 'The first column header must be called SubjectId!';
}
//SecondaryId
if (strtolower($excelData[0][1])!='secondaryid') {
	$errors[] = 'The second column header must be called SecondaryId!';
}
//EnrollmentDate
if (strtolower($excelData[0][2])!='enrollmentdate') {
	$errors[] = 'The third column header must be called EnrollmentDate!';
}
//SecondaryId
if (strtolower($excelData[0][3])!='personid') {
	$errors[] = 'The fourth column header must be called SecondaryId!';
}
//Gender
if (strtolower($excelData[0][4])!='gender') {
	$errors[] = 'The fifth column header must be called Gender';
}
//DOB
if (strtolower($excelData[0][5])!='dob') {
	$errors[] = 'The sixth column header must be called DOB';
}
// datafile headers done

//CHECKING IF SUBJECT ID IS NEVER EMPTY
for ($i=1;$i<$highestRow;$i++){
	if ($excelData[$i][0] == null) {
		$errors[] = 'SubjectId must never be empty!';
		break;
	}
}
//CHECK IF ENROLLMENT DATE IS IN A VALID FORMAT

for ($i=1;$i<$highestRow;$i++){
	if ($excelData[$i][2] == null) continue;
	else {
		$enrollment = $excelData[$i][2];
		//format can be YYYY-MM-DD or DD/MM/YYYY
		$dateFormat = "/(^([0-9]{4})-([0-9]{2})-([0-9]{2})$)|(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";

		//if the enrollment date format is NOT uk or iso format
		if (!(preg_match($dateFormat,$enrollment))){
		$errors[] = 'Wrong enrollment date format! '.$enrollment;
		break;}
	}
}


//DATE OF BIRTH CELLS VALIDATION
$dobFormat = "/(^([0-9]{4})-([0-9]{2})-([0-9]{2})$)|(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";
//if collect date of birth
if($studyParamConf['SPL_collectDob'] =="1") {
	//format can be YYYY-MM-DD or DD/MM/YYYY
	$dobFormat = "/(^([0-9]{4})-([0-9]{2})-([0-9]{2})$)|(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";
}
//if collect year of birth only
if($studyParamConf['SPL_collectDob'] =="2") {
	//format can be YYYY-MM-DD or YYYY or DD/MM/YYYY
	$dobFormat = "/(^([0-9]{4})-([0-9]{2})-([0-9]{2})$)|(^[0-9]{4}$)|(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";
}

//checking date of birth format in file
if ($studyParamConf['SPL_collectDob'] =="1" || $studyParamConf['SPL_collectDob']=="2"){
	for ($i=1;$i<$highestRow;$i++){
		if (!preg_match($dobFormat,$excelData[$i][5])) {
			
			$errors[] = 'Incorrect DOB format! '.$excelData[$i][5];;
			break;
	}
}
}

//CHECK IF PERSON ID IS A REQUIRED FIELD
// if the person id is required
if ($studyParamConf['SPL_subjectPersonIdRequired'] =='required'){
	for ($i=1;$i<$highestRow;$i++){
		if ($excelData[$i][3] == null) {
			$errors[] = 'Missing person id!';
			break;
		}
	}
}

//CHECK IF THE SUBJECT GENDER IS A REQUIRED FIELD
if ($studyParamConf['SPL_genderRequired'] =='true'){
	for ($i=1;$i<$highestRow;$i++){
		//if gender is null or not m or not f
		if ($excelData[$i][4] == null || !(strtolower($excelData[$i][4]) == "m" || strtolower($excelData[$i][4]) == "f")) {
			$errors[] = 'Missing gender or invalid format (must be m or f)! '.$excelData[$i][4];
			break;
		}
	}
}







//check if errors array is empty
if (empty($errors)){
	
	echo '<p><span class="success">There were no errors in the data file.</span><br/>';
	echo '<a href="importsubjects.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue to import subjects</a></p>';
	
}
else {
	
	echo '<p>The following error(s) occured:<br/>';
	foreach ($errors as $err){
		echo '<span class="error">'.$err.'</span><br/>';

	}
	echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';
}


require_once 'includes/html_bottom.inc.php';



?>