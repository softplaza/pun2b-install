function showWeekend(){
	$(".th-weekend, .td-weekend").toggle();
	$('.weekend-toggle input[type="button"]').val($('.weekend-toggle input[type="button"]').val() == "Show Weekend" ? "Hide Weekend" : "Show Weekend");
}
