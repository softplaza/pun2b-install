<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$start_date = isset($_POST['date']) ? intval($_POST['date']) : 0;
$day_hours = hca_fs_get_time_slot();

$query = array(
	'SELECT'	=> 'r.request_desc, r.requested_uid',
	'FROM'		=> 'hca_fs_requests AS r',
	'WHERE'		=> 'r.id='.$id
);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$request_info = $fetch_assoc = $DBLayer->fetch_assoc($result);

function check_free_users($cur_user, $start_date, $requested_uid)
{
	global $DBLayer, $day_hours;
	
	$start_date = date('Ymd', $start_date);
	$query = array(
		'SELECT'	=> 'a.employee_id, a.start_time, a.end_time',
		'FROM'		=> 'hca_fs_assignment AS a',
		'WHERE'		=> 'a.scheduled='.$start_date,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$user_assignment = array();
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$user_assignment[] = $fetch_assoc;
	}
	
	$selected = ($requested_uid == $cur_user['id']) ? 'selected' : '';
	$output = '<option value="'.$cur_user['id'].'" '.$selected.'>'.$cur_user['realname'].'</option>';
	
	if (!empty($user_assignment))
	{
		foreach ($user_assignment as $assignment)
		{
			if ($assignment['employee_id'] == $cur_user['id'])
			{
				// 18 = 8:30am // 35 = 5:00pm
				if ($assignment['start_time'] > 18 || $assignment['start_time'] < 35)
				{
					$start_time = isset($day_hours[$assignment['start_time']]) ? $day_hours[$assignment['start_time']] : 'n/a';
					$end_time = isset($day_hours[$assignment['end_time']]) ? $day_hours[$assignment['end_time']] : 'n/a';
					$output = '<option value="'.$cur_user['id'].'" style="color:orange" '.$selected.' disabled>'.$cur_user['realname'].' ('.$start_time.' - '.$end_time.')'.'</option>';
				}
				else
					$output = '<option value="'.$cur_user['id'].'" disabled style="color:red" '.$selected.' disabled>'.$cur_user['realname'].'</option>';
				
//				break;
			}
		}
	}
	
	return $output;
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.realname, g.g_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		),
	),
	'WHERE'		=> 'g.hca_fs > 0',
	'ORDER BY'	=> 'g.g_id, u.realname'
);
if ($User->get('hca_fs_group') > 0) $query['WHERE'] = 'u.group_id='.$User->get('hca_fs_group');
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[$fetch_assoc['id']] = $fetch_assoc;
}

if (!empty($users_info))
{
	$optgroup = 0;
	$json_array = array();
	$json_array['users'] = '<select name="assign[user_id]" onchange="getTimeSlots('.$start_date.');">'."\n";
	$json_array['users'] .= "\t\t\t\t\t\t".'<option value="0" selected="selected">Select Employee</option>'."\n";
	
	foreach ($users_info as $cur_user)
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				$json_array['users'] .= '</optgroup>';
			}
			$json_array['users'] .= '<optgroup label="'.html_encode($cur_user['g_title']).'">';
			$optgroup = $cur_user['group_id'];
		}
		
		$requested_uid = $request_info['requested_uid'];
		$json_array['users'] .= check_free_users($cur_user, $start_date, $requested_uid);
	}
	$json_array['users'] .= '</select>'."\n";
	
	$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
	$json_array['time_slot'] = '<select name="assign[time_slot]">'."\n";
	foreach($time_slots as $key => $val)
	{
			$json_array['time_slot'] .= '<option value="'.$key.'">'.$val.'</option>'."\n";
	}
	$json_array['time_slot'] .= '</select>'."\n";
	
	echo json_encode(array(
		'users_list'		=> $json_array['users'],
		'time_slot'			=> $json_array['time_slot'],
		'msg_for_maint' 	=> '<textarea name="assign[msg_for_maint]" rows="4">'.$request_info['request_desc'].'</textarea>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
