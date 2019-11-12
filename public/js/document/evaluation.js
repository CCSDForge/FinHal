$(document).ready(function() {
	
    $('#evaluation-msg').change(function() {
        $('#comment').val($('#comment').val() + $('#msg-' + $(this).val()).text());
    });
	

        
    
});

/**
 * Ajoute un input type hidden avec les valeurs des critères
 */
    function addLabelElement(label, value, dest, htmlSource)
    {
    	htmlSource = typeof htmlSource !== 'undefined' ? htmlSource : 'label-html>span';

        var clone = $('#' + htmlSource).clone();
        $(clone).prepend(label);
        $('input:hidden', clone).attr('value', value);
        $(dest).append(clone).append('&nbsp;');
        

    }
    
    
/**
 * Récupération formulaire choix experts
 */
    function getExpertSelectForm(docid, sid, domains, typdoc, forwardAction, sourceController)
    {
        $.ajax({
            url: '/' + sourceController + '/ajaxselectexpert',
            type: "post",
            data: {docid: docid, sid: sid, domains : domains, typdoc: typdoc, forwardAction: forwardAction},
            success: function(data) {
                $('#expert-select-form').html(data);
            }
        });
       
    }

    
    
