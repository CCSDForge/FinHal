/**
 *
 */
//$(function() {
//	function highlightText(text, $node) {
//		var searchText = $.trim(text).toLowerCase(), currentNode = $node.get(0).firstChild, matchIndex, newTextNode, newSpanNode;
//		while ((matchIndex = currentNode.data.toLowerCase().indexOf(searchText)) >= 0) {
//			newTextNode = currentNode.splitText(matchIndex);
//			currentNode = newTextNode.splitText(searchText.length);
//			newSpanNode = document.createElement("span");
//			newSpanNode.className = "highlight";
//			currentNode.parentNode.insertBefore(newSpanNode, currentNode);
//			newSpanNode.appendChild(newTextNode);
//		}
//	}
//
//	var src = $('#q').data('src');
//
//	$("#q").autocomplete({
//		delay: 500,
//		minLength: 2,
//		source : "/search/autocomplete/?src=" + src
//	}).data("ui-autocomplete")._renderItem = function(ul, item) {
//		var $a = $("<a></a>").text(item.label);
//		highlightText(this.term, $a);
//		return $("<li></li>").append($a).appendTo(ul);
//	};
//});

$(function () {

    var src = $('#q').data('src');

    $("#q").autocomplete({
        minLength: 1,
        source: "/search/ajaxautocomplete/?src=" + src,

        select: function (event, ui) {
            $("#q").val(ui.item.label);

            return false;
        }
    }).data("ui-autocomplete")._renderItem = function (ul, item) {
        return $("<li>").append(
                "<a>" + item.label + ' <span class="muted pull-right">('
                    + item.count + " - " + item.category + ")</span></a>")
            .appendTo(ul);
    };
});
