$(document).ready(function() {
	$('a[data-toggle="tooltip"]').tooltip();

	// checkbox redirect
	$("input[type='checkbox']").change(function() {
		var item = $(this);
		if (item.data("target")) {
			window.location.href = item.data("target");
		}
	});
});
