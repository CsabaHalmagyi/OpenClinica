
/**
 * Connects to a Google SpreadSheet read the data and autocomplete the input field
 * 
 * @param doc :
 *            the documents ID
 * @param to :
 *            the span ID of the input field where the selected answer will be
 *            copied.
 * @dependency: jQuery.js,jquery-ui.js,jquery-ui.css           
 */
function spreadsheetLookup(doc, to, col){


		//prepares the url to connect
		var jsonUrl = "https://spreadsheets.google.com/feeds/cells/"
			+ doc + "/od6/public/values?alt=json";
		
		var database = [];
		//define the to selector
		to = "#"+to;
		var responseField = jQuery(to).parent().parent().find("input");
		//check if the column was defined
		if (typeof col==='undefined'){
			col=1;
		}
		//if the passed parameter is a string convert to integer
		col=parseInt(col);
			//read the content to an array
			jQuery.getJSON(jsonUrl, function(data) {
				var entries = data.feed.entry || [];
				
				//determine the maximum number in a row						
				var maxx = 1; 
				for ( var j = col+1; j < entries.length; ++j ) {
					if ( parseInt(entries[j].gs$cell.col) > maxx ) maxx = parseInt(entries[j].gs$cell.col);
				}
				
				// if the passed parameter for the column is invalid, set the column to the max size
				if (col>maxx) col = maxx;
				
				// loop through the array 
				for (var i=col+maxx-1;i<data.feed.entry.length; i+=maxx){
					var rowId = data.feed.entry[i].content.$t;
					//take the value from the array
	      		    var optionElement = entries[i].content.$t; 
	      		    database.push(optionElement);
		        }

		});
			//autocomplete the "to" field
			responseField.autocomplete({
			      source: database
			    });	

}