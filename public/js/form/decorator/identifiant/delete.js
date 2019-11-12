function %%FCT_NAME%% (btn) {
    var container = $(btn).closest('div');
	var lang = $(container).find('input').attr('lang');
	var inputGroup = $(container).parent().find('.input-group:last');
	$(inputGroup).find('.btn-group > ul li a[val=' + lang + ']').closest('li').removeClass('disabled');	
	if($(inputGroup).is(':hidden')) {
		$(inputGroup).find('.btn-group > button').val(lang);
		var textNode = $(inputGroup).find('.btn-group > button').contents().first();
		textNode.replaceWith($(inputGroup).find('.btn-group > ul li a[val=' + lang + ']').closest('li').text());	
		$(inputGroup).find('input').attr('name', $(inputGroup).find('input').attr('name').substr(0, $(inputGroup).find('input').attr('name').length -2) + '[' + lang + ']');	
		$(inputGroup).show();    
    }
    $(btn).tooltip('destroy');
    $(container).remove();
}