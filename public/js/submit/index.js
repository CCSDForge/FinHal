/**
 * Created by sdenoux on 27/01/17.
 */

/**
 * @param selector
 * @param content
 */
function showErrorContent(selector, content)
{
    var errorZone = $(selector);
    errorZone.html(translate(content));
    errorZone.show();
}


function resetErrors() {
    $("#error-zone-file").hide();
    $("#error-zone-recap").hide();
}

/**
 * @param id
 * @param msg
 */
function showLoading(id, msg)
{
    if (msg != undefined) {
        $('#waiting-msg span').text(translate(msg));
    }

    var modalW = $('#'+id);
    if (!modalW.is(":visible")) {
        modalW.modal('show');
    }
}

/**
 * @param id
 */
function hideLoading(id)
{
    $('#'+id).modal('hide');
}

/**
 * Requete post effectuée
 * @param params - parametres passés à la requête ajax
 * @param loadingMsg
 */
function ajaxrequestsubmit(params, loadingMsg)
{
    params.type = "post";

    params.complete = function(data) {

        if (undefined != data.responseText && "" != data.responseText) {
            try {
                data = JSON.parse(data.responseText);
                majView(data);
            } catch (err) { }
        }

        if (undefined != data && undefined != data.errorajax) {

            for(var step in data.errorajax) {
                showErrorContent("#error-zone-"+step, data.errorajax[step]);
            }
        }

        hideLoading('modal-loading');
    };

    params.statusCode = {
        401: function() {
            // Dans le cas où l'utilisateur n'est plus loggué, on recharge la page.
            location.reload();
        }
    };

    params.beforeSend = function(){

        resetErrors();

        if (undefined != loadingMsg && null != loadingMsg && '' != loadingMsg) {
            showLoading('modal-loading', loadingMsg);
        }
    };

    $.ajax(params);
}

/**
 * Mise à jour des icones "etape valide" ou "etape invalide"
 * @param validity
 */
function majstepValidity(validity)
{

    /** Affichage de la validitié de chaque étape */
    $.each(validity, function( key, value ) {
        if (null != value) {
            var c = 'glyphicon glyphicon-' + (value ? 'ok text-success' : '');
            $('#' + key + '-validity i').attr('class', c);
        }

        // Met en rouge l'étape lorsqu'elle n'est pas valide
        if ('recap' != key) {
            if (!value) {
                $('#' + key + '-validity i').parent().parent().addClass('error');
            } else {
                $('#' + key + '-validity i').parent().parent().removeClass('error');
            }
        }
    });
}

// Dupliqué de Ccsd_View_Helper_Autocomplete pour les besoins du dépot simplifié pour s'assurer qu'on autocomplete le champ REVUE
var journalAutocomplete = $.extend({"type":"POST","async":false,"source":"\/ajax\/ajaxgetreferentiel?element=journal&type=journal"},{},{select: function (event, ui) {
    if (ui.item.id == 0) {
        $.ajax({
            url : "/ajax/ajaxgetreferentiel/element/journal/type/journal/new/true",
            async: false,
            type : "get",
            success : function (msg) {
                $(this).attr('disabled', 'disabled');
                $(msg).filter('.modal').modal({keyboard : true});
            }
        });
    } else {
        var founded = false;

        $.each ($(event.target).closest('.form-group').find('div[data-id]'), function (i) {
            if (ui.item.id == $(this).attr('data-id')) {
                founded = true;
            }
        });

        var colorChanged = function (c) {
            $(event.target).closest('.form-group').find('div[data-id=' + ui.item.id + '] *').css('color', c);
            $(event.target).closest('.form-group').find('div[data-id=' + ui.item.id + '] *').css('-webkit-transition', 'color 1000ms linear');
            $(event.target).closest('.form-group').find('div[data-id=' + ui.item.id + '] *').css('-moz-transition', 'color 1000ms linear');
            $(event.target).closest('.form-group').find('div[data-id=' + ui.item.id + '] *').css('-o-transition', 'color 1000ms linear');
            $(event.target).closest('.form-group').find('div[data-id=' + ui.item.id + '] *').css('-ms-transition', 'color 1000ms linear');
            $(event.target).closest('.form-group').find('div[data-id=' + ui.item.id + '] *').css('transition', 'color 1000ms linear');
        };

        if (!founded) {

            $.ajax({
                url : "/ajax/ajaxgetreferentiel/element/journal/type/journal/id/" + ui.item.id,
                async: false,
                type : "get",
                success : function (msg) {

                    add_ref ('journal', 'journal', msg);
                }
            });

        } else {

            colorChanged('#53bc66');
            setTimeout(function() { colorChanged('inherit'); } ,2000);

        }
    }

    $(this).val('');
    return false;
},
    focus: function (event, ui) {
        return false;
    }
});

/**
 *
 * FONCTION A DOUBLE SENS !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * Mise à Jour des vues
 * @param responseData
 */
function majView(responseData) {

    // Fichier principal
    if (responseData.preview)
        majMainVisuel(responseData.preview);

    // Validité des étapes
    if (responseData.validity)
        majstepValidity(responseData.validity);

    // Vue Metadonnées => on recharge toute la vue
    if (responseData.meta) {
        $('#panel-body-meta').html(responseData.meta);
        $(document).on("focus", ":input[id='journal']", function () {
            $(this).autocomplete(journalAutocomplete).data("ui-autocomplete")._renderItem = function (ul, item) {
                return $("<li>").append("<a>" + item.label + "</a>").appendTo(ul);
            };
        });
    }

    // Vue Auteur
    if (responseData.author)
        $('#panel-body-author').html(responseData.author);

    // Vue Recap
    if (responseData.recap)
        $('#panel-body-recap').html(responseData.recap);

    // Types de document
    if (responseData.typdocs) {
        majTypdocsDropdown("typdoc-menu", responseData.typdocs);
        majTypdocs("type", responseData.typdocs);
    }

    // Types de document
    if (responseData.currenttype)
        majCurrentType(responseData.currenttype);
}

/**
 * Passage d'une étape à l'autre
 */
function initSwitchStep() {

    // Init changement d'étape
    $('.panel').on('show.bs.collapse', function () {

        var child = $(this).find($('.panel-collapse'))[0];

        // SI L'ETAPE N'A PAS REELEMENT CHANGE, ON NE FAIT RIEN
        // La fonction element.show sur n'importe quel child de .panel déclenche l'événement show.bs.collapse
        // On Patch pour éviter cet envoi ajax qui pose problèmes !
        if (! child.classList.contains( "in" )) {

            var nextStep = this.id;
            var formId = $('.panel .panel-collapse.in form').attr('id');

            var postData = [];
            // On n'envoit pas le formulaire de recap !!
            // Ca créé un reset des métadonnées !!
            if (formId != "form_recap" && undefined != formId) {
                postData = $("#" + formId).serializeArray();
            }
            postData.push({name: "newStep", value: nextStep});

            ajaxrequestsubmit({url: "/submit/ajaxswitchstep", data: postData, dataType: 'json',
                error:function(data){
                    $('#panel-body-recap').html('<div id="error-zone-recap" class="alert alert-danger fade in" role="alert"><p>'+ translate(data.responseText) + '</p></div>');
                }});

            $('html, body').animate({
                scrollTop: 0
            }, 0);
        }
        popoverDestroy();
    })
}

/**
 * On change la vue simplifiée/détaillée
 * @param step
 * @param status
 */
function ajaxswitchView(step, status) {

    var switchIcon = $('#icon-switch-'+step);

    //SwicthIcon n'existe pas dans le cas de la modification des métadonnées
    if (switchIcon.length > 0) {

        // Empêche le rechargement à l'intialisation
        var unchecked = (switchIcon.attr('class').indexOf("glyphicon-unchecked") != -1);

        if (status != unchecked) {

            // Mise à jour de la checkbox
            if (status) {
                switchIcon.attr('class', "glyphicon glyphicon-unchecked");
            } else {
                switchIcon.attr('class', "glyphicon glyphicon-check");
            }

            // On ne passe pas par ajaxrequest pour ne pas court-circuiter la barre de chargement de addSessionsFiles
            $.ajax({
                url: "/submit/ajaxswitchmode",
                type: "post",
                data: {step: step, status: status},
                dataType: 'json',
                statusCode: {
                    200: function (data) {
                        if ('file' == step) {
                            var panelFile = $('#panel-body-file');
                            panelFile.html(data.fileView);

                            if (status == 1) {
                                panelFile.hide();
                                Dropzone.instances = [];
                                initDropzone();
                                addSessionsFiles();
                            } else {
                                init('/submit/upload');
                            }
                        }
                    },
                    500: function(msg) {
                        if (undefined != msg) {
                            showErrorContent("#error-zone-" + step, msg.responseText);
                        }
                    }
                },
                error: function(msg) {
                    showErrorContent("#error-zone-"+step, msg.responseText);
                }
            });
        }
    }
}

/**
 *
 * @param step
 * @param status
 */
function setView(step, status) {

    ajaxswitchView(step, status);

    if (step == 'meta') {
        if (status) {
            $('#meta-footer-note').show();
        } else {
            $('#meta-footer-note').hide();
        }
        displayMeta(!status);
    }

    // On vérifie qu'on change bien de status
    var viewAuthor = $(".view-author-detailed");

    if (step == 'author' && status == viewAuthor.is(':visible')) {
        if (status) {
            viewAuthor.hide();
        } else {
            viewAuthor.show();
        }
    }
    // Mise à jour de la taille de la dropzone
    if (step == 'file' && !status) {
        dropzoneWidth = $("#demo-upload").width();
    }
}

/**
 * Initialisation du changement de vue entre simplifiée et détaillée
 * @param step
 * @param status
 */
function initSwitchView(step, status) {

    // Options du bouton de switch d'une vue à l'autre
    $("#change-view-button-"+step).click(function() {

        // Passage de la vue simplifiée à la vue détaillée
        if ($('#icon-switch-'+step).attr('class').indexOf("glyphicon-check") == -1) {
            setView(step, 0);
        //Passage de la vue détaillée à la vue simplifiée
        } else {
            setView(step, 1);

        }
    });

    setView(step, status);
}
