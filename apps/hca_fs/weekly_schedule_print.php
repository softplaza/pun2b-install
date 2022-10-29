<?php
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
			
			<tbody class="highlight">
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
