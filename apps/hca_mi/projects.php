<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$section = isset($_GET['section']) ? $_GET['section'] : 'active';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('hca_mi', 1)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$Moisture = new Moisture;
$HcaMi = new HcaMi;

$work_statuses = array(1 => 'IN PROGRESS', 2 => 'ON HOLD', 3 => 'COMPLETED', 0 => 'DELETE');

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_manager = isset($_GET['performed_by']) ? swift_trim($_GET['performed_by']) : '';
$search_by_unit = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';

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
			'SELECT'	=> 'pj.*, p.manager_email, p.pro_name',
			'FROM'		=> 'hca_5840_projects AS pj',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'p.id=pj.property_id'
				]
			],
			'WHERE'		=> 'pj.id='.$project_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$project_info = $DBLayer->fetch_assoc($result);
		
		$mail_subject = ($subject != '') ? $subject : 'HCA: Moisture Inspection';
		$mail_message = $mail_message."\n\n";
		
		if (!empty($project_info))
		{
			if (in_array('property_name', $mailing_fields))
				$mail_message .= 'Property: '.$project_info['pro_name']."\n\n";
			if (in_array('unit_number', $mailing_fields))
				$mail_message .= 'Unit #: '.$project_info['unit_number']."\n\n";
			if (in_array('location', $mailing_fields))
				$mail_message .= 'Location: '.$project_info['location']."\n\n";
			if (in_array('mois_report_date', $mailing_fields))
				$mail_message .= 'Report Date: '.format_time($project_info['mois_report_date'], 1)."\n\n";
			if (in_array('mois_performed_by', $mailing_fields))
				$mail_message .= 'Performed by: '.$project_info['mois_performed_by']."\n\n";
			if (in_array('mois_inspection_date', $mailing_fields))
				$mail_message .= 'Inspection Date: '.format_time('m/d/Y', $project_info['mois_inspection_date'], 1)."\n\n";
			if (in_array('mois_source', $mailing_fields) && $project_info['mois_source'] != '')
				$mail_message .= 'Source: '.$project_info['mois_source']."\n\n";
			if (in_array('symptoms', $mailing_fields) && $project_info['symptoms'] != '')
				$mail_message .= 'Symptoms: '.$project_info['symptoms']."\n\n";
			if (in_array('action', $mailing_fields) && $project_info['action'] != '')
				$mail_message .= 'Action: '.$project_info['action']."\n\n";
			if (in_array('remarks', $mailing_fields) && $project_info['remarks'] != '')
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

else if (isset($_POST['update_event']))
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
			
			$flash_message = 'Event #'.$event_id.' has been updated';
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
			
			$flash_message = 'Event #'.$event_id.' has been created';
		}
		
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

$query = array(
	'SELECT'	=> 'id, realname, email',
	'FROM'		=> 'users',
	'ORDER BY'	=> 'realname',
	//'WHERE'		=> 'hca_5840_access > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_manager = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$project_manager[$fetch_assoc['id']] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'COUNT(pj.id)',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
	],
);
if ($section == 'on_hold') $query['WHERE'] = 'job_status=2';
else if ($section == 'completed') $query['WHERE'] = 'job_status=3';
else $query['WHERE'] = 'job_status=1';

// SEARCH BY SECTION //
if ($search_by_property_id > 0)
	$query['WHERE'] .= ' AND property_id='.$search_by_property_id;
if ($search_by_manager != '') {
	$query['WHERE'] .= ' AND mois_performed_by=\''.$DBLayer->escape($search_by_manager).'\'';
}
if (!empty($search_by_unit)) {
	$search_by_unit2 = '%'.$search_by_unit.'%';
	$query['WHERE'] .= ' AND unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, un.unit_number, u1.realname AS performed_by_realname',
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
	],
	'ORDER BY'	=> 'pt.pro_name, LENGTH(pj.unit_number)',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if ($section == 'on_hold') $query['WHERE'] = 'pj.job_status=2';
else if ($section == 'completed') $query['WHERE'] = 'pj.job_status=3';
//else if ($section == 'roof_leaks') $query['WHERE'] = 'job_status!=0';
else $query['WHERE'] = 'pj.job_status=1';

if ($search_by_property_id > 0)
	$query['WHERE'] .= ' AND pj.property_id='.$search_by_property_id;
if ($search_by_manager != '') {
	$query['WHERE'] .= ' AND pj.mois_performed_by=\''.$DBLayer->escape($search_by_manager).'\'';
}
if (!empty($search_by_unit)) {
	$search_by_unit2 = '%'.$search_by_unit.'%';
	$query['WHERE'] .= ' AND pj.unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$row['unit_number'] = ($row['unit_id'] > 0) ? $row['unit_number'] : $row['unit'];

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
	$uploader_info = array();
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
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$events_info = array();
if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.time, e.message',
		'FROM'		=> 'sm_calendar_events AS e',
		'WHERE'		=> 'e.project_id IN('.implode(',', $projects_ids).') AND project_name=\'hca_5840\'',
		'ORDER BY'	=> 'e.time'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $fetch_assoc;
	}
}

$upcoming_work = $Moisture->get_upcoming_work();

if ($upcoming_work > 0)
	$Core->add_warning('You have work scheduled for today: <strong>'.$upcoming_work.'</strong>');

//$Core->set_page_title('Projects');

if ($section == 'on_hold')
	$Core->set_page_id('hca_5840_projects_on_hold', 'hca_5840');
else if ($section == 'completed')
	$Core->set_page_id('hca_5840_projects_completed', 'hca_5840');
else if ($section == 'roof_leaks')
	$Core->set_page_id('hca_5840_projects_roof_leaks', 'hca_5840');
else
	$Core->set_page_id('hca_5840_projects_active', 'hca_5840');

require SITE_ROOT.'header.php';
?>

<style>
.date {min-width: 100px}
.date p {font-weight: bold}
.vendor-name{font-weight:bold;color:#a319fa;}
.comment {min-width: 200px}
.location{overflow-wrap: anywhere;}
.edit-row{display:none;}
.actions .submit{text-align: center;margin-top:5px;}
.actions p{text-align: center;cursor: pointer;}
.subject input, .email-window textarea{width:97%;}
.events{min-width: 200px;}
.events strong{background: green;color: white;border-radius: 7px;padding: 1px 10px;cursor: pointer;font-weight: bold;}
.events p{margin-bottom: .2em;}
.date-time{color:blue;}
.selected td{background: #b0f0ff;}
</style>

	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<input name="section" type="hidden" value="<?php echo $section ?>"/>
			<div class="row">
				<div class="col">
					<select name="performed_by" class="form-select">
						<option value="">Project Managers</option>
<?php foreach ($project_manager as $val){
			if ($search_by_manager == $val['realname'])
				echo '<option value="'.$val['realname'].'" selected="selected">'.$val['realname'].'</option>';
			else
				echo '<option value="'.$val['realname'].'">'.$val['realname'].'</option>';
} ?>
					</select>
				</div>
				<div class="col">
					<select name="property_id" class="form-select">
						<option value="">All Properties</option>
<?php foreach ($property_info as $val){
			if ($search_by_property_id == $val['id'])
				echo '<option value="'.$val['id'].'" selected="selected">'.$val['pro_name'].'</option>';
			else
				echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
} ?>
					</select>
				</div>
				<div class="col">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Enter Unit #" class="form-control"/>
				</div>
				<div class="col">
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
			<table class="table table-striped table-bordered">
				<thead>
					<tr class="sticky-under-menu">
						<th class="th1">Property/Unit#</th>
						<th>Date Reported</th>
						<th>Date of inspection</th>
						<th>Source of moisture</th>
						<th>Symptoms</th>
						<th>Action</th>
						<th>Delivery Equip.</th>
						<th>PickUp of Equip.</th>
						<th>AFCC</th>
						<th>Follow Up Dates</th>
						<th><p style="color:#d72d2d;">Asbestos Test</p><p>PO/Date</p></th>
						<th><p style="color:#d72d2d;">Remediation</p><p>PO Number</p></th>
						<th>Dates</th>
						<th><p style="color:#d72d2d;">Construction</p><p>PO Number</p></th>
						<th>Dates</th>
						<th>Total Cost</th>
						<th>Relocation</th>
						<th>Maintenance Date</th>
						<th>Finall Walk</th>
						<th>Remarks</th>
					</tr>
				</thead>
				<tbody>
<?php

	foreach ($main_info as $cur_info)
	{
		$page_param['td'] = array();
		$page_param['td']['location'] = str_replace(',', ', ', $cur_info['location']);

		$page_param['td']['mois_report_date'] = format_time($cur_info['mois_report_date'], 1);
		$page_param['td']['mois_inspection_date'] = ($cur_info['mois_inspection_date'] > 0) ? '<p>'.format_time($cur_info['mois_inspection_date'], 1).'</p>' : '';
		
		$page_param['td']['mois_performed_by'] = ($cur_info['performed_by_realname'] != '') ? html_encode($cur_info['performed_by_realname']) : html_encode($cur_info['mois_performed_by']);
		
		$page_param['td']['action'] = html_encode($cur_info['action']);
		$page_param['td']['delivery_equip_date'] = ($cur_info['delivery_equip_date'] > 0) ? '<p>'.format_time($cur_info['delivery_equip_date'], 1).'</p>' : '';
		$page_param['td']['pickup_equip_date'] = ($cur_info['pickup_equip_date'] > 0) ? '<p>'.format_time($cur_info['pickup_equip_date'], 1).'</p>' : '';
		
		$page_param['td']['afcc_date'] = ($cur_info['afcc_date'] > 0) ? '<p>'.format_time($cur_info['afcc_date'], 1).'</p>' : '';
		
		$follow_up_dates = $Moisture->get_events($events_info, $cur_info['id']);
		$page_param['td']['follow_up'] = !empty($follow_up_dates) ? implode('', $follow_up_dates) : '';
		
		$page_param['td']['asb_vendor'] = html_encode($cur_info['asb_vendor']);
		$page_param['td']['asb_test_date'] = ($cur_info['asb_test_date'] > 0) ? '<p>'.format_time($cur_info['asb_test_date'], 1).'</p>' : '';
		
		$page_param['td']['rem_start_date'] = ($cur_info['rem_start_date'] > 0) ? '<p>'.format_time($cur_info['rem_start_date'], 1).'</p>' : '';
		$page_param['td']['rem_vendor'] = '<p class="vendor-name">'.html_encode($cur_info['rem_vendor']).'</p>';
		$page_param['td']['rem_end_date'] = ($cur_info['rem_end_date'] > 0) ? '<p>'.format_time($cur_info['rem_end_date'], 1).'</p>' : '';
		
		$page_param['td']['cons_vendor'] = html_encode($cur_info['cons_vendor']);
		$page_param['td']['cons_start_date'] = ($cur_info['cons_start_date'] > 0) ? '<p>'.format_time($cur_info['cons_start_date'], 1).'</p>' : '';
		$page_param['td']['cons_end_date'] = ($cur_info['cons_end_date'] > 0) ? '<p>'.format_time($cur_info['cons_end_date'], 1).'</p>' : '';
		
		$page_param['td']['asb_total_amount'] = is_numeric($cur_info['asb_total_amount']) ? number_format($cur_info['asb_total_amount'], 2, '.', '') : 0;
		$page_param['td']['rem_total_amount'] = is_numeric($cur_info['rem_total_amount']) ? number_format($cur_info['rem_total_amount'], 2, '.', '') : 0;
		$page_param['td']['cons_total_amount'] = is_numeric($cur_info['cons_total_amount']) ? number_format($cur_info['cons_total_amount'], 2, '.', '') : 0;
		$page_param['td']['total_price'] = $page_param['td']['asb_total_amount'] + $page_param['td']['rem_total_amount'] + $page_param['td']['cons_total_amount'];
		$page_param['td']['total_cost'] = '<a href="'.$URL->link('hca_5840_manage_invoice', $cur_info['id']).'">$'.gen_number_format($page_param['td']['total_price'], 2).'</a>';
		
		$page_param['td']['moveout_date'] = ($cur_info['moveout_date'] > 0) ? '<p>'.format_time($cur_info['moveout_date'], 1).'</p>' : '';
		$page_param['td']['movein_date'] = ($cur_info['movein_date'] > 0) ? '<p>'.format_time($cur_info['movein_date'], 1).'</p>' : '';
		
		$page_param['td']['final_performed_date'] = ($cur_info['final_performed_date'] > 0) ? '<p>'.format_time($cur_info['final_performed_date'], 1).'</p>' : '';
		$page_param['td']['final_performed_by'] = html_encode($cur_info['final_performed_by']);
		
		$page_param['td']['maintenance_date'] = ($cur_info['maintenance_date'] > 0) ? '<p>'.format_time($cur_info['maintenance_date'], 1).'</p>' : '';
		
		$page_param['td']['job_status'] = isset($work_statuses[$cur_info['job_status']]) ? $work_statuses[$cur_info['job_status']] : 'Error';
		$page_param['td']['email_status'] = ($cur_info['email_status'] == 1) ? '<strong style="color:green" onclick="emailWindow('.$cur_info['id'].')">Mailed</strong>' : '<strong style="color:blue" onclick="emailWindow('.$cur_info['id'].')">Send Email</strong>';
		
		$page_param['td']['remarks'] = html_encode($cur_info['remarks']);
		$page_param['td']['css_status'] = ($cur_info['job_status'] == 1) ? 'on-hold' : 'in-progress';
		$page_param['td']['total_price_alert'] = ($page_param['td']['total_price'] >= 5000) ? ' price-alert' : '';
		
		if ($cur_info['job_status'] == 1 || $cur_info['job_status'] == 2)
		{
			$page_param['td']['mois_inspection_date_alert'] = sm_is_today($cur_info['mois_inspection_date']) ? ' table-danger' : '';
			$page_param['td']['asb_test_date_alert'] = sm_is_today($cur_info['asb_test_date']) ? ' table-danger' : '';
			$page_param['td']['rem_start_date_alert'] = (sm_is_today($cur_info['rem_start_date']) || sm_is_today($cur_info['rem_end_date'])) ? ' table-danger' : '';
			$page_param['td']['cons_start_date_alert'] = (sm_is_today($cur_info['cons_start_date']) || sm_is_today($cur_info['cons_end_date'])) ? ' table-danger' : '';
		}
		else
			$page_param['td']['mois_inspection_date_alert'] = $page_param['td']['asb_test_date_alert'] = $page_param['td']['rem_start_date_alert'] = $page_param['td']['cons_start_date_alert'] = '';

		$view_files = ($User->checkAccess('hca_mi', 14) && in_array($cur_info['id'], $uploader_info)) ? '<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'" class="btn btn-sm btn-success text-white">Files</a>' : '';
		
		if ($access)
		{
			if ($User->checkAccess('hca_mi', 12))
				$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_project', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');
			if ($User->checkAccess('hca_mi', 14))
				$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'"><i class="far fa-image"></i> Upload Files</a>');
			if ($User->checkAccess('hca_mi', 15))
				$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_appendixb', $cur_info['id']).'"><i class="far fa-file-pdf"></i> Create Appendix-B</a>');
			//if ($User->checkAccess('hca_mi', 13))
			//	$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_invoice', $cur_info['id']).'"><i class="fas fa-file-invoice-dollar"></i> Edit Invoice</a>');
			//if ($User->checkAccess('hca_mi', 16))
			//	$Core->add_dropdown_item('<a href="#!" onclick="emailWindow('.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="far fa-envelope"></i> Send Email</a>');
		}
?>
					<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id']) ? 'selected' : '' ?>">
						<td class="td1">
							<p class="property"><?php echo html_encode($cur_info['pro_name']) ?></p>
							<p class="unit">Unit: <?php echo html_encode($cur_info['unit_number']) ?></p>
							<p class="location"><?php echo $page_param['td']['location'] ?></p>
							<?php echo $view_files ?>
							<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
						</td>
						<td class="date"><?php echo $page_param['td']['mois_report_date'] ?></td>
						<td class="date <?php echo $page_param['td']['mois_inspection_date_alert'] ?>">
							<?php echo $page_param['td']['mois_inspection_date'] ?>
							<p class="vendor-name"><?php echo $page_param['td']['mois_performed_by'] ?></p>
						</td>
						<td class="comment">
							<p class="fw-bold"><?php echo (isset($HcaMi->leak_types[$cur_info['leak_type']]) ? $HcaMi->leak_types[$cur_info['leak_type']] : '') ?></p>
							<p><?php echo html_encode($cur_info['mois_source']) ?></p>
						</td>
						<td class="comment">
							<p class="fw-bold"><?php echo (isset($HcaMi->symptoms[$cur_info['symptom_type']]) ? $HcaMi->symptoms[$cur_info['symptom_type']] : '') ?></p>
							<p><?php echo html_encode($cur_info['symptoms']) ?></p>
						</td>
						<td class="comment"><p><?php echo $page_param['td']['action'] ?></p></td>
						<td class="date">
							<?php echo $page_param['td']['delivery_equip_date'] ?>
							<p><?php echo html_encode($cur_info['delivery_equip_comment']) ?></p>
						</td>
						<td class="date"><?php echo $page_param['td']['pickup_equip_date'] ?></td>	
						<td class="date">
							<?php echo $page_param['td']['afcc_date'] ?>
							<p><?php echo html_encode($cur_info['afcc_comment']) ?></p>
						</td>
						<td class="events" id="fup<?php echo $cur_info['id'] ?>">
							<?php echo $page_param['td']['follow_up'] ?>
							<p style="float:right" onclick="getEvent(<?php echo $cur_info['id'] ?>, 0)" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-plus-circle fa-lg"></i></p>
						</td>
						<td class="vendor date <?php echo $page_param['td']['asb_test_date_alert'] ?>">
							<?php echo $page_param['td']['asb_test_date'] ?>
							<p class="vendor-name"><?php echo $page_param['td']['asb_vendor'] ?></p>
							<p><?php echo html_encode($cur_info['asb_po_number']) ?></p>
							<?php echo html_encode($cur_info['asb_comment']) ?>
						</td>
						<td class="vendor date <?php echo $page_param['td']['rem_start_date_alert'] ?>">
							<?php echo $page_param['td']['rem_vendor'] ?>
							<p><?php echo html_encode($cur_info['rem_po_number']) ?></p>
							<?php echo html_encode($cur_info['rem_comment']) ?>
						</td>
						<td class="date <?php echo $page_param['td']['rem_start_date_alert'] ?>">
							<small>Start:</small>
							<?php echo $page_param['td']['rem_start_date'] ?>
							<small>End:</small>
							<?php echo $page_param['td']['rem_end_date'] ?>
						</td>
						<td class="vendor date <?php echo $page_param['td']['cons_start_date_alert'] ?>">
							<p class="vendor-name"><?php echo $page_param['td']['cons_vendor'] ?></p>
							<p><?php echo html_encode($cur_info['cons_po_number']) ?></p>
							<?php echo html_encode($cur_info['cons_comment']) ?>
						</td>
						<td class="date <?php echo $page_param['td']['cons_start_date_alert'] ?>">
							<small>Start:</small>
							<?php echo $page_param['td']['cons_start_date'] ?>
							<small>End:</small>
							<?php echo $page_param['td']['cons_end_date'] ?>
						</td>
						<td class="total-cost <?php echo $page_param['td']['total_price_alert'] ?>">
							<p><?php echo $page_param['td']['total_cost'] ?></p>
						</td>
						<td class="date">
							<small>Move-Out:</small>
							<?php echo $page_param['td']['moveout_date'] ?>
							<small>Move-in:</small>
							<?php echo $page_param['td']['movein_date'] ?>
						</td>
						<td class="date">
							<?php echo $page_param['td']['maintenance_date'] ?>
							<p><?php echo html_encode($cur_info['maintenance_comment']) ?></p>
						</td>
						<td class="date">
							<?php echo $page_param['td']['final_performed_date'] ?>
							<p><?php echo $page_param['td']['final_performed_by'] ?></p>
						</td>
						<td class="comment"><p><?php echo $page_param['td']['remarks'] ?></p></td>
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
	<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
?>

<?php if ($access): 
?>
<div class="pop-up-window" id="email-window">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<input type="hidden" name="project_id" value="0" />
		<div class="head">
			<p class="close"><img src="<?=BASE_URL?>/img/close.png" onclick="closeWindows()"></p>
			<p class="title">Message for the Manager</p>
		</div>
		<div class="fields">
			<p>Subject</p>
			<p class="subject"><input type="text" name="subject" value="HCA: Moisture Inspection"></p>
			<p>Message for Manager of Property</p>
			<p style="display:none"><textarea name="email_list" rows="3" placeholder="Enter emails separated by commas"><?php echo $Config->get('o_hca_5840_mailing_list') ?></textarea></p>
			<p><textarea name="mail_message" rows="8" placeholder="Write your message">Hello. </textarea></p>
			<p class="btn-action">
				<span class="submit primary"><input type="submit" name="send_email" value="Send Email"/></span>
			</p>
		</div>
	</form>
</div>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Edit item</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="closeModalWindow()"></button>
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
			$('.msg-section').empty().html('<div class="alert alert-danger" role="alert">Error: No data received.</div>');
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
			$('.msg-section').empty().html('<div class="alert alert-danger" role="alert">Error: No data received.</div>');
		}
	});
}

function closeModalWindow(){
	$('.modal .modal-body"]').val("");
	$('.modal .modal-footer"]').val("");
}
</script>

<?php endif;
require SITE_ROOT.'footer.php';