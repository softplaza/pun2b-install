<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

if ($id > 0)
{
	$cur_info = $DBLayer->select('sm_calendar_events', $id);
	
	echo json_encode(array(
		'event_datetime'		=> format_date($cur_info['date_time'], 'Y-m-d\TH:i'),
		'event_message'			=> html_encode($cur_info['message']),
	));
}
