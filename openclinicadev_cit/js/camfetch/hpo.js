/**
 * Connects to the Human Phenotype Ontology webservice and displays the fetched
 * data
 * 
 * @param groupname:
 *            the name of your itemgroup in the crf
 * @param colNr
 *            the column number of the crf where the lookup will be initiated
 */

function HPO(groupname, colNr) {

	if (typeof (colNr) == 'undefined') {
		colNr = 1;
	}

	var groupN = groupname.replace(" ", "_");
	groupN = groupN.replace("-", "");

	groupN = groupN.toUpperCase();

	jQuery("table.aka_form_table").on("keyup", ":input", function() {
		jQuery(this).attr('autocomplete', 'off');
		var inpId = jQuery(this).attr("id");
		// alert(inpId+" "+groupName);
		if (inpId.indexOf(groupN.toUpperCase()) != -1) {

			connectToHPOService(jQuery(this), colNr);
		}

	});

}

function connectToHPOService(me, clNr) {
	if (me.attr("id") != undefined) {

		var url = "http://openclinica-testing.medschl.cam.ac.uk/webservices/hpo/lookup.php?q=";
		var term = me.val();
		var urlToService = url + term;
		var myParent = me.parent();
		var colNr = jQuery(myParent).parent().children()
				.index(jQuery(myParent)) + 1;
		if (colNr == clNr) {
			jQuery(me).autocomplete(
					{
						source : function(request, response) {
							jQuery.ajax({
								url : urlToService,
								dataType : "json",
								data : {
									name : request.name
								},
								success : function(data) {
									var datas = data.resultsInName
											.concat(data.resultsInSynonym);
									response(jQuery.map(datas, function(item) {
										return {
											label : item.name,
											name : item.name,
											id : item.id,
											synonyms : item.synonyms,
											def : item.def
										};
									}));
								}
							});
						},
						minLength : 3,
						select : function(event, ui) {
							jQuery(me).val(ui.item.name);

							var code = me.parent().siblings(':eq(0)').children(
									'input:text');
							code.val(ui.item.id);
						}
					}).data("ui-autocomplete")._renderItem = function(ul, item) {
				var $a = jQuery("<a></a>");
				jQuery("<span class='termname'></span>").text(item.label)
						.appendTo($a);
				jQuery("<span class='termid'></span>").text(item.id).appendTo(
						$a);
				var synonym = 'Synonym: ' + item.synonyms;
				jQuery("<span class='syn'></span>").text(synonym).appendTo($a);

				var description = 'Definition: ' + item.def;
				jQuery("<span class='descr'></span>").text(description)
						.appendTo($a);
				return jQuery("<li></li>").append($a).appendTo(ul);
			};

		}
	}

}
