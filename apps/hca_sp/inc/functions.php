<?php 

function hca_sp_get_property_manager_id($property_id)
{
	global $DBLayer, $User;
	
	$query = [
		'SELECT'	=> 'u.id, u.realname, u.email',
		'FROM'		=> 'users AS u',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'user_access AS a',
				'ON'			=> 'u.group_id=a.a_gid'
			],
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.manager_id=u.id'
			],
		],
		'ORDER BY'	=> 'u.realname',
		'WHERE'		=> 'a.a_to=\'hca_sp\' AND a.a_key=14 AND a.a_value=1 AND p.id='.$property_id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users = [];
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$users[] = [
			'id'		=> $row['id'],
			'realname'	=> 'Property Manager'
		];
	}

	return $users;
}

function sm_sp_check_mailing($val = -1, $user_mailing = '')
{
	global $User;
	
	$output = false;
	
	if ($user_mailing != '')
	{
		$sm_sp_mailing = explode(',', $user_mailing);
	
		if (in_array($val, $sm_sp_mailing))
			$output = true;
	}
	
	return $output;
}

function sm_special_projects_get_num_wish_projects()
{
	global $DBLayer, $User;
	
	$count = 0;
	$query = array(
		'SELECT'	=> 'id',
		'FROM'		=> 'sm_special_projects_records',
		'WHERE'		=> 'work_status=3',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		++$count;
	}
	
	return $count;
}

function sm_special_projects_num_completed()
{
	global $DBLayer;
	
	$num = 0;
	$query = array(
		'SELECT'	=> 'id',
		'FROM'		=> 'sm_special_projects_records',
		'WHERE'		=> 'main_manager_approved=0 AND work_status=5',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		++$num;
	}
	
	return $num;
}

//CHECK ALL EVENTS and send email - CALLED IN FOOTER JS
function sm_special_projects_check_all_events()
{
	global $DBLayer, $URL, $Config;
	
	$time_now = time();
	
	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.e_date, e.e_message, e.email_status, r.project_number, r.project_manager, r.project_manager_id, r.second_manager, r.second_manager_id, r.project_desc, r.start_date, r.end_date, r.budget, r.cost, r.remarks, pt.pro_name',
		'FROM'		=> 'sm_special_projects_events AS e',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_special_projects_records AS r',
				'ON'			=> 'r.id=e.project_id'
			),
			array(
				'LEFT JOIN'		=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=r.property_id'
			),
		),
		'WHERE'		=> 'e.e_date > '.$time_now,
		'ORDER BY'	=> 'e.e_date ASC',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$events_info = array();
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $fetch_assoc;
	}
	
	if (!empty($events_info))
	{
		$users_info = array();
		$query = array(
			'SELECT'	=> 'id, realname, email, sm_special_projects_notify_time, sm_sp_mailing',
			'FROM'		=> 'users',
			'WHERE'		=> 'sm_special_projects_access > 0'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			if (sm_sp_check_mailing(4, $row['sm_sp_mailing']))
				$users_info[$row['id']] = $row;
		}
		
		foreach ($events_info as $event_info)
		{
			$mailed = false;
			$manager_one = isset($users_info[$event_info['project_manager_id']]) ? $users_info[$event_info['project_manager_id']] : '';
			$manager_two = isset($users_info[$event_info['second_manager_id']]) ? $users_info[$event_info['second_manager_id']] : '';
			$mail_subject = 'Special Project - Upcoming Work';
			
			if (!empty($manager_one))
			{
				$time_notify_before = $time_now + ($manager_one['sm_special_projects_notify_time'] * 3600);
				
				if ($event_info['e_date'] < $time_notify_before && $event_info['email_status'] == 0)
				{
					$mail_message = 'You have an upcoming work.'."\n\n";
					
					$mail_message .= 'Project ID number: '.$event_info['project_number']."\n";
					$mail_message .= 'Property name: '.$event_info['pro_name']."\n";
					$mail_message .= 'Project Managers: '.(($event_info['second_manager'] != '') ? $event_info['project_manager'].', '.$event_info['second_manager'] : $event_info['project_manager'])."\n";
					$mail_message .= 'Description: '.(($event_info['project_desc'] != '') ? html_encode($event_info['project_desc']) : 'N/A')."\n";
					$mail_message .= 'Remarks: '.(($event_info['remarks'] != '') ? html_encode($event_info['remarks']) : 'N/A')."\n";
					$mail_message .= 'Start Date: '.format_time($event_info['start_date'],1)."\n";
					$mail_message .= 'End Date: '.format_time($event_info['end_date'],1)."\n";
					$mail_message .= 'Budget: $.'.$event_info['budget']."\n";
					$mail_message .= 'Cost: $.'.$event_info['cost']."\n";
					$mail_message .= 'Upcoming work: '.format_time($event_info['e_date']).' - '.html_encode($event_info['e_message'])."\n\n";
					$mail_message .= 'Follow this link to manage this project: '.$URL->link('sm_special_projects_manage_follow_up', $event_info['project_id'])."\n";
					
					$SwiftMailer = new SwiftMailer;
					$SwiftMailer->send($manager_one['email'], $mail_subject, $mail_message);

					$mailed = true;
				}
			}
			
			if (!empty($manager_two))
			{
				$time_notify_before = $time_now + ($manager_two['sm_special_projects_notify_time'] * 3600);
				
				if ($event_info['e_date'] < $time_notify_before && $event_info['email_status'] == 0)
				{
					$mail_message = 'You have an upcoming work.'."\n\n";
					
					$mail_message .= 'Project ID number: '.$event_info['project_number']."\n";
					$mail_message .= 'Property name: '.$event_info['pro_name']."\n";
					$mail_message .= 'Project Managers: '.(($event_info['second_manager'] != '') ? $event_info['project_manager'].', '.$event_info['second_manager'] : $event_info['project_manager'])."\n";
					$mail_message .= 'Description: '.(($event_info['project_desc'] != '') ? html_encode($event_info['project_desc']) : 'N/A')."\n";
					$mail_message .= 'Remarks: '.(($event_info['remarks'] != '') ? html_encode($event_info['remarks']) : 'N/A')."\n";
					$mail_message .= 'Start Date: '.format_time($event_info['start_date'],1)."\n";
					$mail_message .= 'End Date: '.format_time($event_info['end_date'],1)."\n";
					$mail_message .= 'Budget: $.'.$event_info['budget']."\n";
					$mail_message .= 'Cost: $.'.$event_info['cost']."\n";
					$mail_message .= 'Upcoming work: '.format_time($event_info['e_date']).' - '.html_encode($event_info['e_message'])."\n";
					$mail_message .= 'Follow this link to manage this project: '.$URL->link('sm_special_projects_manage_follow_up', $event_info['project_id'])."\n";
					
					$SwiftMailer = new SwiftMailer;
					$SwiftMailer->send($manager_two['email'], $mail_subject, $mail_message);

					$mailed = true;
				}
			}
			
			$query = array(
				'UPDATE'	=> 'sm_special_projects_events',
				'SET'		=> 'email_status=1',
				'WHERE'		=> 'id='.$event_info['id']
			);
			if ($mailed)
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}
}

//check next event and SHOWS on pages
function sm_special_projects_check_next_event($project_id, &$event_alert)
{
	global $DBLayer, $User, $Config;
	
	$event_alert = false;
	$output = '';
	$time_now = time();
	$time_notify_before = $time_now + ($User->get('sm_special_projects_notify_time') * 3600);
	
	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.e_date, e.e_message, e.email_status, r.project_manager, r.project_manager_id, r.second_manager, r.second_manager_id, r.project_desc, r.start_date, r.end_date, r.budget, r.cost, r.remarks, pt.pro_name',
		'FROM'		=> 'sm_special_projects_events AS e',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_special_projects_records AS r',
				'ON'			=> 'r.id=e.project_id'
			),
			array(
				'LEFT JOIN'		=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=r.property_id'
			),
		),
		'WHERE'		=> 'e.project_id='.$project_id.' AND e.e_date > '.$time_now,
		'ORDER BY'	=> 'e.e_date ASC',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$events_info = array();
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $fetch_assoc;
	}
	
	if (!empty($events_info))
	{
		foreach ($events_info as $event_info)
		{
			if (!empty($event_info['e_message']) && $event_info['e_date'] < $time_notify_before)
			{
				$output = '<p style="color:red;">'.format_time($event_info['e_date']).': '.$event_info['e_message'].'</p>';
				
				$event_alert = true;
			}
			else if (!empty($event_info['e_message'])) {
				$output = '<p>'.format_time($event_info['e_date']).': '.$event_info['e_message'].'</p>';
				break;
			}
		}
	}
	
	return $output;
}

//check next event and SHOWS on pages
function sm_special_projects_check_next_events($projects_ids)
{
	global $DBLayer, $User, $Config;
	
	$output = array();
	$time_now = time();
	$time_notify_before = $time_now + ($User->get('sm_special_projects_notify_time') * 3600);
	
	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.e_date, e.e_message, e.email_status, r.project_manager, r.project_manager_id, r.second_manager, r.second_manager_id, r.project_desc, r.start_date, r.end_date, r.budget, r.cost, r.remarks, pt.pro_name',
		'FROM'		=> 'sm_special_projects_events AS e',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_special_projects_records AS r',
				'ON'			=> 'r.id=e.project_id'
			),
			array(
				'LEFT JOIN'		=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=r.property_id'
			),
		),
		'WHERE'		=> 'e.project_id IN('.implode(',', $projects_ids).') AND e.e_date > '.$time_now,
		'ORDER BY'	=> 'e.e_date ASC',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$events_info = array();
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $fetch_assoc;
	}
	
	if (!empty($events_info))
	{
		foreach ($events_info as $event_info)
		{
			if (!empty($event_info['e_message']) && $event_info['e_date'] < $time_notify_before)
			{
				$output[$event_info['project_id']] = '<p style="background:pink;">'.format_time($event_info['e_date']).': '.$event_info['e_message'].'</p>';
			}
			else if (!empty($event_info['e_message'])) {
				$output[$event_info['project_id']] = '<p>'.format_time($event_info['e_date']).': '.$event_info['e_message'].'</p>';
				break;
			}
		}
	}
	
	return $output;
}
