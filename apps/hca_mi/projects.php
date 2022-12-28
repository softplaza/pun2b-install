<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$permission2 = ($User->checkPermissions('hca_mi', 2)) ? true : false; // view
$permission4 = ($User->checkPermissions('hca_mi', 4)) ? true : false; // files
$permission5 = ($User->checkPermissions('hca_mi', 5)) ? true : false; // appendix-b
$permission6 = ($User->checkPermissions('hca_mi', 6)) ? true : false; // Send project info to email
$permission9 = ($User->checkPermissions('hca_mi', 9)) ? true : false; // Follow Up Dates

$Moisture = new Moisture;
$HcaMi = new HcaMi;

$section = isset($_GET['section']) ? $_GET['section'] : 'active';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_performed_uid = isset($_GET['performed_uid']) ? swift_trim($_GET['performed_uid']) : 0;

/*
// NEED TO FIX MANAGER FORM
if (isset($_POST['send_email']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$subject = isset($_POST['subject']) ? swift_trim($_POST['subject']) : '';
	$email_list = isset($_POST['email_list']) ? swift_trim($_POST['email_list']) : '';
	$mail_message = isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : '';
	
	$mailing_fields = isset($_POST['hca_5840_mailing_fields']) ? array_keys($_POST['hca_5840_mailing_fields']) : array();

	$time_now = time();
	if ($project_id > 0)
	{
		$query = array(
			'SELECT'	=> 'pj.*, p.manager_email, p.pro_name, un.unit_number, u1.realname AS performed_by',
			'FROM'		=> 'hca_5840_projects AS pj',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'p.id=pj.property_id'
				],
				[
					'LEFT JOIN'		=> 'sm_property_units AS un',
					'ON'			=> 'un.id=pj.unit_id'
				],
				[
					'LEFT JOIN'		=> 'users AS u1',
					'ON'			=> 'u1.id=pj.performed_uid'
				],
			],
			'WHERE'		=> 'pj.id='.$project_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$project_info = $DBLayer->fetch_assoc($result);
		
		$mail_subject = ($subject != '') ? $subject : 'HCA: Moisture Inspection';
		$mail_message = $mail_message."\n\n";
		
		if (!empty($project_info))
		{
			$mail_message .= 'Property: '.$project_info['pro_name']."\n";
			if ($project_info['unit_number'] != '')
				$mail_message .= 'Unit #: '.$project_info['unit_number']."\n";
			$mail_message .= 'Location: '.$project_info['location']."\n\n";

			$mail_message .= 'Report Date: '.format_time($project_info['mois_report_date'], 1)."\n";
			$mail_message .= 'Performed by: '.$project_info['performed_by']."\n";
			$mail_message .= 'Inspection Date: '.format_time($project_info['mois_inspection_date'], 1)."\n\n";

			$mail_message .= 'Source: '.$project_info['mois_source']."\n\n";
			$mail_message .= 'Symptoms: '.$project_info['symptoms']."\n\n";
			$mail_message .= 'Action: '.$project_info['action']."\n\n";
			$mail_message .= 'Remarks: '.$project_info['remarks']."\n\n";
			
			if (!empty($email_list))
			{
				$form_data = array();
				$form_data['project_id'] = $project_id;
				$form_data['msg_for_manager'] = $mail_message;
				$form_data['mailed_time'] = $time_now;
				$form_data['link_hash'] = random_key(5, true, true);;
				
				$new_pid = $DBLayer->insert_values('hca_5840_forms', $form_data);
				
				if ($new_pid)
				{
					$mail_message .= 'Follow this link to submit the form: '.$URL->link('hca_5840_form', array($new_pid, $form_data['link_hash']))."\n\n";
					
					$SwiftMailer = new SwiftMailer;
					$SwiftMailer->isHTML();
					$SwiftMailer->send($email_list, $mail_subject, $mail_message);

					$query = array(
						'UPDATE'	=> 'hca_5840_projects',
						'SET'		=> 'email_status=1',
						'WHERE'		=> 'id='.$project_id
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				
					$flash_message = 'Email has been sent to '.$project_info['manager_email'];
					$FlashMessenger->add_info($flash_message);
					redirect($URL->link('hca_5840_projects', array($section, $project_id)).'#row'.$project_id, $flash_message);
				}
			}
		}
	}
}

else 
*/
if (isset($_POST['update_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$event_datetime = isset($_POST['time']) ? strtotime($_POST['time']) : 0;
	$event_date = date('Ymd', $event_datetime);
	$event_message = isset($_POST['message']) ? swift_trim($_POST['message']) : '';
	
	$form_data = [
		'time'		=> isset($_POST['time']) ? strtotime($_POST['time']) : 0,
		'message'	=> isset($_POST['message']) ? swift_trim($_POST['message']) : ''
	];

	if ($event_message == '')
		$Core->add_error('Event message can not by empty. Write your message.');
	if ($event_datetime == 0)
		$Core->add_error('Incorrect Date of Event. Set the date for the event.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'SELECT'	=> 'id',
			'FROM'		=> 'sm_calendar_dates',
			'WHERE'		=> 'year_month_day='.$event_date.' AND poster_id='.$User->get('id'),
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$calendar_dates_info = $DBLayer->fetch_assoc($result);
		
		$calendar_date_id = (isset($calendar_dates_info['id']) && $calendar_dates_info['id'] > 0) ? $calendar_dates_info['id'] : 0;
		
		if ($calendar_date_id == 0)
		{
			$query = array(
				'INSERT'	=> 'year_month_day, poster_id, num_events',
				'INTO'		=> 'sm_calendar_dates',
				'VALUES'	=> 
					'\''.$DBLayer->escape($event_date).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\'1\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$calendar_date_id = $DBLayer->insert_id();
		}
		
		if ($event_id > 0)
		{
			$query = array(
				'UPDATE'	=> 'sm_calendar_events',
				'SET'		=> 'time=\''.$DBLayer->escape($event_datetime).'\', date=\''.$DBLayer->escape($event_date).'\', message=\''.$DBLayer->escape($event_message).'\', date_id='.$calendar_date_id,
				'WHERE'		=> 'id='.$event_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			//Count and Upodate
			$query = array(
				'SELECT'	=> 'COUNT(id)',
				'FROM'		=> 'sm_calendar_events',
				'WHERE'		=> 'date_id='.$calendar_date_id,
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$num_events = $DBLayer->result($result);

			$query = array(
				'UPDATE'	=> 'sm_calendar_dates',
				'SET'		=> 'num_events='.$num_events,
				'WHERE'		=> 'id='.$calendar_date_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$flash_message = 'Event #'.$event_id.' has been updated.';
		}
		else
		{
			$query = array(
				'INSERT'	=> 'project_name, project_id, poster_id, time, date, subject, message, date_id',
				'INTO'		=> 'sm_calendar_events',
				'VALUES'	=> '\'hca_5840\',
					\''.$DBLayer->escape($project_id).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\''.$DBLayer->escape($event_datetime).'\',
					\''.$DBLayer->escape($event_date).'\',
					\'Moisture Inspection\',
					\''.$DBLayer->escape($event_message).'\',
					\''.$DBLayer->escape($calendar_date_id).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$event_id = $DBLayer->insert_id();
			
			//Count and Upodate
			$query = array(
				'SELECT'	=> 'COUNT(id)',
				'FROM'		=> 'sm_calendar_events',
				'WHERE'		=> 'date_id='.$calendar_date_id,
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$num_events = $DBLayer->result($result);

			$query = array(
				'UPDATE'	=> 'sm_calendar_dates',
				'SET'		=> 'num_events='.$num_events,
				'WHERE'		=> 'id='.$calendar_date_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$flash_message = 'Event #'.$event_id.' has been created.';
		}
		
		$HcaMi->addAction($project_id, $flash_message);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_5840_projects', array($section, $project_id)).'#row'.$project_id, $flash_message);
	}
	
}

else if (isset($_POST['delete_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

	if ($event_id > 0)
	{
		$query = array(
			'DELETE'	=> 'sm_calendar_events',
			'WHERE'		=> 'id='.$event_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
		$flash_message = 'Event #'.$event_id.' has been deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_5840_projects', array($section, $project_id)).'#row'.$project_id, $flash_message);
	}
}

$search_query = [];

if ($section == 'missing')
	$search_query[] = '(pj.performed_uid=0 OR pj.unit_id=0)';
else if ($section == 'on_hold') 
	$search_query[] = 'pj.job_status=2';
else if ($section == 'completed')
	$search_query[] = 'pj.job_status=3';
else
	$search_query[] = 'pj.job_status=1';

// SEARCH BY SECTION //
if ($search_by_property_id > 0)
	$search_query[] = 'pj.property_id='.$search_by_property_id;
if (!empty($search_by_unit))
{
	$search_by_unit2 = '%'.$search_by_unit.'%';
	$search_query[] = 'un.unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
if ($search_by_performed_uid > 0)
	$search_query[] = '(pj.performed_uid='.$search_by_performed_uid.' OR pj.performed_uid2='.$search_by_performed_uid.' OR pj.final_performed_uid='.$search_by_performed_uid.')';

$query = array(
	'SELECT'	=> 'COUNT(pj.id)',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
	],
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, un.unit_number, u1.realname AS project_manager, u2.realname AS project_manager2, u3.realname AS final_performed_by, v1.vendor_name AS services_vendor_name, v2.vendor_name AS asb_vendor_name, v3.vendor_name AS rem_vendor_name, v4.vendor_name AS cons_vendor_name',
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
		// Get Project Managers
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.performed_uid2'
		],
		[
			'LEFT JOIN'		=> 'users AS u3',
			'ON'			=> 'u3.id=pj.final_performed_uid'
		],
		// Get Vendors
		[
			'LEFT JOIN'		=> 'sm_vendors AS v1',
			'ON'			=> 'v1.id=pj.services_vendor_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v2',
			'ON'			=> 'v2.id=pj.asb_vendor_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v3',
			'ON'			=> 'v3.id=pj.rem_vendor_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v4',
			'ON'			=> 'v4.id=pj.cons_vendor_id'
		],
	],
	'ORDER BY'	=> 'pt.pro_name, LENGTH(pj.unit_number)',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$row['unit_number'] = ($row['unit_number'] == 0 && $row['unit'] == '') ? 'Common area' : $row['unit_number'];

	$main_info[] = $row;
	$projects_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'hca_5840_projects\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$uploader_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$events_info = [];
if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.time, e.message',
		'FROM'		=> 'sm_calendar_events AS e',
		'WHERE'		=> 'e.project_id IN('.implode(',', $projects_ids).') AND project_name=\'hca_5840\'',
		'ORDER BY'	=> 'e.time DESC'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $fetch_assoc;
	}
}

$upcoming_work = $Moisture->get_upcoming_work();

if ($upcoming_work > 0)
	$Core->add_warning('You have work scheduled for today: <strong>'.$upcoming_work.'</strong>');

if ($section == 'missing')
	$Core->set_page_id('hca_mi_projects_missing', 'hca_mi');
else if ($section == 'on_hold')
	$Core->set_page_id('hca_mi_projects_on_hold', 'hca_mi');
else if ($section == 'completed')
	$Core->set_page_id('hca_mi_projects_completed', 'hca_mi');
else if ($section == 'roof_leaks')
	$Core->set_page_id('hca_mi_projects_roof_leaks', 'hca_mi');
else
	$Core->set_page_id('hca_mi_projects_active', 'hca_mi');

require SITE_ROOT.'header.php';
?>

<style>
.date {min-width: 100px}
.date p {font-weight: bold}
.vendor-name{font-weight:bold;color:#a319fa;}
.comment {min-width: 200px}
.location{overflow-wrap: anywhere;}
.events{min-width: 200px;}
.events strong{background: green;color: white;border-radius: 7px;padding: 1px 10px;cursor: pointer;font-weight: bold;}
.events p{margin-bottom: .2em;}
.selected td{background: #b0f0ff;}
</style>

<nav class="navbar container-fluid search-box">
	<form method="get" accept-charset="utf-8" action="">
		<input name="section" type="hidden" value="<?php echo $section ?>"/>
		<div class="row">
			<div class="col-md-auto pe-0 mb-1">
				<select name="property_id" class="form-select form-select-sm">
					<option value="">All Properties</option>
<?php 
foreach ($property_info as $val)
{
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
} 
?>
				</select>
			</div>
			<div class="col-md-auto pe-0 mb-1">
				<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Enter Unit #" class="form-control form-control-sm"/>
			</div>
			<div class="col-md-auto pe-0 mb-1">
				<select name="performed_uid" class="form-select form-select-sm">
					<option value="0">All Project Managers</option>
<?php
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

foreach ($users_info as $user_info)
{
	if ($search_by_performed_uid == $user_info['id'])
		echo '<option value="'.$user_info['id'].'" selected>'.html_encode($user_info['realname']).'</option>';
	else
		echo '<option value="'.$user_info['id'].'">'.html_encode($user_info['realname']).'</option>';
}
?>
				</select>
			</div>
			<div class="col-md-auto pe-0 mb-1">
				<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				<a href="<?php echo $URL->link('hca_5840_projects', ['active', 0]) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
			</div>
		</div>
	</form>
</nav>

<?php
if (!empty($main_info)) 
{
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="th1"></th>
			<th colspan="5"></th>
			<th colspan="3"><p class="text-danger">SERVICES</p></th>
			<th></th>
			<th><p class="text-danger">ASBESTOS_TEST</p></th>
			<th colspan="3"><p class="text-danger">REMEDIATION</p></th>
			<th colspan="3"><p class="text-danger">CONSTRUCTION</p></th>
			<th colspan="6"><p class="text-danger">RELOCATION</p></th>
		</tr>
		<tr>
			<th class="th1">Property info</th>
			<th>Date Reported</th>
			<th>Date of inspection</th>
			<th>Source of moisture</th>
			<th>Symptoms</th>
			<th>Action</th>
			<th>Delivery Equip.</th>
			<th>PickUp of Equip.</th>
			<th>Carpet/Vinyl Date</th>
			<th>Follow Up Dates</th>
			<th><p>PO_Number</p><p>Date</p></th>
			<th><p>Vendor</p><p>PO Number</p></th>
			<th>Start Date</th>
			<th>End Date</th>
			<th><p>Vendor</p><p>PO Number</p></th>
			<th>Start Date</th>
			<th>End Date</th>
			<th>Total Cost</th>
			<th>MoveOut Date</th>
			<th>MoveIn Date</th>
			<th>Maintenance Date</th>
			<th>Finall Walk</th>
			<th>Remarks</th>
		</tr>
	</thead>
	<tbody>
<?php

	foreach ($main_info as $cur_info)
	{
		$td = [];

		$cur_info['unit_number'] = ($cur_info['unit_number'] != '') ? '<span>'.html_encode($cur_info['unit_number']).'</span>' : '<span class="text-danger">'.html_encode($cur_info['unit']).'</span>';

		$td['property_info'][] = '<p>'.html_encode($cur_info['pro_name']).', '.$cur_info['unit_number'].'</p>';
		$td['property_info'][] = '<p>'.str_replace(',', ', ', $cur_info['location']).'</p>';
		$td['property_info'][] = ($permission4 && in_array($cur_info['id'], $uploader_info)) ? '<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'" class="btn btn-sm btn-success text-white">Files</a>' : '';

		$td['performed_by'][] = ($cur_info['mois_inspection_date'] > 0) ? '<p class="fw-bold">'.format_time($cur_info['mois_inspection_date'], 1).'</p>' : '';
		$td['performed_by'][] = ($cur_info['project_manager'] != '' ? '<p class="text-primary fw-bold">'.html_encode($cur_info['project_manager']).'</p>' : '<p class="text-muted fw-bold">'.html_encode($cur_info['mois_performed_by']).'</p>');
		$td['performed_by'][] = ($cur_info['project_manager2'] != '' ? '<p class="text-primary">'.html_encode($cur_info['project_manager2']).'</p>' : '');

		$td['mois_source'][] = (isset($HcaMi->leak_types[$cur_info['leak_type']]) ? '<p class="fw-bold">'.$HcaMi->leak_types[$cur_info['leak_type']].'</p>' : '');
		$td['mois_source'][] = '<p>'.html_encode($cur_info['mois_source']).'</p>';

		$td['symptoms'][] = (isset($HcaMi->symptoms[$cur_info['symptom_type']]) ? '<p class="fw-bold">'.$HcaMi->symptoms[$cur_info['symptom_type']].'</p>' : '');
		$td['symptoms'][] = '<p>'.html_encode($cur_info['symptoms']).'</p>';

		$td['delivery_equip'][] = ($cur_info['delivery_equip_date'] > 0) ? '<p class="fw-bold">'.format_time($cur_info['delivery_equip_date'], 1).'</p>' : '';
		$td['delivery_equip'][] = '<p>'.html_encode($cur_info['delivery_equip_comment']).'</p>';

		$td['afcc'][] = ($cur_info['afcc_date'] > 0) ? '<p class="fw-bold">'.format_time($cur_info['afcc_date'], 1).'</p>' : '';
		$td['afcc'][] = '<p class="fw-bold text-primary">'.html_encode($cur_info['services_vendor_name']).'</p>';
		$td['afcc'][] = '<p>'.html_encode($cur_info['afcc_comment']).'</p>';

		$follow_up_dates = ($permission9) ? $Moisture->get_events($events_info, $cur_info['id']) : [];
		$td['follow_up'][] = !empty($follow_up_dates) ? implode("\n", $follow_up_dates) : '';
		if ($permission9)
			$td['follow_up'][] = '<p style="float:right" onclick="getEvent('.$cur_info['id'].', 0)" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-plus-circle fa-lg"></i></p>';
		
		$td['asb'][] = ($cur_info['asb_test_date'] > 0) ? '<p class="fw-bold">'.format_time($cur_info['asb_test_date'], 1).'</p>' : '';
		$td['asb'][] = ($cur_info['asb_vendor_name'] != '' ? '<p class="fw-bold text-primary">'.html_encode($cur_info['asb_vendor_name']).'</p>' : '<p class="fw-bold">'.html_encode($cur_info['asb_vendor']).'</p>');
		$td['asb'][] = '<p>'.html_encode($cur_info['asb_po_number']).'</p>';
		$td['asb'][] = '<p>'.html_encode($cur_info['asb_comment']).'</p>';

		$td['rem'][] = ($cur_info['rem_vendor_name'] != '' ? '<p class="fw-bold text-primary">'.html_encode($cur_info['rem_vendor_name']).'</p>' : '<p class="fw-bold">'.html_encode($cur_info['rem_vendor']).'</p>');
		$td['rem'][] = '<p>'.html_encode($cur_info['rem_po_number']).'</p>';
		$td['rem'][] = '<p>'.html_encode($cur_info['rem_comment']).'</p>';

		//$td['rem_dates'][] = ($cur_info['rem_start_date'] > 0) ? '<p>Start: '.format_time($cur_info['rem_start_date'], 1).'</p>' : '';
		//$td['rem_dates'][] = ($cur_info['rem_end_date'] > 0) ? '<p>End: '.format_time($cur_info['rem_end_date'], 1).'</p>' : '';
		
		//$td['email_status'] = ($cur_info['email_status'] == 1) ? '<strong style="color:green" onclick="emailWindow('.$cur_info['id'].')">Mailed</strong>' : '<strong style="color:blue" onclick="emailWindow('.$cur_info['id'].')">Send Email</strong>';
		
		$td['cons'][] = ($cur_info['cons_vendor_name'] != '' ? '<p class="fw-bold text-primary">'.html_encode($cur_info['cons_vendor_name']).'</p>' : '<p class="fw-bold">'.html_encode($cur_info['cons_vendor']).'</p>');
		$td['cons'][] = '<p>'.html_encode($cur_info['cons_po_number']).'</p>';
		$td['cons'][] = '<p>'.html_encode($cur_info['cons_comment']).'</p>';

		//$td['cons_dates'][] = ($cur_info['cons_start_date'] > 0) ? '<p>Start: '.format_time($cur_info['cons_start_date'], 1).'</p>' : '';
		//$td['cons_dates'][] = ($cur_info['cons_end_date'] > 0) ? '<p>End: '.format_time($cur_info['cons_end_date'], 1).'</p>' : '';

		$td['asb_total_amount'] = is_numeric($cur_info['asb_total_amount']) ? number_format($cur_info['asb_total_amount'], 2, '.', '') : 0;
		$td['rem_total_amount'] = is_numeric($cur_info['rem_total_amount']) ? number_format($cur_info['rem_total_amount'], 2, '.', '') : 0;
		$td['cons_total_amount'] = is_numeric($cur_info['cons_total_amount']) ? number_format($cur_info['cons_total_amount'], 2, '.', '') : 0;
		$td['total_price'] = $td['asb_total_amount'] + $td['rem_total_amount'] + $td['cons_total_amount'];
		$td['total_cost'] = '<a href="'.$URL->link('hca_5840_manage_invoice', $cur_info['id']).'">$'.gen_number_format($td['total_price'], 2).'</a>';
		$td['total_price_alert'] = ($td['total_price'] >= 5000) ? ' price-alert' : '';

		//$td['relocation'][] = ($cur_info['moveout_date'] > 0) ? '<p>Move Out: '.format_time($cur_info['moveout_date'], 1).'</p>' : '';
		//$td['relocation'][] = ($cur_info['movein_date'] > 0) ? '<p>Move In: '.format_time($cur_info['movein_date'], 1).'</p>' : '';

		$td['maintenance'][] = ($cur_info['maintenance_date'] > 0) ? '<p class="fw-bold">'.format_time($cur_info['maintenance_date'], 1).'</p>' : '';
		$td['maintenance'][] = '<p>'.html_encode($cur_info['maintenance_comment']).'</p>';

		$td['final'][] = ($cur_info['final_performed_date'] > 0) ? '<p class="fw-bold">'.format_time($cur_info['final_performed_date'], 1).'</p>' : '';
		$td['final'][] = '<p class="text-primary">'.html_encode($cur_info['final_performed_by']).'</p>';

		if ($cur_info['job_status'] == 1 || $cur_info['job_status'] == 2)
		{
			$td['mois_inspection_date_alert'] = sm_is_today($cur_info['mois_inspection_date']) ? ' table-danger' : '';
			$td['asb_test_date_alert'] = sm_is_today($cur_info['asb_test_date']) ? ' table-danger' : '';
			$td['rem_start_date_alert'] = (sm_is_today($cur_info['rem_start_date']) || sm_is_today($cur_info['rem_end_date'])) ? ' table-danger' : '';
			$td['cons_start_date_alert'] = (sm_is_today($cur_info['cons_start_date']) || sm_is_today($cur_info['cons_end_date'])) ? ' table-danger' : '';
		}
		else
			$td['mois_inspection_date_alert'] = $td['asb_test_date_alert'] = $td['rem_start_date_alert'] = $td['cons_start_date_alert'] = '';
		
		if ($permission2)
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_project', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');
		if ($permission4)
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'"><i class="far fa-image"></i> Upload Files</a>');
		if ($permission5)
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_appendixb', $cur_info['id']).'"><i class="far fa-file-pdf"></i> Create Appendix-B</a>');
		if ($permission2)// ??
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_invoice', $cur_info['id']).'"><i class="fas fa-file-invoice-dollar"></i> Invoice</a>');
		//if ($permission6) // NEED TO FIX MANAGER FORM FIRST
		//	$Core->add_dropdown_item('<a href="#!" onclick="emailWindow('.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="far fa-envelope"></i> Send Email</a>');

		$td['property_info'][] = '<span class="float-end">'.$Core->get_dropdown_menu($cur_info['id']).'</span>';

		if ($section == 'missing')
		{
			if ($cur_info['job_status'] == 0)
				$td['property_info'][] = '<p class="badge bg-danger">Removed</p>';
			else if ($cur_info['job_status'] == 1)
				$td['property_info'][] = '<p class="badge bg-warning">In Progress</p>';
			else if ($cur_info['job_status'] == 2)
				$td['property_info'][] = '<p class="badge bg-secondary">On Hold</p>';
			else if ($cur_info['job_status'] == 3)
				$td['property_info'][] = '<p class="badge bg-success">Completed</p>';
		}
?>
		<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id']) ? 'selected' : '' ?>">
			<td class="td1"><?php echo implode("\n", $td['property_info']) ?></td>
			<td class="ta-center"><p class="fw-bold"><?php echo format_time($cur_info['mois_report_date'], 1) ?></p></td>
			<td class="<?php echo $td['mois_inspection_date_alert'] ?>"><?php echo implode("\n", $td['performed_by']) ?></td>
			<td><?php echo implode("\n", $td['mois_source']) ?></td>
			<td><?php echo implode("\n", $td['symptoms']) ?></td>
			<td><p><?php echo html_encode($cur_info['action']) ?></p></td>

			<td><?php echo implode("\n", $td['delivery_equip']) ?></td>
			<td><p class="fw-bold"><?php echo ($cur_info['pickup_equip_date'] > 0) ? format_time($cur_info['pickup_equip_date'], 1) : '' ?></p></td>
			<td><?php echo implode("\n", $td['afcc']) ?></td>
			<td  class="min-w-10" id="fup<?php echo $cur_info['id'] ?>"><?php echo implode("\n", $td['follow_up']) ?></td>
			<td class="<?php echo $td['asb_test_date_alert'] ?>"><?php echo implode("\n", $td['asb']) ?></td>

			<td class="<?php echo $td['rem_start_date_alert'] ?>"><?php echo implode("\n", $td['rem']) ?></td>
			<td class="<?php echo $td['rem_start_date_alert'] ?>"><p class="fw-bold"><?php echo format_time($cur_info['rem_start_date'], 1) ?></p></td>
			<td class="<?php echo $td['rem_start_date_alert'] ?>"><p class="fw-bold"><?php echo format_time($cur_info['rem_end_date'], 1) ?></p></td>

			<td class="<?php echo $td['cons_start_date_alert'] ?>"><?php echo implode("\n", $td['cons']) ?></td>
			<td class="<?php echo $td['cons_start_date_alert'] ?>"><p class="fw-bold"><?php echo format_time($cur_info['cons_start_date'], 1) ?></p></td>
			<td class="<?php echo $td['cons_start_date_alert'] ?>"><p class="fw-bold"><?php echo format_time($cur_info['cons_end_date'], 1) ?></p></td>

			<td class="<?php echo $td['total_price_alert'] ?>"><p><?php echo $td['total_cost'] ?></p></td>
			<td><p class="fw-bold"><?php echo format_time($cur_info['moveout_date'], 1) ?></p></td>
			<td><p class="fw-bold"><?php echo format_time($cur_info['movein_date'], 1) ?></p></td>
			<td><?php echo implode("\n", $td['maintenance']) ?></td>
			<td><?php echo implode("\n", $td['final']) ?></td>
			<td><p><?php echo html_encode($cur_info['remarks']) ?></p></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<?php
} else {
?>
	<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
?>

<?php if ($permission9): ?>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Follow Up Date</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="clearModalWindowFields()"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function getEvent(pid,id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_5840_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_5840_ajax_get_events') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({pid:pid,id:id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.modal .modal-body').empty().html('<div class="alert alert-danger" role="alert"><p class="fw-bold">Warning:</p> <p>Internet connection may have been lost. Refresh the page and try again.</p></div>');
			$('.modal .modal-footer"]').empty().html('');
		}
	});
}
function emailWindow(pid){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_5840_ajax_send_project_info_by_email')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_5840_ajax_send_project_info_by_email') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({pid:pid,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.modal .modal-body').empty().html('<div class="alert alert-danger" role="alert"><p class="fw-bold">Warning:</p> <p>Internet connection may have been lost. Refresh the page and try again.</p></div>');
			$('.modal .modal-footer"]').empty().html('');
		}
	});
}
function clearModalWindowFields(){
	//$('#modalWindow .modal-body"]').empty().html('');
	//$('#modalWindow .modal-footer"]').empty().html('');
}
</script>

<?php endif;

require SITE_ROOT.'footer.php';
