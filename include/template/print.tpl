<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<!--head_elements-->
	</head>
	<body>

		<div class="head-section">
			<!--main_top_menu-->
		</div>

		<div class="page-content">
			<!--subhead_title-->
			<!--page_content-->
			<!-- forum_main -->
		</div>
	
		<!--footer_javascript-->

<script>
let arrow = document.querySelectorAll(".arrow");
for (var i = 0; i < arrow.length; i++) {
	arrow[i].addEventListener("click", (e)=>{
		//selecting main parent of arrow
		let arrowParent = e.target.parentElement.parentElement;
		arrowParent.classList.toggle("show");
	});
}

let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".fa-bars");
sidebarBtn.addEventListener("click", ()=>{
	sidebar.classList.toggle("close");
});

document.addEventListener('click', function(event) {
    var isClickInsideElement = sidebar.contains(event.target);
    if (!isClickInsideElement) {
        //Do something click is outside specified element
		sidebar.classList.add("close");
    }
});
</script>

	</body>
</html>
