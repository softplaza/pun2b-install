<?php 

//CHECK ALL UNCOMPLETED FORM FROM MANAGERS
// RUNS in manifest.xml ft_end
function sm_pest_control_check_unconfirmed_mngr_forms()
{
	global $DBLayer, $Config, $URL, $SwiftMailer;
 	
	$current_time = time();
	$manager_period_notify = $current_time - ($Config->get('o_sm_pest_control_manager_period_notify') * 3600);
 	
 	$forms_info = $mailed_ids = array();
	$query = array(
		'SELECT'	=> 'f.*, p.manager_email',
		'FROM'		=> 'sm_pest_control_forms AS f',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_pest_control_records AS r',
				'ON'			=> 'r.id=f.project_id'
			),
			array(
				'LEFT JOIN'		=> 'sm_property_db AS p',
				'ON'			=> 'p.id=r.property_id'
			),
		),
		'WHERE'		=> 'f.manager_check=0 AND f.submited_status=0 AND f.mailed_time < '.$manager_period_notify,
		'ORDER BY'	=> 'f.mailed_time'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$forms_info[] = $fetch_assoc;
	}
	
	if (!empty($forms_info))
	{
		foreach($forms_info as $info)
		{
			if (isset($info['manager_email']))
			{
				$new_project_intro = ($Config->get('o_sm_pest_control_manager_email_msg') != '') ? $DBLayer->escape($Config->get('o_sm_pest_control_manager_email_msg')) : $DBLayer->escape('Hello, ');
				$message = $new_project_intro."\n\n";
				$message .= 'Follow this link '.$DBLayer->escape($URL->link('sm_pest_control_form', array($info['id'], $info['link_hash'])));
				
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($info['manager_email'], 'HCA: Pest Control Project', $message);

				$mailed_ids[] = $info['id'];
				break;
			}
		}
		
		// UPDATE IDS
		if (!empty($mailed_ids))
		{
			$query = array(
				'UPDATE'	=> 'sm_pest_control_forms',
				'SET'		=> 'mailed_time=\''.$DBLayer->escape(time()).'\'',
				'WHERE'		=> 'id IN('.implode(',', $mailed_ids).')'
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}
}

// check next event 
// RUNS in active.php
function sm_pest_control_check_next_event($project_id, &$alert) {
	global $DBLayer, $User, $Config, $URL;
	
	$alert = false;
	$output = '';
	$current_time = time();
	$time_notify_before = $current_time + ($User->get('sm_pest_control_notify_time') * 3600);
	
	$query = array(
		'SELECT'	=> 'property, unit, created_by, remarks',
		'FROM'		=> 'sm_pest_control_records',
		'WHERE'		=> 'id='.$project_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = $DBLayer->fetch_assoc($result);

	$query = array(
		'SELECT'	=> 'id, time_slot, event_date, event_text, email_status',
		'FROM'		=> 'sm_pest_control_events',
		'WHERE'		=> 'project_id='.$project_id.' AND event_date>'.$current_time,
		'ORDER BY'	=> 'event_date ASC',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$events_info = array();
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$events_info[] = $fetch_assoc;
	}
	
	foreach ($events_info as $val) {
		if ($val['event_date'] < $time_notify_before) {
			$output = '<p>'.date('F d/y', $val['event_date']).': '.$val['event_text'].'</p>';
			$alert = true;
				break;
			
		} else if (!empty($val['event_text'])) {
			$output = '<p>'.date('F d/y', $val['event_date']).': '.$val['event_text'].'</p>';
			break;
		}
	}
	return $output;
}

// Check new incompleted project
// runs in /hooks/fn_generate_main_submenu_new_link.php
function sm_pest_control_get_num_msg()
{
	global $DBLayer;

	$query = array(
		'SELECT'	=> 'id',
		'FROM'		=> 'sm_pest_control_forms',
		'WHERE'		=> 'manager_check=1 AND submited_status=0',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$counter = 0;
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		++$counter;
	}
	return $counter;
}

function sm_pest_control_get_num_recycle()
{
	global $DBLayer;

	$query = array(
		'SELECT'	=> 'COUNT(id)',
		'FROM'		=> 'sm_pest_control_records',
		'WHERE'		=> 'unit_clearance=5'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$num_records = $DBLayer->result($result);
	
	$counter = isset($num_records) ? $num_records : 0;

	return $counter;
}

