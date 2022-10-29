<?php

if (!defined('DB_CONFIG')) die();

function swift_calendar_IncludeEssentials()
{
    require SITE_ROOT.'apps/swift_calendar/inc/functions.php';
    require SITE_ROOT.'apps/swift_calendar/inc/google-calendar-api.php';
}

function swift_calendar_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'swift_calendar';

    $urls['sm_calendar'] = 'apps/'.$app_id.'/';
    //OLD REMOVE
    $urls['sm_calendar_calendar'] = 'apps/'.$app_id.'/calendar.php?week_of=$1&project_id=$2&property_id=$3&building_number=$4';
    
    $urls['sm_calendar_new_project'] = 'apps/'.$app_id.'/new_project.php';
    $urls['sm_calendar_projects'] = 'apps/'.$app_id.'/projects.php';
    $urls['sm_calendar_events'] = 'apps/'.$app_id.'/events.php?date=$1&pid=$2&pname=$3';
    $urls['sm_calendar_vendors'] = 'apps/'.$app_id.'/vendors.php';
    $urls['sm_calendar_settings'] = 'apps/'.$app_id.'/settings.php';
    
    $urls['sm_calendar_outlook_viewer'] = 'misc.php?section=outlook_viewer';
    
    $urls['sm_calendar_google_redirect_url'] = 'apps/'.$app_id.'/google-login.php';
    $urls['sm_calendar_google_home'] = 'apps/'.$app_id.'/home.php';
    $urls['sm_calendar_google_ajax'] = 'apps/'.$app_id.'/ajax.php';
    $urls['sm_calendar_google_api'] = 'apps/'.$app_id.'/google-calendar-api.php';
    $urls['sm_calendar_google_logout'] = 'apps/'.$app_id.'/logout.php';
    
    $urls['sm_calendar_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';

    $URL->add_urls($urls);
}

function swift_calendar_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    if ($User->is_admin())
    {
        $SwiftMenu->addItem(['title' => 'Calendar', 'link' =>  $URL->link('sm_calendar_projects'), 'id' => 'swift_calendar', 'icon' => '<i class="far fa-calendar-alt"></i>']);

        $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('sm_calendar_new_project'), 'id' => 'sm_calendar_new_project', 'parent_id' => 'swift_calendar']);
        $SwiftMenu->addItem(['title' => 'Active Projects', 'link' => $URL->link('sm_calendar_projects'), 'id' => 'sm_calendar_projects', 'parent_id' => 'swift_calendar']);
        $SwiftMenu->addItem(['title' => 'Current Events', 'link' => $URL->link('sm_calendar_events', array(date('Y-m', time()), 0, 'all')), 'id' => 'sm_calendar_events', 'parent_id' => 'swift_calendar']);

        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('sm_calendar_settings'), 'id' => 'sm_calendar_settings', 'parent_id' => 'swift_calendar']);
    }
    
    if ($User->get('sm_calendar_outlook_viewer') != '')
        $SwiftMenu->addNavbarProfileLink('<li><a class="dropdown-item" href="'.$URL->link('sm_calendar_outlook_viewer').'"><i class="far fa-calendar-alt"></i> Outlook</a></li>');

}

class SwiftCalendarHooks
{
    private static $singleton;

    public static function getInstance(){
        return self::$singleton = new self;
    }

    public static function singletonMethod(){
        return self::getInstance();
    }

    public function MiscNewSection()
    {
        global $Core, $User, $section, $Templator, $Hooks, $URL, $Config, $FlashMessenger, $SwiftMenu, $PagesNavigator, $Loader, $DBLayer;

        if ($section == 'outlook_viewer' && $User->get('sm_calendar_outlook_viewer') != '')
        {
            $Core->set_page_id('sm_calendar_outlook_viewer');
            require SITE_ROOT.'header.php';
        ?>
            <div class="main-content main-frm">
                <iframe src="<?php echo $User->get('sm_calendar_outlook_viewer') ?>"  style="position:fixed;top:60px;left:0;bottom:0;right:0; width:100%;height:100%;border:none;margin:0;padding:0;overflow:hidden;margin-left:20px;"></iframe>
            </div>
        <?php
            require SITE_ROOT.'footer.php';
        }
    }

    public function ProfileChangeDetailsSettingsValidation()
    {
        global $form;

        if (isset($_POST['form']['sm_calendar_outlook_viewer']))
            $form['sm_calendar_outlook_viewer'] = swift_trim($_POST['form']['sm_calendar_outlook_viewer']);
        if (isset($_POST['form']['sm_calendar_outlook_email']))
            $form['sm_calendar_outlook_email'] = swift_trim($_POST['form']['sm_calendar_outlook_email']);
            
        if (isset($_POST['form']['sm_calendar_google_client_id']))
            $form['sm_calendar_google_client_id'] = swift_trim($_POST['form']['sm_calendar_google_client_id']);
        if (isset($_POST['form']['sm_calendar_google_client_secret']))
            $form['sm_calendar_google_client_secret'] = swift_trim($_POST['form']['sm_calendar_google_client_secret']);
    }

    public function ProfileChangeDetailsSettingsEmailFieldsetEnd()
    {
        global $URL, $User, $user, $id;

        if ($id == $User->get('id') && ($User->get('sm_calendar_access') > 0) || $User->is_admin())
        {
?>
			<div class="card-header">
				<h6 class="card-title mb-0">Outlook Calendar settings</h6>
			</div>
			<div class="card-body">	
				<div class="mb-3">
					<label class="form-label" for="input_sm_calendar_outlook_email">Outlook Email: Insert email linked to Outlook</label>
					<input type="text" name="form[sm_calendar_outlook_email]" value="<?php echo $user['sm_calendar_outlook_email'] ?>" class="form-control" id="input_sm_calendar_outlook_email">
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_sm_calendar_outlook_viewer">Shared Link: Insert link to view Outlook Calendar</label>
					<input type="text" name="form[sm_calendar_outlook_viewer]" value="<?php echo $user['sm_calendar_outlook_viewer'] ?>" class="form-control" id="input_sm_calendar_outlook_viewer">
				</div>
			</div>

            <style type="text/css">#logo {text-align: center;width: 150px;display: block;margin: 0 12px;border: 2px solid #2980b9;padding: 5px;color: #2980b9;cursor: pointer;text-decoration: none;border-radius: 5px;}</style>

			<div class="card-header">
				<h6 class="card-title mb-0">Google Calendar settings</h6>
			</div>
			<div class="card-body">	
				<div class="mb-3">
					<label class="form-label" for="input_sm_calendar_google_client_id">Client ID: Insert Client ID of Google Calendar</label>
					<input type="text" name="form[sm_calendar_google_client_id]" value="<?php echo $user['sm_calendar_google_client_id'] ?>" class="form-control" id="input_sm_calendar_google_client_id">
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_sm_calendar_google_client_secret">Client Secret Key: Insert Client Sekret key of Google Calendar</label>
					<input type="text" name="form[sm_calendar_google_client_secret]" value="<?php echo $user['sm_calendar_google_client_secret'] ?>" class="form-control" id="input_sm_calendar_google_client_secret">
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_sm_calendar_outlook_viewer">Client Redirect URL: Copy this link and paste to API settings of Google Calendar</label>
					<input type="text" value="<?php echo $URL->link('sm_calendar_google_redirect_url') ?>" class="form-control" id="input_sm_calendar_outlook_viewer" onfocus="javascript:this.select()">
				</div>
			</div>
<?php 
            if ($User->get('sm_calendar_google_client_id') != '' && $User->get('sm_calendar_google_client_secret') != '')
            {
 ?>
		
			<div class="card-header">
				<h6 class="card-title mb-0">Connect to Google Account</h6>
			</div>
			<div class="card-body">	
				<div class="alert alert-warning fw-bold" role="alert">
<?php 
		$login_url = 'https://accounts.google.com/o/oauth2/auth?scope='.urlencode('https://www.googleapis.com/auth/calendar').'&redirect_uri='.urlencode($URL->link('sm_calendar_google_redirect_url')).'&response_type=code&client_id='.$User->get('sm_calendar_google_client_id').'&access_type=online';
		
		if(!isset($_SESSION['access_token']))
			echo '<a id="logo" style="background:#fff1c0;" href="'.$login_url.'">Login with Google</a>';
		else
			echo '<a id="logo" style="background:lightgreen;" href="'.$URL->link('sm_calendar_google_logout').'">Logout</a>';
?>
				</div>
			</div>
<?php
            }
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('MiscNewSection', ['SwiftCalendarHooks', 'MiscNewSection']);
Hook::addAction('ProfileChangeDetailsSettingsValidation', ['SwiftCalendarHooks', 'ProfileChangeDetailsSettingsValidation']);
Hook::addAction('ProfileChangeDetailsSettingsEmailFieldsetEnd', ['SwiftCalendarHooks', 'ProfileChangeDetailsSettingsEmailFieldsetEnd']);
