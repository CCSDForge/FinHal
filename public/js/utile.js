//Correction bug jQuery sortable table
var fixHelperSortable = function(e, ui) {
	ui.children().each(function() {
		$(this).width($(this).width());
	});
	return ui;
};

/**
 * Lien vers une url
 * @param url
 * @return
 */
function link(url)
{
	self.location.href = url;
}

/**
 * Création d'un lien permanetn à partir d'une chaine de caractèree
 * @param str
 * @returns string
 */
function permalink(str)
{
    str = str.toLowerCase();
    var from = "àáäâèéëêìíïîòóöôùúüûñç·_,:;";
    var to   = "aaaaeeeeiiiioooouuuunc-----";
    for (var i=0, l=from.length ; i<l ; i++) { str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));}
    str = str.replace(/\s/g,'-');
    str = str.replace(/[^a-zA-Z0-9\-]/g,'');
    str = str.toLowerCase();
    str = str.replace(/-+/g,'-');
    str = str.replace(/(^-)|(-$)/g,'');
    return str;
}

/**
 * Vérification de la validité d'un formulaire
 * @param form
 * @return bool
 */

function validForm (form) {
	var inputs;
	var valid = true;
	var fullDisabled;
	inputs = form.find('input.required, textarea.required');
	$('.form-error').css('display', 'none');
	form.find('.error').removeClass('error');
	
	inputs.each(function() {
		if ($(this).hasClass('inputlangmulti') && $(this).attr('type') == 'text') {
			var fullDisabled = true
			
			$(this).parent().find('.dropdown-menu > li').each(function() {
				if (!$(this).hasClass('disabled')) {
					fullDisabled = false;
				}
			});

			if (!fullDisabled) {
				$(this).addClass('error');
				$(this).parents('.typdoc:last').find('.typdoc_render').addClass('error');
				valid = false;
			}
		} else if ($(this).val() == '') {
			$(this).addClass('error');
			$(this).parents('.typdoc:last').find('.typdoc_render').addClass('error');
			valid = false;
		}
	});

	if (!valid) {
		
		//$('.form-error').css('display', 'block');
		$('.form-error').fadeIn(1000);
	}

	return valid;
}

function message(text, type)
{
    $("#flash-messages").html(getMessageHtml(text, type));
    setTimeout(function() {$("#flash-messages .alert").alert("close");},10000);
}

function getMessageHtml(text, type)
{
    return '<div class="alert '
        + type
        + '"><button type="button" class="close" data-dismiss="alert">&times;</button>'
        + text + '</div>';
}

// Renvoie une barre de chargement animée
function getLoader(text)
{
    if (text === undefined) text = translate("Chargement en cours");

    var loading = '';
	loading += '<div class="loader">';
	loading += '<div class="text-info" style="font-size: 12px">' + text + '</div>';
        loading += '<div class="progress progress-striped active" style="height: 7px;">';
        loading += '<div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">';
        loading += '</div>';
        loading += '</div>';
        loading += '</div>';
    
    return loading;
}

/**
 * Transforme une URL dans un texte en lien clicable
 * @param inputText
 * @param options
 * @returns {XML|string}
 */
function linkify(inputText, options) {

    this.options = {linkClass: 'url', targetBlank: true};

    this.options = $.extend(this.options, options);

    inputText = inputText.replace(/\u200B/g, "");

    //URLs starting with http://, https://, or ftp://
    var replacePattern1 = /(src="|href="|">|\s>)?(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;ï]*[-A-Z0-9+&@#\/%=~_|ï]/gim;
    var replacedText = inputText.replace(replacePattern1, function($0,$1){ return $1?$0:'<a class="'+ this.options.linkClass + '" href="' + $0 + '"' + (this.options.targetBlank?'target="_blank"':'') + '>'+ $0+ '</a>';});

    //URLS starting with www and not the above
    var replacePattern2 = /(src="|href="|">|\s>|https?:\/\/|ftp:\/\/)?www\.[-A-Z0-9+&@#\/%?=~_|!:,.;ï]*[-A-Z0-9+&@#\/%=~_|ï]/gim;
    var replacedText = replacedText.replace(replacePattern2, function($0,$1){ return $1?$0:'<a class="'+ this.options.linkClass + '" href="http://' + $0 + '"' + (this.options.targetBlank?'target="_blank"':'') + '>'+ $0+ '</a>';});

    //Change email addresses to mailto:: links
    var replacePattern3 = /([\.\w]+@[a-zA-Z_]+?\.[a-zA-Z]{2,6})/gim;
    var replacedText = replacedText.replace(replacePattern3, '<a class="' + this.options.linkClass + '" href="mailto:$1">$1</a>');

    return replacedText;
}

/**
 * Decryptage d'un mail
 * @param str
 * @param idElem
 */
function decryptMail (mailC, id, text)
{
    var map = new Array();
    var s   = "abcdefghijklmnopqrstuvwxyz";

    for (i=0; i<s.length; i++) map[s.charAt(i)] = s.charAt((i+13)%26);
    for (i=0; i<s.length; i++) map[s.charAt(i).toUpperCase()] = s.charAt((i+13)%26).toUpperCase();
    mail = "";
    for (i=0; i < mailC.length; i++){
        var b = mailC.charAt(i);
        mail += (b>='A' && b<='Z' || b>='a' && b<='z' ? map[b] : b);
    }
    if (text == undefined || text == '') {
        text = mail;
    }

    $('#' + id).attr('href', 'mailto:' + mail).attr('title', mail).attr('data-toggle', 'tooltip').html(text);

}

/**
 * Filtre les balises d'une chaîne
 * @param html
 * @returns {string}
 */
function strip_tags(html)
{
    if(arguments.length < 3) {
        html=html.replace(/<\/?(?!\!)[^>]*>/gi, '');
    } else {
        var allowed = arguments[1];
        var specified = eval("["+arguments[2]+"]" );
        if(allowed){
            var regex='</?(?!(' + specified.join('|') + '))\b[^>]*>';
            html=html.replace(new RegExp(regex, 'gi'), '');
        } else{
            var regex='</?(' + specified.join('|') + ')\b[^>]*>';
            html=html.replace(new RegExp(regex, 'gi'), '');
        }
    }

    var clean_string = html;

    return clean_string;
}