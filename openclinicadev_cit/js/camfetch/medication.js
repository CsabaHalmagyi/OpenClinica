/**
 * Connects to a medication list service and displays the lookup results
 * 
 * @param groupname: the name of your itemgroup in the crf 
 * @param colNr the position of item in the crf where the lookup will be initiated
 */


function Medlist(groupname, colNr){
	jQuery(document).ready(function(){
		if (typeof(colNr) == 'undefined') {
			colNr=1;}
	var groupName = groupname.replace(" ","_");
		groupName = groupname.replace("-","");
	
		//groupName = groupName.toUpperCase();
		jQuery("table.aka_form_table").on("keyup", ":input", function(){
			jQuery(this).attr('autocomplete','off');
			var inpId = jQuery(this).attr("id");
			if (inpId.indexOf(groupName.toUpperCase()) !=-1){
				connectToService(jQuery(this),colNr);	
			}
		});	
	});
	
}

function connectToService(me,colNrPassed){
	if(me.attr("id")!=undefined){
	
	var url="http://openclinica-testing.medschl.cam.ac.uk/webservices/medication/medlist.php?q=";
	var expression = me.val();
	expression = expression.replace(' ', '+');
	var urlToService = url+expression;
    var myParent = me.parent();
    var colNr = jQuery(myParent).parent().children().index(jQuery(myParent)) + 1;
	if (colNr == colNrPassed) {
	jQuery(me).autocomplete({
    source: function( request, response ) {
        jQuery.ajax({
            url: urlToService,
            dataType: "json",
            data: {name: request.name},
            success: function(data) {
            	var datas = data.data;
                        response(jQuery.map(datas, function(item) {
                        return {
                        	label:item.drug_name,
                            name: item.drug_name,
                            id: item.nhs_drug_code,
                       

                            };
                    }));
                }
            });
        },
        minLength: 3,
        select: function(event, ui) {
            jQuery(me).val(ui.item.name);
            var parentIndex = jQuery(me).index();
            var code = me.parent().siblings(':eq(0)').children('input:text');
            code.val(ui.item.id);
        }
    }).data("ui-autocomplete")._renderItem = function(ul, item) {
		var $a = jQuery("<a></a>");
		jQuery("<span class='termname'></span>").text(item.label).appendTo($a);
		jQuery("<span class='termid'></span>").text(item.id).appendTo($a);

		return jQuery("<li></li>").append($a).appendTo(ul);
	};
	
	
	}
	}


}