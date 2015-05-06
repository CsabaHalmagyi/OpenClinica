<?php
// Classes to build ODM structure for import to OpenClinica
// Function to convert to equivalent XML
// Extended to be able to add new items/groups/forms/events/subjects to the xml
require_once "classes/OpenClinicaODMFunctions.php";

class ocODMclinicalDataE extends  ocODMclinicalData{
/*	public $studyOID;
	public $metaDataVersionOID;
	public $subjectData = array ();
*/	
	public function add($study, $subject, $event,$repeatkey, $form, $group, $grouprepkey, $item, $value){
		if ( isset($this->subjectData[$subject]) ) {
			$this->subjectData[$subject]->add($subject, $event, $repeatkey, $form, $group, $grouprepkey, $item, $value);
		}
		else {
			$this->subjectData[$subject] = new ocODMsubjectDataE($subject,array());
			$this->subjectData[$subject]->add($subject, $event, $repeatkey, $form, $group, $grouprepkey, $item, $value);
		}
		
	}
}

class ocODMsubjectDataE extends ocODMsubjectData {
/*
  	public $subjectKey;
	public $studyEventData = array ();
 */
	
	public function add($subject, $event,$repeatkey, $form, $group, $grouprepkey, $item, $value){
		if ( isset($this->studyEventData["".$event.$repeatkey])) 
			$this->studyEventData["".$event.$repeatkey]->add($event, $form, $group, $grouprepkey, $item, $value);
		else {
			$this->studyEventData["".$event.$repeatkey] = new ocODMstudyEventDataE($event, $repeatkey, array());
			$this->studyEventData["".$event.$repeatkey]->add($event, $form, $group, $grouprepkey, $item, $value);
		}
		
	}
	
}
class ocODMstudyEventDataE extends ocODMstudyEventData {
/*	public $studyEventOID;
	public $studyEventRepeatKey;
	public $formData = array ();
	*/
	public function add($event, $form, $group, $grouprepkey, $item, $value){
		if ( isset($this->formData[$form]) ) 
			$this->formData[$form]->add($form, $group, $grouprepkey, $item, $value);
		else {
			$this->formData[$form] = new ocODMformDataE($form, array());
			$this->formData[$form]->add($form, $group, $grouprepkey, $item, $value);
		}
		
	}
}
class ocODMformDataE extends ocODMformData {
/*	public $formOID;
	public $itemGroupData = array ();
*/
	
	public function add($form, $group,$grouprepkey, $item, $value){
		if ( isset($this->itemGroupData["".$group.$grouprepkey])) 
			$this->itemGroupData["".$group.$grouprepkey]->add($group, $item, $value);
		else {
			$this->itemGroupData["".$group.$grouprepkey] = new ocODMitemGroupDataE($group, $grouprepkey, array());
			$this->itemGroupData["".$group.$grouprepkey]->add($group, $item, $value);
		}	
	}
	
}
class ocODMitemGroupDataE extends ocODMitemGroupData {
/*	public $itemGroupOID;
	public $itemGroupRepeatKey;
	public $itemData = array ();
*/		
	public function add($group, $item, $value){
		if ( isset($this->itemData[$item]) )
			$this->itemData[$item]->add($group, $item, $value);
		else {
			$this->itemData[$item] = new ocODMitemDataE($item, $value);

		}
	}
	
	
}
class ocODMitemDataE extends ocODMitemData {
/*
  	public $itemOID;
	public $itemValue;
	*/
	
	
	
}

?>