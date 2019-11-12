/**
 * Created by sdenoux on 27/01/17.
 */


function addOrChangeRow(key, htmlContent) {
    // On selectionne la ligne correspondante à KEY si elle existe et on la modifie
    var existingLine = $('#file-' + key.toString());

    if (0 != existingLine.length) {
        $('#file-details-' + key.toString()).remove();
        existingLine.replaceWith(htmlContent);
    } else {
        // Sinon, on ajoute une nouvelle ligne
        addFile(htmlContent);
    }

    changeFileType(key);
}

/**
 * Telechargement d'un fichier récupéré à partir de son URL
 */
function downloadFile(url)
{
    ajaxrequestsubmit({url: url, data: {'url': $('#download-url').val()},
            success: function (data) {

                for (var key in data.filerow) {
                    if (data.filerow.hasOwnProperty(key)) {
                        addOrChangeRow(key, data.filerow[key]);
                    }
                }

                majView(data);

                $('#download-url').val('');

                hideLoading('modal-loading');

                // On affiche le success message seulement s'il y en a 1 !
                if (undefined != data.sucessMsg) {
                    $('#modal-success').html(data.sucessMsg);
                    showLoading('modal-success');
                }

                showErrorContent("#error-zone-file", data.err);
            }, dataType: 'json',
            error: function (msg) {
                showErrorContent("#error-zone-file", msg.responseText);
            }
        }, 'loadDoc'
    );
}

/**
 * Affichage du bouton "Compilation LaTex"
 */
function displayLatexProcessBtn()
{
    var display = false;
    $('#files .file-name').each(function() {
        if ( /tex$/i.test($(this).text()) || /zip$/i.test($(this).text()) ) {
            display = true;
            return false;
        }
    });

    var divFiles = $('#div-files .compile');

    if (display) {
        divFiles.css('display', 'block');
    } else {
        divFiles.css('display', 'none');
    }
}

/**
 * Ajout d'un fichier dans la liste des documents associés à un dépôt
 * @param row ligne à rajouter
 */
function addFile(row)
{
    $('#files>tbody').append(row);
    $('#div-files').css('display', 'block');

    if ($('#files>tbody .default:visible').length && $('#files>tbody .default:visible:checked').length == 0) {
        //On coche par défaut le 1er fichier pdf déposé
        $('#files>tbody .default:visible:first').prop('checked', 'checked');
    }

    displayLatexProcessBtn();

    // On modifie origine/format/etc pour la ligne que l'on vient d'ajouter
    // todo : créer la ligne directement bonne plutôt que de la modifier dans un second temps !
    changeFileType();
}

/**
 * Affichage des détails d'un fichier
 */
function fileDetails(id)
{
    $('#file-details-' + id).find('div.form-horizontal').toggle();
}

/**
 * Suppression d'un fichier
 */
function deleteFile()
{
    ajaxrequestsubmit({url: "/submit/ajaxdeletefile", data: {'method' : 'del', 'file' : $("#confirm-id").val()},
        success: function(data) {

            if (data.filenb == 'all') {
                $('#files tbody').html('');
            } else if (data.filenb == '1') {
                $('#file-' + $("#confirm-id").val()).remove();
                $('#file-details-' + $("#confirm-id").val()).remove();
            }
            if ($('#files tbody tr').length == 0) {
                $('#div-files').hide();
            }
            displayLatexProcessBtn();

            // On modifie origine/format/etc pour la ligne que l'on vient d'ajouter
            // todo : créer la ligne directement bonne plutôt que de la modifier dans un second temps !
            changeFileType();
        }, dataType: 'json',
        error: function(msg) {
            showErrorContent("#error-zone-file", msg.responseText);
        }
    });
}

/**
 * Suppression de tous les fichiers
 */
function deleteAllFiles()
{
    $("#confirm-id").val('all');
    deleteFile();
}

function addFullFileBlock(filehtml)
{
    $('#files>tbody').html(filehtml);
}

/**
 * Decompression d'une archive ZIP
 */
function unzip(id)
{
    ajaxrequestsubmit({ url: "/submit/ajaxunzip", data: {'file' : id}, success: function(data) {
        addFullFileBlock(data.fullfileblock);
    }, dataType: 'json', error: function(data) {
        showErrorContent("#error-zone-file", data.responseText);
    }}, 'unzipFile');
}


/**
 * Changement du type de fichier (principal, source, annexe)
 * affiche / masque certaines informations
 */
function changeFileType(id)
{

    // Si l'on a pas donné d'id, on modifie pour toutes les lignes
    if (id == undefined) {
        for (var i=0; i<$('#files>tbody tr').length; i++) {
            changeFileType(i);
        }
    }


    if ($('#type-' + id).val() == 'file') {
        $('#default-' + id + ', #origin-' + id).css('display', 'block');
    } else {
        $('#default-' + id).attr('checked', false);
        $('#default-' + id + ', #origin-' + id).css('display', 'none');
    }
    if ($('#type-' + id).val() == 'annex') {
        $('.file-annex-' + id).css('display', 'block');
    } else {
        $('.file-annex-' + id).css('display', 'none');;
        $('#format-' + id).val('');
    }

    if ($('#visible-' + id).val() == 'date') {
        if ( $('#visible-date-' + id).val() == '9999-12-31' ) {
            $('#visible-date-' + id).val('');
        }
        $('.visible-date-' + id).css('display', 'block');;
    } else  {
        $('#visible-date-' + id).val($('#visible-' + id).val());
        $('.visible-date-' + id).css('display', 'none');;
    }
}

function changeDefaultAnnex(elem)
{
    if ($(elem).val() == '1') {
        $('.default-annex').not('#' + $(elem).attr('id')).val('0');
    }

}

/**
 * Affichage des fichiers dispo sur le serveur FTP
 */
function displayFtp(title)
{
    $('#btn-ftp').popover({
        content: $('#ftp').html(),
        placement: 'bottom',
        trigger: 'manual',
        container: 'body',
        title: title +
        "<span class=\"remove-x\"><a href=\"javascript:void(0);\" onclick=\"$('#btn-ftp').popover('destroy')\"><i class=\"glyphicon glyphicon-remove\"></i></a></span>",
        html: true}).popover('show');
}

/**
 * Ajout d'un fichier via l'espace FTP du déposant
 */
function addFtpFile(url)
{
    $('input.ftp:checked').each(function() {
        ajaxrequestsubmit({url: url, data: { 'file' : $(this).val()}, success: function(data) {

            for (var key in data.filerow) {
                if (data.filerow.hasOwnProperty(key)) {
                    addOrChangeRow(key, data.filerow[key]);
                }
            }

            $('#btn-ftp').popover('destroy');

            hideLoading('modal-loading');

            // On affiche le success message seulement s'il y en a 1 !
            if (undefined != data.sucessMsg) {
                $('#modal-success').html(data.sucessMsg);
                showLoading('modal-success');
            }

        }, dataType: 'json', error: function(data){
            showErrorContent("#error-zone-file", data.responseText);
        }}, 'loadDoc');
    });
}

/**
 *
 * @param name
 */
function selectmainFile(key, name)
{
    ajaxrequestsubmit({url: "/submit/ajaxselectmainfile",
        data: {file: name},
        success: function (data) {

            for (var key in data.filerow) {
                if (data.filerow.hasOwnProperty(key)) {
                    addOrChangeRow(key, data.filerow[key]);
                }
            }

            hideLoading('modal-loading');

            // On affiche le success message seulement s'il y en a 1 !
            if (undefined != data.sucessMsg) {
                $('#modal-success').html(data.sucessMsg);
                showLoading('modal-success');
            }
        },
        dataType: 'json',
        error: function (msg) {
            // En cas d'erreur, on remet d'aplomb le fichier switché
            $("#default-"+key).toogle();
            showErrorContent("#error-zone-file", msg.responseText);
        }
    }, 'switchMain');
}

function ajaxchangefileMeta(element) {

    // On récupère le group lié à la métadonnée
    var key = $(element).attr('name');
    var group = "";

    var res = $(element).attr('name').match(/([^\[]*)\[([^\]]*)\]/);

    if (res != undefined && res[1] != undefined) {
        key = res[1];

        if (res[2] != undefined)
            group = res[2];
    }

    ajaxrequestsubmit({url: '/submit/ajaxchangefilemeta',
        data: {key: key, group: group, value: $(element).val()},
        dataType: 'json',
        success: function(response){
            for (var key in response.filerow) {
                if (response.filerow.hasOwnProperty(key)) {
                    addOrChangeRow(key, response.filerow[key]);
                }
            }
        },
        error: function(message) {
            try {
                var response = jQuery.parseJSON(message.responseText);
            } catch (err) { }

            showErrorContent("#error-zone-file", response.errorMsg);
            $(element).val(response.maxDate);
        }});
}

function init(url) {

    'use strict';
    $('#fileupload').fileupload({
        url: url,
        disableImagePreview: true,
        start: function () {
            // On rend visible les mentions légales pour l'ajout de fichier
            // Seulement si la case 'Ne plus afficher' n'a pas été cochée
            if (!$('#seelegal-check').is(":checked")) {
                $('#legal-text').show();
            }
            showLoading('modal-loading', 'loadDoc');
        },
        done: function (e, data) {

            $("#error-zone-file").hide();

            try {
                var response = jQuery.parseJSON(data.result);
            } catch (ex) {}

            // on n'affiche le message de retour que si le paramètre "noReturnMsg" n'est pas positionné
            if (!response.noReturnMsg) {
                // Ajout du message de réussite
                $('#modal-success').html(response.sucessMsg);

                // Si le modal avec les droits est toujours ouvert, on bloque la fermeture du modal
                if ($('#legal-button:visible').length == 1) {
                    var legalButton = $('#legal-button');
                    if (undefined != response.sucessMsg) {
                        legalButton.attr('onclick', legalButton.attr("onclick") + "hideLoading('modal-loading');showLoading('modal-success');");
                    } else {
                        legalButton.attr('onclick', legalButton.attr("onclick") + "hideLoading('modal-loading');");
                    }

                } else {
                    hideLoading('modal-loading');
                    // On affiche le success message seulement s'il y en a 1 !
                    if (undefined != response.sucessMsg) {
                        showLoading('modal-success');
                    }
                }
            } else {
                hideLoading('modal-loading');
            }

            for (var key in response.filerow) {
                if (response.filerow.hasOwnProperty(key)) {
                    addOrChangeRow(key, response.filerow[key]);
                }
            }

            majView(response);

            listenToMetaChanges();

            if (undefined != response.err && response.err.length > 0) {
                // Il peut y avoir une erreur sur l'étape même si l'on charge le fichier (par exemple on met un fichier en type BREVET
                showErrorContent("#error-zone-file", response.err);
            }
        },
        error: function (data) {

            var response = data.responseText;

            //Erreur lors du transfert de fichier (extension, taille, ...)
            showErrorContent("#error-zone-file", response);

            hideLoading('modal-loading');
        }
    });
    displayLatexProcessBtn();

    // On modifie origine/format/etc pour la ligne que l'on vient d'ajouter
    // todo : créer la ligne directement bonne plutôt que de la modifier dans un second temps !
    changeFileType();

    listenToMetaChanges();
}

function listenToMetaChanges() {

    $("#form_file :input").change(function() {
        // On envoie pas de requête dans le cas d'un nouveau fichier (url, ftp inclus) et d'ajout d'un identifiant
        // TODO : trouver un moyen de tester && !this.class.contains("ftp")
        if (this.id != "fileupload" && this.id != "download-url" && this.id != "identifier-input-detailed") {
            ajaxchangefileMeta(this);
        }
    });
}


function sendLatexRequest()
{
    ajaxrequestsubmit({url: "/submit/latexprocess", success: function(response){
        hideLoading('modal-loading');

        // On affiche le success message seulement s'il y en a 1 !
        if (undefined != response.sucessMsg) {
            $('#modal-success').html(response.sucessMsg);
            showLoading('modal-success');
        }

        for (var key in response.filerow) {
            if (response.filerow.hasOwnProperty(key)) {
                addOrChangeRow(key, response.filerow[key]);
            }
        }

    }, dataType: 'json', error: function(data){
        showErrorContent("#error-zone-file", data.responseText);
    }}, 'latexCompilation');
}

function displayDate(display, id)
{
    if (display) {
        $('#date-' + id).css('visibility', 'visible');
        $('#visible-' + id).css('display', 'none');
    } else {
        $('#date-' + id).css('visibility', 'hidden');
        $('#visible-' + id).css('display', 'block');

        //Réinitialiser la valeur à 0 (immédiatement) lorsqu'on ferme l'embargo
        $('#visible-' + id).val($('#visible-' + id + ' option:first').val());
        ajaxchangefileMeta('#visible-' + id);
    }
}

