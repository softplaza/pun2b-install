<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="viewport" content="width=device-width, user-scalable=no">
		<!--head_elements-->
	</head>
	<body>

		<!--flash_messages-->

		<div class="head-section">
			<!--main_top_menu-->
		</div>

		<div class="page-content">
			<div class="msg-section pt-2">
				<!--announcement-->
				
			</div>

			<div class="content-section">
				<!--system_messages-->
				<!--paginate_top-->
				<!--page_content-->
				<!--paginate_bottom-->
			</div>
		</div>
		
		<div class="footer-section">
			<!--footer_about-->
			<!--footer_debug-->
		</div>

		<!--sidebar_menu-->
		
		<!--footer_javascript-->
		
<script>
let arrow = document.querySelectorAll(".arrow");
for (var i = 0; i < arrow.length; i++) {
	arrow[i].addEventListener("click", (e)=>{
		let arrowParent = e.target.parentElement.parentElement; //selecting main parent of arrow
		arrowParent.classList.toggle("show");
	});
}

let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".fa-bars");
sidebarBtn.addEventListener("click", ()=>{
	if (sidebar.classList.contains("close")){
		sidebar.classList.remove("close");
		sidebar.classList.add("opened");
	}else{
		sidebar.classList.remove("opened");
		sidebar.classList.add("close");
	}
});

document.addEventListener('click', function(event) {
	var isClickInsideElement = sidebar.contains(event.target);
	if (!isClickInsideElement) {
		//Do something click is outside specified element
		if (sidebar.classList.contains("opened")){
			sidebar.classList.remove("opened");
			sidebar.classList.add("close");
		}
	}
});
</script>

	</body>
</html>