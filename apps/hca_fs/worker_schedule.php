<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';

$access = ($User->checkAccess('hca_fs', 10) || $User->get('id') == $user_id) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$Facility = new Facility;
$SwiftUploader = new SwiftUploader;

$assignments_info = $Facility->PermanentUserAssignment($user_id);
$property_info = $Facility->PropertyInfo();

$execution_priority = [0 => 'Low', 1 => 'Medium', 2 => 'High'];
$days_of_week = array(
//	'0' => 'Sunday',
	'1' => 'Monday',
	'2' => 'Tuesday',
	'3' => 'Wednesday',
	'4' => 'Thursday',
	'5' => 'Friday',
	'6' => 'Saturday',
	'7' => 'Sunday',
);

$time_slots = array(
	1 => 'ALL DAY',
	2 => 'A.M.',
	3 => 'P.M.',
	4 => 'DAY OFF',
	5 => 'SICK DAY',
	6 => 'VACATION',
);

$template_types = [
	0 => 'Work Order',
	1 => 'Property Work',
	2 => 'Make Ready'
];

if (isset($_POST['confirm_all_days']))
{
	$weekly_schedule_id = isset($_POST['weekly_schedule_id']) ? intval($_POST['weekly_schedule_id']) : 0;
	
	if ($id > 0 && $first_day_of_week > 0 && $weekly_schedule_id > 0)
	{
		$time_now = time();
		$query = array(
			'UPDATE'	=> 'hca_fs_weekly',
			'SET'		=> 'submitted_time='.$time_now,
			'WHERE'		=> 'id='.$weekly_schedule_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$query = array(
			'UPDATE'	=> 'hca_fs_requests',
			'SET'		=> 'work_status=3, submitted_time='.$time_now,
			'WHERE'		=> 'employee_id='.$id.' AND week_of='.$first_day_of_week,
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Work orders of Employee #'.$id.' has been confirmed';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	else
		$Core->add_error('Failed to confirm schedule.');
}
else if (isset($_POST['confirm_day']))
{
	$work_order_id = intval(key($_POST['confirm_day']));
	$time_now = time();
	
	$query = array(
		'UPDATE'	=> 'hca_fs_requests',
		'SET'		=> 'work_status=3, submitted_time='.$time_now,
		'WHERE'		=> 'id='.$work_order_id,
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	$query = array(
		'UPDATE'	=> 'hca_fs_weekly',
		'SET'		=> 'submitted_time='.$time_now,
		'WHERE'		=> 'user_id='.$id.' AND week_of='.$first_day_of_week,
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
	// Add flash message
	$flash_message = 'The work order of Employee #'.$id.' has been confirmed';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['complete_order']))
{
	$work_order_id = intval(key($_POST['complete_order']));
	$msg_from_maint = isset($_POST['msg_from_maint'][$work_order_id]) ? swift_trim($_POST['msg_from_maint'][$work_order_id]) : '';
	$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
	$time_now = time();
	
	if ($msg_from_maint == '')
		$Core->add_error('Please leave your comment before complete the order.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'UPDATE'	=> 'hca_fs_requests',
			'SET'		=> 'completed_time='.$time_now.', work_status=2, msg_from_maint=\''.$DBLayer->escape($msg_from_maint).'\'',
			'WHERE'		=> 'id='.$work_order_id,
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		if ($request_id > 0)
		{
			$query = array(
				'UPDATE'	=> 'hca_fs_requests',
				'SET'		=> 'work_status=2',
				'WHERE'		=> 'id='.$request_id,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'The work order of Employee #'.$id.' has been competed';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
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
	'WHERE'		=> 'r.time_slot < 4 AND r.work_status=1 AND r.employee_id='.$user_id,
	'ORDER BY'	=> 'r.scheduled',
//	'LIMIT'		=> 100, r.work_status=1 AND 
);

if (!empty($query_where))
	$query['WHERE'] = implode(' AND ', $query_where);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$requests_info = $requests_id = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$requests_info[$row['id']] = $row;
	$requests_id[] = $row['id'];
}

$Core->set_page_id('hca_fs_worker_schedule', 'hca_fs');
require SITE_ROOT.'header.php';

if (!empty($requests_info))
{
?>
	<div class="card-header">
		<h6 class="card-title mb-0">Active Work Orders (<?php echo count($requests_id) ?>)</h6>
	</div>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-striped table-bordered">
			<thead class="sticky-under-menu">
				<tr class="table-primary">
					<th>Property/Unit</th>
					<th>Date/Time Requested</th>
					<th>Comments</th>
				</tr>
			</thead>
			<tbody>
<?php

	$SwiftUploader->getProjectFiles('hca_fs_requests', $requests_id);
	
	foreach ($requests_info as $cur_info)
	{
		$td = $submitted = $cur_work_order = [];
		$td['property_info'] = [];

		$uploaded_images = '<p>'.$SwiftUploader->getCurProjectFiles($cur_info['id']).'</p>';

		$td['property_info'][] = '<p><strong>'.html_encode($cur_info['pro_name']).'</strong></p>';
		$td['property_info'][] = ($cur_info['unit_number'] != '' ? '<p>Unit #: <strong>'.html_encode($cur_info['unit_number']).'</strong></p>' : '');
		$td['property_info'][] = (($cur_info['geo_code'] != '') ? '<p>GL Code: <strong>'.html_encode($cur_info['geo_code']).'</strong></p>' : '');

		$template_type_title = isset($template_types[$cur_info['template_type']]) ? $template_types[$cur_info['template_type']] : 'n/a';

		// MAKE READY
		if ($cur_info['template_type'] == 2)
		{
			if ($cur_info['group_id'] == $Config->get('o_hca_fs_painters'))
				$td['property_info'][] = '<p><a href="'.$URL->link('punch_list_management_painter_request', [$cur_info['punch_form_id'], '']).'" class="btn btn-success btn-sm text-white"><i class="fas fa-pen"></i> Make Ready</a></p>';
			else
				$td['property_info'][] = '<p><a href="'.$URL->link('punch_list_management_maintenance_request', [$cur_info['punch_form_id'], '']).'" class="btn btn-success btn-sm text-white"><i class="fas fa-pen"></i> Make Ready</a></p>';
		}
		else
			$td['property_info'][] = '<p><a href="'.$URL->link('hca_fs_edit_work_order', $cur_info['id']).'" class="btn btn-secondary btn-sm text-white">'.$template_type_title.'</a></p>';

		$submitted[] = '<p><small>'.(($cur_info['created'] > 0) ? format_time($cur_info['created']) : 'N/A').'</small></p>';
		if ($cur_info['requested_by'] != '')
			$submitted[] = '<p><small>by: '.html_encode($cur_info['requested_by']).'</small></p>';
		
		$group_id_required = (($cur_info['group_id'] == $Config->get('o_hca_fs_painters')) ? ' Painter Required' : ' Maintenance Required');
		$cur_work_order[] = (($cur_info['employee_id'] > 0) ? '<strong>'.html_encode($cur_info['realname']).'</strong>' : $group_id_required);
		
		$time_slot = (isset($time_slots[$cur_info['time_slot']]) ? $time_slots[$cur_info['time_slot']] : 'Any time');

		if ($cur_info['permission_enter'] == 1)
			$cur_work_order[] = '<p><strong>Permission to Enter - YES</strong></p>';

		if ($cur_info['has_animal'] == 1)
			$cur_work_order[] = '<p><strong>Animal in Unit - YES</strong></p>';

		$css_type = ($cur_info['group_id'] == ($Config->get('o_hca_fs_painters')) ? ' paint' : ' maint');
		
		$work_order_request = '<div class="assign-info '.$css_type.'">'.implode("\n", $cur_work_order).'</div>';
		
		$start_date = ($cur_info['start_date'] > 0) ? '<p><strong>'.format_time($cur_info['start_date'], 1).'</strong></p>' : '<strong>Any date</strong>';
		$start_date .= '<p><strong>'.$time_slot.'</strong></p>';

		$css_tr = [];
		$css_tr[] = ($cur_info['new_start_date'] > 0) ? 'new-start-date' : '';
?>
				<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo implode(' ', $css_tr) ?>">
					<td><?php echo implode("\n", $td['property_info']) ?></td>
					<td style="width:100px;min-width:100px"><?php echo $start_date ?></td>
					<td>
						<p><?php echo html_encode($cur_info['msg_for_maint']) ?></p>
						<?php echo $uploaded_images ?>
					</td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
<?php
} else {
?>
	<div class="me-3 ms-3 alert alert-warning" role="alert">You have no items on this page.</div>
<?php
}
require SITE_ROOT.'footer.php';
