<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod()) ? true : false;
if (!$access)
	message('No permissions for this page.');

require 'class_get_vendors.php';
require 'class_sm_property_db.php';

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

$search_by_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

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

$calendar_info = array();
$query = array(
	'SELECT'	=> 'i.*, pj.unit_number, pj.unit_size, pj.property_id, pj.status, pt.pro_name, v.vendor_name, v.email, v.phone_number, v.orders_limit',
	'FROM'		=> 'hca_vcr_invoices AS i',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'hca_vcr_projects AS pj',
			'ON'			=> 'pj.id=i.project_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_vendors AS v',
			'ON'			=> 'v.id=i.vendor_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'WHERE'		=> 'i.in_house=0 AND i.date_time >= '.$day_of_month_before.' AND i.date_time < '.$day_of_month_after,
	'ORDER BY'	=> 'v.vendor_name, pt.pro_name, LENGTH(pj.unit_number), pj.unit_number'
);
if ($search_by_vendor_id > 0) $query['WHERE'] .= ' AND i.vendor_id='.$search_by_vendor_id;
if ($search_by_property_id > 0) $query['WHERE'] .= ' AND pj.property_id='.$search_by_property_id;
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result)) {
	$calendar_info[] = $row;
}

//$Core->set_page_title('Calendar');
$Core->set_page_id('hca_vcr_calendar', 'hca_vcr');
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
.td-month{max-width:5px;border-style: none;border: none;}
.first-col{background: #f4ad83;min-width: 25px;}
.second-col{background: #c4f483;min-width: 25px;}
.first-col span, .second-col span{left:-4px;}
.td-month span{writing-mode: vertical-rl;text-orientation: upright;font-weight: bold;color: #6f4730;font-size: 18px;position: absolute;left: 8px;top: 0;text-transform: uppercase;}
.td-day{vertical-align:top;width: 14%;min-width:160px;padding:0;}
.td-day .date{font-weight: bold;font-size: 14px;color: brown;border-radius: 0 0 10px 10px;padding: 5px;}
.td-day .weekend{font-weight: bold;font-size: 14px;width: fit-content;color:red;padding-bottom:.2em;}
.td-day p{text-overflow: ellipsis;overflow: hidden;width: auto;white-space: nowrap}/*cut text*/
.subject{color: #001afd;font-weight: bold;}
.event-message{font-weight: bold}
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
.month-odd .date{background: #87e3ff;}
.month-even .date{background: #ffd400;}
.search-box input[type="month"]{font-weight: bold;}
.search-box select{width: 180px;}
.ct-content{display: flex;}
.ct-group{width: -webkit-fill-available;}
.sidebar-right{display:none;float:right;width: 250px;box-shadow: -2px -2px 5px 0 #5abbff;border-width: 1px;margin: 0 .4em .4em .4em;}
.sidebar-right form{
	position: -webkit-sticky;
    position: sticky;
    top: 0;
    z-index: 200;
    text-align: center;
    vertical-align: top;
}
.sb-title{padding: .3em 1em;border-bottom: 1px ridge #538aac;}
.sb-title p{font-weight:bold;padding:0;}
.sb-event{margin: .3em 0;padding: .3em .5em;border-left-width: .5em;border-left-style: double;border-color: #ff8f00;background: #fff8c4;}
.sb-event p{padding: 0;}

.new-event{background: darkseagreen;}
.new-event input[type="text"], .new-event textarea{width: 97%;}
.new-event p{margin: 0 .3em;}
.event-actions img{width:16px;margin-left:10px;cursor: pointer;}
.warn-box span{font-weight: bold;margin: 5px;}
.ct-group td .alert-red {background: red;color: white;padding: 2px;}
.warn-box .alert-red{background: red;color: white;padding: 3px 10px;}
.alert-pink{background: pink;color: #473d3d;padding: 3px 10px;}
.circle-notify{bottom:0;margin-right: 5px;}
.st-event .stat{margin: 5px 0;}
.st-event{margin: .3em 0;padding: .3em .5em;border-left-width: .5em;border-left-style: double;border-color: #ff8f00;background: #fff8c4;}
.st-title{background: lightsalmon;color:white;font-weight:bold;padding:0 .5em;border-bottom: 1px ridge #538aac;}
</style>

<div class="main-content main-frm">
	<div class="ct-box warn-box">
		<p><strong>Legends: </strong><span class="alert-red"></span> - No vendor<span class="alert-pink"></span> - Duplicate</p>
	</div>
	<div class="ct-content">
		<div class="ct-group">
			<div class="search-box">
				<form method="get" accept-charset="utf-8" action="">
					<strong>Month </strong><input type="month" name="date" value="<?php echo date('Y-m', $date) ?>"/>
					<select name="vendor_id">
						<option value="0">Any Vendor</option>
<?php
foreach($VCRVendors->vendors_info as $cur_info)			
{
	if ($search_by_vendor_id == $cur_info['id'])
		echo '<option value="'.$cur_info['id'].'" selected>'.$cur_info['vendor_name'].'</option>';
	else
		echo '<option value="'.$cur_info['id'].'">'.$cur_info['vendor_name'].'</option>';
}				
?>
					</select>
					<select name="property_id">
						<option value="0">Any Property</option>
<?php
foreach($smProperty->main_info as $cur_info)			
{
	if ($search_by_property_id == $cur_info['id'])
		echo '<option value="'.$cur_info['id'].'" selected>'.$cur_info['pro_name'].'</option>';
	else
		echo '<option value="'.$cur_info['id'].'">'.$cur_info['pro_name'].'</option>';
}				
?>
					</select>

					<input type="submit" value="Search" />
				</form>
			</div>
			<table>
				<thead>
					<tr class="sticky-under-menu">
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
for ($i = 1; $i < $num_display_weeks; ++$i) 
{
?>
					<tr>
<?php
	foreach ($days_of_week as $key => $day)
	{
		$css_of_day = array();
		if ($next_month != date('m', $time_next_date))
			$css_of_month = ($css_of_month == 'month-even') ? 'month-odd' : 'month-even';
		
		$css_of_day[] = $css_of_month;
		$today_date = date('Ymd', time());
		$cur_date = date('Ymd', $time_next_date);
		$css_of_day[] = ($today_date == $cur_date) ? ' today' : '';
		$is_today = ($today_date == $cur_date) ? ' (Today)' : '';
		
		$events_list = $vendor_detect = array();
		$events_list[] =  '<p class="date">'.date('d F', $time_next_date) . $is_today.'</p>'."\n";
	
		if (!empty($calendar_info))
		{
			
			foreach($calendar_info as $cur_info)
			{
				$event_css = array();
				$cur_event_date = date('Ymd', $cur_info['date_time']);
				if ($cur_event_date == $cur_date)
				{
					if (isset($vendor_detect[$cur_info['vendor_id']][$cur_info['property_id']][$cur_info['unit_number']]))
						$vendor_detect[$cur_info['vendor_id']][$cur_info['property_id']][$cur_info['unit_number']] = ++$vendor_detect[$cur_info['vendor_id']][$cur_info['property_id']][$cur_info['unit_number']];
					else
						$vendor_detect[$cur_info['vendor_id']][$cur_info['property_id']][$cur_info['unit_number']] = 1;
					
					if ($vendor_detect[$cur_info['vendor_id']][$cur_info['property_id']][$cur_info['unit_number']] > 1)
						$event_css[] = 'alert-pink';
					
					$events_list[] = (($cur_info['vendor_name'] != '') ? '<p class="subject">'.html_encode($cur_info['vendor_name']).'</p>' : '<p class="subject alert-red">No Vendor</p>')."\n";
					$events_list[] = '<p><span class="event-message '.implode(' ', $event_css).'">';
					$events_list[] = html_encode($cur_info['pro_name']);
					if ($cur_info['unit_number'] != '') $events_list[] = ', #'.html_encode($cur_info['unit_number']);
					if ($cur_info['unit_size'] != '') $events_list[] = ', '.html_encode($cur_info['unit_size']);
					$events_list[] = '</span></p>';
				}
			}
		}
		
		echo '<td id="ass'.$time_next_date.'" class="td-day '.implode(' ', $css_of_day).'" onclick="getEvents('.$time_next_date.');">'.implode('', $events_list).'</td>'."\n";

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
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
				<div class="sb-title">
					<p id="event_date"><?php echo format_time(time(), 1) ?></p>
				</div>
				<div id="sb_events">
					<div class="sb-event">
						<p><?php echo format_time(time(), 2) ?>, <strong>Event title</strong></p>
						<p>This is test message that will apear here. Size depends from how many word we will use.</p>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<?php if (!$User->is_guest()) : ?>
<script>
function getEvents(date) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_vcr_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_vcr_ajax_get_events') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({date:date,csrf_token:csrf_token}),
		success: function(re){
			$("#event_date").empty().html(re.event_date);
			$("#sb_events").empty().html(re.sb_events);
			$("#start_time").empty().html(re.start_time);
			
			$(".sidebar-right").slideDown("2000");
			$('#event_id input[name="event_id"]').val('0');
			$('#event_subject input[name="subject"]').val('');
			$('#event_message textarea').val('');
			$('#event_actions input[name="update"]').val('Add event');
		},
		error: function(re){
			$("#events").empty().html('Error: No events.');
		}
	});	
}
</script>

<?php endif;
require SITE_ROOT.'footer.php';