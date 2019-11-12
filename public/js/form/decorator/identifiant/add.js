function %%FCT_NAME%% (btn, name) {

	var id = $(btn).closest('.input-group').find('input').val();
	// Trouver une manière plus propre de récupérer le type d'identifiant
	var idtype = $(btn).closest('.input-group').find('span')[0].children[0].value;

    var empty = id == "";
    if (!empty) {

        $.ajax({
            url: "/submit/ajaxvalidateid",
            data: {idtype: idtype, id: id},
            success: function(){
                var value = $(btn).closest(".input-group").find('input').val();

                var container = $(btn).closest(".input-group").parent();
                var inputGroup = $(container).find('.input-group:last');
                var clone = $(inputGroup).clone();
                var lang = $(clone).find('.btn-group > button').val();

                $(clone).attr('style', $(clone).attr('style') + ' margin-top: 10px;');
                $(clone).find('input').attr('lang', lang);
                $(clone).find('input').attr('name', name + "[" + lang + "]");
                $(clone).find('input').val(value);
                $(clone).find('input').attr('data-language', lang);
                $(clone).find(".errors").remove();
                $(clone).find('.glyphicon-plus').removeClass("glyphicon-plus").addClass("glyphicon-trash").parent().attr('onclick', '%%DELETE%%(this,"' + name + '")');
                $(clone).find('.glyphicon-trash').parent('button').attr('title', 'Supprimer');

                $(clone).insertBefore($(container).find('> :last'));

                $(container).find('.input-group .btn-group').each(function (i) {
                    $(this).find('ul li a[val=' + lang + ']').closest('li').addClass('disabled');
                });

                var elm = $(inputGroup).find('.btn-group > ul li[class!="disabled"]:first a');
                if (typeof $(elm).html() != 'undefined') {
                    $(inputGroup).find('.btn-group > button').val($(elm).attr('val'));
                    $(inputGroup).find('input').attr('name', name + "[" + $(elm).attr('val') + "]");
                    var textNode = $(inputGroup).find('.btn-group > button').contents().first();
                    textNode.replaceWith($(elm).text());
                } else {
                    $(inputGroup).find('input').attr('name', '');
                    $(inputGroup).find('input').attr('disabled', 'disabled');
                    $(inputGroup).hide();
                }

                $(btn).closest('.input-group').find('input:first').val("");
                $(btn).closest('.input-group').find('input:first').focus();
            },
            error: function(msg){

                $(msg.responseText).filter('.modal').modal({keyboard : true});
            }
        });
    }
}