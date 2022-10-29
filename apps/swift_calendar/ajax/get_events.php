<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
$date = isset($_POST['date']) ? intval($_POST['date']) : 0;

// FIST OF EVENTS
if ($date > 0)
{
	$events_info = array();
	$json_data = '';
	
	$query = array(
		'SELECT'	=> 'e.*, u.realname',
		'FROM'		=> 'sm_calendar_events AS e',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=e.poster_id'
			),
		),
//		'WHERE'		=> 'e.date='.$date.' AND e.poster_id='.$User->get('id'),
		'WHERE'		=> 'e.date='.$date,
		'ORDER BY'	=> 'e.time',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $row;
	}
	
	if (!empty($events_info))
	{
		
		foreach($events_info as $cur_info)
		{
			$json_data .= '<div class="sb-event">'."\n";
			$json_data .= '<p>'.format_time($cur_info['time'], 2).', <strong>'.html_encode($cur_info['subject']).'</strong></p>'."\n";
			
			if ($cur_info['project_name'] == 'hca_5840' && $cur_info['project_id'] > 0)
			{
				$query = array(
					'SELECT'	=> 'p.property_name, p.unit_number',
					'FROM'		=> 'hca_5840_projects AS p',
					'WHERE'		=> 'p.id='.$cur_info['project_id'],
				);
				$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
				$project_info = $DBLayer->fetch_assoc($result);
				
				$json_data .= '<p>Property: <strong>'.html_encode($project_info['property_name']).'</strong>, Unit# <strong>'.html_encode($project_info['unit_number']).'</strong></p>'."\n";
			}
			
			$json_data .= '<p>'.html_encode($cur_info['message']).'</p>'."\n";
			
			if ($User->get('id') == $cur_info['poster_id'])
			{
				$json_data .= '<p class="event-actions">';
				$json_data .= '<input type="image" src="'.BASE_URL.'/img/delete.gif" name="delete_event['.$cur_info['id'].']" onclick="return confirm(\'Are you sure you want to delete this event?\')"/>';
				$json_data .= '<img src="'.BASE_URL.'/img/edit.png" onclick="editEvent('.$cur_info['id'].')" />';
				$json_data .= '</p>'."\n";
			}
			$json_data .= '</div>'."\n";
		}
	}
	
	echo json_encode(array(
		//Use for Calendar
		'event_date'		=> format_time(strtotime($date), 1),
		'sb_events'			=> !empty($json_data) ? $json_data : '<div class="sb-event"><p>No events</p></div>',
		'start_time'		=> '<input type="datetime-local" name="start_time" value="'.date('Y-m-d\TH:i', strtotime($date)).'" />'
	));
}

// ONE EVENT
else if ($event_id > 0)
{
	$query = array(
		'SELECT'	=> 'e.*',
		'FROM'		=> 'sm_calendar_events AS e',
		'WHERE'		=> 'e.id='.$event_id,
		'ORDER BY'	=> 'e.time',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$cur_info = $DBLayer->fetch_assoc($result);
	
	echo json_encode(array(
		'start_time'		=> '<input type="datetime-local" name="start_time" value="'.date('Y-m-d\TH:i', $cur_info['time']).'">',
		'event_subject'		=> '<input type="text" name="subject" value="'.html_encode($cur_info['subject']).'" placeholder="Subject"/>',
		'event_message'		=> '<textarea name="message" rows="4" placeholder="Your comment">'.html_encode($cur_info['message']).'</textarea>'
	));
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();