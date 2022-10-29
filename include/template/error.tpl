<!DOCTYPE html>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<title>Error</title>
</head>
<style>
body{
	padding: 0;
    margin: 0;
	font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
}
p {
	padding:2px;
	margin:0;
}
.navbar{
    position: fixed;
	top:0;
    width: -webkit-fill-available;
	background-color: #212529;
	margin-bottom: 15px;
	display: flex;
    justify-content: space-between;
}
.navbar ul {
	list-style: none;
}
.nav-left{
	display: flex;
    flex-direction: column;
    padding-left: 20px;
}
.nav-right{
	float: right;
	padding-right: 20px;
}
.navbar ul li a{
	color: white;
	font-weight: bold;
    text-decoration: none;
}
.alert{
	border-radius: 0.25rem;
    color: #842029;
    background-color: #f8d7da;
    border-color: #f5c2c7;
	padding: 7px 12px;
	margin: 65px 10px;
}
.title{
	font-weight:bold;
	color: #6a1a21;
}
.content{
	font-size: 14px;
}
</style>
<body>

	<div class="navbar">
		<ul class="nav-left">
			<li><a href="<!--base_url-->">Home Page</a></li>
		</ul>
		<ul class="nav-right">
			<li><a href="mailto:<!--email-->?subject=<!--subject-->&amp;body=<!--body-->">Report this issue</a></li>
		</ul>
	</div>

	<div class="alert">
		<!--message_box-->
		<p class="title">Error!</p>
		<p class="content">Bootstrap’s dropdowns, on the other hand, are designed to be generic and applicable to a variety of situations and markup structures. For instance, it is possible to create dropdowns that contain .</p>
	</div>

</body>
</html>