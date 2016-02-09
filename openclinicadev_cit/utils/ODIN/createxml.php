<?php

require_once "classes/OpenClinicaSoapWebService.php";
require_once "classes/OpenClinicaODMFunctions.php";
require_once 'classes/PHPExcel.php';

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';

is_logged_in();
require($_SESSION['settingsfile']);
?>
<p>
<?php 


//read post data sent by map.php or by itself
if (count($_POST)!=0) {

if (isset($_POST['mapdata'])){
	$mapdata = json_decode($_POST['mapdata'],true);


}

if (isset($_POST['eventsAll'])){
	$eventsAll = json_decode($_POST['eventsAll'],true);}

if (isset($_POST['formsAll'])){
	$formsAll = json_decode($_POST['formsAll'],true);}

if (isset($_POST['groupsAll'])){
	$groupsAll = json_decode($_POST['groupsAll'],true);}

if (isset($_POST['itemsAll'])){
	$itemsAll = json_decode($_POST['itemsAll'],true);}
			
// Skip empty cells in datafile?
if (isset($_POST['cbSkip'])){
	$skipEmptyCells = false;
	if ($_POST['cbSkip']=="true") {
		$skipEmptyCells = true;}
}

if (isset($_POST['formstatus']) && $_POST['formstatus'] == "entrystarted"){
	$formstatus = "dataentrystarted";
}
else{
	$formstatus = "complete";
}


//check the study name
if (isset($_SESSION['studyprotname']) && strlen($_SESSION['studyprotname'])>0){
	$ocUniqueProtocolId = $_SESSION['studyprotname'];
}
else {
	echo '<br/>Studyname parameter is missing!';
	die();
}

//SAVING THE MAPPING FILE
$mapFile = 'map/'.htmlspecialchars($_SESSION['user_name']).'/map_'.$_SESSION['importid'].'_.csv';
$objectPHPExcelMap = new PHPExcel();
$objectPHPExcelMap->setActiveSheetIndex(0);

//set excel headers
$objectPHPExcelMap->getActiveSheet()->SetCellValue('A1', 'ItemOID');
$objectPHPExcelMap->getActiveSheet()->SetCellValue('B1', 'ItemName');
$objectPHPExcelMap->getActiveSheet()->SetCellValue('C1', 'XlsName');

$rowCounter = 2;
foreach ($mapdata as $xlsHeader=>$itemFullOid){
	$objectPHPExcelMap->getActiveSheet()->SetCellValue('C'.$rowCounter, $xlsHeader);
	$objectPHPExcelMap->getActiveSheet()->SetCellValue('A'.$rowCounter, $itemFullOid);
	$itemNames = explode("##",$itemFullOid);
	$itemName = $eventsAll[$itemNames[0]]['name'].' '.$formsAll[$itemNames[1]]['name'].' '.$itemsAll[$itemNames[3]]['name'];
	$objectPHPExcelMap->getActiveSheet()->SetCellValue('B'.$rowCounter, $itemName);
	$rowCounter++;
}
$objWriter = PHPExcel_IOFactory::createWriter($objectPHPExcelMap, 'CSV');
$objWriter->save($mapFile);


//END SAVING THE MAPPING FILE

//Send the output buffer
flush();
ob_flush();

if (isset($_SESSION['siteoid']) && strlen($_SESSION['siteoid'])>0){
	$study = $_SESSION['siteoid'];
}
else{
	$study = $_SESSION['studyoid'];
	
}
$odmXML = new ocODMclinicalData($study, 1, array());

set_time_limit(3000);

//  READING THE DATA FILE FROM HERE
$csvdata = $_SESSION['csvdata'];
$highestRow = intval($_SESSION['csvmaxrow']);




	//  Read the header data into an array
	$excelHeaders = $csvdata[0];

	//read the rest of the data
	$excelDataArray= array();
	//  Loop through each row of the worksheet in turn
	for ($row = 1; $row < $highestRow; $row++) {
		
		//  Read a row of data into an array
		$rowData = $csvdata[$row];
		
		array_push($excelDataArray, $rowData);
	}

	echo '<table id="dataImportResult"><thead><tr><td>Subject name</td><td>Event id</td><td>O#</td><td>Form id</td><td>Item id</td>
			<td>Value</td><td>Result</td></tr></thead>';
	echo '<tbody>';
	//read all fields in the excel array
	for ($i=0;$i<sizeof($excelDataArray);$i++){
	
	//read subject oid from the array
	$subject=null;
	$subjectName = $excelDataArray[$i][0];
	
	if (isset($_SESSION['subjectOIDMap'][$subjectName])){
		$subject = $_SESSION['subjectOIDMap'][$subjectName]['oid'];
	}
	
	//if the oid is missing, skip that subject
	if ($subject=='MISSING_SUBJECT_OID') continue;
	//reset the event occurences for every subject
	$eventOccurrences = [];
		//read the data rows for the subject
		for ($j=6;$j<sizeof($excelDataArray[$i]);$j++){

		$headerValue = $excelHeaders[$j];
		
		$dataValue = $excelDataArray[$i][$j];
		
		//if the header can be found in the associated header list
		if (isset($mapdata[$headerValue])){
			$replaceValues = $mapdata[$headerValue];

			//pass the event, form, itemgroup, item oids to meta array
			$meta = explode('##',$replaceValues);
			

			
			//if there is no value in the xls and skipEmptyCells is enabled
			if (($dataValue == '') && $skipEmptyCells ){
				//skip that import
			continue;
			}
			else {
			//ADD SUBJECTS TO THE XML
			$eventOccurrence=1;
			// if the current event is a repeating event
			if ($eventsAll[$meta[0]]['repeating']=="Yes") {
				//if the occurrence was already determined
				if(isset($eventOccurrences[$meta[0]])){
					$eventOccurrence=$eventOccurrences[$meta[0]];
				}
				else {
					$dbh = new PDO("pgsql:dbname=$db;host=$dbhost", $dbuser, $dbpass );
				
					// deal with the repeating events here
			
					
					$sql =	"SELECT max(study_event.sample_ordinal) as last_event
					FROM
					public.study_subject
					INNER JOIN
					public.study_event ON study_subject.study_subject_id = study_event.study_subject_id
					INNER JOIN public.study_event_definition ON  study_event.study_event_definition_id = study_event_definition.study_event_definition_id
					AND study_subject.label = '".$subjectName."'
					AND study_event_definition.oc_oid = '".$meta[0]."'";
				
					$sth = $dbh->prepare($sql);
					$sth->execute();
				
					$result = $sth->fetch(PDO::FETCH_ASSOC);

					$eventOccurrence=$result['last_event'];
					if ($eventOccurrence == ""  || $eventOccurrence==null){ 
						$eventOccurrence=1;
						$result['last_event']=1;
					}
					$eventOccurrences[$meta[0]]=$result['last_event'];
				}
				
			}
				echo '<tr><td>'.$subjectName.'</td><td>'.$meta[0].'</td><td>'.$eventOccurrence.'</td><td>'.$meta[1].'</td><td>'.$meta[3].'</td><td>'.$dataValue.'</td>';

				$event = $meta[0];
				$form = $meta[1];
				$group = $meta[2];
				$item = $meta[3];
				$value = trim($dataValue);

				//check if the value is a repeating item value
				
				if (strpos($value,'::') !== FALSE){
					//this is a repeating item value
						$groupData = explode('::',$value);
						for($k=1;$k<sizeof($groupData);$k++){
							if (!empty($groupData[$k])){

								$odmXML->add_subject($subject, $event, $eventOccurrence, $form, $formstatus, $group,$k, $item, $groupData[$k]);
							}	
						}
						
				}
				else{
					//this is not a repeating item value
					//adding the value to the odmXML object.

					$odmXML->add_subject($subject, $event, $eventOccurrence, $form, $formstatus, $group,1, $item, $value);
				}
				

				
				echo '<td><span class="success">Done!</span></td></tr>';
		
				flush();
				ob_flush();				
			}
						
		}
		else {
			//if replacing rules were not set for that column, skip it
			continue;
		}
		
		
		}

	}//end reading lines from excel
	

	//create the xml file for the study
	$xml = ocODMtoXML(array($odmXML));
		
	
	$xmlName = "import_".$_SESSION['importid'].".xml";
	$importID = $_SESSION['importid'];
	//$xml->saveXML("savedxmls/".$xmlName);
	file_put_contents('savedxmls/'.htmlspecialchars($_SESSION['user_name']).'/'.$xmlName,$xml);
	echo '</tbody></table>';
	echo '<br/>';
	
	//CLOSE SESSION, REGENERATE IMPORT ID
	$old_importid="";
/* 	
	unset($_SESSION['studyprotname']);
	unset($_SESSION['studyoid']);
	unset($_SESSION['studyParamConf']);
	$old_importid = $_SESSION['importid'];
	$new_importid = uniqid();
	 */
	
	if (is_file("savedxmls/".htmlspecialchars($_SESSION['user_name']).'/'.$xmlName)){
		//$_SESSION['importid']=$new_importid;
		echo '<p><span class="success"><b>XML import file created successfully.</b></span><br/>';
		
		echo '<button type="button" onclick="location.href=\'download.php?type=x&id='.$importID.'\'">Download '.$xmlName.'</button>';
		if (isset($allowImport) && $allowImport){
			
		echo '<br/><br/><a href="importxml.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Import data from this XML</a></p>';}
	}
	else {
		echo '<p><span class="error"><b>Failed to create XML import file.</b></span></p>';
	}
	
	
	echo '<p><a href="index.php?import_session=reset" class="easyui-linkbutton" data-options="iconCls:\'icon-blank\'">Start a new import</a></p>'; 
}
?>













</p>
<?php 


require_once 'includes/html_bottom.inc.php';
?>