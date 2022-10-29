<?php if (!defined('DB_CONFIG')) die(); 

if ($event['sent_to_outlook'] > 0)
	$page_param['cur_info']['message_box'] .= '<span class="sent-status">Sent to Outlook</span>';

if ($event['sent_to_google'] > 0)
	$page_param['cur_info']['message_box'] .= '<span class="sent-status">Sent ot Google</span>';