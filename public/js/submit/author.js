/**
 * Created by sdenoux on 27/02/17.
 */

/**
 * @param ev
 */
function allowDrop(ev) {
    ev.preventDefault();
}

/**
 * @param ev
 * @param id
 */
function drag(ev, id) {
    // On transmet l'identifiant de la structure que l'on veut mettre à un autre auteur
    ev.dataTransfer.setData("idxstruct", id);
}

/**
 * @param ev
 * @param element
 */
function drop(ev, element) {
    ev.preventDefault();
    var idxstruct = ev.dataTransfer.getData("idxstruct");

    // On enlève le 'aut_' de l'identifiant de l'auteur sur lequel on drop une nouvelle structure
    var id = $(element).attr('id').substring(4);

    // On envoie la requête que dans le cas où l'auteur n'est pas déjà affilié à cette structure
    if ($('#aut_'+id+'_struct_'+idxstruct).length == 0) {
        var callback = function(data) { $('#aut-struct').html(data.autstruct); };
        ajaxrequestsubmit({url: '/submit/ajaxaddstructure', data: {authid: id, structureidx: idxstruct}, success: callback, dataType: 'json',
            error: function(msg) {
                showErrorContent("#error-zone-author", msg.responseText);
            }
        }, 'copyStructure');
    }
}
