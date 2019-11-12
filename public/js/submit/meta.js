/**
 * Created by sdenoux on 08/02/17.
 */

//The magic code to add show/hide custom event triggers
(function ($) {
    $.each(['show', 'hide'], function (i, ev) {
        var el = $.fn[ev];
        $.fn[ev] = function () {
            this.trigger(ev);
            return el.apply(this, arguments);
        };
    });
})(jQuery);

function displayMeta(show)
{

    //todo ne pas cacher les meta non obligatoires avec une valeur
    var metaComplete = $('div.meta-complete');

    metaComplete.css('display', show ? 'block' : 'none');
    metaComplete.has('input[value!=""]').css('display', 'block');

    // Si le TextArea n'est pas vide, on affiche le block correspondant à la métadonnée
    metaComplete.has('textarea').each(
        function(id, elem){
            if($(elem).find('textarea').html()!="") {
                elem.style = 'display:block';
            }
        }
    );

    var section = $('#myForm .meta-section');

    section.css('display', 'block');
    if (! show) {
        section.each(function() {
            if($(this).nextUntil('.meta-section').filter(':visible').length == 0) {
                $(this).css('display', 'none');
            }
        });
    }
}

function changeDateRequired() {
    if ($("#inPress").is(":checked")) {
        $("label[for='date-id']").removeClass("required");
        $("label[for='inPress']").addClass("required");
    } else {
        $("label[for='date-id']").addClass("required");
        $("label[for='inPress']").removeClass("required");
    }
}

function changeJournalRequired(required) {

    if (required) {
        $("label[for='description']").removeClass("required");
        $("label[for='bookTitle']").removeClass("required");
        $("label[for='journal']").addClass("required");
    } else {
        if (undefined != $("#bookTitle").val() && $("#bookTitle").val().length == 0) {
            $("label[for='description']").addClass("required");
        }
        $("label[for='journal']").removeClass("required");
    }
}

function changeBookRequired() {
    if (undefined != $("#bookTitle").val() && $("#bookTitle").val().length != 0) {
        $("label[for='description']").removeClass("required");
        $("label[for='journal']").removeClass("required");
        $("label[for='bookTitle']").addClass("required");
    } else {
        if ($('#journal').css('display') != 'none') {
            $("label[for='description']").addClass("required");
        }
        $("label[for='bookTitle']").removeClass("required");
    }
}

function removeError(element) {

    // Vire le rouge
    var parent = element.parentNode;
    while (!(parent.classList.contains('form-group'))) {
        parent = parent.parentNode;
    }

    if (parent.classList.contains('has-error')) {
        $(parent).removeClass('has-error');
    }

    // Virer l'erreur
    parent = element.parentNode;
    while (!(parent.classList.contains('col-md-9'))) {
        parent = parent.parentNode;
    }
    $(parent).children().each(function(id, element) {
        if(element.classList.contains('error')) {
            element.parentNode.removeChild(element);
        }
    });
}

function sendTypeChangeAjax(postData)
{
    // Non utilisation de la fonction ajax request pour pouvoir afficher un message de warning en cas de besoin
    ajaxrequestsubmit({
        url: "/submit/ajaxchangemeta",
        type: "post",
        data: postData,
        success: function(response) {
            if (undefined != response.filerow) {
                // Reset la div fichier
                $('#files tbody').html('');

                // Ajout des fichiers
                for (var key in response.filerow) {
                    if (response.filerow.hasOwnProperty(key)) {
                        addOrChangeRow(key, response.filerow[key]);
                    }
                }
            }
        },
        error: function(data){

            try {
                var response = jQuery.parseJSON(data.responseText);
            } catch (err) { }

            showErrorContent("#error-zone-meta", response.errorMsg);

            // Reset de la valeur du type de document
            $("#form_meta :input[id='type']").val(response.type);
        }
    }, 'rechargeForm');
}

/** Init View */
function initViewMeta()
{
    //Focus sur le 1er champ d'erreur
    $('.has-error .form-control').first().focus();

    //Rechargement du formulaire au changement de type de document
    $("#form_meta :input[id='type']").change(function() {
        var postData = $("#form_meta").serializeArray();
        postData.push({name: "istypechange", value: 1});

        sendTypeChangeAjax(postData);
    });

    //Virer les erreurs quand on met à jour l'élément
    $("#form_meta :input").change(function() {
        removeError(this);
    });

    //Virer les erreurs dans le cas du journal
    $("#journal").on('hide', function() {
        removeError(this);
    });

    //Lorsque la date "a paraitre" est remplie, la date de publication n'est plus obligatoire

    var inPress = $("#inPress");
    if (0 != inPress.length) {
        // On initialise le required
        changeDateRequired();

        inPress.change(function () {
            changeDateRequired();
        });
    }

    //Pour le type "Autre Publication", on rend obligatoire soit la description, soit le nom de la revue, soit le titre de l'ouvrage
    var journal = $("#journal");
    var bookTitle = $("#bookTitle");

    if ($("#type option:selected").val() == 'OTHER' && undefined != journal && undefined != bookTitle) {

        // On initialise le required
        changeBookRequired();
        changeJournalRequired(journal.is(':disabled'));

        journal.on('hide',function () {
            changeJournalRequired(true);
        });

        journal.on('show',function () {
            changeJournalRequired(false);
        });

        bookTitle.focusout(function () {
            changeBookRequired();
        });
    }

    //Aide à la saisie
    $(document).on("keyup.autocomplete", '[attr-autocomplete]', function(){
        var elem = $(this);
        var doublon = elem.attr('attr-doublon');
        var thesaurus = elem.attr('attr-thesaurus');
        $(this).autocomplete({
            minLength: doublon ? 3 : 2,
            html: true,
            source: function(request, response){
                var data = {
                    term: request.term,
                    field: elem.attr('attr-autocomplete')
                };
                if (doublon) {
                    data['doublon'] = true;
                }
                if (thesaurus) {
                    data['thesaurus'] = true;
                }
                $.ajax({
                    url: "/submit/ajaxautocompletemeta",
                    type: "post",
                    dataType: "json",
                    data: data,
                    success: function(data) {
                        if (doublon) {
                            response($.map(data, function(item) {
                                    return {id: item.id, label: item.label}
                                })
                            );
                        } else {
                            response(data);
                        }
                    }
                });
            },
            select: function( event, ui ) {
                if (doublon) {
                    if (ui.item.id != '') {
                        window.open(ui.item.id);
                    }
                    return false;
                } else {
                    elem.val(ui.item.label);
                    if (elem.nextAll('span').find('i.glyphicon-plus')) {
                        elem.nextAll('span').find('i.glyphicon-plus').closest('button').click();
                        return false;
                    }
                }
            }
        })
    });
}

function mapCreatecanvas()
{
    var result  = '<div id="map">';
    result += '<div id="map_canvas" style="height:400px; width:100%;"></div>';
    result += '</div>';
    return result;
}

/**
 *
 * @param div
 * @param center
 * @returns {L.map}
 */
function initMap(div, center)
{
    var options = {
        editable: true,
        draggable: true,
        minZoom: 1,
        maxZoom: 20
    }

    return new L.map(document.getElementById(div), options).setView(center, 9);
}

/**
 * @param map
 * @param position
 * @param callback
 */
function addMarker(map, position, callback)
{
    map.on('dblclick', callback);
}

/**
 * @param lat
 * @param lng
 * @returns {{lat: Number, lng: Number}|*}
 */
function getPosition(lat, lng)
{
    position = {lat : parseFloat(lat), lng : parseFloat(lng)};

    if (isNaN(position.lat) || isNaN(position.lng)) {
        position.lat = 48.85341;
        position.lng = 2.3488;
    }

    return position;
}

/**
 * @param lat
 * @param lng
 * @returns {{lat: Number, lng: Number}|*}
 */
function getPositionNE(lat, lng)
{
    position = {lat : parseFloat(lat), lng : parseFloat(lng)};

    if (isNaN(position.lat) || isNaN(position.lng)) {
        position.lat = 48.74894;
        position.lng = 2.14096;
    }

    return position;
}

/**
 * @param lat
 * @param lng
 * @returns {{lat: Number, lng: Number}|*}
 */
function getPositionSW(lat, lng)
{
    position = {lat : parseFloat(lat), lng : parseFloat(lng)};

    if (isNaN(position.lat) || isNaN(position.lng)) {
        position.lat = 48.95046;
        position.lng = 2.53646;
    }

    return position;
}

function selectMapMarker()
{
    var mapDiv = $('#map');
    var latitude = $('#latitude');
    var longitude = $('#longitude');

    if (mapDiv.length == 1 && mapDiv.is(':visible')) {
        mapDiv.hide();
        latitude.closest('.form-group').find('.btn').text(translate('Afficher la carte'));
    } else if (mapDiv.length == 0) {
        latitude.before(mapCreatecanvas());
        latitude.closest('.form-group').find('.btn').text(translate('Masquer la carte'));

        var position = getPosition(latitude.val(), longitude.val());
        var map = initMap('map_canvas', position);
        L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
            attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>'
        }).addTo(map);
        map.doubleClickZoom.disable();

        var marker = null;
        marker = L.marker(position).addTo(map);

        addMarker(map, position, function (event) {
            latitude.val(event.latlng.lat);
            longitude.val(event.latlng.lng);

            longitude.trigger("change");
            latitude.trigger("change");

             if (typeof marker !== 'undefined') {
                 map.removeLayer(marker);
             }
            marker = L.marker([event.latlng.lat, event.latlng.lng]).addTo(map);
        });

        var legend = L.control({position: 'bottomright'});

        legend.onAdd = function (map) {

            var div = L.DomUtil.create('div', 'info legend');

                div.innerHTML =
                    '<span>'+ translate('Double-cliquez pour changer la position du repère') + '</span> ';

            return div;
        };

        legend.addTo(map);

        mapDiv.show();
    } else {
        mapDiv.show();
        latitude.closest('.form-group').find('.btn').text(translate('Masquer la carte'));
    }
}

function selectMapSquare()
{
    var mapDiv = $('#map');
    var latNE = $('#latitudeNE');
    var lonNE = $('#longitudeNE');
    var latSW = $('#latitudeSW');
    var lonSW = $('#longitudeSW');

    if (mapDiv.length == 1 && mapDiv.is(':visible')) {
        mapDiv.hide();
        latSW.closest('.form-group').find('.btn').text(translate('Afficher la carte'));
    } else if (mapDiv.length == 0) {

        latSW.before(mapCreatecanvas());
        latSW.closest('.form-group').find('.btn').text(translate('Masquer la carte'));

        var positionNE = getPositionNE(latNE.val(), lonNE.val());
        var positionSW = getPositionSW(latSW.val(), lonSW.val());
        var map = initMap('map_canvas', positionNE);
        L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
            attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>'
        }).addTo(map);
        map.doubleClickZoom.disable();

        L.EditControl = L.Control.extend({

            options: {
                position: 'topleft',
                callback: null,
                kind: '',
                html: ''
            },

            onAdd: function(map) {
                var container = L.DomUtil.create('div', 'leaflet-control leaflet-bar'),
                    link = L.DomUtil.create('a', '', container);

                link.href = '#';
                link.title = translate('Créer une nouvelle zone');
                link.innerHTML = this.options.html;
                L.DomEvent.on(link, 'click', L.DomEvent.stop)
                    .on(link, 'click', function() {
                        if (typeof rectangle !== 'undefined'){
                            map.removeLayer(rectangle);
                        }
                        rectangle = this.options.callback.call(map.editTools);
                    }, this);

                return container;
            }

        });

        L.NewRectangleControl = L.EditControl.extend({

            options: {
                position: 'topleft',
                callback: map.editTools.startRectangle,
                kind: 'rectangle',
                html: '⬛'
            }

        });

        map.addControl(new L.NewRectangleControl());

        var bounds = [[positionNE.lat, positionNE.lng], [positionSW.lat, positionSW.lng]];

        var rectangle = L.rectangle(bounds).addTo(map);
        rectangle.enableEdit();

        map.on('editable:editing', function() {
            var ne = rectangle.getBounds().getNorthEast();
            var sw = rectangle.getBounds().getSouthWest();

            latNE.val(ne.lat);
            lonNE.val(ne.lng);
            latSW.val(sw.lat);
            lonSW.val(sw.lng);

            latNE.trigger("change");
            lonNE.trigger("change");
            latSW.trigger("change");
            lonSW.trigger("change");
        });

        var legend = L.control({position: 'bottomright'});

        legend.onAdd = function (map) {

            var div = L.DomUtil.create('div', 'info legend');

            div.innerHTML =
                '<span>'+ translate('Cliquez sur le ⬛ pour créer une nouvelle zone') + '</span> ';

            return div;
        };

        legend.addTo(map);

    } else {
        mapDiv.show();
    }
}

function requestChangeMeta()
{
    ajaxrequestsubmit({url: "/submit/ajaxchangemeta", data: $('#form_meta').serializeArray(), success: function(data) {
        // Fermeture de l'étape et remonte en haut de page
        if (data.validity.meta) {
            $('#collapse2').collapse('hide');
        }

        $('html, body').animate({
            scrollTop: 0
        }, 0);
    }, dataType: 'json',
        error: function(msg) {
            showErrorContent("#error-zone-meta", msg.responseText);
        }
    });
}