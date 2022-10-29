<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('sm_calendar_access') >= 3) ? true : false;

$num_display_weeks = 11;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$project_name = isset($_GET['pname']) ? swift_trim($_GET['pname']) : 'all';
$project_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$date = (isset($_GET['date']) && $_GET['date'] != '') ? strtotime($_GET['date']) : time();

//$start_display_week = $first_day_of_week - (7 * 86400);
$start_display_week = strtotime('first day of this month', $date);
$start_display_week = strtotime('Monday this week', $start_display_week);

$day_of_month_before = strtotime('first day of this month', $date) - 2678400;//One month
$day_of_month_after = strtotime('first day of this month', $date) + (2678400 * 2);//One month

$days_of_week = array(
//	0 => 'Sunday', /if it is first day of week
	1 => 'Monday',
	2 => 'Tuesday',
	3 => 'Wednesday',
	4 => 'Thursday',
	5 => 'Friday',
	6 => 'Saturday',
	7 => 'Sunday',
);

if (isset($_POST['update']))
{
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$start_time = isset($_POST['start_time']) ? strtotime($_POST['start_time']) : 0;
	$subject = isset($_POST['subject']) ? swift_trim($_POST['subject']) : '';
	$message = isset($_POST['message']) ? swift_trim($_POST['message']) : '';
	$time_now = time();
	
	if ($start_time > 0)
		$start_date = date('Ymd', $start_time);
	if ($start_time == 0)
		$Core->add_error('Wrong date. Setup date.');
	
	if (empty($Core->errors))
	{
		if ($event_id > 0)
		{
			$query = array(
				'UPDATE'	=> 'sm_calendar_events',
				'SET'		=> 
					'subject=\''.$DBLayer->escape($subject).'\', 
					message=\''.$DBLayer->escape($message).'\',
					time=\''.$DBLayer->escape($start_time).'\', 
					date=\''.$DBLayer->escape($start_date).'\',
					posted=\''.$DBLayer->escape($time_now).'\'',
				'WHERE'		=> 'id='.$event_id,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$flash_message = 'Event #'.$event_id.' has been updated.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		else
		{
			$query = array(
				'INSERT'	=> 'project_id, project_name, subject, message, time, date, posted, poster_id',
				'INTO'		=> 'sm_calendar_events',
				'VALUES'	=> 
					'\''.$DBLayer->escape($project_id).'\',
					\''.$DBLayer->escape($project_name).'\',
					\''.$DBLayer->escape($subject).'\',
					\''.$DBLayer->escape($message).'\',
					\''.$DBLayer->escape($start_time).'\',
					\''.$DBLayer->escape($start_date).'\',
					\''.$DBLayer->escape($time_now).'\',
					\''.$DBLayer->escape($User->get('id')).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$new_id = $DBLayer->insert_id();
			
			$flash_message = 'Event #'.$new_id.' has been updated.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
	}
}

else if (isset($_POST['delete_event']))
{
	$event_id = intval(key($_POST['delete_event']));
	
	if ($event_id > 0)
	{
		$query = array(
			'DELETE'	=> 'sm_calendar_events',
			'WHERE'		=> 'id='.$event_id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Event #'.$event_id.' has been deleted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['send_email']))
{
	$email_list = isset($_POST['email_list']) ? swift_trim($_POST['email_list']) : '';
	$mail_subject = isset($_POST['subject']) ? swift_trim($_POST['subject']) : 'HCA Special Projects';
	$mail_message = (isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : '')."\n\n";
	$mail_message .= get_current_url()."\n\n";
	
	if ($email_list != '' && $mail_message != '')
	{
		$SwiftMailer = new SwiftMailer;
		$SwiftMailer->send($email_list, $mail_subject, $mail_message);
		
		// Add flash message
		$flash_message = 'Email has been sent to: '.$email_list;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	else
		$Core->add_error('Your message is empty or there are no sender addresses.');
}

$query = array(
	'SELECT'	=> 'e.*, u.realname',
	'FROM'		=> 'sm_calendar_events AS e',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=e.poster_id'
		),
	),
	'WHERE'		=> 'e.time > '.$day_of_month_before.' AND e.time < '.$day_of_month_after,
	'ORDER BY'	=> 'e.time'
);
if ($project_id > 0 && $project_name == 'sm_calendar')
	$query['WHERE'] .= ' AND e.project_name=\''.$DBLayer->escape($project_name).'\' AND e.project_id='.$project_id;
else if ($project_name == 'hca_5840')
	$query['WHERE'] .= ' AND e.project_name=\''.$DBLayer->escape($project_name).'\'';
else if ($project_name == 'sm_calendar')
	$query['WHERE'] .= ' AND e.project_name=\''.$DBLayer->escape($project_name).'\'';
//else
//	$query['WHERE'] .= ' AND poster_id='.$User->get('id');

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$events_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$events_info[] = $row;
}

$calendar_project_info = $project_info = array();
if ($project_name == 'sm_calendar' && $project_id > 0)
{
	$query = array(
		'SELECT'	=> 'cp.*, u.realname, pr.pro_name',
		'FROM'		=> 'sm_calendar_projects AS cp',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=cp.project_manager_id'
			),
			array(
				'LEFT JOIN'		=> 'sm_property_db AS pr',
				'ON'			=> 'pr.id=cp.property_id'
			),
		),
		'WHERE'		=> 'cp.id='.$project_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$calendar_project_info = $DBLayer->fetch_assoc($result);
}


// HOOK FOR GEN PROJECT INFO
$hca_5840_projects = array();
foreach($events_info as $cur_info)
{
	if ($cur_info['project_name'] == 'hca_5840')
		$hca_5840_projects[] = $cur_info['project_id'];
}

if (!empty($hca_5840_projects))
{
	$query = array(
		'SELECT'	=> 'pj.id, pj.unit_number, pr.pro_name',
		'FROM'		=> 'hca_5840_projects AS pj',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'sm_property_db AS pr',
				'ON'			=> 'pr.id=pj.property_id'
			),
		),
		'WHERE'		=> 'pj.id IN('.implode(',', $hca_5840_projects).')',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$project_info[$row['id']] = $row;
	}
}

if ($access)
	$Core->add_page_action('<a id="show_email_window" href="#" onclick="emailWindow()"><i class="fa fa-envelope fa-2x"></i>Send Email</a>');

$Core->set_page_title('Calendar');
$Core->set_page_id('sm_calendar_events', 'sm_calendar');
require SITE_ROOT.'header.php';
?>

<style>
.ct-group table{table-layout:initial;}
.ct-group th{text-transform: uppercase;}
.ct-group td{overflow: unset !important;height: 90px !important;max-width:160px;max-height: 100px;}
.ct-group td p{padding:0;}
.pop-up-window {width: 250px;position: absolute;margin-left: -205px;background: #F5E9DC;padding: 5px;line-height: 23px;border-radius: 10px;border: 1px solid #A5A5A5;z-index: 300;opacity: 0.9;}
.email-window {width: 250px;position: absolute;background: #dcf5e5;padding: 5px;line-height: 23px;border-radius: 10px;border: 1px solid #A5A5A5;z-index: 300;opacity: 0.9;top: 15%;left: 40%;}
.close-window{cursor: pointer;float: right;position: relative;z-index: 300;}
.pop-up-window textarea, .email-window textarea{width:97%;}
.th-day{background:#999999;font-weight: bold;}
.th-weekend{background:#999999;font-weight: bold;color: red;}
.td-day{vertical-align:top;width: 14%;min-width:160px;padding: .3em;}
.td-month{max-width:5px;border-style: none;border: none;}
.first-col{background: #f4ad83;min-width: 25px;}
.second-col{background: #c4f483;min-width: 25px;}
.first-col span, .second-col span{left:-4px;}
.td-month span{writing-mode: vertical-rl;text-orientation: upright;font-weight: bold;color: #6f4730;font-size: 18px;position: absolute;left: 8px;top: 0;text-transform: uppercase;}
.td-day .date{font-weight: bold;font-size: 14px;width: fit-content;color: brown;padding-bottom:.2em;}
.td-day .weekend{font-weight: bold;font-size: 14px;width: fit-content;color:red;padding-bottom:.2em;}
.td-day p{text-overflow: ellipsis;overflow: hidden;width: auto;/*white-space: nowrap*/}/*cut text*/
.td-day .subject{color: #001afd;font-weight: bold;}
.warn-box{background:#0f9d58 !important;font-weight: bold; color:#fff;font-size: 16px;}
.h-property{float:right;}
.legends{padding: .1em .7em;margin-right: .5em;margin-left: 1em;border: 1px solid #adafc7;}
.yellow{background: #fff5e7;}
.pink{background: pink;}
.green{background: #c1fcc1}
.send-email{position: relative;float: right;margin-right: 1.6em;margin-top: .5em;}
.month-even{background: #FEFFE6;}
.week_odd {left: 0;position: sticky;z-index: 250;}
.week_even {left: 0;position: sticky;z-index: 100;}
.today{background: #e0feff;border: 1px dashed;}
.search-box input[type="month"]{font-weight: bold;}
.ct-content{display: flex;}
.ct-group{width: -webkit-fill-available;}
.sidebar-right{display:none;float:right;width: 250px;box-shadow: -2px -2px 5px 0 #5abbff;border-width: 1px;margin: 0 .4em .4em .4em;}
.sb-title{padding: .3em 1em;border-bottom: 1px ridge #538aac;}
.sb-title p{font-weight:bold;padding:0;}
.sb-event{margin: .3em 0;padding: .3em .5em;border-left-width: .5em;border-left-style: double;border-color: #ff8f00;background: #fff8c4;}
.sb-event p{padding: 0;}
.new-event{background: darkseagreen;}
.new-event input[type="text"], .new-event textarea{width: 97%;}
.new-event p{margin: 0 .3em;}
.event-actions img{width:16px;margin-left:10px;cursor: pointer;}
</style>
	
<div class="main-content main-frm">
	<div class="ct-box warn-box">
<?php if ($project_name == 'sm_calendar') : ?>
	<?php if (!empty($calendar_project_info)) : ?>
		<span>Calendar Events of <?php echo html_encode($calendar_project_info['realname']) ?></span>
		<span style="float:right">PROPERTY: <?php echo html_encode($calendar_project_info['pro_name']) ?><?php echo ($calendar_project_info['building_number'] != '' ? ', BLDG: '.html_encode($calendar_project_info['building_number']) : '') ?><?php echo ($calendar_project_info['unit_number'] != '' ? ', UNIT#: '.html_encode($calendar_project_info['unit_number']) : '') ?></span>
	<?php else : ?>
		<span>All Calendar Events displayed. To view only one Project select <a href="<?php echo $URL->link('sm_calendar_projects') ?>">Calendar Project.</a></span>
	<?php endif; ?>
		
<?php elseif ($project_name == 'hca_5840') : ?>
		<span>Events of Moisture Projects</span>
<?php else : ?>
		<span>Events of All Projects</span>
<?php endif; ?>
	</div>

	<div class="ct-content">
		<div class="ct-group">
			<div class="search-box">
				<form method="get" accept-charset="utf-8" action="">
					<strong>Month </strong><input type="month" name="date" value="<?php echo date('Y-m', $date) ?>"/>
					<input type="hidden" name="pid" value="<?php echo $project_id ?>"/>
<?php if (!$User->is_guest()) : ?>
					<select name="pname">
<?php
$projects_array = array(
	'all'			=> 'All Events',
	'sm_calendar'	=> 'Calendar Events',
	'hca_5840'		=> 'Moisture Inspections'
);
foreach($projects_array as $key => $val) {
	if ($project_name == $key)
		echo '<option value="'.$key.'" selected>'.$val.'</option>';
	else
		echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
					</select>
<?php else : ?>
				<input type="hidden" name="pname" value="<?php echo $project_name ?>"/>	
<?php endif; ?>
					<input type="submit" value="Search" />
				</form>
			</div>
			
			<table>
				<thead>
					<tr>
						<th class="th-day th-month"></th>
<?php
$header_days2 = $start_display_week;
foreach ($days_of_week as $key => $day) {
	$th_class = ($key == 6 || $key == 7) ? ' th-weekend': ' th-day';
	echo '<th class="'.$th_class.'">'.date('l', $header_days2 ).'</th>';
	$header_days2 = $header_days2 + 86400;
}
?>
					</tr>
				</thead>
				
				<tbody class="hl-cell">
<?php
$time_next_date = $start_display_week;
$next_month = date('m', $start_display_week);
$week_count = 1;
$css_of_month = 'month-odd';
$months = array();
for ($i = 1; $i < $num_display_weeks; ++$i) 
{
?>
					<tr>
<?php
	$css_of_first_col = ($css_of_month == 'month-even' || ($next_month != date('m', $time_next_date))) ? ' second-col' : ' first-col';
	
	if (!isset($months[$next_month]))
	{
		echo '<td class="td-month week_odd '.$css_of_first_col.'"><span>'.date('F', $time_next_date).'</span></td>';
		$months[$next_month] = date('F', $time_next_date);
	} else
		echo '<td class="td-month week_even '.$css_of_first_col.'"><span></span></td>';
	
	foreach ($days_of_week as $key => $day)
	{
		$css_of_day = '';
		if ($next_month != date('m', $time_next_date))
			$css_of_month = ($css_of_month == 'month-even') ? 'month-odd' : 'month-even';
		
		$today_date = date('Ymd', time());
		$cur_date = date('Ymd', $time_next_date);
		$css_of_day .= $css_of_month;
		$css_of_day .= ($today_date == $cur_date) ? ' today' : '';
		$is_today = ($today_date == $cur_date) ? ' (Today)' : '';
		
		$events_list = array();
		
		if ($key == 1)
			$events_list[] =  '<p class="date">'.date('d (F)', $time_next_date) . $is_today.'</p>'."\n";
		else if ($key > 5)
			$events_list[] =  '<p class="weekend">'.date('d', $time_next_date) . $is_today.'</p>'."\n";
		else
			$events_list[] =  '<p class="date">'.date('d', $time_next_date) . $is_today.'</p>'."\n";
		
		if (!empty($events_info))
		{
			foreach($events_info as $cur_info)
			{
				if ($cur_info['date'] == $cur_date)
				{
					$events_list[] = '<p class="subject">'.html_encode($cur_info['subject']).'</p>'."\n";
					
					//HOOKS FOR DISPLAY OTER APPS
					if ($cur_info['project_name'] == 'hca_5840' && isset($project_info[$cur_info['project_id']]))
						$events_list[] = '<p style="font-weight:bold;color:#bd0dc0;">'.html_encode($project_info[$cur_info['project_id']]['pro_name']).', '.html_encode($project_info[$cur_info['project_id']]['unit_number']).'</p>'."\n";
					
					$events_list[] = '<p>';
					
					if ($cur_info['project_name'] != 'sm_calendar')
						$events_list[] = '<span class="event-time">'.format_time($cur_info['time'], 2).': </span>';
					
					$events_list[] = '<span class="event-message">'.html_encode($cur_info['message']).'</span>';
					$events_list[] = '</p>'."\n";
					
				}
			}
		}
		
		echo '<td id="ass'.$time_next_date.'" class="td-day '.$css_of_day.'" onclick="getEvents('.$cur_date.', 0);">'.implode('', $events_list).'</td>'."\n";

		$next_month = date('m', $time_next_date);
		$time_next_date = $time_next_date + 86400;
	}
	
	++$week_count;
?>
					</tr>
<?php
}
?>
				</tbody>
			</table>
		</div>
		<div class="sidebar-right">
			<form method="post" accept-charset="utf-8" action="<?php echo get_current_url() ?>">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(get_current_url()) ?>" />
				<div class="sb-title">
					<p id="event_date"><?php echo format_time(time(), 1) ?></p>
				</div>
				<div id="sb_events">
					<div class="sb-event">
						<p><?php echo format_time(time(), 2) ?>, <strong>Event title</strong></p>
						<p>This is test message that will apear here. Size depends from how many word we will use.</p>
					</div>
				</div>
				<div class="new-event">
					<p><strong>New Event</strong></p>
					<p id="event_id"><input type="hidden" name="event_id" value="0" /></p>
					<p id="start_time"><input type="datetime-local" name="start_time" value="" /></p>
					<p id="event_subject"><input type="text" name="subject" value="" placeholder="Subject"/></p>
					<p id="event_message"><textarea name="message" rows="4" placeholder="Your comment"></textarea></p>
					<p id="event_actions"><input type="submit" name="update" value="Add event"/></p>
				</div>
			</form>
		</div>
	</div>
</div>

<?php if (!$User->is_guest()) : ?>
<div class="email-window" style="display:none">
	<label class="close-window"><img src="<?php echo BASE_URL ?>/img/close.png" width="16px" onclick="closeWindows()"/></label>
	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		</div>
		<div class="edit-assign">
			<p>Subject</p>
			<p><input type="text" name="subject" value="HCA: Facilities Projects"></p>
			<p>Comma-separated email addresses</p>
			<p><textarea name="email_list" rows="3" placeholder="Enter emails separated by commas"><?php echo $Config->get('o_sm_calendar_mailing_list') ?></textarea></p>
			<p><textarea name="mail_message" rows="3" placeholder="Write your message">Hello. To view this project follow the link in the letter</textarea></p>
			<p class="btn-action"><span class="submit primary"><input type="submit" name="send_email" value="Send"/></span></p>
		</div>
	</form>
</div>
			
<script>
function getEvents(d,id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('sm_calendar_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('sm_calendar_ajax_get_events') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({date:d,event_id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#event_date").empty().html(re.event_date);
			$("#sb_events").empty().html(re.sb_events);
			$("#start_time").empty().html(re.start_time);
			
			$(".sidebar-right").slideDown("2000");
			$('#event_id input[name="event_id"]').val('0');
		//	$('#start_time input[name="start_time"]').val('');
			$('#event_subject input[name="subject"]').val('');
			$('#event_message textarea').val('');
			$('#event_actions input[name="update"]').val('Add event');
		},
		error: function(re){
			$("#events").empty().html('Error: No events.');
		}
	});	
}
function emailWindow(){
	$(".email-window").slideToggle("2000");
}
function editEvent(id){
	var csrf_token = "<?php echo generate_form_token($URL->link('sm_calendar_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('sm_calendar_ajax_get_events') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({event_id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#start_time").empty().html(re.start_time);
			$("#event_subject").empty().html(re.event_subject);
			$("#event_message").empty().html(re.event_message);
			$('#event_actions input[name="update"]').val('Update');
		},
		error: function(re){
			$("#events").empty().html('Error: No events.');
		}
	});
	
	$('#event_id input[name="event_id"]').val(id);
}
function closeWindows(){
	$(".pop-up-window, .email-window").slideUp("2000");
	$('.hidden input[name="event_id"]').val("0");
	$('.hidden input[name="start_time"]').val("0");
	$(".pop-up-window textarea").val("");
	$('#select_subject option:selected').removeAttr("selected");
	$("#text_subject").empty().html('');
}
window.onload = function(){
	$(document).mouseup(function(e) 
	{
		var container = $(".pop-up-window, .email-window");
		if (container.is(e.target) && container.has(e.target).length !== 0) {
			closeWindows();
		}
	});
}
</script>

<?php endif;
require SITE_ROOT.'footer.php';