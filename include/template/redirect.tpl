<!DOCTYPE html>

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<!--head_elements-->
</head>

<body>

	<!--flash_messages-->

	<div id="brd-wrap" class="brd">
	
		<div <!--page_id-->>
			
			<div id="brd-main">
				<!--top_menu-->
				<!--top_submenu-->
				<!--announcement-->
				<!--page_content-->
				<!-- forum_main -->
			</div>
			
			<div id="brd-about">
				<!--footer_about-->
			</div>
		
			<!--footer_debug-->
			
		</div>
	
		<!--side_menu-->

	</div>
	
	<!--footer_javascript-->
	
	<script>
	$('.menu-btn').click(function(){
		$(this).toggleClass("clicked");
		$('.menu-bar').toggleClass("opened");
	});
	$('nav ul li').click(function(){
		$(this).toggleClass("active").siblings().removeClass("active");
	});
	</script>

</body>

</html>