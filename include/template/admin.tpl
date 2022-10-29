<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	
	<!--head_elements-->
	<!--css_elements-->
	<!--js_elements-->
</head>
<body>
	<!--flash_messages-->
	
	<div class="wrapper">
		<!--sidebar-->
		
		<div class="main">
			<!--navbar-->
			
			<main class="content">
				<!--content-->
			</main>
			
			<!--footer-->
			
		</div>
	</div>
	
	<!--avascript-->
	<!--debug-->

<script>
function dropDownNavMenu(id){
	$("#dropdown_nav_menu_"+id).slideToggle("1000");
}
$('.menu-btn').click(function(){
	$(this).toggleClass("clicked");
	$('.menu-bar').toggleClass("opened");
});
$('nav ul li').click(function(){
	$(this).toggleClass("active").siblings().removeClass("active");
});

$(document).mouseup(function(e) 
{
	if (!$(".main-dropdown-list").is(e.target) && $(".main-dropdown-list").has(e.target).length === 0) {	
		$(".main-dropdown-list").css("display", "none");
	}
	if (!$(".sub-dropdown-list").is(e.target) && $(".sub-dropdown-list").has(e.target).length === 0) {	
		$(".sub-dropdown-list").css("display", "none");
	}
});
</script>

</body>
</html>