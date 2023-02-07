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

		<div class="page-content px-1">
			<div class="msg-section">
				<!--announcement-->
			</div>

			<div class="content-section">
				<!--system_messages-->
				<!--paginate_top-->
				<!--page_content-->
				<!--paginate_bottom-->
			</div>
		</div>
		
		<div class="toast-container" id="toast_container"></div>

		<div class="footer-section bg-light mt-3">
			<!--footer_about-->
			<!--footer_debug-->
		</div>

		<!--sidebar_menu-->
		
		<!--footer_javascript-->

<script>
// Open/close main menu items by click on ".icon-link"
let arrow = document.querySelectorAll(".icon-link");
for (var i = 0; i < arrow.length; i++)
{
	arrow[i].addEventListener("click", (e)=>{

		// Accordion. Close others by click on current
		let show = document.querySelectorAll(".show");
		show.forEach(s => {
				s.classList.remove("show");
		});

		//selecting main parent of arrow
		let arrowParent = e.target.parentElement.parentElement;
		arrowParent.classList.toggle("show");
	});
}

// Open/close Sidebar
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

// Close Sidebar by click outside
document.addEventListener('click', function(event) {
	var isClickInsideElement = sidebar.contains(event.target);
	if (!isClickInsideElement)
	{
		//Do something click is outside specified element
		if (sidebar.classList.contains("opened")){
			sidebar.classList.remove("opened");
			sidebar.classList.add("close");
		}
	}
});

// Open/close sub-menu by click on sub-arrow
let subArrow = document.querySelectorAll(".sub-arrow");
for (var i = 0; i < subArrow.length; i++)
{
	subArrow[i].addEventListener("click", (e)=>{
		//selecting main parent of arrow
		let subArrowParent = e.target.parentElement.parentElement;
		subArrowParent.classList.toggle("show");
	});
}
</script>
<script>
function quickJumpTo(s){ var a=s.value; window.location.replace(a)}
</script>
	</body>
</html>