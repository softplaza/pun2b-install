<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_fs'))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$week_of = isset($_GET['week_of']) ? strtotime($_GET['week_of']) : time();
$first_day_of_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';
$previous_week = $first_day_of_week - 604800;
$next_week = $first_day_of_week + 604800;

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
	'SELECT'	=> 'r.*, u.realname, p.pro_name',
	'FROM'		=> 'hca_fs_requests AS r',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=r.employee_id'
		),
		array(
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=r.property_id'
		),
	),
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

//$Core->set_page_title('Schedule of '.$property_info['pro_name'].' for the week of '.date('M j, Y', $first_day_of_week));
$Core->set_page_id('hca_fs_property_schedule', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<style>
.badge{text-align: left !important;}
</style>

<nav class="navbar search-box ps-3 mb-1">
	<form method="get" accept-charset="utf-8" action="">
		<input type="hidden" name="id" value="<?php echo $id ?>">
		<div class="row g-3">
			<div class="col">
				<input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_week) ?>" class="form-control form-control-sm">
			</div>
			<div class="col">
				<button type="submit" class="btn btn-sm btn-secondary">Go</button>
			</div>
		</div>
	</form>
</nav>

<div class="m-1 alert alert-light border">
	<span class="badge badge-primary p-2">Maintenance</span>
	<span class="badge badge-warning p-2">Painters</span>
</div>

<div class="card-header">
	<h6 class="card-title mb-0">Property Schedule</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Day of Week</th>
			<th>Assigned Technician</th>
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
				$cur_assignment[] = '<span class="fw-bold">'.$assignment['realname'].'</span>';
				$cur_assignment[] = '<p>'.$time_slots[$assignment['time_shift']].' (Regular)</p>';
				$legend_css = ($Config->get('o_hca_fs_maintenance') == $assignment['group_id']) ? ' badge-primary' : ' badge-warning';
				
				$assignment_list[] = '<div class="badge border m-1 '.$legend_css.'">'.implode('', $cur_assignment).'</div>';
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
				$cur_work_order[] = '<span class="fw-bold">'.$work_order_info['realname'].'</span>';
				$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Code: '.html_encode($work_order_info['geo_code']) : '';
				if ($work_order_info['unit_number'] != '')
					$cur_work_order[] = '<p>Unit#: <span class="fw-bold">'.html_encode($work_order_info['unit_number']).'</span>'.$geo_code.'</p>';
				
				$time_shift = isset($time_slots[$work_order_info['time_slot']]) ? $time_slots[$work_order_info['time_slot']] : 'n/a';
				
				$cur_work_order[] = '<p>Shift: <span class="fw-bold">'.$time_shift.'</span></p>';
				if ($work_order_info['msg_for_maint'] != '')
					$cur_work_order[] = '<p>Remarks: '.html_encode($work_order_info['msg_for_maint']).'</p>';
				$legend_css = ($Config->get('o_hca_fs_maintenance') == $work_order_info['group_id']) ? ' badge-primary' : ' badge-warning';
				
				$assignment_list[] = '<div class="badge border m-1 '.$legend_css.'">'.implode('', $cur_work_order).'</div>';
			}
		}
	}
	
	if (!empty($assignment_list))
	{
?>
		<tr>
			<td class="<?=(in_array($key, [6,7]) ? 'text-danger' : '')?>">
				<p class="fw-bold"><?php echo strtoupper($day_of_week) ?></p>
				<h6><?php echo date('M, d', $cur_day) ?></h6>
			</td>
			<td><?php echo implode('', $assignment_list) ?></td>
		</tr>
<?php
	}
	else
	{
?>
		<tr>
			<td class="<?=(in_array($key, [6,7]) ? 'text-danger' : '')?>">
				<p class="fw-bold"><?php echo strtoupper($day_of_week) ?></p>
				<h6><?php echo date('M, d', $cur_day) ?></h6>
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

<?php
require SITE_ROOT.'footer.php';
