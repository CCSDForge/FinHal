function domainChanged ()
{
    var postData = $("#form_meta").serializeArray();
    postData.push({name: "isdomainchange", value: 1});

    ajaxrequestsubmit({url: '/submit/ajaxchangemeta', data: postData, success: function () {
        $(document.body).find('> .tooltip').remove();
        $(document.body).tooltip({ selector: '[data-toggle="tooltip"]' , html: true, container: 'body'});
    }, dataType: 'json',
        error: function(msg) {
            showErrorContent("#error-zone-meta", msg.responseText);
        }
    }, 'rechargeForm');
}