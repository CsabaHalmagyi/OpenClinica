/**
 * Camfetch js library for integrating 
 * the Panogram standalone application with OpenClinica.
 * 
 * @author Csaba Halmagyi
 * 
 */

//GLOBAL VARIABLES




/**
 * Returns all pedigree versions of a family
 * 
 * @params familyId
 */
function getPedVersionsByFamId(familyId, studyName){
	
	if (familyId != undefined){

		var serviceUrl="http://openclinica-testing.medschl.cam.ac.uk/webservices/panogram/pedlookup.php?fId="+encodeURIComponent(familyId)+"&study="+btoa(studyName);
		return jQuery.ajax({
            type: "GET",
            url: serviceUrl,
            async: true,
            dataType: "json",
            success: function(data){
            	//process pedigree data here and populate versions
            	//alert(data.data);
            }
            
        });		
	}
}


function insertDisorders(disorders){
	//alert(JSON.stringify(disorders));
	for(var i=0;i<disorders.length;i++){
		var index = i+1;
		var actualId="#disorder"+index;
		var actualDisorderField = jQuery(actualId).parent().parent().find("input");
		var actualDisorderValue = actualDisorderField.val(); 
		if (actualDisorderValue!=disorders[i]){
			actualDisorderField.val(disorders[i]);
			actualDisorderField.change();
		}
	}
	var diff;
	var lastDiseasePos = disorders.length;
	//clear the rest of the fields if they are not empty
	
	while (lastDiseasePos<6){
		var index=lastDiseasePos+1;
		var actualId="#disorder"+index;
		var actualDisorderField = jQuery(actualId).parent().parent().find("input");
		var actualDisorderValue = actualDisorderField.val(); 
		if (actualDisorderValue!=""){
			actualDisorderField.val("");
			actualDisorderField.change();
		}
		lastDiseasePos++;
	}
}

/**
 * Loop through the disorders and updates the disorder fields in the CRF
 * @param pedigreeList
 */
function checkNewDisorders(pedigreeData){

	//read the subject id from the crf
	var subject = jQuery("#centralContainer").find("table:first").find("tbody:first").children("tr:nth-child(1)").children("td:nth-child(2)").children("h1").text();
	subject=subject.trim();
	//alert(JSON.stringify(pedigreeData));
	//read the pedigreedata
	for (var i=0;i<pedigreeData.length;i++){
		// if an external id is equals to the current subject id refresh disorder list
		//alert("ext:"+pedigreeData[i].externalId+" subj:"+subject);
		if (pedigreeData[i].externalId==subject){
			//alert(JSON.stringify(pedigreeData[i].disorders));
			insertDisorders(pedigreeData[i].disorders);
		}
	}
	//alert(disorderList);
}

/**
 * Main fuction to call from the CRF
 * Expects a predefined div container with the id=PanogramNavigateArea in the CRF
 */
function Panogram(){

	//refers to the input field where the Family Id is stored/entered
	var familyIdField = jQuery("#PanogramNavigateArea").parent().parent().find("input");
	//refers to the navigation area where the version select menu and the buttons are being inserted
	var navAreaDiv = jQuery("#PanogramNavigateArea");		
	navAreaDiv.empty();
	
	var study = jQuery(".tablebox_center").find("tbody:first").children("tr:nth-child(3)").children("td:nth-child(2)").text();
	study = jQuery.trim(study);
	study = encodeURIComponent(study);
	//alert(study);
	var versions="";
	familyIdField.attr('autocomplete','off');

	var famId = familyIdField.val();
	//if there is a saved family id 
	if (famId.length>0) {
	
		getPedVersionsByFamId(famId, study).done(function(data){
        	for (var i=0;i<data.data.length;i++){
        		if (data.data[i].timestamp.length>5){
        			versions+='<option value="'+encodeURIComponent(data.data[i].timestamp)+'">'+data.data[i].timestamp+'</option>';}
        	}
        	
        	if (typeof(versions) !== 'undefined' && versions !==""){
        		//make sure navigation area is empty
        		navAreaDiv.empty();
        	//create a select
        		var selectMenu = jQuery("<select/>");
        		selectMenu.attr('id', 'versionSelector');
        		selectMenu.append(versions);
        	navAreaDiv.append(selectMenu);
        	//add a button with handler
        	var newButton = jQuery('<input type="button" value="View Pedigree">');
        	newButton.button() // Ask jQuery UI to buttonize it  
        	  .click(function(){ 
        		  //read the parameters and open the Panogram Application
        		  var fid = familyIdField.val();
        		  fid = fid.trim();
        		  var vers = jQuery("#versionSelector").val();
        		  var panogramUrl = "http://openclinica-testing.medschl.cam.ac.uk/panogram_dev/index.php?familyId="+encodeURIComponent(fid)+"&version="+vers+"&study="+btoa(study);
        		  window.open(panogramUrl);
        		  //alert(fid+' '+vers);
        		  }); 
        	//add the button to the navigation area
        	navAreaDiv.append(newButton);
			//alert(versions);
        	}
        	else {
        		//empty the navigation area container
        		navAreaDiv.empty();
        	}
        	
        	//INSERT DISORDERS
        	if (data.data[0] !==undefined){
        	checkNewDisorders(data.data[0].pedigreeData);}
        	
		});

	}
	//add the create button if the family Id is empty
	else {
		
		var newSpanText = jQuery('<span style="color: #f00;">Enter a Family ID to create pedigree.</span>');

    	navAreaDiv.append(newSpanText);
	}
		

	
	//add a listener to the input field
	familyIdField.keyup(function(){
		versions="";
		var enteredFamId = familyIdField.val();
		//alert(enteredFamId);


		if (enteredFamId.length>2){
			
			getPedVersionsByFamId(enteredFamId, study).done(function(data){
	        	for (var i=0;i<data.data.length;i++){
	        		if (data.data[i].timestamp.length>5){
	        			versions+='<option value="'+encodeURIComponent(data.data[i].timestamp)+'">'+data.data[i].timestamp+'</option>';}
	        	}
	        	
	        	if (typeof(versions) !== 'undefined' && versions !==""){
	        		//make sure navigation area is empty
	        		navAreaDiv.empty();
	        	//create a select
	        		var selectMenu = jQuery("<select/>");
	        		selectMenu.attr('id', 'versionSelector');
	        		selectMenu.append(versions);
	        	navAreaDiv.append(selectMenu);
	        	//add a button with handler
	        	var newButton = jQuery('<input type="button" value="View Pedigree">');
	        	newButton.button() // Ask jQuery UI to buttonize it  
	        	  .click(function(){ 
	        		  //read the parameters and open the Panogram Application
	        		  var fid = familyIdField.val();
	        		  fid = fid.trim();
	        		  var vers = jQuery("#versionSelector").val();
	        		  var panogramUrl = "http://openclinica-testing.medschl.cam.ac.uk/panogram_dev/index.php?familyId="+encodeURIComponent(fid)+"&version="+vers+"&study="+btoa(study);
	        		  window.open(panogramUrl);
	        		  //alert(fid+' '+vers);
	        		  }); 
	        	//add the button to the navigation area
	        	navAreaDiv.append(newButton);
				//alert(versions);
	        	}
	        	else {
	        		//empty the navigation area container
	        		navAreaDiv.empty();
	            	var newCreateButton = jQuery('<input type="button" value="Create Pedigree">');
	            	newCreateButton.button() // Ask jQuery UI to buttonize it  
	            	  .click(function(){ 
	            		  //read the parameters and open the Panogram Application
	            		  var fid = familyIdField.val();
	            		  fid = fid.trim();
	            		  
	            		  var httpc = new XMLHttpRequest(); // simplified for clarity
	            		  var url = "http://openclinica-testing.medschl.cam.ac.uk/panogram_dev/savetooc.php";
	            		  httpc.open("POST", url, true); // sending as POST
	            		    
	            		  var params = 'famId='+fid+'&pedigree=[{"id":0,"sex":"unknown"}]'+'&study='+btoa(study);
	            		    
	            		  httpc.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	            		  httpc.setRequestHeader("Content-Length", params.length); // POST request MUST have a Content-Length header (as per HTTP/1.1)

	            		  httpc.onreadystatechange = function() { //Call a function when the state changes.
	            		  if(httpc.readyState == 4 && httpc.status == 200) { // complete and no errors
	            		        //alert(httpc.responseText); 
	            		        }
	            		    }
	            		  	//send the new pedigree request
	            		    httpc.send(params);
	  	        		  var panogramUrl = "http://openclinica-testing.medschl.cam.ac.uk/panogram_dev/index.php?familyId="+encodeURIComponent(fid)+"&study="+btoa(study);
		        		  window.open(panogramUrl);
		        		  //Reload the panogram interface
		        		  Panogram();
	            		  });
	            	
	            	navAreaDiv.append(newCreateButton);
	        	}
	        	//INSERT DISORDERS
	        	if (data.data[0] !==undefined){
	        	checkNewDisorders(data.data[0].pedigreeData);}
	        	
			});

		}
		else{
			navAreaDiv.empty();
			var newSpanText = jQuery('<span style="color: #f00;">Enter a Family ID to create pedigree.</span>');

	    	navAreaDiv.append(newSpanText);
		}

		
	});	

	
}
