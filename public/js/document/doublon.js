/**
 * Created by baptiste on 01/07/2015.
 */

var source = {};

function displayElem(i){
    $("#table"+i).find(".group").hide();
    $("#table"+i).find("input").val('');
    $("#table"+i).find("textarea").val('');
    $("#table"+i).find("div[data-form = '"+ $("#select"+i).val()+"']").css("display", "inline");
}

function escapeHtml(text) {
    return text
        .replace(/'/g, '\'');
}

function cancelDrag(array) {
    var sorted_arr = array.sort();
    var results = [];
    for (var i = 0; i < array.length - 1; i++) {
        if (sorted_arr[i + 1] == sorted_arr[i]) {
            results.push(sorted_arr[i]);
        }
    }
    return results;
}


function getIndexesOf(value, array) {
    var indexes = [];
    for (i in array) {
        if (array[i] == value)
            indexes.push(i);
    }
    return indexes;
}

function uniqueDrag(arr) {
    var res = [];

    for (i in arr) {
        var item = arr[i];
        var indexes = getIndexesOf(item, arr);
        if (indexes.length === 1) {
            res.push(item);
        }
    }
    return res;
}

function dragdrop(){
    // Table 0 => Table 1
    $("#table1").sortable({
        connectWith: "#table0",
        revert: true,
        cursor: "move",
        handle: ".meta-name",
        cancel: ".format,.type,.authors,.struct,.files,.ajoutmeta,"+cancelDrag(metadata).join(),
        stop: function( event, ui ) {
            $(this).find("span").attr('data-docid', $("#table1").attr("data-docid"));
            var docid = $(ui.item).find("span").attr('data-docid');
            var meta = $(ui.item).find("span").attr('data-meta');
            var type = $(ui.item).attr('data-type');
            var val = null;
            var i = 0;
            var grouplang = "";
            var value = {};
            $(ui.item).find("tr").each(function () {
                if (type == "multiTextSimpleLang" || type == "multiTextAreaLang"){
                    grouplang = $(this).find("span:first").text();
                    val = $(this).find("span:eq(1)").text().trim();
                } else if (type == "select") {
                    val = $(this).find("span").attr('data-value');
                } else if (type == "referentiel" || type == "multiReferentiel") {
                    val = $(this).find("span").find(".referentiel").attr('data-id');
                } else {
                    val = $(this).find("span").text();
                }
                var tabgroup = {"text" : null, "textarea" : null, "select" : null, "date" : null, "referentiel" : null, "multiReferentiel" : i, "multiTextSimple" : i, "multiTextSimpleLang" : grouplang, "multiTextArea" : i, "multiTextAreaLang" : grouplang};
                value[val] = tabgroup[type];
                ++i;
            });
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxajoutmeta',
                data: {docid : docid, meta : meta, value : JSON.stringify(value), indexer : administrate}
            });
            editable();
            $("#select"+1+" option[data-meta="+meta+"]").remove();
            setupselect(1);
        }
    });
    // Table 1 => Table 0
    $("#table0").sortable({
        connectWith: "#table1",
        revert: true,
        cursor: "move",
        handle: ".meta-name",
        cancel: ".format,.type,.authors,.struct,.files,.ajoutmeta,"+cancelDrag(metadata).join(),
        stop: function( event, ui ) {
            $(this).find("span").attr('data-docid', $("#table0").attr("data-docid"));
            var docid = $(ui.item).find("span").attr('data-docid');
            var meta = $(ui.item).find("span").attr('data-meta');
            var type = $(ui.item).attr('data-type');
            var val = null;
            var i = 0;
            var grouplang = "";
            var value = {};
            $(ui.item).find("tr").each(function () {
                if (type == "multiTextSimpleLang" || type == "multiTextAreaLang"){
                    grouplang = $(this).find("span:first").text();
                    val = $(this).find("span:eq(1)").text().trim();
                } else if (type == "select") {
                    val = $(this).find("span").attr('data-value');
                } else if (type == "referentiel" || type == "multiReferentiel") {
                    val = $(this).find("span").find(".referentiel").attr('data-id');
                } else {
                    val = $(this).find("span").text();
                }
                var tabgroup = {"text" : null, "textarea" : null, "select" : null, "date" : null, "referentiel" : null, "multiReferentiel" : i, "multiTextSimple" : i, "multiTextSimpleLang" : grouplang, "multiTextArea" : i, "multiTextAreaLang" : grouplang};
                value[val] = tabgroup[type];
                ++i;
            });
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxajoutmeta',
                data: {docid : docid, meta : meta, value : JSON.stringify(value), indexer : administrate}
            });
            editable();
            $('#select'+0+' option[data-meta='+meta+'"]').remove();
            setupselect(0);
        }
    });

    $("#table0 > tbody").draggable({
        connectToSortable: "#table1",
        helper: "clone",
        handle: ".meta-name",
        revert: "invalid",
        cancel: ".format,.type,.authors,.struct,.files,.ajoutmeta,"+cancelDrag(metadata).join()
    });

    $("#table1 > tbody").draggable({
        connectToSortable: "#table0",
        helper: "clone",
        handle: ".meta-name",
        revert: "invalid",
        cancel: ".format,.type,.authors,.struct,.files,.ajoutmeta,"+cancelDrag(metadata).join()
    });

}



function editable (){
    $('.select').editable({
        type: 'select',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var value = $('.editable-input').find("select").val();
            var meta = $(this).attr("data-meta");
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value , indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.selectlang').editable({
        type: 'select',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var meta = $(this).attr("data-meta");
            var value = $('.editable-input').find("select").val();
            var oldlang = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmetagroup',
                data: {docid : docid, meta : meta, value : value, oldlang : oldlang , indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.multiTextAreaLang').editable({
        type: 'textarea',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var meta = $(this).attr("data-meta");
            var value = $('.editable-input').find("textarea").val();
            var old = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value , old : old, indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.multiTextSimpleLang').editable({
        type: 'text',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).closest(".metas").data("docid");
            var meta = $(this).closest(".metas").attr("data-meta");
            var value = $('.editable-input').find("input[type='text']").val();
            var old = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value , old : old, indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.multiTextArea').editable({
        type: 'textarea',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var meta = $(this).attr("data-meta");
            var value = $('.editable-input').find("textarea").val();
            var old = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value , old : old, indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.multiTextSimple').editable({
        type: 'text',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var meta = $(this).attr("data-meta");
            var value = $('.editable-input').find("input[type='text']").val();
            var old = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value , old : old , indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.text').editable({
        type: 'text',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var meta = $(this).attr("data-meta");
            var value = $('.editable-input').find("input[type='text']").val();
            var old = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value, old: old , indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.textarea').editable({
        type: 'textarea',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var meta = $(this).attr("data-meta");
            var value = $('.editable-input').find("textarea").val();
            var old = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value, old : old , indexer : administrate}
            });
        },
        unsavedclass: null
    });
    $('.identifier').editable({
        type: 'text',
        container: 'body',
        pk: 1,
        success: function(){
            var docid = $(this).data("docid");
            var meta = $(this).attr("data-meta");
            var value = $('.editable-input').find("input[type='text']").val();
            var old = $(this).html();
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxmodifmeta',
                data: {docid : docid, meta : meta, value : value, old: old , indexer : administrate}
            });
        },
        unsavedclass: null
    });
}


$( document ).ready(function() {

    if (administrate == "doublon") {
        dragdrop();
        uniqueDrag(metadata).forEach(function(elem){
            $("tr[class='"+elem.substring(1,elem.length)+"']:first-child td:first-child").prepend("<i class=\"glyphicon glyphicon-move handle\"></i>");
        });
    }
    editable();



    $('.input-radio').click(function() {
        $('.input-radio').prop("checked", false);
        $('.input-radio[value="' + $(this).val() + '"]').prop("checked", true);
        $('.input-radio').closest('label').attr('class', "btn btn-default");
        $('.input-radio[value="' + $(this).val() + '"]').closest('label').attr('class', "btn btn-success");
        $('.doc-radio').removeClass('alert-success');
        $('.doc-radio[attr-id="' + $(this).val() + '"]').addClass('alert-success');
    });
    $('.input-btn').click(function() {
        $('.input-btn').attr('class', "input-btn btn btn-default");
        $(this).attr('class', "input-btn btn btn-primary");
    });
    $('#fusion').click(function(){
        $('.editable').editable('enable',true);
        $('.editable').addClass('editable-click');
        $('#choix').attr('value','fusion');
    });
    $('#hierarchiser').click(function(){
        $('.editable').editable('disable',true);
        $('.editable').removeClass('editable-click');
        $('.editable').removeClass('editable-disabled');
        $('#choix').attr('value','hierarchiser');
    });

    $('button[id^="ajouter"]').each(function(){
        var nb = $(this).attr("id").substring(7);
        $(this).click(function(){
           var docid = $(this).data("docid");
           var meta = $('#select'+nb+' option:selected').data("meta");
           var val = $('#select'+nb+' option:selected').val();
           var value = {};
           var cell, ligne;
           var i = 0;
           var tableau = document.getElementById("table"+nb);
           $('input[name^='+val+nb+']').each(function () {
               if ($(this).val()!="") {
                   var grouplang = "";
                   var nbLignes = tableau.rows.length;
                   ligne = tableau.insertRow(nbLignes - 1);
                   cell = ligne.insertCell(0);
                   ligne.className = meta;
                   if (i == 0) {
                       cell.innerHTML = $('#select'+nb+' option:selected').text();
                       cell = ligne.insertCell(1);
                       if ($('#select'+nb+' option:selected').val() == "multiTextSimpleLang" || $('#select'+nb+' option:selected').val() == "multiTextAreaLang"){
                           cell.innerHTML = '<span data-docid="'+docid+'" data-meta="'+meta+'" data-source=\''+lang+'\' class=\"selectlang editable editable-click label label-default\">' + $(this).next('span').find('button').val() + '</span>';
                           cell = ligne.insertCell(2);
                           grouplang = $(this).next('span').find('button').val();
                       } else {
                           tableau.rows[nbLignes - (i + 1)].cells[1].colSpan = "2";
                       }
                       cell.innerHTML = '<span data-docid='+docid+' data-meta='+meta+' class=\"'+ $('#select'+nb+' option:selected').val()+ ' editable editable-click\">' + $(this).val() + '</span>';
                       editable();
                   } else {
                       tableau.rows[nbLignes - (i + 1)].cells[0].rowSpan = i + 1;
                       if ($('#select'+nb+' option:selected').val() == "multiTextSimpleLang" || $('#select'+nb+' option:selected').val() == "multiTextAreaLang") {
                           cell.innerHTML = '<span data-docid="'+docid+'" data-meta="'+meta+'" data-source=\''+lang+'\' class=\"selectlang editable editable-click label label-default\">' + $(this).next('span').find('button').val() + '</span>';
                           cell = ligne.insertCell(1);
                           grouplang = $(this).next('span').find('button').val();
                       } else {
                           cell.colSpan = "2";
                       }
                       cell.innerHTML = '<span data-docid="'+docid+'" data-meta="'+meta+'" class=\"'+ $('#select'+nb+' option:selected').val()+ ' editable editable-click\">' + $(this).val() + '</span>';
                       editable();
                   }
                   var tabgroup = {"text" : null, "textarea" : null, "select" : null, "multiTextSimple" : i, "multiTextSimpleLang" : grouplang, "multiTextArea" : i, "multiTextAreaLang" : grouplang};
                   value[$(this).val()] = tabgroup[val];
                   ++i;
               }
           });
           var i = 0;
           $('select[name^='+'form'+val+nb+']').each(function () {
               if ($(this).val()!="") {
                   var nbLignes = tableau.rows.length;
                   ligne = tableau.insertRow(nbLignes - 1);
                   cell = ligne.insertCell(0);
                   ligne.className = meta;
                   cell.innerHTML = $('#select'+nb+' option:selected').text();
                   cell = ligne.insertCell(1);
                   tableau.rows[nbLignes - (i + 1)].cells[1].colSpan = "2";
                   cell.innerHTML = '<span data-docid="'+docid+'" data-meta="'+meta+'" data-source=\''+escapeHtml(JSON.stringify(source[meta]))+'\' class=\"'+ $('#select'+nb+' option:selected').val()+ ' editable editable-click\">' + $(this).find('option:selected').text() + '</span>';
                   editable();

                   value[$(this).find('option:selected').val()] = null;
               }
           });
           var i = 0;
           $('textarea[name^='+val+nb+']').each(function () {
               if ($(this).val()!="") {
                   var grouplang = "";
                   var nbLignes = tableau.rows.length;
                   ligne = tableau.insertRow(nbLignes - 1);
                   cell = ligne.insertCell(0);
                   ligne.className = meta;
                   if (i == 0) {
                       cell.innerHTML = $('#select'+nb+' option:selected').text();
                       cell = ligne.insertCell(1);
                       if ($('#select'+nb+' option:selected').val() == "multiTextSimpleLang" || $('#select'+nb+' option:selected').val() == "multiTextAreaLang"){
                           cell.innerHTML = '<span data-docid="'+docid+'" data-meta="'+meta+'" data-source=\''+lang+'\' class=\"selectlang editable editable-click label label-default\">' + $(this).next('div').find('button').val() + '</span>';
                           cell = ligne.insertCell(2);
                           grouplang = $(this).next('div').find('button').val();
                       } else {
                           tableau.rows[nbLignes - (i + 1)].cells[1].colSpan = "2";
                       }
                       cell.innerHTML = '<span data-docid='+docid+' data-meta='+meta+' class=\"'+ $('#select'+nb+' option:selected').val()+ ' editable editable-click\">' + $(this).val() + '</span>';
                       editable();
                   } else {
                       tableau.rows[nbLignes - (i + 1)].cells[0].rowSpan = i + 1;
                       if ($('#select'+nb+' option:selected').val() == "multiTextSimpleLang" || $('#select'+nb+' option:selected').val() == "multiTextAreaLang") {
                           cell.innerHTML = '<span data-docid="'+docid+'" data-meta="'+meta+'" data-source=\''+lang+'\' class=\"selectlang editable editable-click label label-default\">' + $(this).next('div').find('button').val() + '</span>';
                           cell = ligne.insertCell(1);
                           grouplang = $(this).next('div').find('button').val();
                       } else {
                           cell.colSpan = "2";
                       }
                       cell.innerHTML = '<span data-docid="'+docid+'" data-meta="'+meta+'" class=\"' + $('#select'+nb+' option:selected').val() + ' editable editable-click\">' + $(this).val() + '</span>';
                       editable();
                   }
                   var tabgroup = {"text" : null, "textarea" : null, "select" : null, "multiTextSimple" : i, "multiTextSimpleLang" : grouplang, "multiTextArea" : i, "multiTextAreaLang" : grouplang};
                   value[$(this).val()] = tabgroup[val];
                   ++i;
               }
           });
            $.ajax({
                type: "POST",
                url: '/administrate/ajaxajoutmeta',
                data: {docid : docid, meta : meta, value : JSON.stringify(value), indexer : administrate}
            });
            if (val == null){
                $('#select'+nb+' option:selected').remove();
            }
           displayElem(nb);
           setupselect(nb);
       });
        $("#select"+nb).change(function() {
            displayElem(nb);
        });

    });
});