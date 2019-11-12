/**
 * Created by sdenoux on 27/01/17.
 */

/********** COMMUN 2 VUES ************/

/**
 * On ajoute les fichiers en sessions dans la dropzone
 */
function addSessionsFiles() {

    var myDropzone = Dropzone.instances[0];

    // Ajout des documents présents en session
    ajaxrequestsubmit({url: "/submit/ajaxgetsessionfiles", success: function(files) {

        for (var i = 0; i < files.length; i++) {
            // On créee un mock file pour imiter le chargement par drop
            addMockFileToDropzone(myDropzone, files[i]["fileid"], files[i]["name"], 0, files[i]["typeMIME"], files[i]["thumb"], files[i]["default"], files[i]["notDeletable"]);
        }
        $('#panel-body-file').show();
    }, dataType: 'json',
        error: function (msg) {
            showErrorContent("#error-zone-file", msg.responseText);
        }
    }, 'reloadFiles');
}

$(function() {
    // Initialisation du changement de type
    $(document).on("click", ".idext-elem", function() {
        $(this).closest('div').find('button').html(this.textContent+"<span class=\"caret\" style=\"margin-left:10px;\"></span>");
        $(this).closest('ul').attr("value", $(this).attr("value"));

        $(this).closest('div').find('input[type=text]').attr("placeholder", $(this).attr('default'));
    });
})


/********** VUE SIMPLIFIEE ************/

/**
 * @param element
 * @param {boolean} mouseover
 */
function toggleFilename(element, mouseover)
{
    var myDropzone = Dropzone.instances[0];
    var nbfiles = myDropzone.files.length;

    if (mouseover) {
        $(element).text(element.name);

        if (nbfiles <= 2) {
            $(element).attr("style", "");
        }
    } else {
        $(element).text(getShortenedTitle(element.name, nbfiles));
        $(element).attr("style", "float:left;");
    }
}

/**
 * @param {string} name
 * @returns {string|string}
 */
function getFileExtension(name)
{
    return name.substring(name.lastIndexOf('.'), name.length) || '';
}

/**
 * @param {string} name
 * @param {int} nbChars
 * @returns {string}
 */
function getShortenedTitle(name, nbfiles)
{
    var nbChars = 7;

    if (nbfiles == 1) {
        nbChars = 30;
    } else if (nbfiles == 2 || nbfiles == 3) {
        nbChars = 12;
    }

    // Le +7 correspond au ...+extension
    if (name.length <= nbChars+7) {
        return name;
    } else {
        return name.substring(0, nbChars - 1) + '...' + getFileExtension(name);
    }
}

/**
 * On met à jour visuellement le fichier principal :
 * On choisit un nouveau fichier principal
 * ou simplement on déselectionne le fichier principal
 * @param element
 */
function majMainVisuel(element) {

    var img = element.getElementsByClassName("dz-image")[0];

    var add = true;
    var mainfiles = document.getElementsByClassName("dz-mainfile");

    for (var i = 0; i < mainfiles.length; i++) {

        if (img == mainfiles[i])
            add = false;

        var detail = mainfiles[i].parentNode.getElementsByClassName("dz-details")[0];

        detail.removeAttribute("data-toggle");
        detail.removeAttribute("data-original-title");

        var icone = mainfiles[i].parentNode.getElementsByClassName("mainRadio");

        if (icone.length > 0) {
            icone = icone[0];
            $(icone).prop("checked", false);
            icone.setAttribute("data-original-title", translate("Définir comme fichier principal"));
        }

        mainfiles[i].classList.remove("dz-mainfile");
    }


    if (add) {
        element.getElementsByClassName("dz-details")[0].setAttribute("data-toggle", "tooltip");
        element.getElementsByClassName("dz-details")[0].setAttribute("data-original-title", translate("Fichier principal du dépôt"));


        var icone2 = element.getElementsByClassName("mainRadio");

        if (icone2.length > 0) {
            icone2 = icone2[0];
            $(icone2).prop("checked", true);
            icone2.setAttribute("data-original-title", translate("Desélectionner le fichier principal"));
        }

        img.classList.add("dz-mainfile");
    }
}

/**
 * On met à jour la taille des preview de fichier selon le nombre de fichiers
 */
function updatePreviewSize() {

    var myDropzone = Dropzone.instances[0];

    if (dropzoneWidth == 0 || dropzoneWidth == null) {
        // Le 0.42 sort pas tout à fait du chapeau.
        // On prend moins de la moitié de la colonne
        dropzoneWidth = $("#collapse1").width()*0.42;
    }

    // Le maximum de fichier sur une ligne : taille de la zone divisée par la taille min d'un élément
    var maxfilesonline = Math.floor(dropzoneWidth/parseInt($(".dz-preview ").css('min-width')));
    var nbfiles = myDropzone.files.length > maxfilesonline ? maxfilesonline : myDropzone.files.length;
    var previewwidth = 70/nbfiles;



    var dzpreview = $('.dz-preview');
    dzpreview.css('width', previewwidth + '%');
    dzpreview.css('height', dropzoneWidth*previewwidth/100);

    // On ajuste le nombre de caractère  qui s'affiche pour le nom d'un fichier
    // De cette manière l'extension apparait
    $('.data-dz-name').each(
        function(i, element){

            var children = $(element).children();
            if (children.length > 0) {
                var elementA = children[0];

                var shortName = getShortenedTitle(elementA.name, myDropzone.files.length);

                $(elementA).text(shortName);

            }
        }
    );
}

/**
 * Mise à jour des types de document grisés dans le select-typdoc
 * @param {string} id - identifiant du select où il faut griser des options
 * @param {Array} typdocs - options à griser
 */
function majTypdocs(id, typdocs) {

    // On remet d'ablomp toutes les options
    $("#"+id+" option").each(function(i, opt) {
        opt.removeAttribute("disabled");
    });

    // On grise les types des documents filtrés
    $.each(typdocs, function(j, val) {
        $("#"+id+" option[value="+ val +"]").attr("disabled", "disabled");
    });
}

function majTypdocsDropdown(id, typdocs)
{
    // On remet d'ablomp toutes les options
    $("#"+id+" li").each(function(i, opt) {
        opt.classList.remove("disabled-option");
    });

    $.each(typdocs, function(j, val) {
        $("#"+id+" li[value="+ val +"]").addClass("disabled-option");
    });

    $(".disabled-option").click(function(event) {
        event.preventDefault();
    });
}

function majCurrentType(currenttype) {
    var typetrad = $("#typdoc-menu li[value="+ currenttype +"]").text();
    $('#typdoc-select').html(typetrad + "<span class=\"caret\" style=\"margin-left:10px;\"></span>");
}

/**
 * On récupère les métadonnées liées à un identifiant externe pour les ajouter au dépot
 */
function addIdExt(selectorId, selectorType) {
    var id = $(selectorId).val();
    var type = $(selectorType).attr("value");

    // On passe par une requête directe pour pouvoir afficher le message de réussite/echec de récupération des métadonnées
    var params = {
        url: "/submit/ajaxaddidext",
        type: "post",
        data: {idext: id, idtype: type},
        success: function(data){

            // Ajout du message de réussite
            $('#modal-success').html(data.sucessMsg);
            hideLoading('modal-loading');
            showLoading('modal-success');

            var idUrl = $("#idurl");
            idUrl.prop('href', data.url);
            idUrl.text(type + ' : ' + id);

            if (data.url != '') {
                $("#idurl").parent().show();
            } else {
                $("#idurl").parent().hide();
            }
        },
        complete: function(data) {
            if (undefined != data.responseText && "" != data.responseText) {
                try {
                    majView(JSON.parse(data.responseText));
                } catch (err) { }
            }
        },
        error: function(msg) {
            // Pourquoi est-ce que c'est stocké dans responseText ??
            showErrorContent("#error-zone-file", msg.responseText);
            hideLoading('modal-loading');
        },
        dataType: 'json',
        beforeSend: function(){
            resetErrors();

            showLoading('modal-loading', 'loadMeta');
        }
    };

    $.ajax(params);
}

/**
 * On créé un fichier simulé pour l'ajouter à la dropzone
 * @param myDropzone
 * @param idx
 * @param name
 * @param size
 * @param type
 * @param imagette
 * @param defaut
 * @param notDeletable
 */
function addMockFileToDropzone(myDropzone, idx, name, size, type, imagette, defaut, notDeletable) {

    var mockFile = { idx: idx, name: name, size: size,  type: type, notdeletable: notDeletable};
    myDropzone.files.push(mockFile);

    myDropzone.emit("addedfile", mockFile);
    myDropzone.emit("complete", mockFile);
    myDropzone.emit("success", mockFile, JSON.stringify({
        name: name,
        main: defaut
    }));
    myDropzone.emit("thumbnail", mockFile, imagette);
}

/**
 * On met à jour le type de document
 * @deprecated
 * @param type
 */
function setTypdoc(elem) {

    if (false === elem.classList.contains("disabled-option")) {

        $('#typdoc-select').html(elem.textContent + "<span class=\"caret\" style=\"margin-left:10px;\"></span>");

        ajaxrequestsubmit({url: "/submit/ajaxchangetype", data: {type: elem.getAttribute("value")},
            error: function(msg) {
                showErrorContent("#error-zone-file", msg.responseText);
            }
        });
    }
}

/**
 * Ajout de la possibilité de choisir le fichier comme fichier principal du dépot
 * @param file
 * @param container
 */
function addMainableToFile(file, container)
{
    var mainfileButton = Dropzone.createElement("<input class=\"mainRadio\" data-toggle=\"tooltip\" data-original-title=\"Définir comme fichier principal\" type=\"radio\"></input>");
    container.insertBefore(mainfileButton, container.firstChild);

    // Click on "select as main file" button
    mainfileButton.addEventListener("click", function (e) {
        
        // Evite l'ouverture du fichier lorsqu'on souhaite selectionner comme fichier principal
        e.stopPropagation();
        
        // Pendant la recherche des metadonnées, on affiche un menu
        var previewElement = file.previewElement;

        // Selection comme fichier principal au sens de Hal
        ajaxMainFile(file.name, previewElement);
    });
}

/**
 * Todo : factoriser la fonction processing
 * On initialise la dropzone et ses actions
 */
function initDropzone() {

    // Evite la définition automatique d'une instance de Dropzone qui engendre une erreur JS
    Dropzone.autoDiscover = false;

    // maxUploadSize est une variable globale défini dans submit/index.phtml
    var myDropzone = new Dropzone("#demo-upload", { url: "/submit/upload", parallelUploads: 20, maxFilesize: maxUploadSize});

    myDropzone.element.className = "dropzone needsclick dz-clickable";

    // Ajout de fonctionnalités sur l'evenement Success
    // => MAJ de la taille des preview de fichier + MAJ du fichier principal

    myDropzone.on("processing", function(_this) {

        // On rend visible les mentions légales pour l'ajout de fichier
        // Seulement si la case 'Ne plus afficher' n'a pas été cochée
        if (!$('#seelegal-check').is(":checked")) {
            $('#legal-text').show();
        }

        showLoading('modal-loading', 'loadDoc');
        $("#error-zone-file").hide();
        _this.previewElement.classList.add("processing");
    });

    myDropzone.on("success", function(_this, responseText) {

        _this.previewElement.classList.remove("processing");
        responseText = jQuery.parseJSON(responseText);

        // MAJ du message de succès du chargement
        // On le remplace pour que ce soit toujours le dernier visible
        $('#modal-success').html(responseText.sucessMsg);

        // Si le modal avec les droits est toujours ouvert, on bloque la fermeture du modal
        if ($('#legal-button:visible').length == 1) {
            var legalButton = $('#legal-button');
            if (undefined != responseText.sucessMsg) {
                legalButton.attr('onclick', legalButton.attr("onclick") + "hideLoading('modal-loading');showLoading('modal-success');");
            } else {
                legalButton.attr('onclick', legalButton.attr("onclick") + "hideLoading('modal-loading');");
            }
            
        } else {
            hideLoading('modal-loading');

            // On affiche le success message seulement s'il y en a 1 !
            if (undefined != responseText.sucessMsg) {
                showLoading('modal-success');
            }
        }

        $("#demo-upload").removeClass("uploading");

        if (responseText) {
            if (responseText.converted) {

                for (var i=0;i<responseText.converted.length;i++) {
                    addMockFileToDropzone(myDropzone, responseText.converted[i].convertedIdx, responseText.converted[i].convertedFile, 0, "", responseText.converted[i].convertedThumb, responseText.converted[i].convertedMain, false);
                }
            }

            if (responseText.main) {
                responseText.preview = _this.previewElement;
            }

            // Ajout d'un lien sur le nom du fichier (cas upload)
            // Nécessaire car la library met à jour le contenu de data-dz-name entre l'événement "Added" et l'événement "success"
            if (typeof responseText.idx != "undefined") {

                var dataname = _this.previewElement.getElementsByClassName("data-dz-name")[0];
                var filename = dataname.textContent;
                // Click sur l'imagette
                $(responseText.preview).click(function(){
                    var win = window.open('/file/tmp/fid/'+ responseText.idx, '_blank');
                    win.focus();
                });
                // Click sur le nom du fichier
                dataname.innerHTML = "<a href=\"/file/tmp/fid/" + responseText.idx + "\" target=\"_blank\" class=\"filename-link\" name=\""+filename+"\" onmouseover=\"toggleFilename(this, true);\" onmouseout=\"toggleFilename(this, false);\">" + filename + "</a>";

                // Déplacement du span data-dz-name dans le span qui englobe pour centrer
                $(dataname).appendTo($(dataname).parent().children()[0]);
            }

            // Add Thumb
            if (undefined != responseText.thumb) {
                myDropzone.emit("thumbnail", _this, responseText.thumb);
            }

            majView(responseText);

            if (undefined != responseText.err && responseText.err.length > 0) {
                // Il peut y avoir une erreur sur l'étape même si l'on charge le fichier (par exemple on met un fichier en type BREVET
                showErrorContent("#error-zone-file", responseText.err);
            }
        }

        updatePreviewSize();

    });
    myDropzone.on("error", function(_this, responseText){

        hideLoading('modal-loading');

        showErrorContent("#error-zone-file", responseText);

        $("#demo-upload").removeClass("uploading");

        // Remove the file preview.
        myDropzone.removeFile(_this);
    });

    // Ajout de fonctionnalités sur l'evenement Drop
    // => MAJ visuelle le temps du chargement du fichier et de ses metadonnées
    myDropzone.on("drop", function() {
        $("#demo-upload").addClass("uploading");
    });

    // Ajout de fonctionnalités sur l'evenement AddedFile
    // => Ajout des icones de suppression et selection du fichier principal
    myDropzone.on("addedfile", function(file) {

        // Capture the Dropzone instance as closure.
        var _this = this;

        // Add the buttons to the file preview element.
        var parent = file.previewElement.getElementsByClassName("dz-filename")[0];

        // On crée un span qui englobe les icones et titre du fichier pour pouvoir les centrer en dessous de l'imagette.
        var container = Dropzone.createElement("<span style=\"display:inline-block; text-align:center;\"></span>");
        parent.insertBefore(container, parent.firstChild);

        // Ajout d'un lien sur le nom du fichier (cas ajax session)
        if (undefined != file.idx) {
            var dataname = parent.getElementsByClassName("data-dz-name")[0];
            var filename = dataname.textContent;
            // Click sur l'imagette
            $(file.previewElement).click(function(){
                var win = window.open('/file/tmp/fid/'+ file.idx, '_blank');
                win.focus();
            });
            // Click sur le nom du fichier
            dataname.innerHTML = "<a href=\"/file/tmp/fid/" + file.idx + "\" target=\"_blank\" onclick=\"event.stopPropagation();\" class=\"filename-link\" name=\""+filename+"\"  onmouseover=\"toggleFilename(this, true);\" onmouseout=\"toggleFilename(this, false);\">" + filename + "</a>";

            // Déplacement du span data-dz-name dans le span qui englobe pour centrer
            $(dataname).appendTo(container);
        }

        // Dans le cas de l'ajout d'un nouveau fichier, les fichiers précédemment déposés ne sont pas modifiables
        if (!file.notdeletable) {

            // Create the remove button and "select as mainfile" button
            var removeButton = Dropzone.createElement("<span class=\"glyphicon glyphicon-trash\" style=\"margin-right:10px;float:left;\"></span>");

            container.insertBefore(removeButton, container.firstChild);

            // Click on remove button
            removeButton.addEventListener("click", function (e) {

                // Evite l'ouverture du fichier lorsqu'on souhaite supprimer le fichier
                e.stopPropagation();
                // Remove the file preview.
                _this.removeFile(file);

                // Delete from Hal Session
                ajaxrequestsubmit({url: "/submit/ajaxdeletefile", data: {file: file.name, method: "del"}, success: function (ret) {

                    updatePreviewSize();
                    majTypdocsDropdown("typdoc-menu", []);
                    majTypdocs("type", []);

                    // Mise à jour du fichier principal lorsque l'on supprime le fichier principal actuel
                    if (ret.wasDefault && myDropzone.files.length > 0) {
                        ajaxMainFile(myDropzone.files[0].name, myDropzone.files[0].previewElement);
                    }
                }, dataType: 'json',
                    error: function(msg) {
                        showErrorContent("#error-zone-file", msg.responseText);
                    }
                });
            });
        }

        // Dans le cas de l'ajout d'un nouveau fichier, les fichiers précédemment déposés ne sont pas modifiables
        var mainables = JSON.parse(mainableExtensions);
        var re = /(?:\.([^.]+))?$/;
        var ext = re.exec(file.name)[1];

        ext = ext.toLowerCase();

        if (mainables.indexOf(ext) != -1) {
            addMainableToFile(file, container);
        }
    });
}


function ajaxMainFile(name, preview) {
    ajaxrequestsubmit({url: "/submit/ajaxselectmainfile",
        data: {file: name},
        success: function (data) {
            data.preview = preview;
            majView(data);

            hideLoading('modal-loading');

            // On affiche le success message seulement s'il y en a 1 !
            if (undefined != data.sucessMsg) {
                $('#modal-success').html(data.sucessMsg);
                showLoading('modal-success');
            }

        }, dataType: 'json',
        error: function (msg) {
            //On modifie la valeur du radio bouton car sinon il reste en place malgré l'erreur
            var radioButton = $(".mainRadio", preview).first();
            radioButton.prop("checked", !radioButton.prop("checked"));

           showErrorContent("#error-zone-file", msg.responseText);
        }}, 'switchMain');
}

function changeSeeLegal()
{
    if ($('#seelegal-check:checked').length) {
        ajaxrequestsubmit({url: "/submit/ajaxchangeuserlegal"});
    }
}

/********** VUE DETAILLEE ************/

