
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
		colNr=parseInt(col);
			//read the content to an array
			jQuery.getJSON(jsonUrl, function(data) {
				var entries = data.feed.entry || [];

				for (var j=0;j<entries.length;j++){
					var actualCol = parseInt(entries[j].gs$cell.col);
 					if (parseInt(entries[j].gs$cell.col) == colNr && parseInt(entries[j].gs$cell.row) != 1 ){
						var optionElement = data.feed.entry[j].gs$cell.$t;

		      		    database.push(optionElement);

					}
				}

		});
			//autocomplete the "to" field
			responseField.autocomplete({
			      source: database
			    });	

}