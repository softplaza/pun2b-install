<?php

define('SITE_ROOT', '../../');
require_once SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message('You do not have permission.');

if ($User->get('sm_calendar_google_client_id') == '')
	$Core->add_error('Client ID cannot be empty. Go to your PROFILE => SETTINGS and fill it.');
if ($User->get('sm_calendar_google_client_secret') == '')
	$Core->add_error('Client Secret cannot be empty. Go to your PROFILE => SETTINGS and fill it.');

if (isset($_GET['code'])) {
	try {
		$capi = new GoogleCalendarApi();
		
		// Get the access token 
		$data = $capi->GetAccessToken($User->get('sm_calendar_google_client_id'), $URL->link('sm_calendar_google_redirect_url'), $User->get('sm_calendar_google_client_secret'), $_GET['code']);
		
		// Save the access token as a session variable
		$_SESSION['access_token'] = $data['access_token'];

		// Redirect to the page where user can create event
//		header('Location: ');
//		exit();
		// Add flash message
		$FlashMessenger->add_info('You are logged in with Google.');
		redirect($URL->link('profile_settings', $User->get('id')), '.');
	}
	catch(Exception $e) {
		echo $e->getMessage();
		exit();
	}
}

$login_url = 'https://accounts.google.com/o/oauth2/auth?scope='.urlencode('https://www.googleapis.com/auth/calendar').'&redirect_uri='.urlencode($URL->link('sm_calendar_google_redirect_url')) . '&response_type=code&client_id='.$User->get('sm_calendar_google_client_id').'&access_type=online';

$Core->set_page_title('Sign in with Google');
$Core->set_page_id('sm_calendar_google_home', 'sm_calendar');
require SITE_ROOT.'header.php';
?>

<style type="text/css">
#logo {
	text-align: center;
	width: 200px;
    display: block;
    margin: 100px auto;
    border: 2px solid #2980b9;
    padding: 10px;
    background: none;
    color: #2980b9;
    cursor: pointer;
    text-decoration: none;
}
</style>

<div class="main-content main-frm">
<?php 
if (empty($Core->errors))
{
	echo '<a id="logo" href="'.$login_url.'">Login with Google</a>';
} ?>
</div>

<?php
require SITE_ROOT.'footer.php';