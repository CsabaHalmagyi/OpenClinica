<?php

require_once "classes/OpenClinicaSoapWebService.php";
require_once "classes/OpenClinicaODMFunctions.php";
require_once 'classes/PHPExcel.php';

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';

is_logged_in();
require($_SESSION['settingsfile']);
?>

<?php 

//read post data sent by crfdef.php
if (count($_POST)!=0) {
//set events, forms, groups, items and checkboxes array
	if (isset($_POST['checkboxdata'])){
		$cbdata = json_decode($_POST['checkboxdata'],true);
//		echo 'Passing checkboxes done.<br/>';
	}

	if (isset($_POST['eventsAll'])){
		$events = json_decode($_POST['eventsAll'],true);
//		echo 'Passing events done.<br/>';
	}	
	if (isset($_POST['formsAll'])){
		$forms = json_decode($_POST['formsAll'],true);
//		echo 'Passing forms done.<br/>';
	}
	if (isset($_POST['groupsAll'])){
		$groups = json_decode($_POST['groupsAll'],true);
//		echo 'Passing groups done.<br/>';
	}	
	if (isset($_POST['itemsAll'])){
		$items = json_decode($_POST['itemsAll'],true);
//		echo 'Passing items done.<br/>';
	}
}//end of reading post data
else {
	echo 'Error at passing data!';
}

?>
	<script type="text/javascript">
	var events = <?php echo json_encode($events).";\n";?>
	var forms = <?php echo json_encode($forms).";\n";?>
	var groups = <?php echo json_encode($groups).";\n";?>
	var items = <?php echo json_encode($items).";\n";?>

	function elementHide(divValue){
	    $("div.right table tr td").filter(function() {
	        return $(this).text() == divValue;
	    }).parent('tr').hide();
	    console.log("Hiding element: "+divValue);
		//alert(divValue);
		}

	function elementShow(divValue){
	    $("div.right table tr td").filter(function() {
	        return $(this).text() == divValue;
	    }).parent('tr').show();
	    console.log("Showing element: "+divValue);
		//alert(divValue);
		}

	$(function(){

			$('.right .item').draggable({
				revert:true//,
				//proxy:'clone'
			});
			
			$('.left td.drop').droppable({
				onDragEnter:function(){
					$(this).addClass('over');
				},
				onDragLeave:function(){
					$(this).removeClass('over');
				},
				onDrop:function(e,source){
					$(this).removeClass('over');
					var newId = $(this).attr("id");

					if ($(source).hasClass('assigned') && $(this).text().trim()==""){
						$(source).attr("id",newId);

						$(this).append(source);
						elementHide($(source).text());
					} else {
						if($(this).text().trim()==""){
						var c = $(source).clone().addClass('assigned');
						//var c = $(source).addClass('assigned');

						elementHide(c.text());
						c.attr("id",newId);
						$(this).empty().append(c);
						c.draggable({
							revert:true
						});
						
						
					}
					}
				}
			});
			$('.right').droppable({
				accept:'.assigned',
				onDragEnter:function(e,source){
					$(source).addClass('trash');
				},
				onDragLeave:function(e,source){
					$(source).removeClass('trash');
				},
				onDrop:function(e,source){
					elementShow($(source).text());
					$(source).remove();
				}
			});
		});

		function saveMapping(){
			
			}
		
		function passDataToImport(){
			$(document).ready(function(){
				var nameOidPairs = new Object;
				var assignedCounter = 0;
				$('div.assigned').each(function(){

					var name = $(this).text();
					var oid = $(this).attr("id");
					assignedCounter++;
					nameOidPairs[name]=oid;

					});

				if (assignedCounter>0){
					
					//set default value for cbSkip
						$('#cbSkip').val(false);				
						//if checkbox is checked, pass true value
						if ($('#skipEmptyCells').is(':checked')) {
							$('#cbSkip').val(true);
							}


				if ($('#cbSkip').val()==="false"){
					if (confirm('Are you sure you want to use empty cells?')) {
						//send the form
						$('#mapdata').val(JSON.stringify(nameOidPairs));
						$("#rrForm")[0].submit();	
								

					} else {
					    // Do nothing!
					}
					}
				else {// if the checkbox is checked and there is something in the map array
					//send the form
					$('#mapdata').val(JSON.stringify(nameOidPairs));
					$('#eventsAll').val(JSON.stringify(events));
					$('#formsAll').val(JSON.stringify(forms));
					$('#groupsAll').val(JSON.stringify(groups));
					$('#itemsAll').val(JSON.stringify(items));
										
					$("#rrForm")[0].submit();	
						
					}
				
				}
				else {
						alert("You must associate at least one item!");
					}
				
				});

			}


		
	</script>


<?php 
//MAPPING FILE
$mapFile = 'map/'.htmlspecialchars($_SESSION['user_name']).'/map_'.$_SESSION['importid'].'_.csv';
$isMapFile = file_exists($mapFile);

//if there is an existing mapping file
if ($isMapFile) {
	//read it
//read xls headers

//  Read excel mapping file
try {
	$mapFileType = PHPExcel_IOFactory::identify($mapFile);
	$mapobjReader = PHPExcel_IOFactory::createReader($mapFileType);
	$mapobjPHPExcel = $mapobjReader->load($mapFile);
} catch (Exception $e) {
	die('Error loading file "' . pathinfo($mapFile, PATHINFO_BASENAME)
			. '": ' . $e->getMessage());
}

//  Get worksheet dimensions
$sheetMap = $mapobjPHPExcel->getSheet(0);
$highestRow = $sheetMap->getHighestRow();
$highestColumn = $sheetMap->getHighestColumn();

$mappingData = array();
//  Loop through the mapping file
for ($row = 2; $row <= $highestRow; $row++) {
	//  Read a row of data into an array
	$rowData = $sheetMap->rangeToArray('A' . $row . ':' . 'C' . $row,
			NULL, TRUE, FALSE);
	//create key value pairs using itemOID as key and XlsName as value
	$mappingData[$rowData[0][0]] =  $rowData[0][2];
}	


//var_dump($mappingData);

}//end of if isMapFile


//reading csv headers
$csvdata = $_SESSION['csvdata'];
$highestRow = intval($_SESSION['csvmaxrow']);

$excelHeaders = array_slice($csvdata[0],6);

if ($isMapFile) {
	echo '<p><span class="success">Mapping file found!</span></p><br/>';
}
else {
	echo '<p><span class="error">Mapping file not found!</span></p><br/>';
} 


echo '<form action="createxml.php" method="post" id="rrForm" name="rrForm">';
echo '<table><tr><td><div class="left">';
echo '<table>';
echo '<thead><tr><td>CRF items</td><td>Associated item</td></tr></thead>';
echo '<tbody>';
$prevForm='';
$rowCounter=0;
$mapAutoAssigned=array();
foreach($cbdata as $cb){

	$item = explode("##",$cb);
	if ($prevForm==$events[$item[0]]['name'].$forms[$item[1]]['name']){
		
	}
	else{
	$rowCounter++;
		$prevForm=$events[$item[0]]['name'].$forms[$item[1]]['name'];
		echo '<tr><th colspan=2>'.$events[$item[0]]['name'].' '.$prevForm.'</th></tr>';
	}

	 echo '<tr><td class="aright">';

	echo $items[$item[3]]['name'];
	echo '</td>';
	echo '<td class="drop" id="'.$cb.'">';
	// if the mapping file contains the association, associate it automatically
	if (isset($mappingData[$cb])){
		//if mapvalue is a valid excel header
		if(in_array($mappingData[$cb],$excelHeaders)){
			echo '<div id="'.$cb.'" class="item assigned">'.$mappingData[$cb].'</div>';
			$mapAutoAssigned[]=$mappingData[$cb];
		}
	}
	
								
	echo '</td></tr>';
}
echo '</tbody></table>';
echo '</div></td><td>';




echo '<div class="right">';
echo '<table>';
echo '<thead><tr><td>Headers from csv</td></tr></thead>';
echo '<tbody>';
for ($i=0;$i<sizeof($excelHeaders);$i++){
	if (in_array($excelHeaders[$i],$mapAutoAssigned) ){
		echo '<tr style="display:none;">';
	}
	else {
		echo '<tr>';}
echo '<td><div class="item">'.$excelHeaders[$i].'</div></td>';	
echo '</tr>';
}
echo '</tbody></table>';
echo '</div></td></tr></table>';
echo '<p><input type="checkbox" id="skipEmptyCells" name="skipEmptyCells" checked/> Skip empty cells in datafile';
echo '<br/>Form(s) status: <select id="formstatus" name="formstatus">';
echo '<option value="complete">Complete</option>';
echo '<option value="entrystarted">Data entry started</option>';
echo '</select>';
echo '<br/><br/><input type="button" value="Create ODM XML!" onclick="passDataToImport()" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'"/>';
echo '<input type="hidden" name="mapdata" id="mapdata"/>';
echo '<input type="hidden" name="cbSkip" id="cbSkip"/>';

echo '<input type="hidden" name="formsAll" id="formsAll"/>';
echo '<input type="hidden" name="groupsAll" id="groupsAll"/>';
echo '<input type="hidden" name="itemsAll" id="itemsAll"/>';
echo '<input type="hidden" id="eventsAll" name="eventsAll">';
echo '</p>';
echo '</form>';






?>







<?php 
echo '<br/>';

require_once 'includes/html_bottom.inc.php';
?>