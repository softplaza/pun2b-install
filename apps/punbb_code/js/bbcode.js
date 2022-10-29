function insertBBcode(s,e){
	var editor = $("#fld_message");
	var str = $(editor).val();
	var selection = getInputSelection($(editor));
	if (selection.length > 0) {
		$(editor).val(str.replace(selection, "[" + s + "]" + selection + "[/" + e + "]"));
	} else {
		$(editor).val(str + "[" + s + "]" + "[/" + e + "]");
	}
}
function getInputSelection(el) {
    if (typeof el != "undefined") {
        s = el[0].selectionStart;
        e = el[0].selectionEnd;
        return el.val().substring(s, e);
    } else {
        return '';
    }
}