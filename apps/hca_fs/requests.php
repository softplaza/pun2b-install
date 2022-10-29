<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 3) || $User->get('sm_pm_property_id') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$Facility = new Facility;
$SwiftUploader = new SwiftUploader;

$section = isset($_GET['section']) ? swift_trim($_GET['section']) : 'active';
$time_slots = array(
	1 => 'ALL DAY', 
	2 => 'A.M.', 
	3 => 'P.M.',
	4 => 'DAY OFF', 
	5 => 'SICK DAY', 
	6 => 'VACATION'
);

$request_statuses = array(
	-1 => 'ON HOLD',
	0 => 'New Request',
	1 => 'In Progress',
	2 => 'Completed',
//	3 => 'Alert',
//	4 => 'Denied',
	5 => 'Canceled',
);
$template_types = [
	0 => 'Work Order',
	1 => 'Property Work',
	2 => 'Make Ready'
];
$status_sections = [-1 => 'on_hold', 0 => 'new', 1 => 'active', 2 => 'completed'];
$execution_priority = [0 => 'Low', 1 => 'Medium', 2 => 'High'];

// Complete expired automatically 1 week before
hca_fs_check_expired_requests();

$query = array(
	'SELECT'	=> 'w.*',
	'FROM'		=> 'hca_fs_weekly AS w',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$weekly_schedule_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$weekly_schedule_info[$row['id']] = $row;
}

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;
$search_by = isset($_GET['search_by']) ? intval($_GET['search_by']) : 0;

$query_where = $query_order_by = [];
$query_where[] = 'r.time_slot < 4';

if ($section == 'active')
{
	$query_where[] = 'r.work_status=1';
	$query_order_by[] = 'r.scheduled';
}
else if ($section == 'completed')
{
	$query_where[] = 'r.work_status=2';
	$query_order_by[] = 'r.completed_time DESC';
}
else // new
{
	$query_where[] = 'r.work_status=0';
	$query_order_by[] = 'r.start_date';
}

if ($User->get('hca_fs_group') > 0)
	$query_where[] = 'r.group_id='.$User->get('hca_fs_group');
if ($User->get('sm_pm_property_id') > 0)
	$query_where[] = 'r.property_id='.$User->get('sm_pm_property_id');
if ($search_by_unit != '') {
	$search_by_unit = '%'.$search_by_unit.'%';
	$query_where[] = '(r.unit_number LIKE \''.$DBLayer->escape($search_by_unit).'\' OR r.msg_for_maint LIKE \''.$DBLayer->escape($search_by_unit).'\')';
}
if ($search_by_property_id > 0)
	$query_where[] = 'r.property_id='.$search_by_property_id;
if ($search_by_user_id > 0)
	$query_where[] = 'r.employee_id='.$search_by_user_id;

if ($search_by == 4)
	$query_where[] = 'r.execution_priority=0';
else if ($search_by == 5)
	$query_where[] = 'r.execution_priority=1';
else if ($search_by == 6)
	$query_where[] = 'r.execution_priority=2';

if ($sort_by == 1) $query_order_by[] = 'r.created DESC';
else if ($sort_by == 2) $query_order_by[] = 'r.created ASC';
else if ($sort_by == 3) $query_order_by[] = 'r.start_date DESC';
else if ($sort_by == 4) $query_order_by[] = 'r.start_date ASC';
else if ($sort_by == 5) $query_order_by[] = 'r.execution_priority DESC';
else if ($sort_by == 6) $query_order_by[] = 'r.execution_priority ASC';

$query = array(
	'SELECT'	=> 'COUNT(r.id)',
	'FROM'		=> 'hca_fs_requests AS r',
);

if (!empty($query_where))
	$query['WHERE'] = implode(' AND ', $query_where);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

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
	'LIMIT'		=> $PagesNavigator->limit(),
);

if (!empty($query_where))
	$query['WHERE'] = implode(' AND ', $query_where);
if (!empty($query_order_by))
	$query['ORDER BY'] = implode(', ', $query_order_by);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$requests_info = $requests_id = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$requests_info[$row['id']] = $row;
	$requests_id[] = $row['id'];
}
$PagesNavigator->num_items($requests_info);

// Get ALL users
$query = array(
	'SELECT'	=> 'u.*, g.g_id, g.g_title, g.hca_fs',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'ORDER BY'	=> 'g.g_id, u.realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = $maint_mngr = $paint_mngr = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$users_info[$row['id']] = $row;

	if ($row['hca_fs_group'] == $Config->get('o_hca_fs_maintenance'))
		$maint_mngr[] = $row['email'];
	if ($row['hca_fs_group'] == $Config->get('o_hca_fs_painters'))
		$paint_mngr[] = $row['email'];
}

if(empty($users_info))
	$Core->add_warning('Employee groups not defined. Go to SETTINGS and check the required groups.');

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

if ($section == 'completed')
	$Core->set_page_id('hca_fs_requests_completed', 'hca_fs');
else if ($section == 'active')
	$Core->set_page_id('hca_fs_requests_active', 'hca_fs');
else
	$Core->set_page_id('hca_fs_requests_new', 'hca_fs');

require SITE_ROOT.'header.php';
?>

<div id="hca_fs_requests">
<?php
	$th = array();
	$th['workers_list'] = ($section == 'on_hold') ? 'Notifications' : 'Assigned Workers';
	$th['workers_desc'] = ($section == 'on_hold') ? '<p><small>Set up time period for notifications, 0 - disabled.</small></p>' : '';
?>
	
	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<input name="section" type="hidden" value="<?php echo $section ?>"/>
			<div class="row">
<?php if ($User->get('sm_pm_property_id') == 0) : ?>
				<div class="col">
					<select name="property_id" class="form-select">
						<option value="">All Properties</option>
<?php foreach ($property_info as $cur_info){
			if ($search_by_property_id == $cur_info['id'])
				echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
} ?>
					</select>
				</div>
<?php endif; ?>
				<div class="col">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? html_encode($_GET['unit_number']) : '' ?>" placeholder="Unit# / keyword " class="form-control"/>
				</div>
				<div class="col">
					<select name="user_id" class="form-select">
						<option value="0">Search by employee</option>
<?php
$optgroup = 0;
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] == $Config->get('o_hca_fs_maintenance') || $cur_user['group_id'] == $Config->get('o_hca_fs_painters'))
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				echo "\t\t\t\t\t".'</optgroup>'."\n";
			}
			echo "\t\t\t\t\t".'<optgroup label="'.html_encode($cur_user['g_title']).'">'."\n";
			$optgroup = $cur_user['group_id'];
		}

		if ($search_by_user_id == $cur_user['id'])
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
}
?>
					</select>
				</div>
				<div class="col">
					<select name="search_by" class="form-select">
<?php 
$search_by_params = [
	0 => 'All WO-Statuses',
	4 => 'Low',
	5 => 'Medium',
	6 => 'High',
];
foreach ($search_by_params as $key => $val){
			if ($search_by == $key)
				echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
} ?>
					</select>
				</div>
				<div class="col">
					<select name="sort_by" class="form-select">
						<option value="0">Sort by...</option>
<?php 
$sort_by_array = [
	1 => 'Submitted date (newest)',
	2 => 'Submitted date (oldest)',
	3 => 'Date requested (newest)',
	4 => 'Date requested (oldest)',
	5 => 'WO-Status (high to low)',
	6 => 'WO-Status (low to high)',
];
foreach ($sort_by_array as $key => $val)
{
			if ($sort_by == $key)
				echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
} ?>
					</select>
				</div>
				<div class="col">
					<button type="submit" class="btn btn-outline-success">Search</button>
				</div>
			</div>
		</form>
	</nav>
<?php
if (!empty($requests_info))
{
?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-striped table-bordered">
			<thead class="sticky-under-menu">
				<tr class="table-primary">
					<th>Property/Unit</th>
					<th>Urgency</th>
					<th>Date Requested</th>
					<th>Message</th>
					<th>Technician</th>
					<th>Submitted</th>
				</tr>
			</thead>
			<tbody>
<?php

	$SwiftUploader->getProjectFiles('hca_fs_requests', $requests_id);

	foreach ($requests_info as $cur_info)
	{
		$property_info = $submitted = $cur_work_order = [];

		$uploaded_images = '<p>'.$SwiftUploader->getCurProjectFiles($cur_info['id']).'</p>';

		$property_info[] = '<strong>'.html_encode($cur_info['pro_name']).'</strong>' . (($cur_info['unit_number'] != '') ? ', unit #: <strong>'.html_encode($cur_info['unit_number']).'</strong>' : '') . (($cur_info['geo_code'] != '') ? ', GL Code: <strong>'.html_encode($cur_info['geo_code']).'</strong>' : '');

		if ($cur_info['work_status'] < 2)
			$property_info[] = '<p><a href="'.$URL->link('hca_fs_new_request', $cur_info['id']).'" class="btn btn-primary btn-sm text-white"><i class="fas fa-pen"></i> Edit</a></p>';

		$priority1 = (isset($execution_priority[$cur_info['execution_priority']]) ? '<strong>'.$execution_priority[$cur_info['execution_priority']].'</strong>' : 'n/a');

		if ($cur_info['execution_priority'] == 3)
			$priority = '<span class="badge bg-danger">'.$priority1.'</span>';
		else if ($cur_info['execution_priority'] == 2)
			$priority = '<span class="badge bg-warning">'.$priority1.'</span>';
		else if ($cur_info['execution_priority'] == 1)
			$priority = '<span class="badge bg-primary">'.$priority1.'</span>';
		else
			$priority = '<span class="badge bg-info">'.$priority1.'</span>';

		$punch_form_type = ($cur_info['group_id'] == $Config->get('o_hca_fs_painters')) ? $URL->link('punch_list_management_painter_request', [$cur_info['punch_form_id'], '']) : $URL->link('punch_list_management_maintenance_request', [$cur_info['punch_form_id'], '']);
		$punch_form_link = ($cur_info['punch_form_id'] > 0) ? '' : '';

		$template_type = isset($template_types[$cur_info['template_type']]) ? $template_types[$cur_info['template_type']] : 'Work Order';
		if ($cur_info['template_type'] == 2)
			$template_type = '<p>'.$punch_form_link.'</p><p><strong>Make Ready</strong></p>';//checklist.png
		else
			$template_type = '<p><strong>'.$template_type.'</strong></p>';

		$submitted[] = (($cur_info['created'] > 0) ? format_time($cur_info['created']) : 'N/A');
		if ($cur_info['requested_by'] != '')
			$submitted[] = '<p><small>by: '.html_encode($cur_info['requested_by']).'</small></p>';

		$cur_work_order = array();
		$group_id_required = (($cur_info['group_id'] == $Config->get('o_hca_fs_painters')) ? ' Painter Required' : ' Maintenance Required');
		$cur_work_order[] = (($cur_info['employee_id'] > 0) ? '<strong>'.html_encode($cur_info['realname']).'</strong>' : $group_id_required);
		
		$time_slot = (isset($time_slots[$cur_info['time_slot']]) ? $time_slots[$cur_info['time_slot']] : 'Any time');
		$start_date = ($cur_info['start_date'] > 0) ? '<strong>'.format_time($cur_info['start_date'], 1).'</strong>' : '<strong>Any date</strong>';
		$start_date .= '<p><strong>'.$time_slot.'</strong></p>';
?>
				<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id'] ? ' anchor' : '') ?>">
					<td><?php echo implode("\n", $property_info) ?></td>
					<td class="min-100"><?php echo $priority ?><?php echo $template_type ?></td>
					<td class="min-100"><?php echo $start_date ?></td>
					<td class="min-200">
						<?php echo html_encode($cur_info['msg_for_maint']) ?>
						<?php echo $uploaded_images ?>
					</td>
					<td class="min-200"><?php echo implode("\n", $cur_work_order) ?></td>
					<td class="min-100"><?php echo implode("\n", $submitted) ?></td>
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
<div class="card">
	<div class="card-body">
		<div class="alert alert-warning" role="alert">You have no items on this page.</div>
	</div>
</div>

<?php
}
?>

</div>

<?php
require SITE_ROOT.'footer.php';