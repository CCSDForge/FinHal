function confirmSave() {
    $('#popupSaveSearch').modal({
	'keyboard' : true
    });
}

function saveSearch() {
    if (validForm($('#formSaveSearch'))) {
	$.ajax({
	    type : "post",
	    url : "/search/ajaxsave",
	    data : $('#formSaveSearch').serialize()
	}).done(function(msg) {
	    $('#saveResult').popover('hide');

	    if (msg == 'ok') {
		// $('#saveResult').addClass('disabled').attr('onClick', '');
		$('#saveSuccess').show().delay(4000).fadeOut(1000);
	    } else {
		$('#saveFail').show().delay(4000).fadeOut(1000);
	    }
	});
    }
}

function initSaveButtons() {
    $('#saveSuccess').css('display', 'none');
    $('#saveFail').css('display', 'none');
    $('#saveResult').css('display', 'inline-block');
}

// +/- intitulés facettes
$(document)
	.ready(
		function() {
		    $("li").find('a[val^=--sep]').parent().text(
			    '- - - - - - - - - -');
		    $('a[data-toggle="tooltip"]').tooltip();
		    $('input[data-toggle="tooltip"]').tooltip();

		    // Boutons tout sélectionner/déselectionner
		    $("[id^=select-all]").click(
			    function() {
				$(this).tooltip('hide');

				$("[id^=docid_]").prop('checked',
					$(this).is(':checked'));
				$("[id^=select-all]").prop('checked',
					$(this).is(':checked'));

				if ($(this).is(':checked')) {
				    $(this).attr('data-original-title',
					    'Sélectionner aucun document');
				} else {
				    $(this).attr('data-original-title',
					    'Sélectionner tous les documents');
				}
				$(this).tooltip('show');

			    });

		    // checkbox redirect
		    $("input[type='checkbox']").change(
			    function() {
				if ($(this).prevAll('a').attr("href")) {
				    window.location.href = $(this).prevAll('a')
					    .attr("href");
				}
			    });

		    $('.facet-label')
			    .click(
				    function() {

					var index = $(this).index() + 2;
					var index_input = $(this).index() + 1;
					var text = $(this).parent().find(
						'> :eq(' + index + ')');
					var text_input = $(this).parent().find(
						'> :eq(' + index_input + ')');

					if (text.is(':hidden')) {
					    text_input.slideDown(50);
					    text.slideDown(50);
					    $(this)
						    .children('span')
						    .html(
							    '<span class="glyphicon glyphicon-minus"></span>');
					} else {
					    text.slideUp(50);
					    text_input.slideUp(50);
					    $(this)
						    .children('span')
						    .html(
							    '<span class="glyphicon glyphicon-plus"></span>');
					}
				    });

		    $('[data-toggle="popover"]').popover();

		    // Sauvegarde de la recherche
		    $('#saveResult').popover({
			html : true,
			placement : 'bottom',
			// trigger : 'manual',
			title : '',
			content : function() {
			    return $('#popupSaveSearch').html();
			}
		    });

		    // Recherche avancée
		    $("#search-advanced").click(function() {
			$("#search-simple-form").toggle();
			$("#search-advanced-form").toggle();
			$("#search-simple").toggle();
			$(this).toggle();
		    });

		    $("#search-simple").click(function() {
			$("#search-simple-form").toggle();
			$("#search-advanced-form").toggle();
			$("#search-advanced").toggle();
			$(this).toggle();

		    });

		    // facettes
		    $("#q").focus();

		    // cases à cocher pour formulaire de filtres avant recherche

            $('body').on('click', '.checkall', function() {
				    $(this).closest('fieldset').find(':checkbox')
					.prop('checked', this.checked);
			    });

		});
