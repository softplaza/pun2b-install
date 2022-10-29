// Autoresize of textarea
$("textarea").each(function () {
	if (this.scrollHeight > 0)
		this.setAttribute("style", "height:" + (this.scrollHeight) + "px;overflow-y:hidden;");
}).on("input", function () {
	this.style.height = "auto";
	this.style.height = (this.scrollHeight) + "px";
});

function closePopUpWindows(){
	$(".pop-up-window").css("display","none");
	$(".pop-up-window .fields").empty().html('');
}

// HighLight Row or Cell
// Use Class class="hl-cell" class for Cell or class="hl-row" class for row
$("td").click(function() {
	//var selected = $(this).hasClass("hl-td");
	$("td").removeClass("hl-td");
	$(this).addClass("hl-td");
});

/* Show/Hide Actions Drop Down Menu */
function btnPageAction(){
	$(".page-actions .action-menu").slideToggle("1000");
}

function btnProjectAction(id){
	$("#project_action_"+id+" .action-menu").slideToggle("1000");
}
/* Dropdown list actions by ID */
function dropDownListActions(id){
	$("#dropdown_menu_"+id).slideToggle("1000");
}
/* Show or Hide field entered value. Used class "fa-eye-slash" */
function showHideFieldValue(id)
{
    if ($("#fa_eye_"+id).hasClass('fa-eye-slash'))
	{
		$("#"+id).attr("type", "text");
		$("#fa_eye_"+id).removeClass("fa-eye-slash");
		$("#fa_eye_"+id).addClass("fa-eye");
    } else {
		$("#"+id).attr("type", "password");
		$("#fa_eye_"+id).removeClass("fa-eye");
		$("#fa_eye_"+id).addClass("fa-eye-slash");
    }
}

$(document).ready(function(){
	/* Use .anchor class for focus active row in top */
	if ($('.anchor').length > 0)
		$('html, body').animate({scrollTop: $('.anchor').offset().top - 150}, 500);
	
	/* Hide flash message after 4 seconds */
	$('#brd-messages .message_info').fadeTo(4000, 0);
	setTimeout(function() { $('#brd-messages .message_info').hide('slow'); }, 4000);
});

/* */
window.onload = function(){
	$(document).mouseup(function(e) 
	{
		if (!$(".action-menu").is(e.target) && $(".action-menu").has(e.target).length === 0) {	
			$(".action-menu").css("display","none");
		}
		if (!$(".list-actions").is(e.target) && $(".list-actions").has(e.target).length === 0) {	
			$(".list-actions").css("display","none");
		}
	});
}
