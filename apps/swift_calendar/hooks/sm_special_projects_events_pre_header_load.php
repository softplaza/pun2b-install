<?php if (!defined('DB_CONFIG')) die(); 

if (isset($_POST['add_to_calendar']))
{
	$event_id = intval(key($_POST['add_to_calendar']));
	$event_time = isset($_POST['calendar_time'][$event_id]) ? intval($_POST['calendar_time'][$event_id ]) : 0;
	$event_text = isset($_POST['calendar_text'][$event_id]) ? swift_trim($_POST['calendar_text'][$event_id ]) : '';
	$time_now = time();
	
	if ($event_time > 0 && $event_text != '')
	{
		$query = array(
			'UPDATE'	=> 'sm_special_projects_events',
			'SET'		=> 'sent_to_outlook=0',
			'WHERE'		=> 'id='.$event_id
		);
		
		if ($User->get('sm_calendar_outlook_email') != '')
		{
			sm_calendar_outlook_create_event($event_time, $event_text, $event_text);
			
			$query['SET'] = 'sent_to_outlook=\''.$DBLayer->escape($time_now).'\'';
		}
		
		$event_time2 = array();
		$start_time2['start_time'] = date('Y-m-d\TH:i:s', $event_time);
		$end_time = $event_time + 1800;
		$start_time2['end_time'] = date('Y-m-d\TH:i:s', $end_time);
		
		if (isset($_SESSION['access_token']))
		{
			try {
				$capi = new GoogleCalendarApi();
			
				$user_timezone = 'America/Los_Angeles';
			
				// Create event on primary calendar  //$event['all_day'] = 0
				$event_id = $capi->CreateCalendarEvent('primary', $event_text, 0, $start_time2, $user_timezone, $_SESSION['access_token']);
				
				echo json_encode(['event_id' => $event_id]);
			}
			catch(Exception $e) {
				header('Bad Request', true, 400);
	//			echo json_encode(array( 'error' => 1, 'message' => $e->getMessage() ));
			}
			
			$query['SET'] .= ', sent_to_google=\''.$DBLayer->escape($time_now).'\'';
		}
		// update events in DB
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$FlashMessenger->add_info('Event has been added to Calendar');
		redirect($URL->link('sm_special_projects_manage_follow_up', $id), '.');
	}
	else $errors[] = 'Incorrect time or event text is empty.';
	
}

if (!isset($_SESSION['access_token']) && $User->get('sm_calendar_google_client_id') != '')
	$warnings[] = 'You are unauthorized with Google Calendar. If you want to add events to Google Calendar you need to log in. For authorization follow this link: <a href="'.$URL->link('sm_calendar_google_redirect_url').'">Google Calendar Login</a>';

