<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$date_week_of = isset($_POST['date_week_of']) ? swift_trim($_POST['date_week_of']) : 0;
$zone = isset($_POST['zone']) ? intval($_POST['zone']) : 0;
$id = isset($_POST['id']) ? intval($_POST['id']) : 0; // schedule ID

$user_id = 0;
if ($id > 0)
{
	$query = array(
		'SELECT'	=> 'es.id, es.user_id, es.zone, es.date_week_of',
		'FROM'		=> 'hca_fs_emergency_schedule AS es',
		'WHERE'		=> 'es.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$schedule = $DBLayer->fetch_assoc($result);
	$user_id = $schedule['user_id'];
}

$query = array(
	'SELECT'	=> 'es.user_id, es.zone',
	'FROM'		=> 'hca_fs_emergency_schedule AS es',
	'WHERE'		=> 'es.date_week_of=\''.$DBLayer->escape($date_week_of).'\'' //.' AND es.zone='.$zone,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$assigned_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$assigned_info[$fetch_assoc['user_id']] = $fetch_assoc;
}

/*
$query = array(
	'SELECT'	=> 'u.id, u.realname, u.group_id, u.first_name, u.last_name, u.hca_fs_zone, p.zone',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.emergency_uid=u.id'
		),
	),
	'WHERE'		=> 'p.zone > 0 AND u.group_id='.intval($Config->get('o_hca_fs_maintenance')),
	'ORDER BY'	=> 'u.realname',
);
*/
$query = array(
	'SELECT'	=> 'u.id, u.realname, u.group_id, u.first_name, u.last_name, u.hca_fs_zone',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.hca_fs_zone > 0 AND u.group_id='.intval($Config->get('o_hca_fs_maintenance')),
	'ORDER BY'	=> 'u.realname',
);
$query['ORDER BY'] = ($User->get('users_sort_by') == 1) ? 'last_name' : 'realname';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$users_info[$row['id']] = $row;
	
	if ($User->get('users_sort_by') == 1);
		$users_info[$row['id']]['realname'] = $row['last_name'].' '.$row['first_name'];
}

if (!empty($users_info))
{
	$json_array['users'] = '<input type="hidden" name="date_week_of" value="'.$date_week_of.'"/>';
	$json_array['users'] .= '<input type="hidden" name="zone" value="'.$zone.'"/>';
	
	$json_array['users'] .= '<select name="user_id">'."\n";
	$json_array['users'] .= '<option value="0" selected disabled>Select an Employee</option>'."\n";
	
	foreach($users_info as $cur_info)
	{
		if ($user_id == $cur_info['id'])
			$json_array['users'] .= '<option value="'.$cur_info['id'].'" selected>'.$cur_info['realname'].' ('.$cur_info['hca_fs_zone'].')</option>'."\n";
		//else if (isset($assigned_info[$cur_info['id']]))
		//	$json_array['users'] .= '<option value="'.$cur_info['id'].'" style="color:red" disabled>'.$cur_info['realname'].' ('.$cur_info['hca_fs_zone'].')</option>'."\n";
		else if (isset($assigned_info[$cur_info['id']]))
			$json_array['users'] .= '<option value="'.$cur_info['id'].'" style="color:red">'.$cur_info['realname'].' ('.$cur_info['hca_fs_zone'].')</option>'."\n";
		else
			$json_array['users'] .= '<option value="'.$cur_info['id'].'">'.$cur_info['realname'].' ('.$cur_info['hca_fs_zone'].')</option>'."\n";
	}
	
	$json_array['users'] .= '</select>'."\n";
	
	echo json_encode(array(
		'users_list' => $json_array['users'],
	));
}
else
{
	echo json_encode(array(
		'users_list' => 'No Empoyees founded.',
	));
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
