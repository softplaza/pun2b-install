<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 7)) ? true : false;
$access5 = ($User->checkAccess('hca_fs')) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
	0 => 'Pending',
	1 => 'In Progress',
	2 => 'Completed',
//	3 => 'Alert',
//	4 => 'Denied',
	5 => 'Canceled',
);

$status_sections = [-1 => 'on_hold', 0 => 'new', 1 => 'active', 2 => 'completed'];
$execution_priority = [0 => 'Low', 1 => 'Medium', 2 => 'High'];

// Complete expired automatically 1 week before
//hca_fs_check_expired_requests();

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

if ($search_by == 1)
	$query_where[] = 'r.work_status=0';
else if ($search_by == 2)
	$query_where[] = 'r.work_status=1';
else if ($search_by == 3)
	$query_where[] = 'r.work_status=2';
else if ($search_by == 4)
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
	'ORDER BY'	=> 'r.start_date',
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
//	'WHERE'		=> 'g.hca_fs > 0',
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

$Core->set_page_id('hca_fs_report', 'hca_fs');

require SITE_ROOT.'header.php';

$th = array();
$th['workers_list'] = ($section == 'on_hold') ? 'Notifications' : 'Assigned Workers';
$th['workers_desc'] = ($section == 'on_hold') ? '<p><small>Set up time period for notifications, 0 - disabled.</small></p>' : '';
?>
		
	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<input name="section" type="hidden" value="<?php echo $section ?>"/>
			<div class="row">
<?php if ($access5) : ?>
				<div class="col">
					<select name="property_id" class="form-select">
						<option value="">All Properties</option>
<?php 
foreach ($property_info as $cur_info)
{
	if ($search_by_property_id == $cur_info['id'])
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
<?php endif; ?>
				<div class="col">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? html_encode($_GET['unit_number']) : '' ?>" placeholder="Unit# / keyword " size="15" class="form-control"/>
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
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected="selected">'.html_encode($cur_user['realname']).'</option>'."\n";
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
$search_by_params = array(
	0 => 'All Statuses',
	1 => 'Pending',
	2 => 'In Progress',
	3 => 'Completed',
	4 => 'Low WO-Status',
	5 => 'Medium WO-Status',
	6 => 'High WO-Status',
);
foreach ($search_by_params as $key => $val){
			if ($search_by == $key)
				echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
} ?>
					</select>
				</div>
				<div class="col">
					<select name="sort_by" class="form-select">
						<option value="0">Sort by...</option>
<?php 
$sort_by_array = array(
	1 => 'Submitted date (newest)',
	2 => 'Submitted date (oldest)',
	3 => 'Date requested (newest)',
	4 => 'Date requested (oldest)',
	5 => 'WO-Status (high to low)',
	6 => 'WO-Status (low to high)',
);
foreach ($sort_by_array as $key => $val){
			if ($sort_by == $key)
				echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
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
		<table class="table table-striped">
			<thead class="sticky-under-menu">
				<tr class="table-primary">
					<th>Property/Unit</th>
					<th>Priority</th>
					<th>Date Requested</th>
					<th>Message</th>
					<th>Work Order</th>
					<th>Submitted</th>
				</tr>
			</thead>
			<tbody>
<?php

	$uploaded_files = $SwiftUploader->getProjectFiles('hca_fs_requests', $requests_id);
	
	foreach ($requests_info as $cur_info)
	{
		$page_param['cur_info'] = $td = $submitted = $cur_work_order = $page_param['disabled'] = [];

		$td[] = '<p><strong>'.html_encode($cur_info['pro_name']).'</strong>' . (($cur_info['unit_number'] != '') ? ', unit #: <strong>'.html_encode($cur_info['unit_number']).'</strong>' : '') . (($cur_info['geo_code'] != '') ? ', GL Code: <strong>'.html_encode($cur_info['geo_code']).'</strong>' : '').'</p>';

		$priority1 = (isset($execution_priority[$cur_info['execution_priority']]) ? '<strong>'.$execution_priority[$cur_info['execution_priority']].'</strong>' : 'n/a');
		if ($cur_info['execution_priority'] == 2)
			$priority = '<p><span class="badge bg-warning">'.$priority1.'</span></p>';
		else if ($cur_info['execution_priority'] == 1)
			$priority = '<p><span class="badge bg-primary">'.$priority1.'</span></p>';
		else
			$priority = '<p><span class="badge bg-info">'.$priority1.'</span></p>';


		$submitted[] = '<p><small>'.(($cur_info['created'] > 0) ? format_time($cur_info['created']) : 'N/A').'</small></p>';
		if ($cur_info['requested_by'] != '')
			$submitted[] = '<p><small>by: '.html_encode($cur_info['requested_by']).'</small></p>';

		$btn_actions = (($access && $section != 'completed') ? 'onclick="updateRequest('.$cur_info['id'].')"' : '');
		$btn_image = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>';

		if ($cur_info['work_status'] == 0)
			//$td[] = '<p><button type="button" class="btn btn-warning btn-sm">Pending</button></p>';
			$td[] = '<a class="btn btn-warning btn-sm" href="'.$URL->link('hca_fs_requests', ['new', $cur_info['id']]).'" role="button">Pending</a>';
		else if ($cur_info['work_status'] == 1)
			//$td[] = '<p><button type="button" class="btn btn-primary btn-sm">In Progress</button></p>';
			$td[] = '<a class="btn btn-primary btn-sm text-white" href="'.$URL->link('hca_fs_requests', ['active', $cur_info['id']]).'" role="button">In Progress</a>';
		else if ($cur_info['work_status'] == 2)
			$td[] = '<p><button type="button" class="btn btn-success btn-sm" disabled>Completed</button></p>';
		else if ($cur_info['work_status'] == 5)
			$td[] = '<p><button type="button" class="btn btn-danger btn-sm" disabled>Canceled</button></p>';

		$cur_work_order = array();
		
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
		$css_tr[] = ($id == $cur_info['id']) ? ' anchor' : '';
?>
						<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo implode(' ', $css_tr) ?>">
							<td><?php echo implode("\n", $td) ?></td>
							<td><?php echo $priority ?></td>
							<td><?php echo $start_date ?></td>
							<td><p><?php echo html_encode($cur_info['msg_for_maint']) ?></p></td>
							<td><?php echo implode("\n", $cur_work_order) ?></td>
							<td><?php echo implode("\n", $submitted) ?></td>
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

require SITE_ROOT.'footer.php';