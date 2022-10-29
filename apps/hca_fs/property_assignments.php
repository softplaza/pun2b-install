<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$week_of = isset($_GET['week_of']) ? strtotime($_GET['week_of']) : time();
$first_day_of_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$search_by_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

$search_query = [];
$search_query[] = 'r.week_of='.$first_day_of_week;
if ($search_by_user_id > 0)
	$search_query[] = 'r.employee_id='.$search_by_user_id;

$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.', 4 => 'DAY OFF', 5 => 'SICK DAY', 6 => 'VACATION');
$days_of_week = array(
	'1' => 'Monday',
	'2' => 'Tuesday',
	'3' => 'Wednesday',
	'4' => 'Thursday',
	'5' => 'Friday',
	'6' => 'Saturday',
	'7' => 'Sunday',
);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[$fetch_assoc['id']] = $fetch_assoc;
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
	'ORDER BY'	=> 'ps.start_time',
);

if ($search_by_user_id > 0)
	$query['WHERE']	= 'ps.user_id='.$search_by_user_id;

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$assignments_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$assignments_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'r.*, u.realname, p.pro_name',
	'FROM'		=> 'hca_fs_requests AS r',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'r.employee_id=u.id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'r.property_id=p.id'
		),
	),
	//'WHERE'		=> 'r.week_of='.$first_day_of_week,
	'WHERE'		=> implode(' AND ', $search_query),
	'ORDER BY'	=> 'r.scheduled'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$work_orders_info = $mailed_users = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$work_orders_info[] = $fetch_assoc;
}

if (isset($_POST['send_email']))
{
	if (!empty($property_info))
	{
		foreach($property_info as $cur_info)
		{
			if ($cur_info['manager_email'] != '')
			{
				$mail_subject = 'HCA: Property Schedule';
				$mail_message = 'You have updated schedule for the week of '.format_time($first_day_of_week, 1)."\n\n";
				$mail_message .= 'To see the updated schedule, follow the links below: '."\n\n";
				$mail_message .= $URL->link('hca_fs_property_schedule', array($cur_info['id'], date('Y-m-d', $first_day_of_week)));
				
				//$SwiftMailer = new SwiftMailer;
				//$SwiftMailer->send($cur_info['manager_email'], $mail_subject, $mail_message);
			}
		}
		
		// Add flash message
		$flash_message = 'The schedule has been sent to all properties.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.hca_fs_perms, u.hca_fs_group, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.group_id='.$Config->get('o_hca_fs_painters').' OR u.group_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$Core->set_page_title('Schedule of Properties for the week of '.format_time($first_day_of_week, 1));
$Core->set_page_id('hca_fs_property_assignments', 'hca_fs');
require SITE_ROOT.'header.php';

if (!empty($property_info))
{
?>

<nav class="navbar search-bar mb-2">
	<form method="get" accept-charset="utf-8" action="<?php echo get_current_url() ?>">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_week) ?>" class="form-control form-control-sm">
				</div>
				<div class="col">
					<select name="user_id" class="form-select form-select-sm">
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="0" selected="selected">All employees</option>'."\n";
	foreach ($users_info as $cur_user)
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				echo '</optgroup>';
			}
			echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
			$optgroup = $cur_user['group_id'];
		}
		if ($search_by_user_id == $cur_user['id'])
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="col">
					<button class="btn btn-sm btn-outline-success" type="submit">Search</button>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="alert alert-light mb-2 py-1" role="alert">
	<span class="alert-primary border py-1 px-3 me-1"></span> Maintenance
	<span class="alert-warning border py-1 px-3 me-1"></span> Painters
</div>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<table class="table table-striped table-bordered">
		<thead class="start-50">
			<tr>
				<th rowspan="2" class="align-middle">Properties</th>
<?php
	$header_days2 = $first_day_of_week;
	foreach ($days_of_week as $key => $day) {
		echo '<th>'.date('l', $header_days2 ).'</th>';
		$header_days2 = $header_days2 + 86400;
	}
?>
			</tr>
			<tr>
				
<?php
	$header_days = $first_day_of_week;
	foreach ($days_of_week as $key => $day) {
		echo '<th>'.date('m/d', $header_days).'</th>';
		$header_days = $header_days + 86400;
	}
?>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($property_info as $cur_info)
	{
		$page_param['table_row'] = array();
		$page_param['table_row']['pro_name'] = '<td><a href="'.$URL->link('hca_fs_property_schedule', array($cur_info['id'], date('Y-m-d', $first_day_of_week))).'">'.html_encode($cur_info['pro_name']).'</a></td>';
?>
			<tr>
				<?php echo implode("\n\t\t\t\t\t\t", $page_param['table_row'])."\n" ?>
<?php
		foreach($days_of_week as $key => $day)
		{
			$assignment_list = array();
			if (!empty($assignments_info))
			{
				foreach($assignments_info as $assignment)
				{
					$cur_assignment = array();
					if ($cur_info['id'] == $assignment['property_id'] && $key == $assignment['day_of_week'])
					{
						$cur_assignment[] = '<strong>'.$assignment['realname'].'</strong>';
						$cur_assignment[] = '<p>'.$time_slots[$assignment['time_shift']].' (Weekly)</p>';
						$legend_css = ($Config->get('o_hca_fs_maintenance') == $assignment['group_id']) ? 'alert-primary' : 'alert-warning';
						
						$assignment_list[] = '<div class="mb-1 p-1 '.$legend_css.'">'.implode('', $cur_assignment).'</div>';
					}
				}
			}
			
			if (!empty($work_orders_info))
			{
				foreach($work_orders_info as $work_order_info)
				{
					$cur_work_order = array();
					$day_number = date('N', strtotime($work_order_info['scheduled']));
					if ($cur_info['id'] == $work_order_info['property_id'] && $key == $day_number)
					{
						$cur_work_order[] = '<strong>'.$work_order_info['realname'].'</strong>';
						$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Codes: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
						if ($work_order_info['unit_number'] != '')
							$cur_work_order[] = '<p class="wo-time">Unit#: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
						
						$cur_work_order[] = isset($time_slots[$work_order_info['time_slot']]) ? '<p class="wo-time">Time: <strong>'.$time_slots[$work_order_info['time_slot']].'</strong></p>' : 'n/a';

						if ($work_order_info['msg_for_maint'] != '')
							$cur_work_order[] = '<p class="msg">Msg: '.html_encode($work_order_info['msg_for_maint']).'</p>';
						$legend_css = ($Config->get('o_hca_fs_maintenance') == $work_order_info['group_id']) ? 'alert-primary' : ' alert-warning';
						
						$assignment_list[] = '<div class="mb-1 p-1 '.$legend_css.'">'.implode('', $cur_work_order).'</div>';
					}
				}
			}
?>
			<td class="min-150"><?php echo implode('', $assignment_list); ?></td>
<?php
		}
?>
		</tr>
<?php
	}
?>
	</tbody>
</table>

<?php
}
require SITE_ROOT.'footer.php';