<?php

define('SITE_ROOT', '../../');

require SITE_ROOT.'include/common.php';

$hash = isset($_GET['hash']) ? intval($_GET['hash']) : 0;

$Core->set_page_id('missionary_start', 'missionary');
require SITE_ROOT.'header.php';

?>
<style>
html {font-family: sans-serif;}
body{
	background-image: url("images/studio.jpg");
    background-repeat: no-repeat;
    background-size: cover;
}
.audio-player{display:none;}
.game-content {}
video{
	position: absolute;
	top: 30%;
	left: 30%;
}
#start_game{
	border: 3px solid #3b5dce;
	border-radius: 1em;
	padding: 15px;
	background: #000;
	margin: 10px;
	cursor: pointer;
	text-align: center;
	font-size: 16px;
}
.levels-frame{
	background-color: #2b016d;
	color: #f8de5b;
	font-weight: bold;
	padding: 20px;
	width: 200px;
}
.level-info{background: black;text-align: center;}
.levels-frame ul{list-style-type: none;}
.levels-frame p{padding-left:10px;}
.level-highligt{
	background: #e4942d;
	color: #6b320a;
	border-style: outset;
	padding-top: 6px;
}
.scene-frame{margin-bottom:20px;}
.question-frame, .left-frame, .right-frame{display:none;}
.question-frame{
	text-align: center;
}
.question-frame {
	text-align: center;
	border: 3px solid #3b5dce;
	border-radius: 1em;
	padding: 15px;
    background: #001741;
    margin: 10px 10%;
    opacity: 0.9;
}
.question-frame span{
	font-size: 24px;
	color:#fff;
}
.version-frame{
	border: 3px solid #3b5dce;
	border-radius: 1em;
	padding: 15px;
	background: #000;
	margin: 10px;
	cursor: pointer;
	min-width: 300px;
}
.version-frame span{
	font-size: 20px;
	color: #fafa9b;
}
.version-frame .percent {float: right;}
.left-frame {margin-left: 15px;}
.right-frame {float:right;margin-right: 15px;}
/*effects*/
@keyframes submit-answer-blink {
	0%   { background-color: #000; }
	49% { background-color: #000; }
	50% { background-color: orange; }
	99% { background-color: orange; }
	100% { background-color: #000; }
}
.submit-answer{animation: submit-answer-blink 1s infinite;}

@keyframes time-out-blink {
	0%   { background-color: #000; }
	49% { background-color: #000; }
	50% { background-color: #b02323; }
	99% { background-color: #b02323; }
	100% { background-color: #000; }
}
.time-out{animation: time-out-blink 1s infinite;}
.right-answer{background:#117311;}
.wrong-answer{background:#b02323;}
.help-frame{float:right}
.help-frame img{width:100px;margin:7px}
</style>



	<div class="audio-player"></div>
	
	<div class="hidden-fields">
		<input type="hidden" name="end" value="0">
		<input type="hidden" name="quest_id" value="0">
		<input type="hidden" name="level" value="0">
		<input type="hidden" name="selected" value="1">
		<input type="hidden" name="result" value="1">
		<input type="hidden" name="true_v" value="0">
	</div>
	
	<div class="game-content">
		
		<div class="scene-frame">
			
			<div class="help-frame">
				<span class="help-50"><img src="<?php echo $URL->link('missionary').'images/help_50x50.png' ?>" onclick="getHelp50x50()"></span>
				<span class="help-audience"><img src="<?php echo $URL->link('missionary').'images/help_audience.png' ?>" onclick="getHelpAudience()"></span>
				<span class="help-call"><img src="<?php echo $URL->link('missionary').'images/help_call.png' ?>" onclick="getHelpCall()"></span>
			</div>
			
			<div class="levels-frame">
				<p class="level-info">ТАЛАНТЫ</p>
				<p id="level15">15&nbsp;&nbsp;&diams;&nbsp;&nbsp;1.000.000</p>
				<p id="level14">14&nbsp;&nbsp;&diams;&nbsp;&nbsp;500.000</p>
				<p id="level13">13&nbsp;&nbsp;&diams;&nbsp;&nbsp;250.000</p>
				<p id="level12">12&nbsp;&nbsp;&diams;&nbsp;&nbsp;100.000</p>
				<p id="level11">11&nbsp;&nbsp;&diams;&nbsp;&nbsp;50.000</p>
				<p id="level10">10&nbsp;&nbsp;&diams;&nbsp;&nbsp;25.000</p>
				<p id="level9">&nbsp;&nbsp;9&nbsp;&nbsp;&diams;&nbsp;&nbsp;16.000</p>
				<p id="level8">&nbsp;&nbsp;8&nbsp;&nbsp;&diams;&nbsp;&nbsp;8.000</p>
				<p id="level7">&nbsp;&nbsp;7&nbsp;&nbsp;&diams;&nbsp;&nbsp;4.000</p>
				<p id="level6">&nbsp;&nbsp;6&nbsp;&nbsp;&diams;&nbsp;&nbsp;2.000</p>
				<p id="level5">&nbsp;&nbsp;5&nbsp;&nbsp;&diams;&nbsp;&nbsp;1.000</p>
				<p id="level4">&nbsp;&nbsp;4&nbsp;&nbsp;&diams;&nbsp;&nbsp;500</p>
				<p id="level3">&nbsp;&nbsp;3&nbsp;&nbsp;&diams;&nbsp;&nbsp;300</p>
				<p id="level2">&nbsp;&nbsp;2&nbsp;&nbsp;&diams;&nbsp;&nbsp;200</p>
				<p id="level1">&nbsp;&nbsp;1&nbsp;&nbsp;&diams;&nbsp;&nbsp;100</p>
				<div id="start_game" onclick="getNextQuestion()">
					<span class="start">НАЧАТЬ ИГРУ</span>
				</div>
			</div>
			
		</div>
		
		<div class="question-frame">
			<span></span>
		</div>
		
		<div class="answers-frame">
			
			<div class="left-frame">
				<div class="version-frame">
					<span>A: </span><span class="text" id="version1"></span>
				</div>
				<div class="version-frame">
					<span>B: </span><span class="text" id="version2"></span>
				</div>
			</div>
			<div class="right-frame">
				<div class="version-frame">
					<span>C: </span><span class="text" id="version3"></span>
				</div>
				<div class="version-frame">
					<span>D: </span><span class="text" id="version4"></span>
				</div>
			</div>
		
		</div>
		
		<video id="video_intro" width="640" height="480" autoplay controls muted>
			<source src="<?php echo $URL->link('missionary').'video/intro.mp4' ?>" type="video/mp4">
			Your browser does not support the video.
		</video>
		
	</div>

<script>

window.onload = function(){
	
	$("#start_game").click(function(){
		$("#video_intro").css("display","none");
//		$("#start_game").css("display","none");
		$(".question-frame").css("display","block");
		$(".left-frame, .right-frame").css("display","inline-block");
	});
	
	//get Result
	$(document).on("click", ".version-frame .text", function()
//	$(document).on("click", ".version-frame", function()
	{
		var sel = $('.hidden-fields input[name="selected"]').val();
		var level = $('.hidden-fields input[name="level"]').val();
		var msec = level * 1000;
		var el = $(this);
		
		if(sel == 0){
			$(this).parent().addClass("submit-answer");
			musicAnswerAccepted();
			setTimeout(function(){getQuestionResult(el);},msec);
		}
	});
	
}

//get Question and versions
function getNextQuestion(){
	var csrf_token = "<?php echo gen_form_token($URL->link('missionary_ajax')) ?>";
	var next = 1;
	var level = $('.hidden-fields input[name="level"]').val();
	var res = $('.hidden-fields input[name="result"]').val();
	var end = $('.hidden-fields input[name="end"]').val();
	var sel = $('.hidden-fields input[name="selected"]').val();
	if (res == 1 && sel == 1 || end > 0)
	{
		jQuery.ajax({
			url:	"<?php echo $URL->link('missionary_ajax') ?>",
			type:	"POST",
			dataType: "json",
			cache: false,
			data: ({next:next,level:level,csrf_token:csrf_token}),
			success: function(re){
				$(".question-frame span").empty().html(re.question);
				$("#version1").empty().html(re.version1);
				$("#version2").empty().html(re.version2);
				$("#version3").empty().html(re.version3);
				$("#version4").empty().html(re.version4);
				$("#start_game span").empty().html(re.text_start);
				$(".levels-frame p").removeClass("level-highligt");
				$("#level"+re.level).addClass("level-highligt");
				$(".version-frame .percent").empty().html("");
				$(".version-frame").removeClass("right-answer");
				$(".version-frame").removeClass("wrong-answer");

				var lev = re.level;
				if(lev > 10)
					musicRound3();
				else if(lev > 5)
					musicRound2();
				else
					musicRound1();
				
				$('.hidden-fields input[name="quest_id"]').val(re.id);
				$('.hidden-fields input[name="level"]').val(re.level);
				$('.hidden-fields input[name="selected"]').val(0);
				$('.hidden-fields input[name="result"]').val(re.result);
				$('.hidden-fields input[name="end"]').val(re.end);
				$('.hidden-fields input[name="true_v"]').val(re.true_v);
				
				starCountDownTumer();
			},
			error: function(re){
				document.getElementById(".question-frame span").innerHTML = re;
			}
		});

		$(".version-frame").removeClass("submit-answer");
	}
	
	if (end > 0) setHelpDefault();
}

function getQuestionResult(el)
{
	var answ = el.text();
	var qid = $('.hidden-fields input[name="quest_id"]').val();
	var sel = $('.hidden-fields input[name="selected"]').val();
	var csrf_token = "<?php echo gen_form_token($URL->link('missionary_ajax')) ?>";
	if(sel == 0)
	{
		jQuery.ajax({
			url:	"<?php echo $URL->link('missionary_ajax') ?>",
			type:	"POST",
			dataType: "json",
			cache: false,
			data: ({id:qid,answer:answ,csrf_token:csrf_token}),
			success: function(re){
				$(".question-frame span").empty().html(re.question);
				$("#start_game span").empty().html(re.text_start);
				$('.hidden-fields input[name="result"]').val(re.result);
				$('.hidden-fields input[name="end"]').val(re.end);
				$('.hidden-fields input[name="level"]').val(re.level);
				$(".version-frame").removeClass("submit-answer");
				
				var res = re.result;
				var lev = re.level;
				
				if(lev == 16 && res == 1){
					musicWinner();
//					el.addClass("right-answer");
					el.parent().addClass("right-answer");
				}else if(res == 1){
					musicAnswerTrue();
					el.parent().addClass("right-answer");
				}else{
					musicAnswerFalse();
					el.parent().addClass("wrong-answer");
				}
				$('#start_game').removeClass("time-out");
				stopCountDownTumer();
			},
			error: function(re){
				document.getElementById(".question-frame span").innerHTML = re;
			}
		});
	}
	$('.hidden-fields input[name="selected"]').val(1);
}

function musicAnswerAccepted() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay><source src="'+ link +'answer_accepted.wav" type="audio/wav"><source src="'+ link +'answer_accepted.ogg" type="audio/ogg"><source src="'+ link +'answer_accepted.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function musicWinner() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay><source src="'+ link +'winner.wav" type="audio/wav"><source src="'+ link +'winner.ogg" type="audio/ogg"><source src="'+ link +'winner.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function musicAnswerTrue() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay><source src="'+ link +'answer_true.wav" type="audio/wav"><source src="'+ link +'answer_true.ogg" type="audio/ogg"><source src="'+ link +'answer_true.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function musicAnswerFalse() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay><source src="'+ link +'answer_false.wav" type="audio/wav"><source src="'+ link +'answer_false.ogg" type="audio/ogg"><source src="'+ link +'answer_false.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function musicRound1() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay loop><source src="'+ link +'question1_5.wav" type="audio/wav"><source src="'+ link +'question1_5.ogg" type="audio/ogg"><source src="'+ link +'question1_5.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function musicRound2() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay loop><source src="'+ link +'question6_10.wav" type="audio/wav"><source src="'+ link +'question6_10.ogg" type="audio/ogg"><source src="'+ link +'question6_10.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function musicRound3() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay loop><source src="'+ link +'question11_15.wav" type="audio/wav"><source src="'+ link +'question11_15.ogg" type="audio/ogg"><source src="'+ link +'question11_15.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function musicTimeOut() {
	var link = '<?php echo $URL->link('missionary').'audio/' ?>';
	var a = '<audio autoplay><source src="'+ link +'time_out.wav" type="audio/wav"><source src="'+ link +'time_out.ogg" type="audio/ogg"><source src="'+ link +'time_out.mp3" type="audio/mpeg"></audio>';
	$('.audio-player').empty().html(a);
}
function getHelp50x50() {
	var sel = $('.hidden-fields input[name="selected"]').val();
	var level = $('.hidden-fields input[name="level"]').val();
	if(level > 0 && sel == 0) {
		var img = '<img src="<?php echo $URL->link('missionary').'images/help_50x50_used.png' ?>">';
		$('.help-50').empty().html(img);
		
		var link = '<?php echo $URL->link('missionary').'audio/' ?>';
		var a = '<audio autoplay><source src="'+ link +'fifty_fifty.wav" type="audio/wav"><source src="'+ link +'fifty_fifty.ogg" type="audio/ogg"><source src="'+ link +'fifty_fifty.mp3" type="audio/mpeg"></audio>';
		$('.audio-player').empty().html(a);
		
		var v = $('.hidden-fields input[name="true_v"]').val();
		var t = 1;
		for(i = 1; i < 5; i++) {
			if(i != v && t < 3) {
				$('#version' + i).empty().html("");
				t++;
			}
		}
	}
}
function getHelpAudience() {
	var sel = $('.hidden-fields input[name="selected"]').val();
	var level = $('.hidden-fields input[name="level"]').val();
	if(level > 0 && sel == 0) {
		var img = '<img src="<?php echo $URL->link('missionary').'images/help_audience_used.png' ?>">';
		$('.help-audience').empty().html(img);
		
		var link = '<?php echo $URL->link('missionary').'audio/' ?>';
		var a = '<audio autoplay><source src="'+ link +'audience.wav" type="audio/wav"><source src="'+ link +'audience.ogg" type="audio/ogg"><source src="'+ link +'audience.mp3" type="audio/mpeg"></audio>';
		$('.audio-player').empty().html(a);
		
		setTimeout(function()
		{
			var pr = 25;
			var r1 = genRandNumber(1, 20);
			var v = $('.hidden-fields input[name="true_v"]').val();
			for(i = 1; i < 5; i++) {
				pr = 25;
				if(i == v) {
					pr = pr + r1;
					$('<span class="percent">'+ pr +' %</span>').insertAfter('#version' + i);
				} else {
					pr = pr + (r1 / 3);
					$('<span class="percent">'+ Math.round(pr) +' %</span>').insertAfter('#version' + i);
				}
			}
		},11000);
	}
}

function getHelpCall() {
	var sel = $('.hidden-fields input[name="selected"]').val();
	var level = $('.hidden-fields input[name="level"]').val();
	if(level > 0 && sel == 0) {
		var img = '<img src="<?php echo $URL->link('missionary').'images/help_call_used.png' ?>">';
		$('.help-call').empty().html(img);
		
		var link = '<?php echo $URL->link('missionary').'audio/' ?>';
		var a = '<audio autoplay><source src="'+ link +'call_friend.wav" type="audio/wav"><source src="'+ link +'call_friend.ogg" type="audio/ogg"><source src="'+ link +'call_friend.mp3" type="audio/mpeg"></audio>';
		$('.audio-player').empty().html(a);
	}
}
function genRandNumber(min, max) {
	return Math.floor(min + Math.random()*(max + 1 - min))
}

var interval = null;
function starCountDownTumer() {
	$('#start_game').removeClass("time-out");
	var seconds = "60";
	interval = setInterval(function()
	{
		--seconds;
		if(seconds < 1){
			$('#start_game').addClass("time-out");
			$('#start_game .start').empty().html("Время вышло");
			clearInterval(interval);
			musicTimeOut();
		}
		else
			$('#start_game .start').empty().html(seconds + " секунд");
	}, 1000);
}
function stopCountDownTumer() {
	clearInterval(interval);
	$('#start_game').removeClass("time-out");
	$('#start_game .start').empty().html("Продолжить");
}

function setHelpDefault() {
	var fifty = '<img src="<?php echo $URL->link('missionary').'images/help_50x50.png' ?>" onclick="getHelp50x50()">';
	var audience = '<img src="<?php echo $URL->link('missionary').'images/help_audience.png' ?>" onclick="getHelpAudience()">';
	var call = '<img src="<?php echo $URL->link('missionary').'images/help_call.png' ?>" onclick="getHelpCall()">';
	
	$('.help-50').empty().html(fifty);
	$('.help-audience').empty().html(audience);
	$('.help-call').empty().html(call);
}
</script>

<?php
require SITE_ROOT.'footer.php';