<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Project does not exist or has been removed.');

$HcaMi = new HcaMi;
$work_statuses = array(1 => 'IN PROGRESS', 2 => 'ON HOLD', 3 => 'COMPLETED');
$apt_locations = explode(',', $Config->get('o_hca_5840_locations'));

if (isset($_POST['form_sent']))
{
	$form_data = [
		'mois_report_date' 		=> isset($_POST['mois_report_date']) ? strtotime($_POST['mois_report_date']) : 0,
		'mois_inspection_date' 	=> isset($_POST['mois_inspection_date']) ? strtotime($_POST['mois_inspection_date']) : 0,
		'performed_uid' 		=> isset($_POST['performed_uid']) ? intval($_POST['performed_uid']) : 0,
		'performed_uid2' 		=> isset($_POST['performed_uid2']) ? intval($_POST['performed_uid2']) : 0,

		'leak_type' 			=> isset($_POST['leak_type']) ? intval($_POST['leak_type']) : 0,
		'symptom_type' 			=> isset($_POST['symptom_type']) ? intval($_POST['symptom_type']) : 0,

		'symptoms' 				=> isset($_POST['symptoms']) ? swift_trim($_POST['symptoms']) : '',
		'action' 				=> isset($_POST['action']) ? swift_trim($_POST['action']) : '',

		'services_vendor_id' 	=> isset($_POST['services_vendor_id']) ? intval($_POST['services_vendor_id']) : 0,
		'delivery_equip_date' 	=> isset($_POST['delivery_equip_date']) ? strtotime($_POST['delivery_equip_date']) : 0,
		'pickup_equip_date' 	=> isset($_POST['pickup_equip_date']) ? strtotime($_POST['pickup_equip_date']) : 0,
		'afcc_date' 			=> isset($_POST['afcc_date']) ? strtotime($_POST['afcc_date']) : 0,
		'afcc_comment' 			=> isset($_POST['afcc_comment']) ? swift_trim($_POST['afcc_comment']) : '',

		'asb_vendor' 			=> isset($_POST['asb_vendor']) ? swift_trim($_POST['asb_vendor']) : '',
		'asb_vendor_id' 		=> isset($_POST['asb_vendor_id']) ? intval($_POST['asb_vendor_id']) : 0,
		'asb_test_date' 		=> isset($_POST['asb_test_date']) ? strtotime($_POST['asb_test_date']) : 0,
		'asb_po_number' 		=> isset($_POST['asb_po_number']) ? swift_trim($_POST['asb_po_number']) : '',
		'asb_total_amount' 		=> is_numeric($_POST['asb_total_amount']) ? swift_trim($_POST['asb_total_amount']) : 0,
		'asb_comment' 			=> isset($_POST['asb_comment']) ? swift_trim($_POST['asb_comment']) : '',

		'rem_vendor' 			=> isset($_POST['rem_vendor']) ? swift_trim($_POST['rem_vendor']) : '',
		'rem_vendor_id' 		=> isset($_POST['rem_vendor_id']) ? intval($_POST['rem_vendor_id']) : 0,
		'rem_start_date' 		=> isset($_POST['rem_start_date']) ? strtotime($_POST['rem_start_date']) : 0,
		'rem_end_date' 			=> isset($_POST['rem_end_date']) ? strtotime($_POST['rem_end_date']) : 0,
		'rem_po_number' 		=> isset($_POST['rem_po_number']) ? swift_trim($_POST['rem_po_number']) : '',
		'rem_budget' 			=> isset($_POST['rem_budget']) ? intval($_POST['rem_budget']) : 0,
		'rem_total_amount' 		=> is_numeric($_POST['rem_total_amount']) ? swift_trim($_POST['rem_total_amount']) : 0,
		'rem_comment' 			=> isset($_POST['rem_comment']) ? swift_trim($_POST['rem_comment']) : '',

		'cons_vendor' 			=> isset($_POST['cons_vendor']) ? swift_trim($_POST['cons_vendor']) : '',
		'cons_vendor_id' 		=> isset($_POST['cons_vendor_id']) ? intval($_POST['cons_vendor_id']) : 0,
		'cons_start_date' 		=> isset($_POST['cons_start_date']) ? strtotime($_POST['cons_start_date']) : 0,
		'cons_end_date' 		=> isset($_POST['cons_end_date']) ? strtotime($_POST['cons_end_date']) : 0,
		'cons_po_number' 		=> isset($_POST['cons_po_number']) ? swift_trim($_POST['cons_po_number']) : '',
		'cons_total_amount' 	=> isset($_POST['cons_total_amount']) ? swift_trim($_POST['cons_total_amount']) : 0,
		'cons_comment' 			=> isset($_POST['cons_comment']) ? swift_trim($_POST['cons_comment']) : '',

		'moveout_date'			=> isset($_POST['moveout_date']) ? strtotime($_POST['moveout_date']) : 0,
		'movein_date' 			=> isset($_POST['movein_date']) ? strtotime($_POST['movein_date']) : 0,
		'maintenance_date' 		=> isset($_POST['maintenance_date']) ? strtotime($_POST['maintenance_date']) : 0,
		'maintenance_comment' 	=> isset($_POST['maintenance_comment']) ? swift_trim($_POST['maintenance_comment']) : '',

		//'final_performed_by' 	=> isset($_POST['final_performed_by']) ? swift_trim($_POST['final_performed_by']) : '',
		'final_performed_uid' 	=> isset($_POST['final_performed_uid']) ? intval($_POST['final_performed_uid']) : 0,
		'final_performed_date' 	=> isset($_POST['final_performed_date']) ? strtotime($_POST['final_performed_date']) : 0,
		'job_status' 			=> isset($_POST['job_status']) ? intval($_POST['job_status']) : 0,
		'remarks' 				=> isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : '',
		'time_updated'			=> time(),
		'updated_by'			=> $User->get('id'), // last updated
	];

	if (isset($_POST['unit_id'])) $form_data['unit_id'] = intval($_POST['unit_id']);

	$location = $locations = [];
	foreach ($HcaMi->locations as $key => $value)
	{
		if (isset($_POST['location'][$key]) && $_POST['location'][$key] == '1')
		{
			$location[] = $value;
			$locations[] = $key;
		}
	}

	$form_data['location'] = implode(',', $location);
	$form_data['locations'] = implode(',', $locations);

	if ($form_data['performed_uid'] == 0)
		$Core->add_error('No project manager was selected.');
	if ($form_data['locations'] == '')
		$Core->add_error('No "Locations" was selected.');
	
	$form_total_cost = $form_data['asb_total_amount'] + $form_data['rem_total_amount'] + $form_data['cons_total_amount'];
	
	if (empty($Core->errors))
	{
		$DBLayer->update('hca_5840_projects', $form_data, $id);
		
		$query = [
			'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, un.unit_number, u1.realname AS project_manager1, u2.realname AS project_manager2',
			'FROM'		=> 'hca_5840_projects AS pj',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'sm_property_db AS pt',
					'ON'			=> 'pt.id=pj.property_id'
				],
				[
					'LEFT JOIN'		=> 'sm_property_units AS un',
					'ON'			=> 'un.id=pj.unit_id'
				],
				[
					'LEFT JOIN'		=> 'users AS u1',
					'ON'			=> 'u1.id=pj.performed_uid'
				],
				[
					'LEFT JOIN'		=> 'users AS u2',
					'ON'			=> 'u2.id=pj.performed_uid2'
				],
				//add users proj mng 1 and 2
			],
			'WHERE'		=> 'pj.id='.$id,
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$main_info = $DBLayer->fetch_assoc($result);
		
		// Extension
		$main_info['unit_number'] = ($main_info['unit_number'] == 0 && $main_info['unit'] == '') ? 'Common area' : $main_info['unit_number'];
		$main_info['unit_number'] = ($main_info['unit_number'] == 0 && $main_info['unit'] != '') ? $main_info['unit'] : $main_info['unit_number'];

		$old_moveout_date = isset($_POST['old_moveout_date']) ? intval($_POST['old_moveout_date']) : 0;
		if ($old_moveout_date != $form_data['moveout_date'] && $form_data['moveout_date'] > 0)
		{
			$mail_subject = 'Moisture Inspection';
			$mail_message = 'Move Out date has been changed. See details below.'."\n\n";
			$mail_message .= 'Property: '.$main_info['pro_name']."\n\n";
			$mail_message .= 'Unit #: '.$main_info['unit_number']."\n\n";
			$mail_message .= 'Location: '.$main_info['location']."\n\n";
			$mail_message .= 'Source: '.$main_info['mois_source']."\n\n";
			$mail_message .= 'Symptoms: '.$main_info['symptoms']."\n\n";
			$mail_message .= 'Move-Out Date: '.date('m/d/Y', $main_info['moveout_date'])."\n\n";
			
			$move_out_mailing_list = $User->getNotifyEmails('hca_mi', 5); // Move Out Date changed
			if (!empty($move_out_mailing_list) && $main_info['move_out_notified'] == 0)
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
				$SwiftMailer->isHTML();
				$SwiftMailer->send(implode(',', $move_out_mailing_list), $mail_subject, $mail_message);

				$query = array(
					'UPDATE'	=> 'hca_5840_projects',
					'SET'		=> 'move_out_notified=1',
					'WHERE'		=> 'id='.$id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		

		if ($form_total_cost >= 5000)
		{
			$mail_subject = 'Moisture Inspection';
			$mail_message = 'Hello. The total cost of the project exceeded $ 5,000. See details bellow.'."\n\n";

			$mail_message .= 'Property: '.$main_info['pro_name']."\n\n";
			$mail_message .= 'Unit #: '.$main_info['unit_number']."\n\n";
			$mail_message .= 'Location: '.$main_info['location']."\n\n";
			$mail_message .= 'Report Date: '.date('m/d/Y', $main_info['mois_report_date'])."\n\n";
			$mail_message .= 'Performed by: '.$main_info['project_manager1']."\n\n";
			$mail_message .= 'Inspection Date: '.date('m/d/Y', $main_info['mois_inspection_date'])."\n\n";
			$mail_message .= 'Source: '.$main_info['mois_source']."\n\n";
			$mail_message .= 'Symptoms: '.$main_info['symptoms']."\n\n";
			$mail_message .= 'Action: '.$main_info['action']."\n\n";
			$mail_message .= 'Remarks: '.$main_info['remarks']."\n\n";

			$mail_message .= 'Total cost: '.$form_total_cost."\n\n";
			$mail_message .= 'To view all the details of the project follow this link: '.$URL->link('hca_5840_manage_invoice', $id)."\n\n";
			
			$over_budget_mailing_list = $User->getNotifyEmails('hca_mi', 1); // Over 5000$
			if (!empty($over_budget_mailing_list) && $main_info['over_price_notified'] == 0)
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
				$SwiftMailer->isHTML();
				$SwiftMailer->send(implode(',', $over_budget_mailing_list), $mail_subject, $mail_message);
				
				$query = array(
					'UPDATE'	=> 'hca_5840_projects',
					'SET'		=> 'over_price_notified=1',
					'WHERE'		=> 'id='.$id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		
		$flash_message = 'Project #'.$id.' has been updated';
		$HcaMi->addAction($id, $flash_message);

		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$query = array(
		'UPDATE'	=> 'hca_5840_projects',
		'SET'		=> 'job_status=0',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$flash_message = 'Project #'.$id.' has been removed';
	$HcaMi->addAction($id, $flash_message);

	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_5840_projects', ['active', 0]), $flash_message);
}

$query = [
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, un.unit_number, u1.realname AS created_name, u2.realname AS updated_name',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.created_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.updated_by'
		],
		//add users proj mng 1 and 2
	],
	'WHERE'		=> 'pj.id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

// Temporary solution. Remove after set all unit IDS
$main_info['unit_number'] = ($main_info['unit_number'] != '') ? $main_info['unit_number'] : $main_info['unit'];
$main_info['unit_number'] = ($main_info['unit_number'] != '') ? $main_info['unit_number'] : 'Common area';

if (empty($main_info))
	message('Sorry, this project does not exist or has been removed.');

$User->getUserAccess('hca_mi');
$project_user_ids = $User->getUserAccessIDS();
$project_user_ids[] = $User->get('id');
$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.id > 2',
	'ORDER BY'	=> 'u.realname',
);
if (!empty($project_user_ids))
	$query['WHERE'] = 'u.id IN ('.implode(',', $project_user_ids).')';

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$query = array(
	'SELECT'	=> 'v.*, f.group_id, f.enabled',
	'FROM'		=> 'sm_vendors AS v',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_5840_vendors_filter AS f',
			'ON'			=> 'v.id=f.vendor_id'
		],
	],
	'WHERE'		=> 'v.hca_5840=1',
	'ORDER BY'	=> 'v.vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

$asb_total_amount = is_numeric($main_info['asb_total_amount']) ? number_format($main_info['asb_total_amount'], 2, '.', '') : 0;
$rem_total_amount = is_numeric($main_info['rem_total_amount']) ? number_format($main_info['rem_total_amount'], 2, '.', '') : 0;
$cons_total_amount = is_numeric($main_info['cons_total_amount']) ? number_format($main_info['cons_total_amount'], 2, '.', '') : 0;
$total_cost = $asb_total_amount + $rem_total_amount + $cons_total_amount;

if ($total_cost >= 5000)
	$Core->add_warning('The total cost of the project exceeded $ 5,000.00');

$Core->set_page_id('hca_mi_manage_project', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<style>
.filled input, .filled select, .filled textarea{background: #e2ffe2;}
.unfilled input, .unfilled select, .unfilled textarea{background: #ffe6e2;}
</style>

<form method="post" accept-charset="utf-8" action="" id="main_form">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header d-flex justify-content-between">
			<h6 class="card-title mb-0">Property information</h6>
			<div>
				<a href="<?=$URL->link('hca_5840_manage_files', $id)?>" class="badge bg-primary text-white">Files</a>
				<a href="<?=$URL->link('hca_5840_manage_invoice', $id)?>" class="badge bg-primary text-white">Invoice</a>
				<a href="<?=$URL->link('hca_5840_manage_appendixb', $id)?>" class="badge bg-primary text-white">+ Appendix-B</a>
			</div>
		</div>
		<div class="card-body">

<?php 
if ($main_info['job_status'] == 0)
	echo '<div class="alert alert-danger" role="alert">This project was removed. To activate the project, change the status and save the changes.</div>';
?>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="property_id">Property name</label>
					<h5 class=""><?php echo html_encode($main_info['pro_name']) ?></h5>
				</div>
				<div class="col-md-2 mb-3">
					<label class="form-label">Unit number</label>
					<h5 class="<?=($main_info['unit_id'] == 0 ? 'text-danger' : '')?>"><?php echo html_encode($main_info['unit_number']) ?></h5>
				</div>

<?php if ($User->is_admin()): ?>
				<div class="col-md-2 mb-3">
					<label class="form-label" for="fld_unit_id">Setup Unit #</label>
					<select name="unit_id" required class="form-select" id="fld_unit_id">
						<option value="0" selected>Common area</option>
<?php
$query = array(
	'SELECT'	=> 'u.id, u.unit_number',
	'FROM'		=> 'sm_property_units AS u',
	'WHERE'		=> 'u.property_id='.$main_info['property_id'],
	'ORDER BY'	=> 'u.id',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$units_info = $json_array = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$units_info[] = $row;
}

foreach ($units_info as $cur_info)
{
	if (isset($_POST['unit_id']) && $_POST['unit_id'] == $cur_info['id'] || $main_info['unit_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['unit_number']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['unit_number']).'</option>'."\n";
}
?>
					</select>
				</div>

				<div class="col-sm-4 d-flex justify-content-end">
					<div>
						<div>
							<span class="text-muted">Created by:</span>
							<span class="text-muted fw-bold"><?php echo html_encode($main_info['created_name']) ?></span>
						</div>
						<div>
							<span class="text-muted">Created on</span>
							<span class="text-muted fw-bold"><?php echo format_time($main_info['time_created'], 1) ?></span>
						</div>
						<div>
							<span class="text-muted">Updated by:</span>
							<span class="text-muted fw-bold"><?php echo html_encode($main_info['updated_name']) ?></span>
						</div>
						<div>
							<span class="text-muted">Last updated:</span>
							<span class="text-muted fw-bold"><?php echo format_time($main_info['time_updated'], 0) ?></span>
						</div>
					</div>
				</div>

<?php endif; ?>

			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_location">Locations</label>
					<a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="To add locations, select from the dropdown list."><i class="fas fa-info-circle"></i></a>
					<select class="form-select form-select-sm" id="fld_location" onchange="addLocation()">
<?php
echo '<option value="0" selected >Select one</option>'."\n";
foreach ($HcaMi->locations as $key => $value)
{
	echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<div id="locations"></div>
<?php
$locations = explode(',', $main_info['locations']);
$old_locations = explode(',', str_replace(' ', '', $main_info['location']));

foreach ($HcaMi->locations as $key => $value) // ! save by key
//foreach($apt_locations as $key => $value) // not use
{
	if ($main_info['locations'] != '')
	{
		if (in_array($key, $locations))
		{
?>
				<div class="form-check-inline" id="location_<?=$key?>"><div class="mb-1 d-flex btn" style="border-color: #6c757d;">
					<input type="hidden" name="location[<?=$key?>]" value="1">
					<div class="toast-body fw-bold py-0 pe-1"><?=$value?></div>
						<button type="button" class="btn-close" aria-label="Close" onclick="return confirm('Are you sure you want to delete it?')?clearLocation(<?=$key?>):'';"></button>
					</div>
				</div>
<?php
		}
	}
	else
	{
		$v = str_replace(' ', '', $value);
		if (in_array($v, $old_locations))
		{
?>
			<div class="form-check-inline" id="location_<?=$key?>"><div class="mb-1 d-flex btn" style="border-color: #6c757d;">
				<input type="hidden" name="location[<?=$key?>]" value="1">
				<div class="toast-body fw-bold py-0 pe-1"><?=$value?></div>
					<button type="button" class="btn-close" aria-label="Close" onclick="return confirm('Are you sure you want to delete it?')?clearLocation(<?=$key?>):'';"></button>
				</div>
			</div>
<?php
		}
	}
}	
?>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Moisture Inspection</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_mois_report_date">Date Reported</label>
					<input type="date" name="mois_report_date" id="fld_mois_report_date" class="form-control" value="<?php echo sm_date_input($main_info['mois_report_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('fld_mois_report_date').value=''">Click to clear date</label>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_mois_inspection_date">Date of Inspection</label>
					<input type="date" name="mois_inspection_date" id="fld_mois_inspection_date" class="form-control" value="<?php echo sm_date_input($main_info['mois_inspection_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('fld_mois_inspection_date').value=''">Click to clear date</label>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_performed_uid">Project Manager</label>
					<select name="performed_uid" required class="form-select" id="fld_performed_uid">
<?php
echo '<option value="0" selected disabled>Select one</option>'."\n";
foreach ($users_info as $cur_info)
{
	if ($main_info['performed_uid'] == $cur_info['id'] || $main_info['mois_performed_by'] == $cur_info['realname'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>'."\n";
}
?>
					</select>
<?php if ($main_info['mois_performed_by'] != '') : ?>
					<label class="text-muted"><?php echo html_encode($main_info['mois_performed_by']) ?></label>
<?php endif; ?>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_performed_uid2">Project Manager 2</label>
					<select name="performed_uid2" required class="form-select" id="fld_performed_uid2">
<?php
echo '<option value="0" selected>Select one</option>'."\n";
foreach ($users_info as $cur_info)
{
	if ($main_info['performed_uid2'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_leak_type">Source of Moisture</label>
					<select name="leak_type" required class="form-select" id="fld_leak_type">
						<option value="0" selected>Select one</option>
<?php
foreach ($HcaMi->leak_types as $key => $value)
{
	if ($main_info['leak_type'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
					<p class="text-muted"><?php echo  html_encode($main_info['mois_source']) ?></p>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_symptom_type">Symptoms</label>
					<select name="symptom_type" required class="form-select" id="fld_symptom_type">
						<option value="0" selected>Select one</option>
<?php
foreach ($HcaMi->symptoms as $key => $value)
{
	if ($main_info['symptom_type'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_symptoms">Comments</label>
				<textarea id="fld_symptoms" class="form-control" name="symptoms" placeholder="Leave your comment"><?php echo html_encode($main_info['symptoms']) ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_action">Action</label>
				<textarea id="fld_action" class="form-control" name="action" placeholder="Leave your comment"><?php echo html_encode($main_info['action']) ?></textarea>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Services</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_services_vendor_id">Vendor</label>
					<a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="To add new Vendors go to: 'Setup' -> 'Vendors' -> 'Suggest a Vendor'"><i class="fas fa-info-circle"></i></a>
					<select name="services_vendor_id" class="form-select" id="fld_services_vendor_id">
<?php
echo '<option value="0" selected>Select one</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['group_id'] == 1 && $cur_info['enabled'] == 1)
	{
		if ($main_info['services_vendor_id'] == $cur_info['id'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	}
}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_afcc_date">Carpet/Vinyl Date</label>
					<input type="date" name="afcc_date" id="fld_afcc_date" class="form-control" value="<?php echo sm_date_input($main_info['afcc_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('fld_afcc_date').value=''">Click to clear date</label>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_delivery_equip_date">Delivery Equip. Date</label>
					<input type="date" name="delivery_equip_date" id="fld_delivery_equip_date" class="form-control" value="<?php echo sm_date_input($main_info['delivery_equip_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('fld_delivery_equip_date').value=''">Click to clear date</label>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_pickup_equip_date">PickUp of Equip. Date</label>
					<input type="date" name="pickup_equip_date" id="fld_pickup_equip_date" class="form-control" value="<?php echo sm_date_input($main_info['pickup_equip_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('fld_pickup_equip_date').value=''">Click to clear date</label>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_afcc_comment">Action</label>
				<textarea id="fld_afcc_comment" class="form-control" name="afcc_comment" placeholder="Leave your comment"><?php echo html_encode($main_info['afcc_comment']) ?></textarea>
			</div>
		</div>
			
		<div class="card-header">
			<h6 class="card-title mb-0">Scope of Work/Asbestos</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_asb_vendor_id">Vendor</label>
					<a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="To add new Vendors go to: 'Setup' -> 'Vendors' -> 'Suggest a Vendor'"><i class="fas fa-info-circle"></i></a>
					<select name="asb_vendor_id" class="form-select" id="fld_asb_vendor_id">
<?php
$asb_vendor_selected = false;
echo '<option value="" selected>Select one</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['group_id'] == 2 && $cur_info['enabled'] == 1)
	{
		if ($main_info['asb_vendor'] == $cur_info['vendor_name'] || $main_info['asb_vendor_id'] == $cur_info['id'])
		{
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
			$asb_vendor_selected = true;
		}
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	}
}
?>
					</select>
<?php if (!$asb_vendor_selected && $main_info['asb_vendor'] != '') : ?>
					<input type="text" name="asb_vendor" id="asb_vendor" class="form-control" value="<?php echo html_encode($main_info['asb_vendor']) ?>">
<?php endif; ?>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_asb_test_date">Test Date</label>
					<input type="date" name="asb_test_date" id="fld_asb_test_date" class="form-control" value="<?php echo sm_date_input($main_info['asb_test_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('fld_asb_test_date').value=''">Click to clear date</label>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_asb_po_number">PO Number</label>
					<input type="text" name="asb_po_number" class="form-control" id="fld_asb_po_number" value="<?php echo html_encode($main_info['asb_po_number']) ?>">
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_asb_total_amount">Total Amount</label>
					<input type="text" name="asb_total_amount" class="form-control" id="fld_asb_total_amount" value="<?php echo $asb_total_amount ?>">
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_asb_comment">Work Performed</label>
				<textarea class="form-control" id="fld_asb_comment" name="asb_comment" placeholder="Leave your comment"><?php echo html_encode($main_info['asb_comment']) ?></textarea>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Remediation Dates</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_rem_vendor_id">Vendor</label>
					<a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="To add new Vendors go to: 'Setup' -> 'Vendors' -> 'Suggest a Vendor'"><i class="fas fa-info-circle"></i></a>
					<select name="rem_vendor_id" class="form-select" id="fld_rem_vendor_id">
<?php
$rem_vendor_selected = false;
echo '<option value="0" selected>Select one</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['group_id'] == 3 && $cur_info['enabled'] == 1)
	{
		if ($main_info['rem_vendor'] == $cur_info['vendor_name'] || $main_info['rem_vendor_id'] == $cur_info['id']){
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
			$rem_vendor_selected = true;
		} else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	}
}
?>
					</select>
<?php if (!$rem_vendor_selected && $main_info['rem_vendor'] != '') : ?>
					<input type="text" name="rem_vendor" id="rem_vendor" class="form-control" value="<?php echo html_encode($main_info['rem_vendor']) ?>">
<?php endif; ?>

				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="rem_start_date">Start Date</label>
					<input type="date" name="rem_start_date" id="rem_start_date" class="form-control" value="<?php echo sm_date_input($main_info['rem_start_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('rem_start_date').value=''">Click to clear date</label>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="rem_end_date">End Date</label>
					<input type="date" name="rem_end_date" id="rem_end_date" class="form-control" value="<?php echo sm_date_input($main_info['rem_end_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('rem_end_date').value=''">Click to clear date</label>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="rem_po_number">PO Number</label>
					<input type="text" name="rem_po_number" id="rem_po_number" class="form-control" value="<?php echo html_encode($main_info['rem_po_number']) ?>">
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="rem_total_amount">Total Amount</label>
					<input type="text" name="rem_total_amount" id="rem_total_amount" class="form-control" value="<?php echo $rem_total_amount ?>">
				</div>
			</div>
			
			<div class="mb-3">
				<label for="rem_comment">Work Performed</label>
				<textarea name="rem_comment" id="rem_comment" class="form-control" placeholder="Leave your comment"><?php echo html_encode($main_info['rem_comment']) ?></textarea>
			</div>
		</div>
			
		<div class="card-header">
			<h6 class="card-title mb-0">Constructions Dates</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_cons_vendor_id">Vendor</label>
					<a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="To add new Vendors go to: 'Setup' -> 'Vendors' -> 'Suggest a Vendor'"><i class="fas fa-info-circle"></i></a>
					<select name="cons_vendor_id" class="form-select" id="fld_cons_vendor_id">
<?php
$cons_vendor_selected = false;
echo '<option value="" selected>Select one</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['group_id'] == 4 && $cur_info['enabled'] == 1)
	{
		if ($main_info['cons_vendor'] == $cur_info['vendor_name'] || $main_info['cons_vendor_id'] == $cur_info['id']){
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
			$cons_vendor_selected = true;
		} else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	}
}
?>
					</select>
<?php if (!$cons_vendor_selected && $main_info['cons_vendor'] != '') : ?>
					<input type="text" name="cons_vendor" id="cons_vendor" class="form-control" value="<?php echo html_encode($main_info['cons_vendor']) ?>">
<?php endif; ?>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="cons_start_date">Start Date</label>
					<input type="date" name="cons_start_date" id="cons_start_date" class="form-control" value="<?php echo sm_date_input($main_info['cons_start_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('cons_start_date').value=''">Click to clear date</label>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="cons_end_date">End Date</label>
					<input type="date" name="cons_end_date" id="cons_end_date" class="form-control" value="<?php echo sm_date_input($main_info['cons_end_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('cons_end_date').value=''">Click to clear date</label>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="cons_po_number">PO Number</label>
					<input type="text" name="cons_po_number" id="cons_po_number" class="form-control" value="<?php echo html_encode($main_info['cons_po_number']) ?>">
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="cons_total_amount">Total Amount</label>
					<input type="text" name="cons_total_amount" id="cons_total_amount" class="form-control" value="<?php echo $cons_total_amount ?>">
				</div>
			</div>
		
			<div class="mb-3">
				<label class="form-label" for="cons_comment">Work Performed</label>
				<textarea id="cons_comment" name="cons_comment" class="form-control" placeholder="Leave your comment"><?php echo html_encode($main_info['cons_comment']) ?></textarea>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Relocation</h6>
		</div>
		<div class="card-body">

			<div class="mb-3">
				<label class="form-label">Total Cost</label>
				<h5><?php echo gen_number_format($total_cost, 2) ?></h5>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="moveout_date">Move-Out Date</label>
					<input type="hidden" name="old_moveout_date" value="<?php echo $main_info['moveout_date'] ?>">
					<input type="date" name="moveout_date" class="form-control" id="moveout_date" value="<?php echo sm_date_input($main_info['moveout_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('moveout_date').value=''">Click to clear date</label>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="movein_date">Move-In Date</label>
					<input type="date" name="movein_date" class="form-control" id="movein_date" value="<?php echo sm_date_input($main_info['movein_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('movein_date').value=''">Click to clear date</label>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="maintenance_date">Maintenance Date</label>
					<input type="date" name="maintenance_date" class="form-control" id="maintenance_date" value="<?php echo sm_date_input($main_info['maintenance_date']) ?>" onclick="this.showPicker()">
					<label class="text-danger" onclick="document.getElementById('maintenance_date').value=''">Click to clear date</label>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="maintenance_comment">Comment</label>
				<textarea id="maintenance_comment" name="maintenance_comment" class="form-control" id="maintenance_comment" placeholder="Leave your comment"><?php echo html_encode($main_info['maintenance_comment']) ?></textarea>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_final_performed_uid">Performed by</label>
					<select name="final_performed_uid" class="form-select" id="fld_final_performed_uid">
<?php
echo '<option value="0" selected>Select one</option>'."\n";
foreach ($users_info as $cur_info)
{
	if ($main_info['final_performed_uid'] == $cur_info['id'] || $main_info['final_performed_by'] == $cur_info['realname'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="final_performed_date">Performed Date</label>
					<input type="date" name="final_performed_date" class="form-control" id="final_performed_date" value="<?php echo sm_date_input($main_info['final_performed_date']) ?>" onclick="this.showPicker()">
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_remarks">Remarks</label>
				<textarea id="fld_remarks" name="remarks" class="form-control" placeholder="Leave your comment"><?php echo html_encode($main_info['remarks']) ?></textarea>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label">Job Status</label>
					<select name="job_status" class="form-select" required>
<?php
if ($main_info['job_status'] == 0)
	echo '<option value="0" selected>REMOVED</option>'."\n";

foreach ($work_statuses as $key => $status)
{
	if ($main_info['job_status'] == $key)
		echo '<option value="'.$key.'" selected>'.html_encode($status).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($status).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-3">
<?php if ($User->checkPermissions('hca_mi', 7)): ?>
				<button type="submit" name="form_sent" class="btn btn-primary">Update Project</button>
<?php endif; ?>

<?php if ($User->checkPermissions('hca_mi', 8)): ?>
				<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">Delete Project</button>
<?php endif; ?>
			</div>

<?php if ($User->checkPermissions('hca_mi', 7)): ?>
			<div class="toast" id="toastButtonActions" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
				<div class="toast-header badge-info fw-bold border">
					<strong class="me-auto">You have unsaved changes on this page.</strong>
					<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
				</div>
				<div class="toast-body badge-info d-flex justify-content-around">
					<button type="submit" name="form_sent" class="btn btn-sm btn-primary">Save changes</button>
					<a href="<?=$URL->link('hca_5840_projects', ['active', 0])?>" class="btn btn-sm btn-secondary text-white ">Cancel</a>
				</div>
			</div>
<?php endif; ?>

		</div>
	</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function()
{
	var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
	var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl)
	});

	$("#main_form").change(function() {
		$("#toastButtonActions").addClass('show position-fixed bottom-0 end-0');
	});

}, false);

function addLocation(){
	//get all added

	var val = $("#fld_location").val();
	var text = $("#fld_location").find("option:selected").text();
	
	var html = '<div class="form-check-inline" id="location_'+val+'"><div class="mb-1 d-flex btn" style="border-color: #6c757d;">';
	html += '<input type="hidden" name="location['+val+']" value="1">';
	html += '<div class="toast-body fw-bold py-0 pe-1">'+text+'</div>';
	html += '<button type="button" class="btn-close" aria-label="Close" onclick="return confirm(\'Are you sure you want to delete it?\')?clearLocation('+val+'):\'\';"></button>';
	html += '</div></div>';

	if (val > 0)
		$( "#locations" ).after(html);
}
function clearLocation(id){
	$("div").remove("#location_"+id);
	$("#toastButtonActions").addClass('show position-fixed bottom-0 end-0');
}
</script>

<?php
require SITE_ROOT.'footer.php';
