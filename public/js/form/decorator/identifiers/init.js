function %%FCT_NAME%% (elm, name) {            
    var code = $(elm).attr('val');
    var libelle = $(elm).html();

    if ($(elm).closest('li').hasClass('disabled')) {
        return false;
    }            
                
    $(elm).closest(".btn-group").find("button").val(code);
    var textNode = $(elm).closest(".btn-group").find("button").contents().first();            
    textNode.replaceWith(libelle);  
                                
    $(elm).closest('.input-group').find('input').attr('lang', code);
    $(elm).closest('.input-group').find('input').attr('name', name + "[" + code + "]");     

    return false;
}