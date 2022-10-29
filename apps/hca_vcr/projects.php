<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vcr', 1)) ? true : false; // view
$access3 = ($User->checkAccess('hca_vcr', 3)) ? true : false; // edit
$access4 = ($User->checkAccess('hca_vcr', 4)) ? true : false; // delete
if (!$access)
	message($lang_common['No permission']);

require 'functions_generate_pdf.php';
require 'class_get_vendors.php';
require 'class_hca_vcr_pdf.php';
require 'class_HCAVCR.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$row_id = isset($_GET['row']) ? intval($_GET['row']) : 0;
$property_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';
$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';
$section = isset($_GET['section']) ? swift_trim($_GET['section']) : 'active';
$statuses = array(0 => 'ACTIVE', 1 => 'COMPLETED', 2 => 'ON HOLD', 5 => 'DELETE');

//hca_vcr_check_expired_final_walk();

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.group_id, u.email, u.hca_vcr_access, u.hca_vcr_notify',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.id > 2',
	'ORDER BY'	=> 'realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vcr_managers_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	if ($row['hca_vcr_access'] > 0)
		$vcr_managers_info[$row['id']] = $row;
}

$week_of = isset($_GET['week_of']) && $_GET['week_of'] != '' ? strtotime($_GET['week_of']) : 0;
$first_day_of_this_week = ($week_of > 0) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$first_day_of_next_week = ($week_of > 0) ? strtotime('Monday next week', $week_of) : strtotime('Monday next week');
$yesterday = strtotime(date('Y-m-d\T00:00:00', time())) - 3601; // If daylight changes

$search_by_property_id = isset($_GET['property_id']) ? swift_trim($_GET['property_id']) : '';
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by = isset($_GET['search_by']) ? intval($_GET['search_by']) : 0;

$query = array(
	'SELECT'	=> 'id, pro_name, manager_id',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$query = array(
	'SELECT'	=> 'COUNT(pj.id)',
	'FROM'		=> 'hca_vcr_projects AS pj',
);
if ($section == 'expired') $query['WHERE'] = 'pj.status=0 AND pj.walk_date > 0 AND pj.walk_date < '.time();
else if ($section == 'on_hold') $query['WHERE'] = 'pj.status=2';
else if ($section == 'completed') $query['WHERE'] = 'pj.status=1';
else if ($section == 'recycle') $query['WHERE'] = 'pj.status=5';
else if ($section == 'reports') $query['WHERE'] = 'status > -1';
else $query['WHERE'] = 'status=0';
if ($search_by_property_id > 0) {
	$query['WHERE'] .= ' AND pj.property_id=\''.$DBLayer->escape($search_by_property_id).'\'';
}
if (!empty($search_by_unit_number)) {
	$search_by_unit2 = '%'.$search_by_unit_number.'%';
	$query['WHERE'] .= ' AND pj.unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
if ($search_by > 0)
{
	if ($search_by == 1)
	{
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.move_out_date >='.$first_day_of_this_week.' AND pj.move_out_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.move_out_date > 0';
	} else if ($search_by == 3) {
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.move_in_date >='.$first_day_of_this_week.' AND pj.move_in_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.move_in_date > 0';
	} else if ($search_by == 7) {
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.pre_walk_date >='.$first_day_of_this_week.' AND pj.pre_walk_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.pre_walk_date > 0';
	} else if ($search_by == 11) {
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.walk_date >='.$first_day_of_this_week.' AND pj.walk_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.walk_date > 0';
	} else if ($search_by == 13) {
		if ($week_of > 0)
		{
			$query['WHERE'] .= ' AND pj.walk_date >='.$first_day_of_this_week.' AND pj.walk_date < '.$first_day_of_next_week;
		} else {
			$query['WHERE'] .= ' AND pj.walk_date > 0';
		}
		$query['WHERE'] .= ' AND (pj.pre_walk_name=\''.$DBLayer->escape($User->get('realname')).'\' OR pj.walk=\''.$DBLayer->escape($User->get('realname')).'\')';
	} else if ($search_by == 14) {
		if ($week_of > 0)
		{
			$query['WHERE'] .= ' AND pj.walk_date >='.$yesterday.' AND pj.walk_date < '.$first_day_of_next_week;
		} else {
			$query['WHERE'] .= ' AND pj.walk_date > '.$yesterday;
		}
		$query['WHERE'] .= ' AND (pj.pre_walk_name=\''.$DBLayer->escape($User->get('realname')).'\' OR pj.walk=\''.$DBLayer->escape($User->get('realname')).'\')';
	}
}
else if ($week_of > 0) 
	$query['WHERE'] .= ' AND pj.move_out_date >='.$first_day_of_this_week.' AND pj.move_out_date < '.$first_day_of_next_week;

if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE'] .= ' AND pj.property_id='.$User->get('sm_pm_property_id');

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// GET DATA
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name',
	'FROM'		=> 'hca_vcr_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'ORDER BY'	=> 'pj.property_name, LENGTH(pj.unit_number), pj.unit_number',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if ($section == 'expired') $query['WHERE'] = 'pj.status=0 AND pj.walk_date > 0 AND pj.walk_date < '.time();
else if ($section == 'on_hold') $query['WHERE'] = 'pj.status=2';
else if ($section == 'completed') $query['WHERE'] = 'pj.status=1';
else if ($section == 'recycle') $query['WHERE'] = 'pj.status=5';
else if ($section == 'reports') $query['WHERE'] = 'status > -1';
else $query['WHERE'] = 'status=0';

if ($search_by_property_id > 0) {
	$query['WHERE'] .= ' AND pj.property_id=\''.$DBLayer->escape($search_by_property_id).'\'';
}
if (!empty($search_by_unit_number)) {
	$search_by_unit2 = '%'.$search_by_unit_number.'%';
	$query['WHERE'] .= ' AND pj.unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
if ($search_by > 0)
{
	if ($search_by == 1)
	{
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.move_out_date >='.$first_day_of_this_week.' AND pj.move_out_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.move_out_date > 0';
		$query['ORDER BY'] = 'pj.move_out_date';
	} else if ($search_by == 3) {
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.move_in_date >='.$first_day_of_this_week.' AND pj.move_in_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.move_in_date > 0';
		$query['ORDER BY'] = 'pj.move_in_date';
	} else if ($search_by == 7) {
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.pre_walk_date >='.$first_day_of_this_week.' AND pj.pre_walk_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.pre_walk_date > 0';
		$query['ORDER BY'] = 'pj.pre_walk_date';
	} else if ($search_by == 11) {
		if ($week_of > 0)
			$query['WHERE'] .= ' AND pj.walk_date >='.$first_day_of_this_week.' AND pj.walk_date < '.$first_day_of_next_week;
		else
			$query['WHERE'] .= ' AND pj.walk_date > 0';
		$query['ORDER BY'] = 'pj.walk_date';
	} else if ($search_by == 13) {
		if ($week_of > 0)
		{
			$query['WHERE'] .= ' AND pj.walk_date >='.$first_day_of_this_week.' AND pj.walk_date < '.$first_day_of_next_week;
		} else {
			$query['WHERE'] .= ' AND pj.walk_date > 0';
		}
		$query['WHERE'] .= ' AND (pj.pre_walk_name=\''.$DBLayer->escape($User->get('realname')).'\' OR pj.walk=\''.$DBLayer->escape($User->get('realname')).'\')';
		$query['ORDER BY'] = 'pj.walk_date';
	} else if ($search_by == 14) {
		if ($week_of > 0)
		{
			$query['WHERE'] .= ' AND pj.walk_date >='.$yesterday.' AND pj.walk_date < '.$first_day_of_next_week;
		} else {
			$query['WHERE'] .= ' AND pj.walk_date > '.$yesterday;
		}
		$query['WHERE'] .= ' AND (pj.pre_walk_name=\''.$DBLayer->escape($User->get('realname')).'\' OR pj.walk=\''.$DBLayer->escape($User->get('realname')).'\')';
		$query['ORDER BY'] = 'pj.walk_date';
	}
}
else if ($week_of > 0) 
	$query['WHERE'] .= ' AND pj.move_out_date >='.$first_day_of_this_week.' AND pj.move_out_date < '.$first_day_of_next_week;

if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE'] .= ' AND pj.property_id='.$User->get('sm_pm_property_id');

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = $fs_ids = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$projects_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

if (isset($_POST['update_modal']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$form_data = [];

	if (isset($_POST['unit_number'])) $form_data['unit_number'] = swift_trim($_POST['unit_number']);
	if (isset($_POST['unit_size'])) $form_data['unit_size'] = swift_trim($_POST['unit_size']);
	if (isset($_POST['status'])) $form_data['status'] = intval($_POST['status']);

	if (isset($_POST['move_out_date'])) $form_data['move_out_date'] = strtotime($_POST['move_out_date']);
	if (isset($_POST['move_out_comment'])) $form_data['move_out_comment'] = swift_trim($_POST['move_out_comment']);

	if (isset($_POST['pre_walk_date'])) $form_data['pre_walk_date'] = strtotime($_POST['pre_walk_date']);
	if (isset($_POST['pre_walk_name'])) $form_data['pre_walk_name'] = swift_trim($_POST['pre_walk_name']);
	if (isset($_POST['pre_walk_comment'])) $form_data['pre_walk_comment'] = swift_trim($_POST['pre_walk_comment']);

	if (isset($_POST['walk_date'])) $form_data['walk_date'] = strtotime($_POST['walk_date']);
	if (isset($_POST['walk'])) $form_data['walk'] = swift_trim($_POST['walk']);
	if (isset($_POST['walk_comment'])) $form_data['walk_comment'] = swift_trim($_POST['walk_comment']);

	if (isset($_POST['move_in_date'])) $form_data['move_in_date'] = strtotime($_POST['move_in_date']);
	if (isset($_POST['move_in_comment'])) $form_data['move_in_comment'] = swift_trim($_POST['move_in_comment']);

	if ($project_id > 0 && !empty($form_data))
	{
		$query = array(
			'SELECT'	=> 'pj.*, pt.pro_name',
			'FROM'		=> 'hca_vcr_projects AS pj',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'sm_property_db AS pt',
					'ON'			=> 'pt.id=pj.property_id'
				)
			),
			'WHERE'		=> 'pj.id='.$project_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$old_project_info = $DBLayer->fetch_assoc($result);

		$DBLayer->update_values('hca_vcr_projects', $project_id, $form_data);
	
		if (isset($form_data['move_out_date']) && ($old_project_info['move_out_date'] != $form_data['move_out_date']) && ($old_project_info['move_out_date'] > 0))
		{
			$HCAVCRProjects = new HCAVCRProjects;
			$emails = $HCAVCRProjects->getEmails(2); // When move out date was changed

			$mail_message = [];
			$mail_message[] = 'This email is to inform you that Move Out Date has been changed.'."\n";
			$mail_message[] = 'Property name: '.$old_project_info['pro_name'];
			$mail_message[] = 'Unit #: '.$old_project_info['unit_number'];
			$mail_message[] = 'Previous Move Out date: '.format_time($old_project_info['move_out_date'], 1);
			$mail_message[] = 'Current Move Out date: '.format_time($form_data['move_out_date'], 1);
			$mail_message[] = 'Submitted by: '.$User->get('realname');

			if (!empty($emails))
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send(implode(',', $emails), 'VCR: Move Out date was changed', implode("\n", $mail_message));
			}
		}
		else if (isset($form_data['status']) && ($old_project_info['status'] != $form_data['status']))
		{
			$HCAVCRProjects = new HCAVCRProjects;
			$emails = $HCAVCRProjects->getEmails(1); // When move out date was changed

			$mail_message = [];
			$mail_message[] = 'This email is to inform you that Move Out Date has been changed.'."\n";
			$mail_message[] = 'Property name: '.$old_project_info['pro_name'];
			$mail_message[] = 'Unit #: '.$old_project_info['unit_number'];
			$mail_message[] = 'Previous status: '.(isset($statuses[$old_project_info['status']]) ? $statuses[$old_project_info['status']] : 'n/a');
			$mail_message[] = 'Current status: '.(isset($statuses[$form_data['status']]) ? $statuses[$form_data['status']] : 'n/a');
			$mail_message[] = 'Submitted by: '.$User->get('realname');

			if (!empty($emails))
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send(implode(',', $emails), 'VCR: Move Out date was changed', implode("\n", $mail_message));
			}
		}

		$flash_message = 'Project #'.$project_id.' updated.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_projects', ['active', $project_id]), $flash_message);
	}	
}

else if (isset($_POST['update_vendor']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
	$in_house = isset($_POST['in_house']) ? intval($_POST['in_house']) : 0;
	
	$HCAVCRProjects = new HCAVCRProjects;

	if ($invoice_id > 0)
	{
		$HCAVCRProjects->updateVendor();
	}
	else
	{
		$HCAVCRProjects->insertVendor();
	}

	$flash_message = 'Project #'.$project_id.' updated';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_vcr_projects', ['active', $project_id]), $flash_message);
}

else if (isset($_POST['complete_project']))
{
	$id = intval(key($_POST['complete_project']));

	if ($id > 0)
	{
		$DBLayer->update('hca_vcr_projects', ['status' => 1], $id);

		$flash_message = 'Project completed';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_projects', ['completed', $id]), $flash_message);
	}
}
else if (isset($_POST['active_project']))
{
	$id = intval(key($_POST['active_project']));

	if ($id > 0)
	{
		$DBLayer->update('hca_vcr_projects', ['status' => 0], $id);

		$flash_message = 'Project is active now';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_projects', ['active', $id]), $flash_message);
	}
}
else if (isset($_POST['remove_project']))
{
	$id = intval(key($_POST['remove_project']));

	if ($id > 0)
	{
		$DBLayer->update('hca_vcr_projects', ['status' => 5], $id);

		$flash_message = 'Project removed';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_projects', ['recycle', $id]), $flash_message);
	}
}
//
else if (isset($_POST['delete_project']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

	if ($project_id > 0)
	{
		$DBLayer->delete('hca_vcr_projects', $project_id);

		$flash_message = 'Project deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_projects', ['active', 0]), $flash_message);
	}
}

$vendor_schedule_info = $fs_request_ids = [];
// GET UPLOADED FILES
if (!empty($projects_ids))
{
	// GET SCHEDULED VENDORS
	$query = array(
		'SELECT'	=> 'i.*, v.vendor_name, u.realname',
		'FROM'		=> 'hca_vcr_invoices AS i',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'sm_vendors AS v',
				'ON'		=> 'v.id=i.vendor_id'
			),
			array(
				'LEFT JOIN'	=> 'hca_fs_requests AS r',
				'ON'		=> 'r.id=i.fs_request_id'
			),
			array(
				'LEFT JOIN'	=> 'users AS u',
				'ON'		=> 'u.id=r.employee_id'
			),
		),
		'WHERE'		=> 'i.project_id IN ('.implode(',', $projects_ids).') AND i.project_name=\''.$DBLayer->escape('hca_vcr_projects').'\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendor_schedule_info[] = $row;
	}
	
	$VCRVendors->AddSchedule($vendor_schedule_info);
}

$hca_vcr_dupe = $HCAVCR->search_dupe_projects();
if (!empty($hca_vcr_dupe)) {
	foreach($hca_vcr_dupe as $cur_dupe)
		$Core->add_warning($cur_dupe);
}

if ($section == 'expired')
	$Core->set_page_id('hca_vcr_projects_expired', 'hca_vcr');
else if ($section == 'on_hold')
	$Core->set_page_id('hca_vcr_projects_on_hold', 'hca_vcr');
else if ($section == 'completed')
	$Core->set_page_id('hca_vcr_projects_completed', 'hca_vcr');
else if ($section == 'recycle')
	$Core->set_page_id('hca_vcr_projects_recycle', 'hca_vcr');
else if ($section == 'reports')
	$Core->set_page_id('hca_vcr_projects_reports', 'hca_vcr');
else
	$Core->set_page_id('hca_vcr_projects_active', 'hca_vcr');

require SITE_ROOT.'header.php';
?>

<nav class="navbar container-fluid search-box">
	<form method="get" accept-charset="utf-8" action="">
		<input type="hidden" name="section" value="<?php echo $section ?>">
		<div class="row">
			<div class="col pe-0">
				<input type="date" name="week_of" value="<?php echo sm_date_input($week_of) ?>" class="form-control">
			</div>

<?php if ($User->get('sm_pm_property_id') == 0): ?>
			<div class="col pe-0">
				<select name="property_id" class="form-select">
					<option value="">Select property</option>
<?php
	foreach ($property_info as $val)
	{
		if ($search_by_property_id == $val['id'])
			echo '<option value="'.$val['id'].'" selected="selected">'.html_encode($val['pro_name']).'</option>';
		else
			echo '<option value="'.$val['id'].'">'.html_encode($val['pro_name']).'</option>';
	}
?>
				</select>
			</div>
<?php endif; ?>

			<div class="col pe-0">
				<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Enter unit #" class="form-control">
			</div>
			<div class="col pe-0">
				<select name="search_by" class="form-select">
<?php
$search_by_array = array(
	0 => 'Display All Projects',
	1 => 'Move-Out Date', 
//	2 => 'Move-Out Date (Old First)', 
	3 => 'Move-In Date', 
//	4 => 'Move-In Date (Old First)',
//	5 => 'Pre Walk Inspector',
//	6 => 'Final Walk Inspector (A-Z)',
	7 => 'Pre Walk Date',
//	8 => 'Final Walk Date (1-31)',
//	9 => 'Final Walk Inspector',
//	10 => 'Final Walk Inspections',
	11 => 'Final Walk Date',
//	12 => 'Final Walk Date',
//	13 => 'All My Projects',
//	14 => 'My Upcoming Projects',
);
	foreach ($search_by_array as $key => $val)
	{
				if($search_by == $key)
					echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
				else
					echo '<option value="'.$key.'">'.$val.'</option>';
	}
?>
				</select>
			</div>
			<div class="col pe-0">
				<button type="submit" class="btn btn-outline-success">Search</button>
			</div>
		</div>
	</form>
</nav>

<?php
	if (!empty($main_info))
	{
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-sm table-striped table-bordered">
		<thead>
			<tr>
				<th class="th1">Property</th>
				<th class="min-100">Move-Out</th>
				<th class="min-100">Pre Walk</th>
				<th class="min-100">Maintenance</th>
				<th class="min-100">Urine Scan</th>
				<th class="min-100">Painter</th>
				<th class="min-100">Cleaning Service</th>
				<th class="min-100">Vinyl</th>
				<th class="min-100">Carpet</th>
				<th class="min-100">Carpet Clean</th>
				<th class="min-100">Refinish</th>
				<th class="min-100">Pest Control</th>
				<th class="min-100">Final Walk</th>
				<th class="min-100">Move-In</th>
			</tr>
		</thead>
		<tbody>
<?php
		foreach ($main_info as $cur_info)
		{
			if ($access3)
			{
				$Core->add_dropdown_item('<a href="'.$URL->link('hca_vcr_manage_project', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');
				$Core->add_dropdown_item('<a href="'.$URL->link('hca_vcr_manage_files', $cur_info['id']).'"><i class="fas fa-file"></i> Project Files</a>');
				$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectInfo('.$cur_info['id'].', 1)"><i class="fas fa-check-square"></i> Change Status</a>');
			}
				
			$td = [];
			//if ($access)
			//	$td['property_info'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectInfo('.$cur_info['id'].', 1)"></i></p>';
			$td['property_info'][] = '<p>'.html_encode($cur_info['pro_name']).'</p>';
			$td['property_info'][] = '<p>Unit #: '.html_encode($cur_info['unit_number']).'</p>';
			$td['property_info'][] = '<p>Size: '.html_encode($cur_info['unit_size']).'</p>';
			$td['property_info'][] = '<span class="float-end">'.$Core->get_dropdown_menu($cur_info['id']).'</span>';

			//<i class="fas fa-pencil-alt"></i>
			if ($access)
				$td['move_out'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectInfo('.$cur_info['id'].', 2)"></i></p>';
			$td['move_out'][] = '<p class="fw-bold text-darkblue">'.format_time($cur_info['move_out_date'], 1).'</p>';
			$td['move_out'][] = '<p>'.html_encode($cur_info['move_out_comment']).'</p>';

			if ($access)
				$td['pre_walk'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectInfo('.$cur_info['id'].', 3)"></i></p>';
			$td['pre_walk'][] = '<p class="fw-bold text-darkblue">'.format_time($cur_info['pre_walk_date'], 1).'</p>';
			$td['pre_walk'][] = '<p class="fw-bold text-darkred">'.html_encode($cur_info['pre_walk_name']).'</p>';
			$td['pre_walk'][] = '<p>'.html_encode($cur_info['pre_walk_comment']).'</p>';

			//<!--MAINTENANCE-->
			if ($access)
				$td['maintenance'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 8)"></i></p>';
			$td['maintenance'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 8);

			//<!--URINE SCAN-->
			if ($access)
				$td['urine_scan'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 1)"></i></p>';
			$td['urine_scan'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 1);

			//<!--PAINTER SERVICE-->
			if ($access)
				$td['painter'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 2)"></i></p>';
			$td['painter'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 2);

			//<!--CLEANING SERVICE-->
			if ($access)
				$td['cleaning'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 6)"></i></p>';
			$td['cleaning'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 6);

			//<!--VINYL SERVICE-->
			if ($access)
				$td['vinyl'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 3)"></i></p>';
			$td['vinyl'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 3);

			//<!--CARPET SERVICE-->
			if ($access)
				$td['carpet'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 4)"></i></p>';
			$td['carpet'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 4);

			//<!--CARPET CLEAN-->
			if ($access)
				$td['carpet_clean'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 9)"></i></p>';
			$td['carpet_clean'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 9);

			//<!--REFINISH-->
			if ($access)
				$td['refinish'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 7)"></i></p>';
			$td['refinish'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 7);

			//<!--PEST CONTROL-->
			if ($access)
				$td['pest_control'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectVendor('.$cur_info['id'].', 5)"></i></p>';
			$td['pest_control'][] = $VCRVendors->GetVendorInfo($cur_info['id'], 5);

			if ($access)
				$td['final_walk'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectInfo('.$cur_info['id'].', 11)"></i></p>';
			$td['final_walk'][] = '<p class="fw-bold text-darkblue">'.format_time($cur_info['walk_date'], 1).'</p>';
			$td['final_walk'][] = '<p class="fw-bold text-darkred">'.html_encode($cur_info['walk']).'</p>';
			$td['final_walk'][] = '<p style="white-space:pre-wrap;overflow:overlay;height:100px;">'.html_encode($cur_info['walk_comment']).'</p>';

			if ($access)
				$td['move_in'][] = '<p class="float-end"><i class="fas fa-edit fa-lg text-secondary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectInfo('.$cur_info['id'].', 12)"></i></p>';
			$td['move_in'][] = '<p class="fw-bold text-darkblue">'.format_time($cur_info['move_in_date'], 1).'</p>';
			$td['move_in'][] = '<p>'.html_encode($cur_info['move_in_comment']).'</p>';

			$css_alert_final_walk = ($FormatDateTime->is_today($cur_info['walk_date']) && $User->get('realname') == $cur_info['walk']) ? 'alert-date' : '';		
?>
	<tr class="<?php echo ($cur_info['id'] == $id) ? 'anchor' : '' ?>">
		<td class="td1"><?php echo implode("\n", $td['property_info']) ?></td>
		<td><?php echo implode("\n", $td['move_out']) ?></td><!--MOVE_OUT-->
		<td><?php echo implode("\n", $td['pre_walk']) ?></td><!--PRE WALK-->
		<td><?php echo implode("\n", $td['maintenance']) ?></td><!--MAINTENANCE-->
		<td><?php echo implode("\n", $td['urine_scan']) ?></td><!--URINE SCAN-->
		<td><?php echo implode("\n", $td['painter']) ?></td><!--PAINTER SERVICE-->
		<td><?php echo implode("\n", $td['cleaning']) ?></td><!--CLEANING SERVICE-->
		<td><?php echo implode("\n", $td['vinyl']) ?></td><!--VINYL SERVICE-->
		<td><?php echo implode("\n", $td['carpet']) ?></td><!--CARPET SERVICE-->
		<td><?php echo implode("\n", $td['carpet_clean']) ?></td><!--CARPET CLEAN-->
		<td><?php echo implode("\n", $td['refinish']) ?></td><!--REFINISH-->
		<td><?php echo implode("\n", $td['pest_control']) ?></td><!--PEST CONTROL-->
		<td><?php echo implode("\n", $td['final_walk']) ?></td><!--FINAL WALK-->
		<td><?php echo implode("\n", $td['move_in']) ?></td><!--MOVE_IN-->
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

<div class="alert alert-warning" role="alert">You have no items on this page or not found within your search criteria.</div>

<?php
	}
?>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Edit information</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="closeModalWindow()"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
					<textarea class="form-control"></textarea>
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function editProjectInfo(pid, type) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_vcr_ajax_get_acive_info')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_vcr_ajax_get_acive_info') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({pid:pid,type:type,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.msg-section').empty().html('<div class="alert alert-danger" role="alert">Error: No data received.</div>');
		}
	});
}
function editProjectVendor(pid, type) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_vcr_ajax_get_acive_vendors')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_vcr_ajax_get_acive_vendors') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({pid:pid,type:type,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.msg-section').empty().html('<div class="alert alert-danger" role="alert">Error: No data received.</div>');
		}
	});
}
function closeModalWindow(){
	$('.modal .modal-title').empty().html('');
	$('.modal .modal-body').empty().html('');
	$('.modal .modal-footer').empty().html('');
}
function inHousePainter(v){
	if (v==1) {
		$("#painter_vendors").css('display', 'none');
		$("#painter_vendors select").val(0);
	} else {
		$("#painter_vendors").css('display', 'block');
	}
}
</script>

<?php
require SITE_ROOT.'footer.php';