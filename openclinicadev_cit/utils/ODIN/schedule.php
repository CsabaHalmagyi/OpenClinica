<?php 

require_once "classes/OpenClinicaSoapWebService.php";
require_once "classes/OpenClinicaODMFunctions.php";
require_once 'classes/PHPExcel.php';

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';

is_logged_in();
require($_SESSION['settingsfile']);

$inputFileName = 'temp/oid_'.$_SESSION['importid'].'_.csv';

//check the study name
if (isset($_SESSION['studyprotname']) && strlen($_SESSION['studyprotname'])>0){
	$ocUniqueProtocolId = $_SESSION['studyprotname'];
}
else {
	echo '<br/>Studyname parameter is missing!';
	die();
}

//check the site name
if (isset($_SESSION['siteprotname']) && strlen($_SESSION['siteprotname'])>0){
	$ocUniqueProtocolIDSiteRef = $_SESSION['siteprotname'];   
}
else {
	$ocUniqueProtocolIDSiteRef = null;
}

//if the event form was submitted
if (count($_POST)!=0) {

	$eventsMeta = json_decode($_POST['eventsmeta'],true);
	//connect to webservices
	$client = new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION['user_name'], $_SESSION['passwd']);
	//var_dump($eventsMeta);
	echo '<br/>';
	// DO THE SCHEDULING HERE
	
	$inputFileName = 'temp/oid_'.$_SESSION['importid'].'_.csv';
	
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
		//  Read a row of data about the subject name
		$subjName = $sheet->getCell('A'.$row)->getValue();


		 //schedule the events
		for ($i=0;$i<sizeof($eventsMeta);$i++){
//			echo $subj.' '.$eventsMeta[$i]['id'].' '.$eventsMeta[$i]['date'].' '.$eventsMeta[$i]['time'].'<br/>';
		
		$schedule = $client->eventSchedule($subjName, $eventsMeta[$i]['id'],
				'', $eventsMeta[$i]['date'], $eventsMeta[$i]['time'], '',
				'', $ocUniqueProtocolId, $ocUniqueProtocolIDSiteRef);
		//check if scheduling the event was successful
		if ($schedule->xpath('//v1:result')[0]=='Success'){ 
        echo 'Scheduling event ('.$eventsMeta[$i]['id'].') for subject ('.$subjName.'):<span class="success"> ' . $schedule->xpath('//v1:result')[0] . '</span><br/>'; }
        //if the scheduling is failed
        else {
        	echo 'Scheduling event ('.$eventsMeta[$i]['id'].') for subject ('.$subjName.'):<span class="error"> ' . $schedule->xpath('//v1:result')[0] . ' </span><br/>';
        	echo '<span class="error">'.$schedule->xpath('//v1:error')[0].'</span><br/>';
        }
		
		echo '<br/>';
		}//end of scheduling loop
		 

		
				 
	}//end of reading xlsx
	
	
	
	
	
	
	
	echo '<p>
			<a href="uploadmap.php" class="easyui-linkbutton" data-options="iconCls:\'icon-add\'">Upload a mapping file</a> or <a href="crfdef.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue to CRF definitions</a></p>';
	
	require_once 'includes/html_bottom.inc.php';
}


else{


//connect to OC Web Services
$client = new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION['user_name'], $_SESSION['passwd']);

//list all events
//get metadata from server
$getMetadata = $client->studyGetMetadata($ocUniqueProtocolId);

$odmMetaRaw = $getMetadata->xpath('//v1:odm');
$odmMeta = simplexml_load_string($odmMetaRaw[0]);
$odmMeta->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);

//Destination server metadata
$metaDataIds = array();

//holds the oid=>eventname key-value pairs + form references
$events = array();

//list all the events for the studyevents
foreach ($odmMeta->Study->MetaDataVersion->StudyEventDef as $eventDefs){
	$eventId = (string)$eventDefs->attributes()->OID;
	$eventName = (string)$eventDefs->attributes()->Name;
	$eventRepeating = (string)$eventDefs->attributes()->Repeating;
	$refs = array();

	foreach ($eventDefs->FormRef as $formRefs){
		$formRef = (string)$formRefs->attributes()->FormOID;
		$refs[] = $formRef;
	}

	$events[]=array('id'=>$eventId,'name'=>$eventName, 'repeating'=>$eventRepeating, 'refs'=>$refs);
}
//get all the forms in the study
$forms = array();
foreach ($odmMeta->Study->MetaDataVersion->FormDef as $formDefs){
	$formId = (string)$formDefs->attributes()->OID;
	$formName = (string)$formDefs->attributes()->Name;
	$forms[$formId]= $formName;
}



?>

<script type="text/javascript">
$(document).ready(function() {
    $('#selecctall').click(function(event) {  //on click
        if(this.checked) { // check select status
            $('.event').each(function() { //loop through each checkbox
                this.checked = true;  //select all checkboxes with class "checkbox1"              
            });
        }else{
            $('.event').each(function() { //loop through each checkbox
                this.checked = false; //deselect all checkboxes with class "checkbox1"                      
            });        
        }
    });
   
});

function sendEventsData(){
	var errorDate = 0;
	var errorTime = 0;
	var eventsData = [];

	$('#eventdata > tr.evrow').each(function(){
		var eventRow = new Object;
		if ($(this).find('td > input.event').is(':checked')){
			var eventName = $(this).find('td.evname').text();
			var eventId = $(this).find('td > input.event').attr("id");
			var eventDate = $(this).find('td > input.eventdate').val();
			var eventTime = $(this).find('td > input.evetime').val();
			
			//set defauld border color
			$(this).find('td > input.eventdate').css('background','white');
			$(this).find('td > input.evetime').css('background','white');
			
			var regex_date = /^\d{4}\-\d{2}\-\d{2}$/;
			if(!regex_date.test(eventDate))
		    {
				$(this).find('td > input.eventdate').css('background','red');
		        errorDate++;
		    }
		    
			var regex_time = /([0-2]?\d)[:]([0-5]\d)/;
			if(!regex_time.test(eventTime))
		    {

				$(this).find('td > input.evetime').css('background','red');
		        errorTime++;
		    }
		    

			
			eventRow['id'] = eventId;
			eventRow['name'] = eventName;
			eventRow['date'] = eventDate;
			eventRow['time'] = eventTime;
			eventsData.push(eventRow);

			}

		
		});


	 
	if (errorDate==0 && errorTime==0){
		var stringData = JSON.stringify(eventsData);
		$('#eventsmeta').val(stringData);
			
	$('#scheduleForm')[0].submit();	}
	else {
		alert ("ERROR in form! Year format must be YYYY-MM-DD, time format must be HH:MM !")
		}
//	alert("EvN: "+stringData);
}



</script>
		
<p>
<form id="scheduleForm" action="schedule.php" method="post">
<table name="eventsubjectlist" id="eventsubjectlist">
<thead><tr><td>Event name </td><td>Schedule?<input type="checkbox" id="selecctall"/ checked></td><td>Date</td><td>Time</td><td>Forms</td></tr></thead>
<tbody id="eventdata">
<?php 
$date = date("Y-m-d");
$time = date("H:i");
$evcounter = 0;
// displaying the events table rows
foreach ($events as $ev){
	echo '<tr id="row'.$ev['id'].'" class="evrow"';
	if ($evcounter%2==1) echo ' bgcolor = "#E0ECFF"';
	$evcounter++;
	echo '><td ';
	if ($ev['repeating']=="Yes") echo ' bgcolor = "#FF6600"';
	echo ' class="evname">'.$ev['name'].'</td>';
	echo '<td ';
	if ($ev['repeating']=="Yes") echo ' bgcolor = "#FF6600"';
	echo	'><input type="checkbox" id="'.$ev['id'].'" class="event" checked></td>';
	echo '<td ';
	if ($ev['repeating']=="Yes") echo ' bgcolor = "#FF6600"';
	echo '><input type="text" id="eventdate#'.$ev['id'].'" class="eventdate" value="'.$date.'" size="10"></td>';
	echo '<td ';
	if ($ev['repeating']=="Yes") echo ' bgcolor = "#FF6600"';
	echo '><input type="text" id="eventtime#'.$ev['id'].'" class="evetime" value="'.$time.'" size="5"></td>';
	echo '<td ';
	if ($ev['repeating']=="Yes") echo ' bgcolor = "#FF6600"';
	echo '><ul>';
	for ($i=0;$i<sizeof($ev['refs']);$i++) {
		if (isset($forms[$ev['refs'][$i]])) echo '<li>'.$forms[$ev['refs'][$i]].'</li>';

	} 
	echo '</ul></td></tr>';

}

?>

<tr><td><input type="button" onclick=sendEventsData() value="Schedule selected events!" 
class="easyui-linkbutton" data-options="iconCls:'icon-back'"/></td></tr>
</tbody>
</table>
<input type="hidden" name="eventsmeta" id="eventsmeta" value="" />
</form>
</p>


<?php 

//var_dump($events);

require_once 'includes/html_bottom.inc.php';
}
?>

