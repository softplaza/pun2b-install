<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 2) || $User->get('sm_pm_property_id') > 0) ? true : false;
$access2 = ($User->checkAccess('hca_fs')) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$time_slots = array(0 => 'ANY TIME', 1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
$execution_priority = [0 => 'Low', 1 => 'Medium', 2 => 'High'];
$template_types = [0 => 'Work Order', 1 => 'Property Work', 2 => 'Make Ready'];
$SwiftUploader = new SwiftUploader;

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
);
if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE']	= 'id='.$User->get('sm_pm_property_id');
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
}

if (isset($_POST['new_request']))
{
	$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$property = sm_get_data_by_id($property_info, $property_id);
	$selected_geo_code = isset($_POST['selected_geo_code']) ? swift_trim($_POST['selected_geo_code']) : '';
	$geo_code = isset($_POST['geo_code']) ? swift_trim($_POST['geo_code']) : '';

	$form_data = array(
		'property_id'		=> $property_id,
		'unit_number'		=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',
		'geo_code'			=> ($geo_code != '') ? $geo_code : $selected_geo_code,
		'group_id'			=> isset($_POST['group_id']) ? intval($_POST['group_id']) : 0,
		'time_slot'			=> isset($_POST['time_slot']) ? intval($_POST['time_slot']) : 1,
		'request_msg'		=> isset($_POST['request_msg']) ? swift_trim($_POST['request_msg']) : '',
		'msg_for_maint'		=> isset($_POST['request_msg']) ? swift_trim($_POST['request_msg']) : '',
		'created'			=> time(),
		'scheduled'			=> isset($_POST['start_date']) ? date('Ymd', strtotime($_POST['start_date'])) : 0,
		'start_date'		=> isset($_POST['start_date']) ? strtotime($_POST['start_date']) : 0,
		'execution_priority'=> isset($_POST['execution_priority']) ? intval($_POST['execution_priority']) : 0,
		'permission_enter'	=> isset($_POST['permission_enter']) ? intval($_POST['permission_enter']) : 0,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'requested_by'		=> $User->get('realname'),
		'template_type'		=> isset($_POST['template_type']) ? intval($_POST['template_type']) : 0,
	);
	//$check_name = count(explode(' ', $form_data['requested_by']));
	
	if (empty($property))
		$Core->add_error('Select a property from dropdown list.');
	if ($form_data['msg_for_maint'] == '' && $form_data['template_type'] != 2)
		$Core->add_error('The comment field cannot be empty. Please describe the type of request.');
	if ($form_data['permission_enter'] == 1 && $form_data['start_date'] == 0)
		$Core->add_error('Since you have checked "Permission to enter", then you must set the "Requested Date".');

	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('hca_fs_requests', $form_data);

		if ($new_id)
		{
			if (isset($_POST['request_text']) && !empty($_POST['request_text']))
			{
				foreach($_POST['request_text'] as $key => $value)
				{
					$task_data = [
						'request_id'		=> $new_id,
						'request_text'		=> swift_trim($value),
					];
					if ($value != '')
						$DBLayer->insert_values('hca_fs_tasks', $task_data);
				}
			}

			$SwiftUploader->uploadFiles('hca_fs_requests', $new_id);
			$Core->add_errors($SwiftUploader->getErrors());

			$mail_subject = 'Facility Project Request';
			$mail_message = [];
			$mail_message[] = 'A new In-House Project Request. See details bellow.'."\n";
			$mail_message[] = 'Property name: '.$property['pro_name'];
			$mail_message[] = 'Unit number: '.$form_data['unit_number'];
			$mail_message[] = 'Date requested: '.format_time($form_data['start_date'], 1);
			$mail_message[] = 'Permission to enter: '.($form_data['permission_enter'] == 1 ? 'YES' : 'NO');
			$mail_message[] = 'Message: '.$form_data['msg_for_maint'];
			$mail_message[] = 'Requested by: '.$form_data['requested_by']."\n";
			$mail_message[] = 'To view the request follow this link:';
			$mail_message[] = $URL->link('hca_fs_requests', ['new', $new_id]);

			$query = array(
				'SELECT'	=> 'u.id, u.realname, u.email, u.hca_fs_access, u.hca_fs_group',
				'FROM'		=> 'groups AS g',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'users AS u',
						'ON'			=> 'g.g_id=u.group_id'
					)
				),
				'WHERE'		=> 'u.hca_fs_access > 0',
				'ORDER BY'	=> 'realname'
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$users_info = array();
			while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
				$users_info[] = $fetch_assoc;
			}

            $recipients = [];
			if (!empty($users_info))
			{
				foreach($users_info as $user_info)
				{
					if ($form_data['group_id'] == $user_info['hca_fs_group'])
						$recipients[] = $user_info['email'];
				}
			}

            if (!empty($recipients))
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send(implode(',', $recipients), $mail_subject, implode("\n", $mail_message));
			}

			// Add flash message
			$flash_message = 'In-House request has been created';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_fs_new_request', $new_id), $flash_message);
		}
	}
}

else if (isset($_POST['update_request']))
{
	$form_data = array(
		'unit_number'		=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',
		//'geo_code'			=> ($geo_code != '') ? $geo_code : $selected_geo_code,
		//'group_id'			=> isset($_POST['group_id']) ? intval($_POST['group_id']) : 0,
		'time_slot'			=> isset($_POST['time_slot']) ? intval($_POST['time_slot']) : 1,
		'request_msg'		=> isset($_POST['request_msg']) ? swift_trim($_POST['request_msg']) : '',
		'msg_for_maint'		=> isset($_POST['request_msg']) ? swift_trim($_POST['request_msg']) : '',
		//'created'			=> time(),
		'scheduled'			=> isset($_POST['start_date']) ? date('Ymd', strtotime($_POST['start_date'])) : 0,
		'start_date'		=> isset($_POST['start_date']) ? strtotime($_POST['start_date']) : 0,
		'execution_priority'=> isset($_POST['execution_priority']) ? intval($_POST['execution_priority']) : 0,
		'permission_enter'	=> isset($_POST['permission_enter']) ? intval($_POST['permission_enter']) : 0,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		//'requested_by'		=> $User->get('realname'),
		//'template_type'		=> isset($_POST['template_type']) ? intval($_POST['template_type']) : 0,
	);

	$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
	if ($employee_id > 0)
		$form_data['employee_id'] = $employee_id;

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_fs_requests', $form_data, $id);
		
		if (isset($_POST['task_text']) && !empty($_POST['task_text']))
		{
			foreach($_POST['task_text'] as $key => $value)
			{
				$task_data = array(
					'request_text'		=> swift_trim($value),
				);
				$DBLayer->update('hca_fs_tasks', $task_data, $key);
			}
		}

		if (isset($_POST['request_text']) && !empty($_POST['request_text']))
		{
			foreach($_POST['request_text'] as $key => $value)
			{
				$task_data = array(
					'request_id'		=> $id,
					'request_text'		=> swift_trim($value),
				);
				if ($value != '')
					$DBLayer->insert_values('hca_fs_tasks', $task_data);
			}
		}

		$SwiftUploader->uploadFiles('hca_fs_requests', $id);

		// Add flash message
		$flash_message = 'In-House request has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

if (isset($_POST['approve_request']))
{
	$query = array(
		'SELECT'	=> 'r.*, r.unit_number, p.pro_name, p.manager_email',
		'FROM'		=> 'hca_fs_requests AS r',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'r.property_id=p.id'
			),
		),
		'WHERE'		=> 'r.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$request_info = $DBLayer->fetch_assoc($result);

	$form_data = [];
	$form_data['employee_id'] = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : $request_info['employee_id'];
	$form_data['time_slot'] = isset($_POST['time_slot']) ? intval($_POST['time_slot']) : $request_info['time_slot'];
	$form_data['start_date'] = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : $request_info['start_date'];
	$form_data['msg_for_maint'] = isset($_POST['msg_for_maint']) ? swift_trim($_POST['msg_for_maint']) : $request_info['msg_for_maint'];
	if ($form_data['start_date'] > 0)
	{
		$form_data['scheduled'] = date('Ymd', $form_data['start_date']);
		$form_data['week_of'] = strtotime('Monday this week', $form_data['start_date']);
	}

	if ($form_data['employee_id'] == 0)
		$Core->add_error('No technician has been assigned. Please, select a technician from dropdown list and update again.');
	if ($form_data['start_date'] == 0)
		$Core->add_error('The start date of the work must be selected.');
	if ($form_data['start_date'] < time())
		$Core->add_error('The start date must be greater than today.');

	// IF SHEDULED - IN PROGRESS or APPROVED
	if (empty($Core->errors))
	{
		$form_data['work_status'] = 1;
		$DBLayer->update('hca_fs_requests', $form_data, $id);

		if (isset($_POST['task_text']) && !empty($_POST['task_text']))
		{
			foreach($_POST['task_text'] as $key => $value)
			{
				$task_data = array(
					'request_text'		=> swift_trim($value),
				);
				$DBLayer->update('hca_fs_tasks', $task_data, $key);
			}
		}

		if (isset($_POST['request_text']) && !empty($_POST['request_text']))
		{
			foreach($_POST['request_text'] as $key => $value)
			{
				$task_data = array(
					'request_id'		=> $id,
					'request_text'		=> swift_trim($value),
				);
				if ($value != '')
					$DBLayer->insert_values('hca_fs_tasks', $task_data);
			}
		}

		$SwiftUploader->uploadFiles('hca_fs_requests', $id);

		$SwiftMailer = new SwiftMailer;
		$mail_subject = 'Facility Request';

		$mail_message = [];
		$mail_message[] = 'The request #'.$id.' has been scheduled.';
		$mail_message[] = 'Property name: '.$request_info['pro_name'];
		$mail_message[] = 'Unit # '.($request_info['unit_number'] != '' ? $request_info['unit_number'] : 'n/a');
		$mail_message[] = 'Date requested: '.($request_info['start_date'] > 0 ? format_time($request_info['start_date'], 1) : 'n/a');
		$mail_message[] = 'Message: '.$form_data['msg_for_maint'];
		$mail_message[] = 'Submitted by: '.$User->get('realname');
		$mail_message[] = 'To view the request follow this link:';
		$mail_message[] = $URL->link('hca_fs_new_request', $id);

		// Send only to Maintenance Manager
		$SwiftMailer->send($request_info['manager_email'], $mail_subject, implode("\n", $mail_message));

		// If Make Ready Template
		if ($request_info['template_type'] == 2)
		{
			include SITE_ROOT.'apps/punch_list_management/classes/PunchList.php';
			$PunchList = new PunchList;

			$punch_data = [
				'start_date'	=> $request_info['start_date'],
				'property_id'	=> $request_info['property_id'],
				'unit_number'	=> $request_info['unit_number'],
				'employee_id'	=> $form_data['employee_id']
			];

			if ($request_info['group_id'] == $Config->get('o_hca_fs_painters'))
				$punch_form_id = $PunchList->createPainterForm($punch_data);
			else
				$punch_form_id = $PunchList->createMaintForm($punch_data);

			if ($punch_form_id > 0)
			{
				$query = array(
					'UPDATE'	=> 'hca_fs_requests',
					'SET'		=> 'punch_form_id='.$punch_form_id,
					'WHERE'		=> 'id='.$request_info['id']
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}

		// Add flash message
		$flash_message = 'The work order #'.$id.' has been scheduled.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_weekly_schedule', [$request_info['group_id'], date('Y-m-d', $form_data['start_date'])]).'&uid='.$form_data['employee_id'], $flash_message);
	}
}

if (isset($_POST['complete_request']))
{
	$query = array(
		'SELECT'	=> 'r.*, r.unit_number, p.pro_name, p.manager_email',
		'FROM'		=> 'hca_fs_requests AS r',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'r.property_id=p.id'
			),
		),
		'WHERE'		=> 'r.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$request_info = $DBLayer->fetch_assoc($result);

	$form_data = [];
	$form_data['employee_id'] = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : $request_info['employee_id'];
	$form_data['time_slot'] = isset($_POST['time_slot']) ? intval($_POST['time_slot']) : $request_info['time_slot'];
	$form_data['start_date'] = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : $request_info['start_date'];
	$form_data['msg_for_maint'] = isset($_POST['msg_for_maint']) ? swift_trim($_POST['msg_for_maint']) : $request_info['msg_for_maint'];
	if ($form_data['start_date'] > 0)
	{
		$form_data['scheduled'] = date('Ymd', $form_data['start_date']);
		$form_data['week_of'] = strtotime('Monday this week', $form_data['start_date']);
	}
	$form_data['completed_time'] = time();

	// IF SHEDULED - IN PROGRESS or APPROVED
	if (empty($Core->errors))
	{
		$form_data['work_status'] = 2;
		$DBLayer->update('hca_fs_requests', $form_data, $id);

		if (isset($_POST['task_text']) && !empty($_POST['task_text']))
		{
			foreach($_POST['task_text'] as $key => $value)
			{
				$task_data = array(
					'request_text'		=> swift_trim($value),
				);
				$DBLayer->update('hca_fs_tasks', $task_data, $key);
			}
		}

		if (isset($_POST['request_text']) && !empty($_POST['request_text']))
		{
			foreach($_POST['request_text'] as $key => $value)
			{
				$task_data = array(
					'request_id'		=> $id,
					'request_text'		=> swift_trim($value),
				);
				if ($value != '')
					$DBLayer->insert_values('hca_fs_tasks', $task_data);
			}
		}

		$SwiftUploader->uploadFiles('hca_fs_requests', $id);

		// Get users
		$query = array(
			'SELECT'	=> 'u.*, g.g_id, g.g_title',
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
		$maint_mngr = $paint_mngr = array();
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if ($row['hca_fs_group'] == $Config->get('o_hca_fs_maintenance'))
				$maint_mngr[] = $row['email'];
			if ($row['hca_fs_group'] == $Config->get('o_hca_fs_painters'))
				$paint_mngr[] = $row['email'];
		}

		$SwiftMailer = new SwiftMailer;
		$mail_subject = 'Facility Request';

		$mail_message = [];
		$mail_message[] = 'The request #'.$id.' has been completed.';
		$mail_message[] = 'Property name: '.$request_info['pro_name'];
		$mail_message[] = 'Unit # '.($request_info['unit_number'] != '' ? $request_info['unit_number'] : 'n/a');
		$mail_message[] = 'Date requested: '.($request_info['start_date'] > 0 ? format_time($request_info['start_date'], 1) : 'n/a');
		$mail_message[] = 'Message: '.$form_data['msg_for_maint'];
		$mail_message[] = 'Submitted by: '.$User->get('realname');
		$mail_message[] = 'To view the request follow this link:';
		$mail_message[] = $URL->link('hca_fs_new_request', $id);

		// Send only to Maintenance Manager
		if ($User->get('sm_pm_property_id') > 0 && $request_info['group_id'] == $Config->get('o_hca_fs_maintenance'))
			$SwiftMailer->send(implode(",", $maint_mngr), $mail_subject, implode("\n", $mail_message));
		// Send only to Painter Manager
		else if ($User->get('sm_pm_property_id') > 0 && $request_info['group_id'] == $Config->get('o_hca_fs_painters'))
			$SwiftMailer->send(implode(",", $paint_mngr), $mail_subject, implode("\n", $mail_message));
		// Send only to Property Manager
		else
			$SwiftMailer->send($request_info['manager_email'], $mail_subject, implode("\n", $mail_message));

		// Add flash message
		$flash_message = 'The work order #'.$id.' has been completed.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_requests', ['completed', $id]), $flash_message);
	}
}

if (isset($_POST['cancel_request']))
{
	$query = array(
		'SELECT'	=> 'r.*, r.unit_number, p.pro_name, p.manager_email',
		'FROM'		=> 'hca_fs_requests AS r',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'r.property_id=p.id'
			),
		),
		'WHERE'		=> 'r.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$request_info = $DBLayer->fetch_assoc($result);
	
	$form_data = [];
	$form_data['employee_id'] = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : $request_info['employee_id'];
	$form_data['time_slot'] = isset($_POST['time_slot']) ? intval($_POST['time_slot']) : $request_info['time_slot'];
	$form_data['start_date'] = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : $request_info['start_date'];
	$form_data['msg_for_maint'] = isset($_POST['msg_for_maint']) ? swift_trim($_POST['msg_for_maint']) : $request_info['msg_for_maint'];
	if ($form_data['start_date'] > 0)
	{
		$form_data['scheduled'] = date('Ymd', $form_data['start_date']);
		$form_data['week_of'] = strtotime('Monday this week', $form_data['start_date']);
	}

	// IF SHEDULED - IN PROGRESS or APPROVED
	if (empty($Core->errors))
	{
		$form_data['work_status'] = 2;
		$DBLayer->update('hca_fs_requests', $form_data, $id);

		if (isset($_POST['task_text']) && !empty($_POST['task_text']))
		{
			foreach($_POST['task_text'] as $key => $value)
			{
				$task_data = array(
					'request_text'		=> swift_trim($value),
				);
				$DBLayer->update('hca_fs_tasks', $task_data, $key);
			}
		}

		if (isset($_POST['request_text']) && !empty($_POST['request_text']))
		{
			foreach($_POST['request_text'] as $key => $value)
			{
				$task_data = array(
					'request_id'		=> $id,
					'request_text'		=> swift_trim($value),
				);
				if ($value != '')
					$DBLayer->insert_values('hca_fs_tasks', $task_data);
			}
		}

		$SwiftUploader->uploadFiles('hca_fs_requests', $id);

		// Get users
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
		$maint_mngr = $paint_mngr = array();
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if ($row['hca_fs_group'] == $Config->get('o_hca_fs_maintenance'))
				$maint_mngr[] = $row['email'];
			if ($row['hca_fs_group'] == $Config->get('o_hca_fs_painters'))
				$paint_mngr[] = $row['email'];
		}

		$SwiftMailer = new SwiftMailer;
		$mail_subject = 'Facility Request';

		$mail_message = [];
		$mail_message[] = 'The request #'.$id.' has been canceled.';
		$mail_message[] = 'Property name: '.$request_info['pro_name'];
		$mail_message[] = 'Unit # '.($request_info['unit_number'] != '' ? $request_info['unit_number'] : 'n/a');
		$mail_message[] = 'Date requested: '.($request_info['start_date'] > 0 ? format_time($request_info['start_date'], 1) : 'n/a');
		$mail_message[] = 'Message: '.$form_data['msg_for_maint'];
		$mail_message[] = 'Submitted by: '.$User->get('realname');
		$mail_message[] = 'To view the request follow this link:';
		$mail_message[] = $URL->link('hca_fs_new_request', $id);

		// Send only to Maintenance Manager
		if ($User->get('sm_pm_property_id') > 0 && $request_info['group_id'] == $Config->get('o_hca_fs_maintenance'))
			$SwiftMailer->send(implode(",", $maint_mngr), $mail_subject, implode("\n", $mail_message));
		// Send only to Painter Manager
		else if ($User->get('sm_pm_property_id') > 0 && $request_info['group_id'] == $Config->get('o_hca_fs_painters'))
			$SwiftMailer->send(implode(",", $paint_mngr), $mail_subject, implode("\n", $mail_message));
		// Send only to Property Manager
		else
			$SwiftMailer->send($request_info['manager_email'], $mail_subject, implode("\n", $mail_message));

		// Add flash message
		$flash_message = 'The work order #'.$id.' has been canceled.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_fs_requests', ['completed', $id]), $flash_message);
	}
}

else if (isset($_POST['delete_task']))
{
	$tid = intval(key($_POST['delete_task']));
	$DBLayer->delete('hca_fs_tasks', $tid);

	// Add flash message
	$flash_message = 'Task has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['delete_file']))
{
	$fid = intval(key($_POST['delete_file']));

	$file_info = $DBLayer->select('sm_uploader', $fid);
	$file_path = SITE_ROOT.$file_info['file_path'].$file_info['file_name'];

	$DBLayer->delete('sm_uploader', $fid);

	if (file_exists($file_path))
		unlink($file_path);

	// Add flash message
	$flash_message = 'File has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$Core->set_page_id('hca_fs_new_request', 'hca_fs');
require SITE_ROOT.'header.php';

if ($id > 0)
{
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
		'WHERE'	=> 'r.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$main_info = $DBLayer->fetch_assoc($result);

	$query = array(
		'SELECT'	=> 't.*',
		'FROM'		=> 'hca_fs_tasks AS t',
		'WHERE'		=> 't.request_id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit property request #<?php echo $main_info['id'] ?></h6>
		</div>
		<div class="card-body">
<?php if ($main_info['work_status'] == 1): ?>
			<div class="alert alert-success alert-dismissible fade show" role="alert">The request alredy scheduled.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
<?php endif; ?>
			<div class="row mb-3">
				<div class="col border">
					<label class="form-label">Template Type:</label>
					<h5><?php echo isset($template_types[$main_info['template_type']]) ? $template_types[$main_info['template_type']] : '' ?></h5>
				</div>
				<div class="col border">
					<label class="form-label">Technician:</label>
					<h5><?php echo ($main_info['group_id'] == $Config->get('o_hca_fs_painters') ? 'Painter required' : 'Maintenance required') ?></h5>
				</div>
				<div class="col border">
					<label class="form-label">GL Code:</label>
					<h5><?php echo html_encode($main_info['geo_code']) ?></h5>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label">Property:</label>
					<h5 class="mb-0"><?php echo html_encode($main_info['pro_name']) ?></h5>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_unit_number">Location</label>
					<input type="text" name="unit_number" value="<?php echo html_encode($main_info['unit_number']) ?>" class="form-control" id="fld_unit_number" placeholder="Enter unit #">
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_start_date">Date Requested</label>
					<input type="date" name="start_date" value="<?php echo format_time($main_info['start_date'], 1, 'Y-m-d') ?>" class="form-control" id="fld_start_date" onchange="checkAvailableTechnician(<?php echo $main_info['group_id'] ?>)">
				</div>
				<div class="col-md-2">
					<label class="form-label" for="execution_priority">Urgency</label>
					<select name="execution_priority" id="execution_priority" class="form-select">
<?php
foreach ($execution_priority as $key => $val)
{
	if ($main_info['execution_priority'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="permission_enter" id="permission_enter" value="1" <?php echo $main_info['permission_enter'] == 1 ? ' checked' : '' ?>>
					<label class="form-check-label" for="permission_enter">Permission to Enter</label>
				</div>
				<div class="form-check form-check-inline" id="box_has_animal">
					<input class="form-check-input" type="checkbox" name="has_animal" id="has_animal" value="1" <?php echo $main_info['has_animal'] == 1 ? ' checked' : '' ?>>
					<label class="form-check-label" for="has_animal">Animal in Unit</label>
				</div>
			</div>

			<div class="row mb-3">
<?php if ($User->checkAccess('hca_fs', 12) && $main_info['work_status'] == 0): ?>
				<div class="col-md-4">
					<label class="form-label" for="fld_employee_id">Technician name</label>
					<div id="technician_list">
						<h5><?php echo ($main_info['realname'] != '') ? html_encode($main_info['realname']) : 'Not assigned' ?><h5>
					</div>
				</div>
<?php else: ?>
				<div class="col-md-3">
					<label class="form-label">Technician name</label>
					<h5><?php echo ($main_info['realname'] != '') ? html_encode($main_info['realname']) : 'Not assigned' ?><h5>
				</div>
<?php endif; ?>
				<div class="col-md-2">
					<label class="form-label" for="time_slot">Time</label>
					<select name="time_slot" id="time_slot" class="form-select">
<?php
foreach ($time_slots as $key => $val)
{
	if ($main_info['time_slot'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<hr>

			<div class="mb-3">
				<label class="form-label" for="request_msg">Task</label>
				<textarea type="text" name="request_msg" class="form-control" id="request_msg"><?php echo html_encode($main_info['request_msg']) ?></textarea>
			</div>

<?php
$i = 2;
if (!empty($tasks_info))
{
	foreach($tasks_info as $cur_task)
	{
?>
			<div class="mb-3">
				<label class="form-label" for="fld_task_text_<?php echo $cur_task['id'] ?>">Task</label>
				<textarea name="task_text[<?php echo $cur_task['id'] ?>]" class="form-control" placeholder="Your comment" id="fld_task_text_<?php echo $cur_task['id'] ?>"><?php echo html_encode($cur_task['request_text']) ?></textarea>
				<label class="form-label float-end"><button type="submit" name="delete_task[<?php echo $cur_task['id'] ?>]" class="badge bg-danger">Delete task</button></label>
			</div>
<?php
		++$i;
	}
}
?>

			<div class="task"></div>

			<button type="button" class="btn btn-sm btn-info" onclick="addTask()">+ add additional task</button>

<?php
$files = $SwiftUploader->getProjectFiles('hca_fs_requests', $id);
if (!empty($files))
{
	echo '<hr>';
	echo '<h6 class="card-title mb-1">Uploaded files</h6>';
	foreach($files as $file_info)
	{
		$file_link = BASE_URL.'/'.$file_info['file_path'].$file_info['file_name'];
		echo '<div style="display: inline-block; margin-left:1em">';

		if ($file_info['file_type'] == 'image')
			echo '<a data-fancybox="single" href="'.$file_link.'" target="_blank"><img style="height:84px;margin-right:5px" src="'.$file_link.'"></a>';
		else
			echo '<a data-fancybox="single" href="'.$URL->link($file_info['file_path'].$file_info['file_name']).'" target="_blank" class="text-primary"><i class="fas fa-7x fa-file-pdf"></i></a>';

		echo '<p><button type="submit" name="delete_file['.$file_info['id'].']" class="badge bg-danger" confirm(\'Are you sure you want to delete this file?\')">Delete file</button></p>';
		echo '</div>';
	}
}
?>
			<hr>

			<?php $SwiftUploader->setForm() ?>

			<div class="mb-3">
<?php if ($User->checkAccess('hca_fs', 12) && $main_info['work_status'] == 0): ?>
				<button type="submit" name="approve_request" class="btn btn-sm btn-primary">Approve</button>
<?php else: ?>
				<button type="submit" name="update_request" class="btn btn-sm btn-primary">Update request</button>
<?php endif; ?>
				<button type="submit" name="complete_request" class="btn btn-success btn-sm">Completed</button>
				<button type="submit" name="cancel_request" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel property request?')">Cancel work</button>
			</div>

			<div class="alert alert-info" role="alert">
				<h5 class="alert-heading">Information</h5>
				<hr class="my-2">
<?php if ($User->checkAccess('hca_fs', 12) && $main_info['work_status'] == 0): ?>
				<p><strong>Approve</strong> - use this action to schedule request. Property manager will receive a notice.</p>
<?php endif; ?>
				<p><strong>Completed</strong> - use this action if work already completed.</p>
				<p><strong>Cancel work</strong> - use this action if work no longer needed.</p>
			</div>

		</div>
	</div>
</form>

<script>
var task_number = 2;
function addTask()
{
	var fld = '<div class="mb-3 task" id="task_'+task_number+'"><label class="form-label">Task</label><textarea name="request_text[]" class="form-control"></textarea><label class="form-label float-end"><span class="badge bg-danger" onclick="deleteTask('+task_number+')">Remove task</span></label></div>';
	$(fld).insertAfter($('.task').last());
	task_number = task_number + 1;
}
function deleteTask(id)
{
	$('#task_'+id).remove();
}
function hideAnimalUnit()
{
	var id = $("#template_type").val();
	if (id == 0)
		$("#box_has_animal").css("display", "inline-block");
	else
		$("#box_has_animal").css("display", "none");
}
function checkAvailableTechnician(){
	var id = <?php echo $id ?>;
	var start_date = $("#fld_start_date").val();
	var gid = <?php echo $main_info['group_id'] ?>;
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_available_technician')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_available_technician') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,start_date:start_date,gid:gid,csrf_token:csrf_token}),
		success: function(re){
			$("#technician_list").empty().html(re.technician_list);
		},
		error: function(re){
			document.getElementById("input_unit_number").innerHTML = re;
		}
	});
}
function getAvailableTimeSlots()
{
	var uid = $("#fld_maint_id").val();
	var start_date = $("#fld_start_date").val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_available_technician')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_available_technician') ?>",
		type:	"POST",
		dataType: "json",
		data: ({start_date:start_date,uid:uid,csrf_token:csrf_token}),
		success: function(re){
			$("#available_slots").empty().html(re.available_slots);
		},
		error: function(re){
			document.getElementById("input_unit_number").innerHTML = re;
		}
	});
}

document.addEventListener("DOMContentLoaded", function() {
	checkAvailableTechnician();
});
</script>

<?php
}
else
{
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Create a new request</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="template_type">Template Type</label>
					<select name="template_type" id="template_type" class="form-select" onchange="hideAnimalUnit()">
<?php
foreach($template_types as $key => $val) {
	if (isset($_POST['template_type']) && $_POST['template_type'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($val).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
<?php if ($access2) : ?>
				<div class="col-md-3">
					<label class="form-label" for="selected_geo_code">GL Code</label>
					<input type="text" name="selected_geo_code" value="<?php echo (isset($_POST['selected_geo_code']) ? html_encode($_POST['selected_geo_code']) : '') ?>" class="form-select" list="geo_code" placeholder="Enter GL Code">
					<datalist id="geo_code">
<?php
$geo_codes = explode(',', ($Config->get('o_hca_fs_geo_codes')));
foreach ($geo_codes as $geo_code) 
	echo "\t\t\t\t\t\t\t".'<option value="'.$geo_code.'">'."\n";
?>
					</datalist>
				</div>
<?php endif; ?>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="property_id">Property</label>
					<select id="property_id" name="property_id" class="form-select" required onchange="getUnits()">
<?php
echo '<option value="0" selected="selected" disabled>Select Property</option>'."\n";
foreach ($property_info as $cur_info) {
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>

				<div class="col-md-2">
					<label class="form-label" for="form_unit_number">Location</label>
					<div id="input_unit_number">
						<input type="text" name="unit_number" value="<?php echo (isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : '') ?>" class="form-control" id="form_unit_number" placeholder="Enter unit #">
					</div>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="group_id" id="group_id1" value="<?php echo $Config->get('o_hca_fs_maintenance') ?>" checked>
						<label class="form-check-label" for="group_id1">Maintenance</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="group_id" id="group_id2" value="<?php echo $Config->get('o_hca_fs_painters') ?>" <?php echo isset($_POST['group_id']) && $_POST['group_id'] == $Config->get('o_hca_fs_painters') ? ' checked' : '' ?>>
						<label class="form-check-label" for="group_id2">Painter</label>
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="permission_enter" id="permission_enter" value="1" <?php echo isset($_POST['permission_enter']) && $_POST['permission_enter'] == 1 ? ' checked' : '' ?>>
						<label class="form-check-label" for="permission_enter">Permission to Enter</label>
					</div>
					<div class="form-check form-check-inline" id="box_has_animal">
						<input class="form-check-input" type="checkbox" name="has_animal" id="has_animal" value="1" <?php echo isset($_POST['has_animal']) && $_POST['has_animal'] == 1 ? ' checked' : '' ?>>
						<label class="form-check-label" for="has_animal">Animal in Unit</label>
					</div>
				</div>
			</div>
			
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="start_date">Date Requested</label>
					<input type="date" name="start_date" value="<?php echo (isset($_POST['start_date']) ? html_encode($_POST['start_date']) : '') ?>" class="form-control" id="start_date">
				</div>
				<div class="col-md-2">
					<label class="form-label" for="time_slot">Time</label>
					<select name="time_slot" id="time_slot" class="form-select">
<?php
foreach ($time_slots as $key => $val) {
	if (isset($_POST['time_slot']) && $_POST['time_slot'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>

				<div class="col-md-2">
					<label class="form-label" for="execution_priority">Urgency</label>
					<select name="execution_priority" id="execution_priority" class="form-select">
<?php
foreach ($execution_priority as $key => $val)
{
	if (isset($_POST['execution_priority']) && $_POST['execution_priority'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<hr>

			<div class="mb-3">
				<label class="form-label" for="request_msg">Task</label>
				<textarea type="text" name="request_msg" class="form-control" id="request_msg"><?php echo (isset($_POST['request_msg']) ? html_encode($_POST['request_msg']) : '') ?></textarea>
			</div>

			<div class="task">
				<!--task_list-->
			</div>

			<button type="button" class="btn btn-sm btn-info" onclick="addTask()">+ add additional task</button>

			<hr>

			<?php $SwiftUploader->setForm() ?>

			<div class="mb-3">
				<button type="submit" name="new_request" class="btn btn-primary">Send request</button>
			</div>
		</div>
	</div>
</form>

<script>
var task_number = 2;
function addTask()
{
	var fld = '<div class="mb-3 task" id="task_'+task_number+'"><label class="form-label">Task</label><textarea name="request_text[]" class="form-control"></textarea><label class="form-label float-end"><span class="badge bg-danger" onclick="deleteTask('+task_number+')">Remove task</span></label></div>';
	$(fld).insertAfter($('.task').last());
	task_number = task_number + 1;
}
function deleteTask(id)
{
	$('#task_'+id).remove();
}
function hideAnimalUnit()
{
	var id = $("#template_type").val();
	if (id == 0)
		$("#box_has_animal").css("display", "inline-block");
	else
		$("#box_has_animal").css("display", "none");
}
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_units')) ?>";
	var id = $("#property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#input_unit_number").empty().html(re.unit_number);
		},
		error: function(re){
			document.getElementById("input_unit_number").innerHTML = re;
		}
	});
}
</script>

<?php
}

require SITE_ROOT.'footer.php';
