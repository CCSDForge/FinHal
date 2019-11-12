    $(function() {
    	
    	$('.terminate').click(function() {
    		var btnId = $(this).attr('id');
    		var right = btnId.replace('terminate-', '');
    		terminateUser(right);
            
        });

        
        $('#struct').autocomplete({
            minLength: 2,
            html: true,
            source: function(request, response){
                $.ajax({
                    url: "/ajax/ajaxsearchstructure",
                    dataType: "json",
                    data: {term: request.term},
                    success: function(data) {
                        response($.map(data.response.docs, function(item) {
                            return {id: item.docid, label: item.label_html}
                        }));
                    }
                });
            },
            focus: function( event, ui ) {
                return false;
            },
            select: function( event, ui ) {
                find = false;
                $('#structures input:hidden').each(function() {
                    find = find || $(this).val() == ui.item.id;
                });
                if (! find) {
                    addLabelElement(ui.item.label + ' (' + ui.item.id + ')', "roles[adminstruct][]", ui.item.id, '#structures');
                }
                return false;
            }
        });

        $('#collection').autocomplete({
            minLength: 2,
            html: true,
            source: "/administrate/ajaxsearchcollection",
            select: function( event, ui ) {
                find = false;
                $('#collections input:hidden').each(function() {
                    find = find || $(this).val() == ui.item.id;
                });
                if (! find) {
                    addLabelElement(ui.item.label , "roles[tamponneur][]", ui.item.id, '#collections');
                }
                return false;
            }
        });


    	$('.select-group').on('change', function() {
        	selectId = $(this).attr('id');
        	selectId = selectId.replace('-group', '-value');
    		 switch ( this.value ) {
                 case 'domain':
                 case 'structure':
                 case 'typdoc':
                 case 'sql':
                     $('#' + selectId ).prop( "disabled", false );
                     break;
                 default:
                     $('#' + selectId ).prop( "disabled", true );
                     break;
             }
    	});
    	

        });


    /**
     * END JQuery
     */
    
    
/**
 * Ajoute un critère qui définit l'utilisateur comme inactif pour un privilege 
 */
function terminateUser(right) {

	var sid = '0';
	var group = '';
	var value = 'terminated';
	addLabelElement('Droit suspendu', "roles[" + right + "][" + sid + "][" + group + "][]", value, "#" + right + "-list", 'label-html-terminated>span', right);
	$('#terminate-' + right).prop( "disabled", true );
}

/**
 * Réactive un privilège d'utilisateur
 */
function engageUser(right) {
	$('#terminate-' + right).prop( "disabled", false );
}



 
/**
 * Ajoute un critère
 */
    function addCritere(right)
    {
    	sidLabel = $('#' + right + '-sid :selected').text();
    	sidLabel = sidLabel + ' [' + $('#' + right + '-sid').val() + ']';

        sid = $('#' + right + '-sid').val();
        
        group = $('#' + right + '-group').val();
        value = $('#' + right + '-value').val();

        if (group == '0') {
        	group = '';
        }
        
        if (value == 0) {
            value = '';
        }

        if ((value == '') && (group !='')) {
        	message(translate('Une valeur est obligatoire pour : ') + translate(right), 'alert-danger');
        	return false;
        }
        
       var label = sidLabel + ' - ' + group + ':' + value;
            
        addLabelElement(label, "roles[" + right + "][" + sid + "][" + group + "][]", value, "#" + right + "-list");
        
		// reset form
        $('#' + right + '-sid, #' + right + '-group, #' + right + '-value').val('');
    }
    
/**
 * Ajoute un input type hidden avec les valeurs des critères
 */
    function addLabelElement(label, name, value, dest, htmlSource, right)
    {
    	htmlSource = typeof htmlSource !== 'undefined' ? htmlSource : 'label-html>span';

        var clone = $('#' + htmlSource).clone();
        $(clone).prepend(label);
        $('input:hidden', clone).attr('name',name).attr('data-right', right).val(value);
        $(dest).append(clone).append('&nbsp;');

    }

