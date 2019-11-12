/**
 * Created by sdenoux on 10/02/17.
 */

/**
 * Test la possibilité de
 */
function testarXiv()
{
    if ($("#arxiv").prop('checked')) {
        $.ajax({
            url: "/submit/ajaxtestarxiv",
            method: "post",
            error: function(msg) {
                $("#arxiv").prop("checked", false);
                showErrorContent("#error-zone-arxiv", msg.responseText);
            }
        });
    }
}

function submitRecap()
{
    if (document.getElementById("form_recap").checkValidity()) {
        ajaxrequestsubmit({url: "/submit/ajaxchangemeta", data: $("#form_meta").serializeArray(), success: function (data) {
            if (data.validity.meta) {
                document.getElementById("form_recap").submit();
            }
        }, dataType: 'json',
            error: function (error) {

                try {
                    // Test pour différencier les 2 cas... oui c'est un peu moche :s
                    var response = jQuery.parseJSON(error.responseText);

                    // Erreur : le dépôt n'est pas valide
                    $("#error-zone-recap").show();
                    $('html, body').animate({
                        scrollTop: 0
                    }, 0);
                } catch (ex) {
                    // Erreur bloquante du serveur
                    $('#panel-body-recap').html('<div id="error-zone-recap" class="alert alert-danger fade in" role="alert"><p>'+ translate(error.responseText) + '</p></div>');
                }
            }
        }, 'saveSubmission');
    }
    // On empêche l'envoi automatique du formulaire pour le faire seulement dans nos conditions
    return false;
}
