<?php

require_once "classes/OpenClinicaSoapWebService.php";
require_once "classes/OpenClinicaODMFunctions.php";
require_once 'classes/PHPExcel.php';

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';

is_logged_in();
require($_SESSION['settingsfile']);

//check the study name
if (isset($_SESSION['studyprotname']) && strlen($_SESSION['studyprotname'])>0){
	$ocUniqueProtocolId = $_SESSION['studyprotname'];
}
else {
	echo '<br/>Studyname parameter is missing!';
	die();
}






//connect to webservices
$client = new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION['user_name'], $_SESSION['passwd']);

//get metadata from server
$getMetadata = $client->studyGetMetadata($ocUniqueProtocolId);

$odmMetaRaw = $getMetadata->xpath('//v1:odm');
$odmMeta = simplexml_load_string($odmMetaRaw[0]);
$odmMeta->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);

//read study metadata
$metaDataIds = array();


$studoid = $odmMeta->Study->attributes()->OID;

$events = array();
$forms = array();
$groups = array();
$items = array();
//events
foreach ($odmMeta->Study->MetaDataVersion->StudyEventDef as $eventDefs){
	$eventId = (string)$eventDefs->attributes()->OID;
	$eventName = (string)$eventDefs->attributes()->Name;
	$refs = array();
	$eventRepeating = (string)$eventDefs->attributes()->Repeating;
	foreach ($eventDefs->FormRef as $formRefs){
		$formRef = (string)$formRefs->attributes()->FormOID;
		$refs[] = $formRef;

	}

	$events[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
}

//forms
foreach ($odmMeta->Study->MetaDataVersion->FormDef as $formDefs){
	$formId = (string)$formDefs->attributes()->OID;
	$formName = (string)$formDefs->attributes()->Name;
	$refs = array();
	foreach ($formDefs->ItemGroupRef as $igRefs){
		$igRef = (string)$igRefs->attributes()->ItemGroupOID;
		$refs[] = $igRef;
	}

	$forms[$formId]= array ("name"=>$formName,"refs"=>$refs);
}

//groups
foreach ($odmMeta->Study->MetaDataVersion->ItemGroupDef as $igDefs){
	$igId = (string)$igDefs->attributes()->OID;
	$igName = (string)$igDefs->attributes()->Name;
	$refs = array();
	foreach ($igDefs->ItemRef as $iRefs){
		$iRef = (string)$iRefs->attributes()->ItemOID;
		$refs[] = $iRef;
	}

	$groups[$igId]= array ("name"=>$igName,"refs"=>$refs);
}

//items
foreach ($odmMeta->Study->MetaDataVersion->ItemDef as $iDefs){
	$iId = (string)$iDefs->attributes()->OID;
	$iName = (string)$iDefs->attributes()->Name;
	$namespaces = $iDefs->getNameSpaces(true);

	$OpenClinica = $iDefs->children($namespaces['OpenClinica']); 
	//echo $OpenClinica->asXML();
		$fOID = array();
	foreach ($OpenClinica as $oc){
		$subelement = $oc->children($namespaces['OpenClinica']);
		foreach ($subelement as $sube){
		$subattr = $sube->attributes();
		$fOID[] = (string)$subattr['FormOID'];
		}
	}


	
	$items[$iId]= array ("name"=>$iName,"foid"=>$fOID);
}
//finish reading study metadata
?>
<script type="text/javascript">
var events = <?php echo json_encode($events)."\n";?>
var forms = <?php echo json_encode($forms)."\n";?>
var groups = <?php echo json_encode($groups)."\n";?>
var items = <?php echo json_encode($items)."\n";?>

$(document).ready(function() {
//invoking the select list
    $('select').change(function() {
    	var insertItems = [];
   
		var eventform = $(this).attr("id");
    	//read event id
        var eventformm = eventform.split("##");
        var selId = eventformm[0];
        var targetFID = eventformm[1];

        //read form id
        var formId = $(this).val();
		for (var index in forms[formId]){
			if (index=="refs"){
				for (var subkey in forms[formId][index]){

					//item group id
					var iGr = forms[formId][index][subkey];
					for (var ind in groups[iGr]){
							if (ind=="refs"){
							for (var subk in groups[iGr][ind]){
									var iId = groups[iGr][ind][subk];

									formIndexes = [];
									formIndexes = items[iId]["foid"];
									if (formIndexes.indexOf(formId) != -1){
										//create itemgroupid##itemid response
										insertItems.push(iGr+"##"+iId);

										} 
								
								}
							}
						}
					
			}					
				}

			}
		//prepare the item list
		var insertList = "<ul>";

		for(var i=0;i<insertItems.length;i++){
			var insertNameId=insertItems[i].split("##");
			insertList = insertList+'<li><input type="checkbox" class="crfdatacheck" id="'+selId+
			'##'+formId+'##'+insertItems[i]+'" name="'+selId+
			'##'+formId+'##'+insertItems[i]+'" checked />'+items[insertNameId[1]]['name']+'</li>';
			}
        insertList = insertList+"</ul>";
        //alert(insertList);
        var listId = selId+"££"+targetFID+"_list";
        //replace the content in the table
        $("#"+listId).html(insertList);	
    });

    $('.crfAll').change(function() {
	var checkId = $(this).attr("id");
	checkId = checkId.replace("££","##");
	//stores the position of the last underscore in the Id
	var last_ = checkId.lastIndexOf("_");
	checkId = checkId.substring(0, last_);
	
	//alert(checkId);
	
    if(this.checked) { // check select status
        $('.crfdatacheck').each(function() { //loop through each checkbox
            var cbid= $(this).attr("id");
			if (cbid.indexOf(checkId)>-1)
            this.checked = true;  //select all checkboxes with class "checkbox1"              
        });
    }else{
        $('.crfdatacheck').each(function() { //loop through each checkbox
            var cbid= $(this).attr("id");
			if (cbid.indexOf(checkId)>-1)
            this.checked = false; //deselect all checkboxes with class "checkbox1"                      
        });        
    }
        // do stuff here. It will fire on any checkbox change

}); 


    $('.crfAllAll').change(function() {
    	
        if(this.checked) { // check select status
            $('.crfAll').each(function() { //loop through each checkbox

                this.checked = true;  //select all checkboxes with class "checkbox1"              
            });
            
            $('.crfdatacheck').each(function() { //loop through each checkbox

                this.checked = true;  //select all checkboxes with class "checkbox1"              
            });
            
        }else{
        	$('.crfAll').each(function() { //loop through each checkbox

                this.checked = false; //deselect all checkboxes with class "checkbox1"                      
            });    
            $('.crfdatacheck').each(function() { //loop through each checkbox

                this.checked = false; //deselect all checkboxes with class "checkbox1"                      
            });        
        }
            // do stuff here. It will fire on any checkbox change

    }); 
    
    
});

function submitCrfData(){
	$(document).ready(function(){
		//pass events, forms, groups and items data
	$('#eventsAll').val(JSON.stringify(events));
	$('#formsAll').val(JSON.stringify(forms));
	$('#groupsAll').val(JSON.stringify(groups));
	$('#itemsAll').val(JSON.stringify(items));
	//read all the checkboxes
	var checkboxdata =[];
	$('input:checkbox.crfdatacheck').each(function(){
		//if checkbox is checked, add its id to the array
		if ($(this).is(':checked')) {
			checkboxdata.push($(this).attr("id"));
			}
		});
	//pass the arraydata to the hidden input field in form
	$('#checkboxdata').val(JSON.stringify(checkboxdata));
	//submit the form
	$("#crfdefs")[0].submit();
	});
}

</script>
<?php 


?>
<form action="map.php" method="post" id="crfdefs">
<table id="crflist" name ="crflist" class="left">
<thead>
<tr><td>Events</td><td>Default CRF for the Event</td><td>Check<input type="checkbox" class="crfAllAll" checked/></td><td>Version of CRF to use for import</td><td>Items in study</td></tr>
</thead>
<tbody>
<?php 
$rowCounter=0;
//display all the events
foreach ($events as $ekey=>$ev){
$rowCounter++;
//display all the forms associated with the event  
//echo '</td><td>';
$formR = $ev['refs'];
$usedFormR = array();
foreach ($formR as $fr){
	$parentFormParts = explode("_",$fr);
	$parentForm = $parentFormParts[0]."_".$parentFormParts[1];
	
	if (!in_array($parentForm,$usedFormR)){
		$usedFormR[]=$parentForm;

		if ($rowCounter%2==1) echo '<tr class="notcolored"><td>';
		else echo '<tr><td>';

		
		echo $ev['name']; //print event
		
		echo '</td><td>'.$forms[$fr]['name'].'</td>';
		echo '<td><input type="checkbox" class="crfAll" id="'.$ekey.'££'.$fr.'" checked/>';
		echo '</td>'; //print form
		
		echo '<td><select id="'.$ekey.'##'.$fr.'">';
		$firstFR = null;
		foreach ($formR as $fr2){

			$fRefPart = explode("_",$fr2);
			$fRefParent = $fRefPart[0]."_".$fRefPart[1];
			if ($fRefParent==$parentForm){
				echo '<option value="'.$fr2.'">'.$forms[$fr2]['name'].'</option>';
				if ($firstFR==null) $firstFR = $fr2;
			}
		}
		
		echo '</select></td>';
		
		echo '<td id="'.$ekey.'££'.$firstFR.'_list" class="aleft">';
		echo '<ul>';
		
		//$fr = $ev['refs'][0];
		
		$igr = $forms[$firstFR]['refs'][0];
		foreach ($forms[$firstFR]['refs'] as $igr){
		
		$irefs = $groups[$igr]['refs'];
		//display all the items associated with the current form version
		foreach ($irefs as $ikey=>$item){
			if (in_array($firstFR,$items[$item]['foid'])){
				echo '<li>';
				echo '<input type="checkbox" class="crfdatacheck" id="'.$ekey.'##'.$firstFR.'##'.$igr.'##'.$item.'" name="'.$ekey.'##'.$fr2.'##'.$igr.'##'.$item.'" checked/>';
				echo $items[$item]['name'];
				echo '</li>';
			}
		
		}
		}
		echo '</ul></td></tr>';
		
		
	}

}


}


?>
</tbody>
</table>

<input type="hidden" id="eventsAll" name="eventsAll">
<input type="hidden" id="formsAll" name="formsAll">
<input type="hidden" id="groupsAll" name="groupsAll">
<input type="hidden" id="itemsAll" name="itemsAll">
<input type="hidden" id="checkboxdata" name="checkboxdata">

<p><input type="button" value="Continue to mapping!" onclick="submitCrfData()"></p>
</form>


<?php 
echo '<br/><br/>';
require_once 'includes/html_bottom.inc.php';
?>