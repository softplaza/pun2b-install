<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs')) ? true : false;

$Facility = new Facility;
$gid = $Facility->group_id = isset($_GET['gid']) ? intval($_GET['gid']) : $Config->get('o_hca_fs_maintenance');
$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$week_of = $Facility->week_of = isset($_GET['week_of']) ? strtotime($_GET['week_of']) : time();
$first_day_of_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$Facility->first_day_of_week = $first_day_of_week;
$days_of_week = $Facility->days_of_week;
$time_slots = $Facility->time_slots;

$previous_week = $first_day_of_week - 604800;
$next_week = $first_day_of_week + 604800;

if ($uid > 0)
{
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
		'WHERE'		=> 'ps.user_id='.$uid,
		'ORDER BY'	=> 'ps.start_time',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$assignments_info = array();
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$assignments_info[] = $fetch_assoc;
	}

	$query = [
		'SELECT'	=> 'r.*, u.realname, p.pro_name',
		'FROM'		=> 'hca_fs_requests AS r',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'r.employee_id=u.id'
			],
			[
				'LEFT JOIN'		=> 'sm_property_db AS p',
				'ON'			=> 'r.property_id=p.id'
			],
		],
		'WHERE'		=> 'r.employee_id='.$uid.' AND r.week_of='.$first_day_of_week,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$main_info = [];
	$user_schedule = 'Technician';
	while ($row = $DBLayer->fetch_assoc($result)) {
		$main_info[] = $row;
		$user_schedule = $row['realname'];
	}

	$Core->set_page_id('hca_fs_weekly_technician_schedule', 'hca_fs');
	require SITE_ROOT.'header.php';
?>

<nav class="navbar search-box ps-3 mb-1">
	<form method="get" accept-charset="utf-8" action="">
		<div class="row g-3">
			<div class="col">
				<a href="<?php echo $URL->link('hca_fs_weekly_technician_schedule', [$gid, $uid, date('Y-m-d', $previous_week)]) ?>" class="btn btn-sm btn-secondary text-white form-control">Previous</a>
			</div>
			<div class="col">
				<input type="text" value="<?php echo date('F d', $first_day_of_week) ?>" class="form-control" disabled>
			</div>
			<div class="col">
				<a href="<?php echo $URL->link('hca_fs_weekly_technician_schedule', [$gid, $uid, date('Y-m-d', $next_week)]) ?>" class="btn btn-sm btn-secondary text-white form-control">Next</a>
			</div>
		</div>
	</form>
</nav>

<div class="card-header">
	<h6 class="card-title mb-0">Weekly Schedule of <?php echo $user_schedule ?></h6>
</div>
<table class="table table-striped table-bordered my-0">
	<thead>
		<tr>
			<th>Date</th>
			<th>Work orders</th>
		</tr>
	</thead>
	<tbody>
<?php
	$time_next_date = $first_day_of_week;
	foreach ($days_of_week as $key => $day)
	{
		$date = $tasks = [];
		$cur_date = date('Ymd', $time_next_date);
		
		$date[] = '<h6>'.date('l', $time_next_date).'</h6>';
		$date[] = '<p>'.date('F d', $time_next_date).'</p>';

		if (!empty($main_info))
		{
			foreach($main_info as $cur_info)
			{
				$task_info = [];
				if ($cur_info['scheduled'] == $cur_date)
				{
					$slot_text = isset($time_slots[$cur_info['time_slot']]) ? $time_slots[$cur_info['time_slot']] : '';
					if (in_array($cur_info['time_slot'], [4,5,6]))
					{
						$tasks[] = '<div class="alert alert-success" role="alert">';
						$tasks[] = '<p>'.$slot_text.'</p>';
						$tasks[] = '</div>';
					}
					else if ($cur_info['time_slot'] == 7)
					{
						$tasks[] = '<div class="alert alert-danger" role="alert">';
						$tasks[] = '<p>'.$slot_text.'</p>';
						$tasks[] = '</div>';
					}
					else
					{
						$tasks[] = '<div class="alert alert-info" role="alert">';

						if ($cur_info['pro_name'] != '')
							$task_info[] = html_encode($cur_info['pro_name']);
						if ($cur_info['unit_number'] != '')
							$task_info[] = 'unit #: '.html_encode($cur_info['unit_number']);
						if ($cur_info['geo_code'] != '')
							$task_info[] = 'GL code: '.html_encode($cur_info['geo_code']);
						$task_info[] = 'Shift: '.$slot_text;
						
						if (!empty($task_info))
							$tasks[] = implode(', ', $task_info);
							
						$tasks[] = '<p>'.html_encode($cur_info['msg_for_maint']).'</p>';
						$tasks[] = '</div>';
					}
				}
			}
		}

		if (empty($tasks) && !empty($assignments_info))
		{
			foreach($assignments_info as $regular)
			{
				if ($regular['day_of_week'] == $key)
				{
					$tasks[] = '<div class="alert alert-warning" role="alert">';
					$tasks[] = html_encode($regular['pro_name']);
					$tasks[] = '<p>Regular work</p>';
					$tasks[] = '</div>';
				}
			}
		}

		echo '<tr>';
		echo '<td class="max-100">'.implode('', $date).'</td>';
		echo '<td>'.implode('', $tasks).'</td>';
		echo '</tr>';

		$time_next_date = $time_next_date + 86400;
	}
?>
	</tbody>
</table>

<?php
}
else
{
	$weekly_schedule_info = $Facility->WeeklyInfo();
	$users_info = $Facility->UsersInfo();
	$work_orders_info = $Facility->WorkOrdersInfo();
	$property_info = $Facility->PropertyInfo();
	$assignments_info = $Facility->PermanentAssignments();

	$Core->set_page_id('hca_fs_weekly_technician_schedule', 'hca_fs');
	require SITE_ROOT.'header.php';

	$schedule_title = ($gid == $Config->get('o_hca_fs_painters')) ? 'PAINTER WEEKLY SCHEDULE' : 'MAINTENANCE WEEKLY SCHEDULE';
?>
<div class="card-header">
	<h6 class="mb-2" style="text-align:center"><?php echo $schedule_title ?></h6>
</div>
<div id="weekly_shedule_print">
	<table class="border">
		<thead>
			<tr class='th-tr1'>
				<th class="tc1">Employee Name</th>
	<?php
	$hasWeekendJob = $Facility->hasWeekendJob();
	$header_days2 = $first_day_of_week;
	foreach ($days_of_week as $key => $day) {
		$cur_css = (in_array($key, array(6,7)) && !$hasWeekendJob) ? 'th-weekend' : '';
		echo '<th class="th-day '.$cur_css.'">'.date('l', $header_days2 ).'</th>';
		$header_days2 = $header_days2 + 86400;
	}
	?>
			</tr>
			<tr class='th-tr2'>
				<th class="tc2"></th>
<?php
	$header_days = $first_day_of_week;
	foreach ($days_of_week as $key => $day) {
		$cur_css = (in_array($key, array(6,7)) && !$hasWeekendJob) ? 'th-weekend' : '';
		echo '<th class="th-date '.$cur_css.'">'.date('m/d', $header_days).'</th>';
		$header_days = $header_days + 86400;
	}
?>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($users_info as $user_info) 
	{
?>
			<tr>
				<td class="user-info"><?php echo $user_info['realname'] ?></td>
<?php
		//$user_property_days = unserialize($user_info['hca_fs_property_days']);
		
		$time_next_date = $first_day_of_week;
		foreach ($days_of_week as $key => $day)
		{
			$cur_date = date('Ymd', $time_next_date);
			$assignment_list = $assignment_ids = array();
			
			if (!empty($assignments_info))
			{
				foreach($assignments_info as $assignment)
				{
					$cur_assignment = array();
					if ($user_info['id'] == $assignment['user_id'] && $key == $assignment['day_of_week'])
					{
						$cur_assignment[] = '<strong>'.$property_info[$assignment['property_id']]['pro_name'].'</strong>';
						$cur_assignment[] = '<p>'.$time_slots[$assignment['time_shift']].'</p>';
						
						$assignment_list[$assignment['id']] = '<div class="assign-info pending">'.implode('', $cur_assignment).'</div>';
						$assignment_ids[] = $assignment['id'];
					}
				}
			}
			
			$assigned_to_property = implode('', $assignment_list);
			$work_order_list = $work_order_ids = $cur_info = array();
			$day_off_id = $time_next_date + $user_info['id'];
			if (!empty($work_orders_info))
			{
				foreach($work_orders_info as $work_order_info)
				{
					$cur_work_order = $css_status = array();
					$day_number = date('N', strtotime($work_order_info['scheduled']));
	
					if ($user_info['id'] == $work_order_info['employee_id'] && $key == $day_number)
					{
						$css_status[] = ($work_order_info['time_slot'] > 3) ? ' day-off' : '';
						
						if ($work_order_info['completed_time'] > 0) {
							$css_status[] = ' completed';
						} else if ($work_order_info['submitted_time'] > 0) {
							$css_status[] = ' submitted';
						} else if ($work_order_info['viewed_time'] > 0) {
							$css_status[] = ' viewed';
						} else if ($work_order_info['mailed_time'] > 0) {
							$css_status[] = ' miled';
						} else {
							$css_status[] = ' pending';
						}
						
						$cur_work_order[] = ($work_order_info['time_slot'] > 3) ? '<strong>'.$time_slots[$work_order_info['time_slot']].'</strong>' : '<strong>'.html_encode($work_order_info['pro_name']).'</strong>';
						
						$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Codes: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
						if ($work_order_info['unit_number'] != '')
							$cur_work_order[] = '<p class="wo-time">Unit#: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
						
						$time_slot = isset($time_slots[$work_order_info['time_slot']]) ? $time_slots[$work_order_info['time_slot']] : 'n/a';
						
						if ($work_order_info['time_slot'] < 4)
							$cur_work_order[] = '<p class="wo-time">Time: <strong>'.$time_slot.'</strong></p>';
							
						if ($work_order_info['msg_for_maint'] != '')
							$cur_work_order[] = '<p class="msg-for-maint">'.html_encode($work_order_info['msg_for_maint']).'</p>';
						
						$work_order_list[] = '<div class="assign-info'.implode('', $css_status).'">'.implode('', $cur_work_order).'</div>';
						
						$cur_info = $work_order_info;
						$work_order_ids[] = $work_order_info['id'];
					}
				}
				
			}
			
			$cur_css = (in_array($key, array(6,7)) && !$hasWeekendJob) ? 'td-weekend' : '';
			if (!empty($work_order_list))
			{
				echo '<td id="ass'.$day_off_id.'" class="work-orders-list '.$cur_css.'" >'.implode('', $work_order_list).'</td>'."\n";
			}
			else
			{
				// IS SCHEDULED PERMANENTLY WORKER ? 
				if ($assigned_to_property != '')
				{
					echo '<td id="ass'.$day_off_id.'" class="work-orders-list '.$cur_css.'">'.$assigned_to_property.'</td>'."\n";
					// IS UNASSIGNED WORKER ? 
				} else {
					echo '<td id="ass'.$day_off_id.'" class="work-orders-empty '.$cur_css.'"></td>'."\n";
				}
			} 
			$time_next_date = $time_next_date + 86400;
		}
?>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</div>
<?php
}
require SITE_ROOT.'footer.php';