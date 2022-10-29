<?php if (!defined('DB_CONFIG')) die();

if ($section == 'outlook_viewer' && $User->get('sm_calendar_outlook_viewer') != '')
{
//	sm_outlook_calendar_create_event();
	if (!isset($lang_profile))
		require SITE_ROOT.'lang/'.$User->get('language').'/profile.php';
	
	$Core->set_page_id('help');
	require SITE_ROOT.'header.php';
?>
	<div class="main-content main-frm">
		<iframe src="<?php echo $User->get('sm_calendar_outlook_viewer') ?>"  style="position:fixed; top:60px; left:0; bottom:0; right:0; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;"></iframe>
	</div>
<?php
	require SITE_ROOT.'footer.php';
}
