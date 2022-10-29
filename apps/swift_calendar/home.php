<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message('You do not have permission.');

if(!isset($_SESSION['access_token'])) {
	header('Location: google-login.php');
	exit();	
}

//header('Content-type: application/json');
if(isset($_POST['create'])){
	
	$event = $_POST['form'];
	$start_time = isset($event['event_time']['start_time']) ? strtotime($event['event_time']['start_time']) : 0;
	$end_time = isset($event['event_time']['end_time']) ? strtotime($event['event_time']['end_time']) : 0;
	
	if ($start_time == 0 || $end_time == 0 || $start_time > $end_time)
		$Core->add_error('The time is not correct');
	
	if (empty($Core->errors))
	{
		$event['event_time']['start_time'] .= ':00';
		$event['event_time']['end_time'] .= ':00';
		$event_id = '';
		
		try {
			$capi = new GoogleCalendarApi();
		
			// Get user calendar timezone
			//$user_timezone = $capi->GetUserCalendarTimezone($_SESSION['access_token']);
			$user_timezone = 'America/Los_Angeles';
		
			// Create event on primary calendar  //$event['all_day'] = 0
			$event_id = $capi->CreateCalendarEvent('primary', $event['title'], 0, $event['event_time'], $user_timezone, $_SESSION['access_token']);
			
			echo json_encode([ 'event_id' => $event_id ]);
		}
		catch(Exception $e) {
			header('Bad Request', true, 400);
			echo json_encode(array( 'error' => 1, 'message' => $e->getMessage() ));
		}
		
		if ($event_id != '') {
			// Show, save or do something else
			echo $event_id;
		}
		
		echo '<pre>';
		print_r($event);
		echo '</pre>';
	}
	
	// Add flash message
	$FlashMessenger->add_info('Event was created: '.$event_id );
	redirect($URL->link('sm_calendar_google_home'), '.');
}

$Core->set_page_title('Sign in with Google');
$Core->set_page_id('sm_calendar_google_home', 'sm_calendar');
require SITE_ROOT.'header.php';
?>
	
<div class="main-content main-frm">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="content-head">
			<h6 class="hn"><span>Create new Google Calendar event</span></h6>
		</div>
		<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
			<legend class="group-legend"><strong></strong></legend>
			<input type="hidden" name="form[all_day]" value="0"/>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Start time</span>	<small>Start time of event</small></label><br />
					<span class="fld-input"><input type="datetime-local" id="fld<?php echo $page_param['fld_count'] ?>" name="form[event_time][start_time]" value="<?php echo date('Y-m-d\TH:i',time()) ?>" required/></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>End time</span>	<small>End time of event</small></label><br />
					<span class="fld-input"><input type="datetime-local" id="fld<?php echo $page_param['fld_count'] ?>" name="form[event_time][end_time]" value="<?php echo date('Y-m-d\TH:i',time()) ?>" required/></span>
				</div>
			</div>
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Message</span><small>Write a message to be sent to the Google Calendar.</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="form[title]" rows="3" cols="55" required></textarea></span></div>
				</div>
			</div>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="create" value="Create event"></span>
		</div>
	</form>
</div>

<?php
require SITE_ROOT.'footer.php';