<?php

if (!defined('SITE_ROOT'))
	define('SITE_ROOT', '../../');

require SITE_ROOT.'include/common.php';
header('Content-type: application/json');

if ($User->is_guest())
	message('You do not have permission.');

if(isset($_POST['form']))
{
	// Get event details
	$event = $_POST['form'];

	try {
		$capi = new GoogleCalendarApi();
	
		// Get user calendar timezone
		//	$user_timezone = $capi->GetUserCalendarTimezone($_SESSION['access_token']);
		$user_timezone = 'America/Los_Angeles';
	
		// Create event on primary calendar  //$event['all_day'] = 0
		$event_id = $capi->CreateCalendarEvent('primary', $event['title'], 0, $event['event_time'], $user_timezone, $_SESSION['access_token']);
		
		echo json_encode([ 'event_id' => $event_id ]);
	}
	catch(Exception $e) {
		header('Bad Request', true, 400);
		echo json_encode(array( 'error' => 1, 'message' => $e->getMessage() ));
	}
}
