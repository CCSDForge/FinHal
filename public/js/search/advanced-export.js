$( document ).ready(function() {
// 	multisortable
	$('ul.sortable').multisortable();
	$('ul#available').sortable('option', 'connectWith', 'ul#fl');
	$('ul#fl').sortable(
		{ update: function(event, ui) {
		    	$( "#userExportUrl" ).html('');
		    	$( "#userExportFile" ).html('');
		    }
		},{ connectWith: 'ul#available' }
	);




	// Formulaire
	$("#bringData").submit( function(e) {
		e.preventDefault();
		$('#loadingmessage').show();

		var $this = $(this);
	    var sortedIDs =  $('ul#fl').sortable( "toArray" );

	      var data = $this.serializeArray();
	      data.push({name: 'fl', value: sortedIDs});

	      $.ajax({
              url: $this.attr('action'),
              type: $this.attr('method'),
              data: data,
              success: function(res) {
		var parsedData = JSON.parse(res);
                $( "#userExportUrl" ).html( '<a href="' + parsedData['url'] + '" role="button" class="btn btn-success" target="_blank"><span class="glyphicon glyphicon-link" aria-hidden="true"></span>&nbsp;' + translate("Voir l\'export") + '</a>' );
		if (parsedData['file'] != null) {
		    $( "#userExportFile" ).html( '<a href="' + parsedData['file'] + '" role="button" class="btn btn-success"><span class="glyphicon glyphicon-download" aria-hidden="true"></span>&nbsp;' + translate("Télécharger l\'export") + '</a>');
		}
                $('#loadingmessage').hide('slow');
              }
          });
	  });

// changement format
	$('#wt').on('change', function() {
		$( "#userExportUrl" ).html('');
		$( "#userExportFile" ).html('');
		 switch ( this.value ) {
		 case 'xml':
		 case 'json':
		     $("#messageAboutExport").html('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon-warning" aria-hidden="true"></span>' + translate("L'ordre personnalisé de vos champs ne sera pas conservé à l'export") + "</div>");
		     $( "#exportableFields" ).show( "fast" );
		     break;

		 case 'csv':
		     $("#messageAboutExport").html('<div class="alert alert-info" role="alert">' + translate("Vous pouvez réordonner les champs à l'intérieur du cadre. L'ordre personnalisé de vos champs sera conservé à l'export") + "</div>");
		     $( "#exportableFields" ).show( "fast" );
		     break;

		 default:
 			$( "#exportableFields" ).hide( "fast" );
		 	$("#messageAboutExport").html("<div class=\"alert alert-warning\" role=\"alert\">" + translate("Le choix des champs n'est pas possible avec ce format") + "</div>");
			 break;
		 }
	});

});
