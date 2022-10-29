<?php if (!defined('DB_CONFIG')) die(); 

if (isset($_SESSION['access_token']) || $User->get('sm_calendar_outlook_email') != '')
{
	$disabled_sending_btn = ($event['sent_to_outlook'] > 0 || $event['sent_to_google'] > 0) ? ' disabled' : '';
	
	$page_param['cur_info']['btn_actions'] .= '<span class="submit primary"><input type="submit" name="add_to_calendar['.$event['id'].']" value="@" '.$disabled_sending_btn.'></span>';
	
	echo '<input type="hidden" name="calendar_time['.$event['id'].']" value="'.$event['e_date'].'" />';
	echo '<input type="hidden" name="calendar_text['.$event['id'].']" value="'.html_encode($event['e_message']).'" />';
}

