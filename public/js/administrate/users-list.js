function submitForm(uid, method) {
	$('#uid').val(uid);
	$('#method').val(method);
	$('#form-users').submit();
}
/**
 * Active un compte
 * 
 * @param elem
 * @param uid
 */
function validateAccount(elem, uid) {
	var ajax = $.ajax({
		url : "/administrate/ajaxvalidateuser",
		type : "post",
		data : {
			uid : uid
		},
		success : function(data) {
			$(elem).remove();
			message(translate('Compte activé'), 'alert-success');
		}
	});
}
/**
 * Désactive un compte
 * 
 * @param elem
 * @param uid
 */
function terminateAccount(elem, uid) {
	var ajax = $.ajax({
		url : "/administrate/ajax-terminate-user",
		type : "post",
		dataType : 'json',
		data : {
			uid : uid
		},
		success : function(response) {

			if (response['error']) {
				message(translate(response['error']), 'alert-danger');
			} else {
				message(translate(response['success']), 'alert-success');
				$(elem).remove();
			}
		}
	});
}
