<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';
$search_by_status = isset($_GET['search_by_status']) ? intval($_GET['search_by_status']) : 0;
$search_by_project_manager = isset($_GET['project_manager']) ? swift_trim($_GET['project_manager']) : '';
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';

$access = ($User->is_admmod() || $User->checkAccess('hca_sp', 3)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$work_statuses = [
	0 => 'ALL STATUSES', 
	1 => 'WORK STARTED', 
	2 => 'BID', 
	3 => 'WISH LIST', 
	4 => 'ON HOLD', 
	5 => 'COMPLETED'
];
$admin_approved_array = array(0 => 'NOT APPROVED', 1 => 'APPROVED', 2 => 'NOT AVAILABLE');

if (isset($_POST['send_email']))
{
	$mail_subject = isset($_POST['mail_subject']) ? swift_trim($_POST['mail_subject']) : '';
	$email_list = isset($_POST['email_list']) ? swift_trim($_POST['email_list']) : '';
	$mail_message = isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : '';
	
	if ($email_list != '' && $mail_message != '')
	{
		$SwiftMailer = new SwiftMailer;
		$SwiftMailer->send($email_list, $mail_subject, $mail_message);

		// Add flash message
		$flash_message = 'Email has been sent to '.$email_list;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	else
		$Core->add_error('Your message is empty or there are no sender addresses.');
}

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'sm_special_projects_records',
	'WHERE'		=> 'work_status!=0',
);
//SEARCH BY
if (!empty($search_by_project_manager)) {
	$query['WHERE'] .= ' AND (project_manager_id=\''.$DBLayer->escape($search_by_project_manager).'\' OR second_manager_id=\''.$DBLayer->escape($search_by_project_manager).'\')';
}
if ($search_by_property_id > 0)
	$query['WHERE'] .= ' AND property_id='.$search_by_property_id;
if ($search_by_status > 0)
	$query['WHERE'] .= ' AND work_status='.$search_by_status;
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u1.realname AS first_manager, u2.realname AS second_manager',
	'FROM'		=> 'sm_special_projects_records AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.project_manager_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.second_manager_id'
		),
	),
	//'WHERE'		=> 'pj.work_status!=5 AND pj.work_status!=3 AND pj.work_status!=0',
	'WHERE'		=> 'pj.work_status!=0',
	'ORDER BY'	=> 'pt.pro_name, pj.project_manager',
	'LIMIT'		=> $PagesNavigator->limit()
);
if (!empty($search_by_project_manager)) {
	$query['WHERE'] .= ' AND (pj.project_manager_id=\''.$DBLayer->escape($search_by_project_manager).'\' OR pj.second_manager_id=\''.$DBLayer->escape($search_by_project_manager).'\')';
}
if ($search_by_property_id > 0)
	$query['WHERE'] .= ' AND pj.property_id='.$search_by_property_id;
if ($search_by_status > 0)
	$query['WHERE'] .= ' AND pj.work_status='.$search_by_status;
	
//SORT BY
if ($sort_by == 2) $query['ORDER BY'] = 'pj.project_manager, pt.pro_name';

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$projects_info = $projects_ids = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$projects_info[] = $fetch_assoc;
	$projects_ids[] = $fetch_assoc['id'];
}
$PagesNavigator->num_items($projects_info);

$follow_up_info = array();
$query = array(
	'SELECT'	=> 'id, project_id, e_date, e_message',
	'FROM'		=> 'sm_special_projects_events',
);
if (!empty($projects_ids))
{
	$query['WHERE'] = 'project_id IN ('.implode(',', $projects_ids).')';
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$follow_up_info[] = $fetch_assoc;
	}
}

$query = array(
	'SELECT'	=> 'id, pro_name',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$project_managers = $User->getUserAccess('hca_sp', 14, 1);

$Core->set_page_id('sm_special_projects_report', 'hca_sp');
require SITE_ROOT.'header.php';
?>

<style>

</style>
	
	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<input type="hidden" name="hash" value="<?php echo $hash ?>" />
			<div class="row">
				<div class="col pe-0">
					<select name="property_id" class="form-select-sm">
						<option value="">Display All Properties</option>
<?php
foreach ($property_info as $val) {
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected="selected">'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="project_manager" class="form-select-sm">
						<option value="">All Managers</option>
<?php 
foreach ($project_managers as $key => $val) {
	if($search_by_project_manager == $val['id'])
		echo '<option value="'.$val['id'].'" selected="selected">'.$val['realname'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['realname'].'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="search_by_status" class="form-select-sm">
<?php
foreach ($work_statuses as $key => $val) {
	//if (!in_array($key, array(3,5))) {
	if ($key == $search_by_status) {
		echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
	} else {
		echo '<option value="'.$key.'">'.$val.'</option>';
	}
	//}
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="sort_by" class="form-select-sm">
<?php
$sort_by_arr = array(1 => 'Sort By: Property Name', 2 => 'Sort By: Project Manager');
foreach ($sort_by_arr as $key => $val) {
	if ($key == $sort_by) {
		echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
	} else {
		echo '<option value="'.$key.'">'.$val.'</option>';
	}
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				</div>
			</div>
		</form>
	</nav>
<?php
if (!empty($projects_info))
{
?>
	<table class="table table-sm table-striped table-bordered">
		<thead>
			<tr>
				<th class="th1">Property</th>
				<th>Project number</th>
				<th>Project description</th>
				<th>Action Date</th>
				<th>Project manager</th>
				<th>Action</th>
				<th>Satrt Date</th>
				<th>End Date</th>
				<th>Budget</th>
				<th>Remarks</th>
				<th>Work status</th>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($projects_info as $cur_info) 
	{
		$follow_up_dates = array();
		foreach ($follow_up_info as $key => $val) {
			if ($cur_info['id'] == $val['project_id']) {
				$follow_up_dates[] = '<p>'.format_time($val['e_date']).': '.$val['e_message'].'</p>';
			}
		}
		
		$work_status_title = isset($work_statuses[$cur_info['work_status']]) ? $work_statuses[$cur_info['work_status']] : 'Error';
		if ($cur_info['work_status'] == 1)
			$work_status = '<p><span class="badge bg-primary">'.$work_status_title.'</span></p>';
		else if ($cur_info['work_status'] == 2)
			$work_status = '<p><span class="badge bg-info">'.$work_status_title.'</span></p>';
		else if ($cur_info['work_status'] == 3)
			$work_status = '<p><span class="badge bg-warning">'.$work_status_title.'</span></p>';
		else if ($cur_info['work_status'] == 4)
			$work_status = '<p><span class="badge bg-secondary">'.$work_status_title.'</span></p>';
		else if ($cur_info['work_status'] == 5)
			$work_status = '<p><span class="badge bg-success">'.$work_status_title.'</span></p>';
		else
			$work_status = '<p><span class="badge bg-danger">'.$work_status_title.'</span></p>';

		$second_manager = isset($cur_info['second_manager']) ? '<p>'.$cur_info['second_manager'].'</p>' : '';
?>
			<tr>
				<td class="td1">
					<p><?php echo $cur_info['pro_name'] ?></p>
					<p>(Scale: <?php echo ($cur_info['project_scale'] == 1 ? 'Major' : 'Minor') ?>)</p>
				</td>
				<td><a href="<?php echo $URL->link('sm_special_projects_manage', $cur_info['id']) ?>"><?php echo $cur_info['project_number'] ?></a></td>
				<td class="description"><?php echo $cur_info['project_desc'] ?></td>
				<td><?php echo !empty($cur_info['action_date']) ? date('m/d/Y', $cur_info['action_date']) : 'N/A' ?></td>
				<td class="proj-mngr">
					<p><?php echo $cur_info['project_manager'] ?></p>
					<?php echo $second_manager ?>
				</td>
				<td class="description"><?php echo implode("\n", $follow_up_dates) ?></td>
				<td><?php echo !empty($cur_info['start_date']) ? date('m/d/Y', $cur_info['start_date']) : 'N/A' ?></td>
				<td><?php echo !empty($cur_info['end_date']) ? date('m/d/Y', $cur_info['end_date']) : 'N/A' ?></td>
				<td class="budget">$<?php echo gen_number_format($cur_info['budget'], 2) ?></td>
				<td class="description"><?php echo $cur_info['remarks'] ?></td>
				<td><?php echo $work_status ?></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
<?php
} else {
?>	
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php	
}
require SITE_ROOT.'footer.php';
