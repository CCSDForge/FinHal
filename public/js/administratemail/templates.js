$(document).ready(function() {
	
	//Préparation de la modal
	if ($('.modal-opener').length && !modalStructureExists()) {
		createModalStructure();
	}

	// Activation des modals
	$('#templates').on('click', '.modal-opener', function(e) {
		e.preventDefault();
		openModal($(this).attr('href'), $(this).attr('title'), $(this).data());
		return false;
	});

	// Bouton de suppression (restore defaults)
	$('#templates').on('click', 'a.delete-template', function(e) {
		
		e.preventDefault();
		var url = $(this).attr('href');
		
		bootbox.setDefaults({locale: locale});
		bootbox.confirm(translate("Êtes-vous sûr ?"), function(result)
		{
			if (result) {
				window.location = url;
			}
		});
	});
	
	// Suppression des TinyMCE à la fermeture d'une modal 
	$('#modal-box').on('hidden.bs.modal', function () {
		tinymce.remove('#modal-box textarea');
	})

});


function modalStructureExists()
{
	if ($('#modal-box').length) {
		return true;
	} else {
		return false;
	}
}

function createModalStructure(params)
{
	// Chargement en ajax de la structure de la modal
	$.ajax({
		url 	:	'/partial/modal',
		type	:	'POST',
		data 	:	params,
		success :	function (modalStructure) {
			$('body').append(modalStructure);
		}
	});	
}


// Initialise une modalbox avec contenu ajax
function openModal(url, title, params)
{
	// Mise à jour des paramètres css de la modal
	if (params) {
		for (key in params) {
			$('#modal-box .modal-dialog').css(key, params[key]);
		}
	}
	
	// Si la structure n'existe pas, on ne peut pas ouvrir la modal
	if (!modalStructureExists()) {
		return false;
	}
	
	// Initialisation de la modal
	$('#modal-box .modal-title').html(title);
	$('#modal-box .modal-body').html(getLoader());
	
	$('#modal-box').modal();
	
	// Chargement en ajax du contenu de la modal
	var oUrl = $.url(url);
	$.ajax({
		url 	:	oUrl.attr('path'),
		type	:	'POST',
		data 	:	oUrl.param(),
		success :	function (content) {
			$('#modal-box .modal-body').html(content);
			$(document).ready(function() {
				__initMCE('textarea', $('#modal-box .modal-body'));
			});
		}
	});
	
	// Redimensionnement de la modal (wip)
	// resizeModal();
}

function resizeModal(width, height) 
{
	if (!width) {width='560px';}
	if (!height) {height='400px';}
	
	$('#modal-box .modal').css('width', width);
	$('#modal-box .modal').css('margin-left', function () {return -($(this).width() / 2);});
	$('#modal-box .modal-body').css({'max-height': height});
}

function resetModalSize()
{
	var default_width 	= '560px';
	var default_height 	= '400px';
	var default_ml 		= '-'+(default_width/2)+'px';
	
	$('#modal-box .modal').css({
		'width': default_width,
		'margin': '-250px 0 0 '+default_ml 
	});
	
	$('#modal-box .modal-body').css({
		'max-height': default_height
	});
}

function scrollTo(target, container)
{
	if (!container) {container = $('html, body')}
	window.location.hash = target;
}