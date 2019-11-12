    $(document).ready(function() {
        $('.btn-action').click(function(event) {

        	event.preventDefault();

        	var action = $(this).attr('attr-action');

           	 if ( ($.trim($('#comment').val()) == '') && (action== 'refuse') ) {
           		
        		 $(getMessageHtml("Le commentaire est obligatoire en cas de refus.", 'alert-danger') ).insertAfter($( ".label-comment" ));
        		 return false;
        	                 
            } else {

                $('#validate-action').val(action);
                $('#validate-form').submit();
                return true;
                
            } 
        });
        
        
        $('#evaluation-msg').change(function() {
            $('#comment').val($('#comment').val() + $('#msg-' + $(this).val()).text());
        });
        
    });



    