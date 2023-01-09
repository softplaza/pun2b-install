<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 1) || $User->checkAccess('hca_fs', 4) || $User->checkAccess('hca_fs', 5)) ? true : false;
if (!$access)
	message($lang_common['No permission']);
// to manage 
$access_4_5 = ($User->checkAccess('hca_fs', 4) || $User->checkAccess('hca_fs', 5)) ? true : false;

$HcaFS = new HcaFS;
$Facility = new Facility;
$action = $Facility->action = isset($_GET['action']) ? $_GET['action'] : '';
$gid = $Facility->group_id = isset($_GET['gid']) ? intval($_GET['gid']) : 0;
$week_of = $Facility->week_of = isset($_GET['week_of']) ? strtotime($_GET['week_of']) : time();
$first_day_of_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;

//$Facility->first_day_of_week($first_day_of_week);
$Facility->first_day_of_week = $first_day_of_week;
$days_of_week = $Facility->days_of_week;
$time_slots = $Facility->time_slots;

$weekly_schedule_info = $Facility->WeeklyInfo();
$users_info = $Facility->UsersInfo();
$work_orders_info = $Facility->WorkOrdersInfo();
$property_info = $Facility->PropertyInfo();
$assignments_info = $Facility->PermanentAssignments();

$template_types = [
	0 => 'Work Order',
	1 => 'Property Work',
//	2 => 'Make Ready'
];
if (($gid == $Config->get('o_hca_fs_maintenance')))
	$template_types[2] = 'Make Ready';

$param = array(
	'work_order_id'	=> isset($_POST['work_order_id']) ? intval($_POST['work_order_id']) : 0,
	'day_off'		=> isset($_POST['day_off']) ? 4 : 5,
	'user_id'		=> isset($_POST['user_id']) ? intval($_POST['user_id']) : 0,
	'property_id'	=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
	'message'		=> isset($_POST['message']) ? swift_trim($_POST['message']) : '',
	'time_slot'		=> isset($_POST['time_slot']) ? intval($_POST['time_slot']) : 1,
);

$param['user_name'] = isset($users_info[$param['user_id']]) ? $users_info[$param['user_id']]['realname'] : '';
$param['user_email'] = isset($users_info[$param['user_id']]) ? $users_info[$param['user_id']]['email'] : '';
$param['property_name'] = isset($property_info[$param['property_id']]) ? $property_info[$param['property_id']]['pro_name'] : '';

$time_now = time();
$Core->add_warnings($Facility->Warnings);

// NEW VERSION from 2022/3/31
if (isset($_POST['create_task']))
{
	$form_data = [];
	$form_data['template_type'] = isset($_POST['template_type']) ? intval($_POST['template_type']) : 0;
	$form_data['employee_id'] = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
	$form_data['property_id'] = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$form_data['unit_number'] = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';
	$form_data['time_slot'] = isset($_POST['time_slot']) ? intval($_POST['time_slot']) : 0;
	$form_data['geo_code'] = isset($_POST['geo_code']) ? swift_trim($_POST['geo_code']) : '';
	$form_data['group_id'] = $gid;
	$form_data['created'] = time();

	$form_data['start_date'] = isset($_POST['start_date']) ? intval($_POST['start_date']) : 0; // $_POST['new_date'] already as strtotime
	$form_data['scheduled'] = ($form_data['start_date'] > 0) ? date('Ymd', $form_data['start_date']) : 0;
	$form_data['week_of'] = ($form_data['start_date'] > 0) ? strtotime('Monday this week', $form_data['start_date']) : strtotime('Monday this week');
	// Replace to 2022-10-17 format
	$form_data['date_requested'] = isset($_POST['start_date']) ? date('Y-m-d', $_POST['start_date']) : 0;

	$form_data['msg_for_maint'] = isset($_POST['msg_for_maint']) ? swift_trim($_POST['msg_for_maint']) : '';
	$form_data['work_status'] = 1;
	$form_data['requested_by'] = $User->get('realname');
	
	if ($form_data['template_type'] == 4)
	{
		$form_data['time_slot'] = 4;
	}
	else if ($form_data['template_type'] == 5)
	{
		$form_data['time_slot'] = 5;
	}
	else if ($form_data['template_type'] == 6)
	{
		$form_data['time_slot'] = 6;
	}
	else if ($form_data['template_type'] == 7)
	{
		$form_data['time_slot'] = 7;
	}

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert_values('hca_fs_requests', $form_data);

		// Sends Email if over limit WO
		$Facility->checkWorkOrderLimit($form_data['employee_id'], $form_data['start_date'], $form_data['time_slot']);

		if ($form_data['property_id'] > 0 && $form_data['employee_id'] > 0 && $form_data['template_type'] == '2')
		{
			include SITE_ROOT.'apps/punch_list_management/classes/PunchList.php';
			$PunchList = new PunchList;

			$punch_data = [
				'start_date'	=> $form_data['start_date'],
				'property_id'	=> $form_data['property_id'],
				'unit_number'	=> $form_data['unit_number'],
				'employee_id'	=> $form_data['employee_id']
			];

			if ($form_data['group_id'] == $Config->get('o_hca_fs_painters'))
				$punch_form_id = $PunchList->createPainterForm($punch_data);
			else
				$punch_form_id = $PunchList->createMaintForm($punch_data);

			if ($punch_form_id)
			{
				$query = array(
					'UPDATE'	=> 'hca_fs_requests',
					'SET'		=> 'punch_form_id='.$punch_form_id,
					'WHERE'		=> 'id='.$new_id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}

		// Add flash message
		$flash_message = 'Task created';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_weekly_schedule', [$gid, date('Y-m-d', $week_of)]).'&uid='.$form_data['employee_id'], $flash_message);
	}
}
else if (isset($_POST['update_task']))
{
	$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
	$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

	$form_data = [];
	$form_data['template_type'] = isset($_POST['template_type']) ? intval($_POST['template_type']) : 0;
	$form_data['property_id'] = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$form_data['unit_number'] = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';
	$form_data['time_slot'] = isset($_POST['time_slot']) ? intval($_POST['time_slot']) : 0;
	$form_data['geo_code'] = isset($_POST['geo_code']) ? swift_trim($_POST['geo_code']) : '';
	$form_data['msg_for_maint'] = isset($_POST['msg_for_maint']) ? swift_trim($_POST['msg_for_maint']) : '';
	
	if ($form_data['template_type'] == 4)
		$form_data['time_slot'] = 4;
	else if ($form_data['template_type'] == 5)
		$form_data['time_slot'] = 5;
	else if ($form_data['template_type'] == 6)
		$form_data['time_slot'] = 6;
	else if ($form_data['template_type'] == 7)
		$form_data['time_slot'] = 7;

	if ($request_id > 0)
	{
		$DBLayer->update('hca_fs_requests', $form_data, $request_id);

		// send email to TECH & MNGR if something changes
		if ($Facility->CheckMailedUserStatus($employee_id))
		{
			// Get request info, user info, and property info
			$cur_work_order = $Facility->getWorkOrder($request_id);

			$emails = $mail_message = [];
			$emails[] = $cur_work_order['email'];
			$emails[] = $cur_work_order['manager_email'];

			$mail_message[] = 'Task #'.$request_id.' has been changed.'."\n";
			$mail_message[] = 'Property: '.$cur_work_order['pro_name'];
			$mail_message[] = 'Unit #: '.$cur_work_order['unit_number'];
			$mail_message[] = 'Scheduled on: '.format_time($cur_work_order['start_date'], 1, 'F j, Y');
			$mail_message[] = 'Technician: '.$cur_work_order['realname'];
			$mail_message[] = 'Comment: '.html_encode($cur_work_order['msg_for_maint']);
			$mail_message[] = 'Task changed by: '.$User->get('realname');
			
			// Send email to Technician and Property Manager
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
			$SwiftMailer->send(implode(',', $emails), 'Technician Weekly Schedule', implode("\n", $mail_message));
		}

		// Add flash message
		$flash_message = 'Task #'.$request_id.' updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_weekly_schedule', [$gid, date('Y-m-d', $week_of)]).'&uid='.$employee_id, $flash_message);
	}
}
// assign_task
else if (isset($_POST['assign_task']))
{
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	$form_data = [
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		//'unit_id'			=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
		'unit_number'		=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',
		'geo_code'			=> isset($_POST['gl_code']) ? swift_trim($_POST['gl_code']) : '',
		'msg_for_maint'		=> isset($_POST['task_details']) ? swift_trim($_POST['task_details']) : '',
		'time_slot'			=> isset($_POST['time_slot']) ? intval($_POST['time_slot']) : 1,
		'template_type'		=> isset($_POST['template_type']) ? intval($_POST['template_type']) : 1,
		'employee_id'		=> isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,
		'group_id'			=> isset($_POST['group_id']) ? intval($_POST['group_id']) : 0,
		'created'			=> time(),
		'start_date'		=> isset($_POST['requested_date']) ? strtotime($_POST['requested_date']) : 0,
		'scheduled'			=> isset($_POST['requested_date']) ? date('Ymd', strtotime($_POST['requested_date'])) : '',
		'week_of'			=> isset($_POST['requested_date']) ? strtotime('Monday this week', strtotime($_POST['requested_date'])) : strtotime('Monday this week'),
		'date_requested'	=> isset($_POST['requested_date']) ? swift_trim($_POST['requested_date']) : '',
		'work_status'		=> 1,
		'requested_by'		=> $User->get('realname'),

	];

	if ($form_data['property_id'] == 0)
		$Core->add_error('No property to assign the task.');
	if ($form_data['employee_id'] == 0)
		$Core->add_error('To assign the task select user.');

	if ($form_data['group_id'] == 0 && $form_data['employee_id'] > 0)
	{
		$query = [
			'SELECT'	=> 'u.id, u.group_id, u.realname',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id='.$form_data['employee_id'],
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$user = $DBLayer->fetch_assoc($result);
		$form_data['group_id'] = $user['group_id'];
	}

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert_values('hca_fs_requests', $form_data);

		if ($task_id > 0)
		{
			$query = array(
				'UPDATE'	=> 'hca_fs_tasks',
				'SET'		=> 'task_status=1',
				'WHERE'		=> 'id='.$task_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			$hca_fs_task = $Facility->getWorkOrder($new_id);
			if (isset($hca_fs_task['manager_email']) && $Config->get('o_hca_wom_notify_managers_from_inhouse') == 1)
			{
				$SwiftMailer = new SwiftMailer;
				//$SwiftMailer->isHTML();

				$mail_subject = 'Property Request is in The Facility Schedule';
				$mail_message = [];
				$mail_message[] = 'Property: '.$hca_fs_task['pro_name'];
				$mail_message[] = 'Location/Unit: '.$hca_fs_task['unit_number'];

				if (strtotime($hca_fs_task['date_requested']) > 0)
					$mail_message[] = 'Scheduled date: '.format_date($hca_fs_task['date_requested'], 'm/d/Y');
				$mail_message[] = 'Time: '.$HcaFS->getTimeSlot($hca_fs_task['time_slot']);
				
				$mail_message[] = 'Technician: '.$hca_fs_task['realname']."\n";

				if ($form_data['msg_for_maint'] != '')
					$mail_message[] = 'Comment: '.$hca_fs_task['msg_for_maint']."\n";
	
				$SwiftMailer->send($hca_fs_task['manager_email'], $mail_subject, implode("\n", $mail_message));
			}
		}

		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been assigned.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_weekly_schedule', [$gid, date('Y-m-d', $form_data['week_of'])]).'&uid='.$form_data['employee_id'], $flash_message);
	}
}
else if (isset($_POST['delete_task']))
{
	$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
	$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

	if ($request_id > 0)
	{
		// Get request info, user info, and property info
		$cur_work_order = $Facility->getWorkOrder($request_id);

		// Delete work order
		$DBLayer->delete('hca_fs_requests', $request_id);

		//if ($cur_work_order['week_of'] == $this_week)
		if ($Facility->CheckMailedUserStatus($employee_id))// if schedule sent
		{
			$emails = $mail_message = [];
			$emails[] = $cur_work_order['email'];
			$emails[] = $cur_work_order['manager_email'];

			$mail_message[] = 'Task #'.$request_id.' has been deleted.'."\n";
			$mail_message[] = 'Property: '.$cur_work_order['pro_name'];
			$mail_message[] = 'Unit #: '.$cur_work_order['unit_number'];
			$mail_message[] = 'Scheduled on: '.format_time($cur_work_order['start_date'], 1, 'F j, Y');
			$mail_message[] = 'Technician: '.$cur_work_order['realname'];
			$mail_message[] = 'Comment: '.html_encode($cur_work_order['msg_for_maint']);
			$mail_message[] = 'Deleted by: '.$User->get('realname');
	
			// Send email to Technician and Property Manager
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
			$SwiftMailer->send(implode(',', $emails), 'Technician Weekly Schedule', implode("\n", $mail_message));
		}

		// Add flash message
		$flash_message = 'Task deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_weekly_schedule', [$gid, date('Y-m-d', $week_of)]).'&uid='.$employee_id, $flash_message);
	}
}
else if (isset($_POST['copy_task']) || isset($_POST['move_task']))
{
	$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
	$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

	if ($request_id > 0)
	{
		$query = array(
			'SELECT'	=> 'r.group_id, r.employee_id, r.property_id, r.unit_number, r.template_type, r.geo_code, r.time_slot, r.msg_for_maint',
			'FROM'		=> 'hca_fs_requests AS r',
			'WHERE'		=> 'r.id='.$request_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$form_data = $DBLayer->fetch_assoc($result);

		$form_data['created'] = time();
	
		$form_data['start_date'] = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : 0;
		$form_data['scheduled'] = ($form_data['start_date'] > 0) ? date('Ymd', $form_data['start_date']) : 0;
		$form_data['week_of'] = ($form_data['start_date'] > 0) ? strtotime('Monday this week', $form_data['start_date']) : strtotime('Monday this week');
		// Replace to 2022-10-17 format
		$form_data['date_requested'] = isset($_POST['start_date']) ? $_POST['start_date'] : '';
	
		$form_data['work_status'] = 1;
		$form_data['requested_by'] = $User->get('realname');

		if ($form_data['start_date'] == 0)
			$Core->add_error('Date was not set.');
		
		if (empty($Core->errors))
		{
			if (isset($_POST['copy_task']))
			{
				$DBLayer->insert_values('hca_fs_requests', $form_data);
				$flash_message = 'Task copied';
			}
			else if (isset($_POST['move_task']))
			{
				$move_data = [];
				$move_data['start_date'] = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : 0; // $_POST['new_date'] already as strtotime
				$move_data['scheduled'] = ($move_data['start_date'] > 0) ? date('Ymd', $move_data['start_date']) : 0;
				$move_data['week_of'] = ($move_data['start_date'] > 0) ? strtotime('Monday this week', $move_data['start_date']) : strtotime('Monday this week');
				// Replace to 2022-10-17 format
				$move_data['date_requested'] = isset($_POST['start_date']) ? $_POST['start_date'] : '';

				$DBLayer->update('hca_fs_requests', $move_data, $request_id);
				$flash_message = 'Task moved';
			}

			// Add flash message
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_fs_weekly_schedule', [$gid, date('Y-m-d', $week_of)]).'&uid='.$employee_id, $flash_message);
		}
	}
}

// SEND SCHEDULE
else if (isset($_POST['send_schedule']))
{
	$id = intval(key($_POST['send_schedule']));

	// ADD OPTION 2 WAYS HOW TO SEND THE SCHEDULE: 1 - separated / 2 - whole

	$pdf_path = ($gid == $Config->get('o_hca_fs_painters')) ? 'files/painter_schedule'.'_'.$id.'.pdf' : 'files/maintenance_schedule'.'_'.$id.'.pdf';
	
	if ($id > 0)
	{
		$time_now = time();
		$GenPDF = new GenPDF($work_orders_info, $assignments_info);
		$GenPDF->users_list = $users_info;
		$GenPDF->property_info = $property_info;
		$GenPDF->group_id = $gid;
		$GenPDF->first_day_of_week = $first_day_of_week;
		$GenPDF->GenSeparatedUserShedule($id);

		$UserInfo = $Facility->GetUserInfo($id);
		$mail_subject = 'Weekly Schedule';

		$mail_message[] = 'Your weekly schedule has been updated.';
		$SwiftMailer = new SwiftMailer;

		$mail_message[] = 'To view your schedule, follow the link below:';
		$mail_message[] = $URL->link('hca_fs_weekly_technician_schedule', [$gid, $id, date('Y-m-d', $week_of)])."\n";
		$mail_message[] = 'To manage your work Work Orders and tasks, go to the website, then log in with your username and password.';
		$mail_message[] = BASE_URL."\n";

		if (file_exists($pdf_path))
		{
			$mail_message[] = 'See the attached file for the full weekly schedule.';
			$SwiftMailer->send($UserInfo['email'], $mail_subject, implode("\n", $mail_message), [$pdf_path]);
		}
		else
		{
			$SwiftMailer->send($UserInfo['email'], $mail_subject, implode("\n", $mail_message));
		}

		if ($Facility->CheckMailedUserStatus($id))
		{
			$query = array(
				'UPDATE'	=> 'hca_fs_weekly',
				'SET'		=> 'submitted_time=0, mailed_time='.$time_now,
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		else
		{
			$hash = random_key(5, true, true);
			$query = array(
				'INSERT'	=> 'user_id, week_of, mailed_time, hash',
				'INTO'		=> 'hca_fs_weekly',
				'VALUES'	=> 
					''.$id.',
					\''.$DBLayer->escape($first_day_of_week).'\',
					\''.$time_now.'\',
					\''.$DBLayer->escape($hash).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'The Schedule has been sent to '.$UserInfo['realname'];
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_weekly_schedule', [$gid, date('Y-m-d', $week_of)]).'&uid='.$id, $flash_message);
	}
}

$Facility->checkWorkOrderLimit();

$pdf_path = ($gid == $Config->get('o_hca_fs_painters')) ? 'files/painter_schedule.pdf' : 'files/maintenance_schedule.pdf';
$Core->add_page_action('<a href="'.$pdf_path.'" target="_blank"><i class="fas fa-file-pdf fa-2x"></i>Print as PDF</a>');

$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="'.$pdf_path.'" target="_blank"><i class="fas fa-file-pdf fa-1x" aria-hidden="true"></i> Print as PDF</a></li>');

$group_name = ($gid == $Config->get('o_hca_fs_painters')) ? 'Painter' : 'Maintenance';
$Core->set_page_title($group_name.' Weekly Schedule');

if ($action == 'print')
{
	$Core->set_page_id('print', 'hca_fs');
	require SITE_ROOT.'header.php';
	require 'weekly_schedule_print.php';
	require SITE_ROOT.'footer.php';
}
else if ($action == 'pdf')
{
	$Core->set_page_id('pdf', 'hca_fs');
	require SITE_ROOT.'header.php';
	require 'weekly_schedule_pdf.php';
	require SITE_ROOT.'footer.php';
}
else
{
	$Core->set_page_id('hca_fs_weekly_schedule_'.$gid, 'hca_fs');
	require SITE_ROOT.'header.php';
?>
<div id="weekly_schedule">

<?php
if (!empty($users_info)) 
{
	$query = [
		'SELECT'	=> 't.*, pt.pro_name, un.unit_number',
		'FROM'		=> 'hca_fs_tasks AS t',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=t.property_id'
			],
			[
				'LEFT JOIN'		=> 'sm_property_units AS un',
				'ON'			=> 'un.id=t.unit_id'
			],
		],
		'WHERE'		=> 't.task_status=0 AND t.group_id='.$gid,
		'ORDER BY'	=> 't.requested_date',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_fs_tasks = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_fs_tasks[] = $row;
	}

	if (!empty($hca_fs_tasks))
	{
?>

<div class="accordion mb-2">
	<div class="accordion-item">
		<button class="accordion-button badge-danger text-danger collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#unassigned_property_requests" aria-expanded="false" aria-controls="unassigned_property_requests">

			<span class="position-relative fw-bold">Unassigned Property Requests<span class="position-absolute top-0 start-100 ms-2 translate-middle badge rounded-pill bg-danger"><?=count($hca_fs_tasks)?></span>
			</span>

		</button>
		<div id="unassigned_property_requests" class="accordion-collapse collapse" aria-labelledby="heading_warnings" data-bs-parent="#warning_messages">
			<div class="accordion-body p-2">
				<div class="row px-2">
<?php 

		$task_info = [];
		foreach($hca_fs_tasks as $cur_info)
		{
			$task_info[] = '<div class="col-2 callout-warning rounded px-1 m-2 min-w-10">';
			$task_info[] = '<p>';
			$task_info[] = '<span class="float-end" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="assignPropertyRequest('.$cur_info['id'].')"><i class="fas fa-edit"></i></span>';
			$task_info[] = '<span class="fw-bold">'.html_encode($cur_info['pro_name']).', </span>';

			if ($cur_info['unit_number'] != '')
				$task_info[] = 'unit: <span class="fw-bold">'.html_encode($cur_info['unit_number']).'</span>';
			else
				$task_info[] = '<span class="fw-bold">Common area</span>';
			$task_info[] = '</p>';
			$task_info[] = '<p>';
			if (strtotime($cur_info['requested_date']) > 0)
				$task_info[] = '<span class="fw-bold">'.format_date($cur_info['requested_date'], 'm/d/Y').'</span>, ';
			$task_info[] = '<span class="fw-bold">'.$HcaFS->getTimeSlot($cur_info['time_slot']).'</span>';
			if ($cur_info['gl_code'] != '')
				$task_info[] = ', <span class="fw-bold">GL Code: '.html_encode($cur_info['gl_code']).'</span>';
			$task_info[] = '</p>';
			$task_info[] = '<p>'.html_encode($cur_info['task_details']).'</p>';
			$task_info[] = '</div>';
		}
		echo implode("\n", $task_info);
?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
}
?>

<nav class="navbar container-fluid justify-content-between mb-2" style="background-color: #cff4fc">
	<form method="get" accept-charset="utf-8" action="">
		<input type="hidden" name="gid" value="<?php echo $gid ?>"/>
		<div class="row">
			<div class="col">
				<input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_week) ?>" class="form-control" onclick="this.showPicker()">
			</div>
			<div class="col">
				<button type="submit" class="btn btn-outline-success">Go to Date</button>
			</div>
		</div>
	</form>
	<div class="float-end">
		<button type="button" class="btn btn-outline-success float-end" onclick="showWeekend()">Weekend</button>
	</div>
</nav>

<form method="post" accept-charset="utf-8" action="">
	<div class="hidden">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	</div>
	<table class="table table-bordered">
		<thead>
			<tr class="sticky-under-menu">
				<th class="tc1">Employee Name</th>
<?php
	$header_days = $first_day_of_week;
	foreach ($days_of_week as $key => $day)
	{
		echo '<th class="'.(in_array($key, array(6,7)) ? 'th-weekend' : '').'"><p>'.date('l', $header_days ).'</p>
		<p>'.date('m/d', $header_days).'</p></th>';
		$header_days = $header_days + 86400;
	}
?>
			</tr>
		</thead>
		<tbody>
<?php

	foreach ($users_info as $user_info) 
	{
		$user_actions = [];

		$user_actions[] = '<button type="button" class="btn btn-outline-info btn-xs"><a href="'.$URL->link('user', $user_info['id']).'" target="_blank" title="Go to profile"><i class="far fa-user fa-2x"></i></a></button>';
		$user_actions[] = '<button type="button" class="btn btn-outline-info btn-xs"><a href="'.$URL->link('hca_fs_weekly_technician_schedule', [$gid, $user_info['id'], date('Y-m-d', $first_day_of_week)]).'" target="_blank" title="View schedule"><i class="far fa-calendar-alt fa-2x"></i></a></button>';

		$user_actions[] = '<button type="submit" name="send_schedule['.$user_info['id'].']" title="Send the schedule" class="btn btn-outline-info btn-xs" /><a href="#"><i class="far fa-envelope fa-2x"></i></a></button>';

		$weekly_info = $Facility->getWeeklyInfoById($user_info['id']);
		$schedule_sent_status = ($Facility->CheckMailedUserStatus($user_info['id'])) ? '<p><span class="badge bg-success">Sent on '.format_time($weekly_info['mailed_time'], 1).'</span></p>' : '<p><span class="badge bg-warning">Waiting to be mailed</span></p>';
?>

			<tr id="uid_<?=$user_info['id']?>" class="<?=($uid == $user_info['id'] ? 'anchor' : '')?>">
				<td class="user-info table-info">
					<p class="username"><?php echo $user_info['realname'] ?></p>
					<p><?php echo implode("\n", $user_actions) ?></p>
					<?php echo $schedule_sent_status ?>
				</td>

<?php
		$time_next_date = $first_day_of_week;
		foreach ($days_of_week as $key => $day)
		{
			$cur_date = date('Ymd', $time_next_date);
			
			$assignment_list = $assignment_ids = array();
			if (!empty($assignments_info))
			{
				foreach($assignments_info as $assignment)
				{
					$cur_assignment = array();
					if ($user_info['id'] == $assignment['user_id'] && $key == $assignment['day_of_week'])
					{
						$cur_assignment[] = '<strong>'.$property_info[$assignment['property_id']]['pro_name'].'</strong>';
						$cur_assignment[] = '<p>'.$time_slots[$assignment['time_shift']].' (Regular)</p>';
						
						$assignment_list[$assignment['id']] = '<div class="alert-warning border mb-1 ps-1 min-h-3">'.implode('', $cur_assignment).'</div>';
						$assignment_ids[] = $assignment['id'];
					}
				}
			}
			
			$td_css_classes = array();
			$assigned_to_property = implode('', $assignment_list);
			$work_order_list = $work_order_ids = $cur_info = array();
			$day_off_id = $time_next_date + $user_info['id'];
			if (!empty($work_orders_info))
			{
				foreach($work_orders_info as $work_order_info)
				{
					$cur_work_order = $css_status = array();
					$day_number = date('N', strtotime($work_order_info['scheduled']));

					// Display only: IN PROGRESS && COMPLETED
					if ($user_info['id'] == $work_order_info['employee_id'] && $key == $day_number)
					{
						$Facility->addDDItem2('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editRequestInfo('.$work_order_info['id'].')"><i class="fas fa-edit"></i> Edit task</a>');
						$Facility->addDDItem2('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="copyTaskTo('.$work_order_info['id'].')"><i class="fas fa-copy"></i> Copy/Move task</a>');

						$cur_work_order[] = $access ? '<span class="float-end">'.$Facility->getDDMenu2($work_order_info['id']).'</span>' : '';

						if ($work_order_info['time_slot'] == 7)
							$cur_work_order[] = '<strong class="day-off text-warning">'.$time_slots[$work_order_info['time_slot']].'</strong>';
						else if ($work_order_info['time_slot'] == 6)
							$cur_work_order[] = '<strong class="day-off text-info">'.$time_slots[$work_order_info['time_slot']].'</strong>';
						else if ($work_order_info['time_slot'] == 5)
							$cur_work_order[] = '<strong class="day-off text-danger">'.$time_slots[$work_order_info['time_slot']].'</strong>';
						else if ($work_order_info['time_slot'] == 4)
							$cur_work_order[] = '<strong class="day-off text-success">'.$time_slots[$work_order_info['time_slot']].'</strong>';
						else
							$cur_work_order[] = '<strong>'.html_encode($work_order_info['pro_name']).'</strong>';

						$geo_code = ($work_order_info['geo_code'] != '') ? ', GL code: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
						if ($work_order_info['unit_number'] != '')
							$cur_work_order[] = '<p class="wo-time">Unit#: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
						
						$time_slot = isset($time_slots[$work_order_info['time_slot']]) ? $time_slots[$work_order_info['time_slot']] : 'n/a';
						
						if ($work_order_info['time_slot'] < 4)
							$cur_work_order[] = '<p class="wo-time">Time: <strong>'.$time_slot.'</strong></p>';
						
						if ($work_order_info['msg_for_maint'] != '')
							$cur_work_order[] = '<p class="msg-for-maint">Remarks: '.html_encode($work_order_info['msg_for_maint']).'</p>';
							
						// Punch form link
						if ($work_order_info['punch_form_id'] > 0)
						{
							$punch_form_link = ($work_order_info['group_id'] == $Config->get('o_hca_fs_painters')) ? $URL->link('punch_list_management_painter_request', [$work_order_info['punch_form_id'], '']) : $URL->link('punch_list_management_maintenance_request', [$work_order_info['punch_form_id'], '']);
							$cur_work_order[] = '<p><i class="fas fa-file-pdf fa-lg text-danger"></i> <a href="'.$punch_form_link.'" target="_blank">MAKE READY</a></p>';
						}

						/* TURN WHEN EMPLOYEE WILL RECEIVE EMAIL */
						if ($work_order_info['time_slot'] < 4)
						{
							if ($work_order_info['work_status'] == 2)
								$cur_work_order[] = '<p style="text-align:end;"><span class="badge bg-success">Completed</span></p>';
							else if ($work_order_info['work_status'] == 1)
								$cur_work_order[] = '<p style="text-align:end;"><span class="badge bg-warning">In Progress</span></p>';
						}

						//if (isset($assignment_list[$work_order_info['permanent_id']]))
							$work_order_list[] = '<div class="alert-warning border mb-1 ps-1 min-h-3">'.implode('', $cur_work_order).'</div>';
						
						$cur_info = $work_order_info;
						$work_order_ids[] = $work_order_info['id'];
					}
				}
			}
			
			$add_work_order = $access ? '<button class="btn btn-sm float-end" type="button" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="createTask('.$user_info['id'].','.$time_next_date.','.$key.');"><i class="fas fa-plus-circle fa-lg text-success"></i></button>' : '';

			$td_css_classes[] =  (in_array($key, array(6,7)) ? ' td-weekend' : '');
			if (!empty($work_order_list))
			{
				echo '<input type="hidden" name="form_work_orders['.$user_info['id'].']['.$time_next_date.']" value="'.implode(',', $work_order_ids).'" />'."\n";

				echo '<td id="ass'.$day_off_id.'" class="work-orders-list '.implode(' ', $td_css_classes).'" >'.implode('', $work_order_list) . $add_work_order.'</td>'."\n";
			}
			else
			{
				// IS SCHEDULED PERMANENTLY WORKER ? 
				if ($assigned_to_property != '')
				{
					echo '<input type="hidden" name="form_permanently['.$user_info['id'].']['.$time_next_date.']" value="'.implode(',', $assignment_ids).'" />'."\n";
					echo '<td id="ass'.$day_off_id.'" class="work-orders-list '.implode(' ', $td_css_classes).'">'.$assigned_to_property . $add_work_order.'</td>'."\n";
					// IS UNASSIGNED WORKER ? 
				} else {
					echo '<td id="ass'.$day_off_id.'" class="work-orders-empty '.implode(' ', $td_css_classes).'">'.$add_work_order.'</td>'."\n";
				}
			} 
			$time_next_date = $time_next_date + 86400;
		}
?>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</form>

<?php
//print_r($Facility->work_order_counter);

$GenPDF = new GenPDF($work_orders_info, $assignments_info);
$GenPDF->users_list = $users_info;
$GenPDF->property_info = $property_info;
$GenPDF->group_id = $gid;
$GenPDF->first_day_of_week = $first_day_of_week;
$GenPDF->genMainWeelkySchedule();
$pdf_path = ($gid == $Config->get('o_hca_fs_painters')) ? 'files/painter_schedule.pdf' : 'files/maintenance_schedule.pdf';
if (file_exists($pdf_path))
{
?>

<style>#demo_iframe{width: 100%;height: 400px;zoom: 2;}</style>
<div class="card-header">
	<h6 class="card-title mb-0">PDF preview</h6>
</div>
<iframe id="demo_iframe" src="<?=$pdf_path?>?<?php echo time() ?>"></iframe>

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
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
//
function assignPropertyRequest(task_id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_assign_property_request')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_assign_property_request') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({task_id:task_id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.modal .modal-body').empty().html('Error: No data received.');
		}
	});
}
function createTask(uid,time,day)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_weekly_shedule_request')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_weekly_shedule_request') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({uid:uid,time:time,day:day,type:0,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.msg-section').empty().html('Error: No data.');
		}
	});
}
function editRequestInfo(id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_weekly_shedule_request')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_weekly_shedule_request') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,type:1,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.msg-section').empty().html('Error: No data.');
		}
	});
}
function copyTaskTo(id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_weekly_shedule_request')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_weekly_shedule_request') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,type:2,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.msg-section').empty().html('Error: No data.');
		}
	});
}
function templateType(v){
	if (v > 0) {
		$("#template_body").css('display', 'none');
	} else {
		$("#template_body").css('display', 'block');
	}
}
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_units')) ?>";
	var id = $("#fld_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#property_units").empty().html(re.unit_number);
		},
		error: function(re){
			document.getElementById("#unit_number").innerHTML = re;
		}
	});
}
function closeModalWindow(){
	$('.modal .modal-title').empty().html('');
	$('.modal .modal-body').empty().html('');
	$('.modal .modal-footer').empty().html('');
}
</script>

<?php
	} else {
?>
	<div class="alert alert-warning" role="alert">
		<p>You have not selected any user groups. Enter the Schedule Management settings and mark the user groups to display in the list.</p>
	</div>
<?php
	}
?>
</div>
<?php
	require SITE_ROOT.'footer.php';
}
