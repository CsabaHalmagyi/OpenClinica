/**
 * Connects to the SNOMED webservice and displays the fetched data
 * 
 * @param groupname:
 *            the name of your itemgroup in the crf
 * @param colNr
 *            the column number of the crf where the lookup will be initiated
 */

function Snomed_rep(groupname, colNr) {

	if (typeof (colNr) == 'undefined') {
		colNr = 1;
	}
	var groupN = groupname.replace(" ", "_");
	groupN = groupN.replace("-", "");

	groupN = groupN.toUpperCase();
	jQuery("table.aka_form_table").on("keyup", ":input", function() {

		jQuery(this).attr('autocomplete', 'off');
		var inpId = jQuery(this).attr("id");
		// alert(inpId+" "+groupN);
		if (inpId.indexOf(groupN.toUpperCase()) != -1) {
			connectToSnomed(jQuery(this), colNr);
		}
	});

}

function connectToSnomed(me, colNrPassed) {
	if (me.attr("id") != undefined) {

		var url = "http://openclinica-testing.medschl.cam.ac.uk/webservices/snomed/snomedlookup.php?q=";
		var term = me.val();
		var urlToService = url + term;
		var myParent = me.parent();
		var colNr = jQuery(myParent).parent().children()
				.index(jQuery(myParent)) + 1;
		if (colNr == colNrPassed) {
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
									var datas = data.data;
									response(jQuery.map(datas, function(item) {
										return {
											label : item.snomed_term,
											name : item.snomed_term,
											id : item.snomed_ct,

										};
									}));
								}
							});
						},
						minLength : 3,
						select : function(event, ui) {
							jQuery(me).val(ui.item.name);
							var parentIndex = jQuery(me).index();
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

				return jQuery("<li></li>").append($a).appendTo(ul);
			};

		}
	}

}
