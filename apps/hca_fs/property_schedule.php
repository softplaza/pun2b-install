<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$week_of = isset($_GET['week_of']) ? strtotime($_GET['week_of']) : time();
$first_day_of_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';

if ($id < 1)
	message($lang_common['No permission']);

$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.', 4 => 'DAY OFF', 5 => 'SICK DAY', 6 => 'VACATION');

$days_of_week = array(
//	0 => 'Sunday',
	1 => 'Monday',
	2 => 'Tuesday',
	3 => 'Wednesday',
	4 => 'Thursday',
	5 => 'Friday',
	6 => 'Saturday',
	7 => 'Sunday',
);

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'WHERE'		=> 'id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = $DBLayer->fetch_assoc($result);

if (empty($property_info))
	message($lang_common['Bad request']);

$query = array(
	'SELECT'	=> 'r.*',
	'FROM'		=> 'hca_fs_requests AS r',
	'WHERE'		=> 'r.property_id=\''.$DBLayer->escape($property_info['id']).'\' AND r.week_of='.$first_day_of_week,
	'ORDER BY'	=> 'r.scheduled'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$work_orders_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$work_orders_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'ps.*, u.realname, u.email, p.pro_name, p.manager_email',
	'FROM'		=> 'hca_fs_permanent_assignments AS ps',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'ps.user_id=u.id'
		),
		array(
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'ps.property_id=p.id'
		),
	),
	'WHERE'		=> 'ps.property_id='.$id,
	'ORDER BY'	=> 'ps.start_time',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$assignments_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$assignments_info[] = $fetch_assoc;
}

$Core->set_page_title('Schedule of '.$property_info['pro_name'].' for the week of '.date('M j, Y', $first_day_of_week));
$Core->set_page_id('hca_fs_property_schedule', 'hca_fs');
require SITE_ROOT.'header.php';
?>
	
<div class="main-content main-frm" id="property_shedule">
	<div class="ct-box warn-box">
		<span>Information:</span>
		<span class="legends maint"></span><span>Maintenance</span>
		<span class="legends paint"></span><span>Painters</span>
	</div>
	
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<input type="hidden" name="id" value="<?php echo $id ?>"/>
				<strong>Week of: </strong><input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_week) ?>"/>
				<input type="submit" value="Go to Date" />
			</form>
		</div>
		<table>
			<thead>
				<tr class="sticky-under-subhead">
					<th class="tc1">Day of Week</th>
					<th class="tc2">Assigned Maintenance/Painters</th>
				</tr>
			</thead>
			<tbody>
<?php
$cur_day = $first_day_of_week;
foreach ($days_of_week as $key => $day_of_week) 
{
	$assignment_list = array();
	if (!empty($assignments_info))
	{
		foreach($assignments_info as $assignment)
		{
			$cur_assignment = array();
			if ($key == $assignment['day_of_week'])
			{
				$cur_assignment[] = '<strong>'.$assignment['realname'].'</strong>';
				$cur_assignment[] = '<p>'.$time_slots[$assignment['time_shift']].' (Weekly)</p>';
				$legend_css = ($Config->get('o_hca_fs_maintenance') == $assignment['group_id']) ? ' maint' : ' paint';
				
				$assignment_list[] = '<div class="assign-info '.$legend_css.'">'.implode('', $cur_assignment).'</div>';
			}
		}
	}
	
	if (!empty($work_orders_info))
	{
		foreach($work_orders_info as $work_order_info)
		{
			$cur_work_order = array();
			$day_number = date('N', strtotime($work_order_info['scheduled']));
			if ($key == $day_number)
			{
				$cur_work_order[] = '<strong>'.$work_order_info['employee_name'].'</strong>';
				$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Codes: '.html_encode($work_order_info['geo_code']) : '';
				if ($work_order_info['unit_number'] != '')
					$cur_work_order[] = '<p class="wo-time">Unit#: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
				
				$time_shift = isset($time_slots[$work_order_info['time_slot']]) ? $time_slots[$work_order_info['time_slot']] : 'n/a';
				
				$cur_work_order[] = '<p class="wo-time">Shift: <strong>'.$time_shift.'</strong></p>';
				if ($work_order_info['msg_for_maint'] != '')
					$cur_work_order[] = '<p>Remarks: '.html_encode($work_order_info['msg_for_maint']).'</p>';
				$legend_css = ($Config->get('o_hca_fs_maintenance') == $work_order_info['group_id']) ? ' maint' : ' paint';
				
				$assignment_list[] = '<div class="assign-info '.$legend_css.'">'.implode('', $cur_work_order).'</div>';
			}
		}
	}
	
	if (!empty($assignment_list))
	{
?>
				<tr>
					<td class="days <?=(in_array($key, [6,7]) ? 'weekend' : '')?>">
						<p><strong><?php echo strtoupper($day_of_week) ?></strong></p>
						<p><?php echo format_time($cur_day, 1) ?></p>
					</td>
					<td class="info"><p><?php echo implode('', $assignment_list) ?></p></td>
				</tr>
<?php
	}
	else
	{
?>
				<tr>
					<td class="days <?=(in_array($key, [6,7]) ? 'weekend' : '')?>">
						<p><strong><?php echo strtoupper($day_of_week) ?></strong></p>
						<p><?php echo format_time($cur_day, 1) ?></p>
					</td>
					<td></td>
				</tr>
<?php
	}
	$cur_day = $cur_day + 86400;
}
?>
			</tbody>
		</table>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';