<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$access = (!$User->is_guest()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$start_date = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : 0;
$busy_users = $json_array = array();

if ($start_date > 0)
{
	$query = array(
		'SELECT'	=> 'r.employee_id, r.time_slot',
		'FROM'		=> 'hca_fs_requests AS r',
		'WHERE'		=> 'start_date='.$start_date,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result))
		$busy_users[] = $row;
	
	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.realname, g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'group_id='.$Config->get('o_hca_fs_painters'),
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$maint_list = $json_array = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$maint_list[] = $row;
	}
	
	$json_array['maint_list'] = '';
	if (!empty($maint_list))
	{
		$json_array['maint_list'] = '<select name="paint_id" onchange="getPaintTimeSlot()">'."\n";
		$json_array['maint_list'] .= '<option value="0">Any Painter</option>'."\n";
		foreach($maint_list as $cur_info)
		{
			$busy_slots = array();
			if (!empty($busy_users))
			{
				foreach($busy_users as $busy_user)
				{
					if ($cur_info['id'] == $busy_user['employee_id'])
						$busy_slots[] = $busy_user['time_slot'];
				}
			}
			
			if (in_array(1, $busy_slots) || (in_array(2, $busy_slots) && in_array(3, $busy_slots)))
				$json_array['maint_list'] .= '<option value="'.$cur_info['id'].'" disabled style="color:red">'.$cur_info['realname'].' (n/a)</option>'."\n";
			else if (!in_array(1, $busy_slots) && !in_array(2, $busy_slots) && in_array(3, $busy_slots))
				$json_array['maint_list'] .= '<option value="'.$cur_info['id'].'" style="color:blue">'.$cur_info['realname'].' (AM Only)</option>'."\n";	
			else if (!in_array(1, $busy_slots) && !in_array(3, $busy_slots) && in_array(2, $busy_slots))
				$json_array['maint_list'] .= '<option value="'.$cur_info['id'].'" style="color:blue">'.$cur_info['realname'].' (PM Only)</option>'."\n";
			else
				$json_array['maint_list'] .= '<option value="'.$cur_info['id'].'">'.$cur_info['realname'].'</option>'."\n";
		}
		
		$json_array['maint_list'] .= '</select>'."\n";
	}
	
	echo json_encode(array(
		'paint_list'		=> $json_array['maint_list'],
	));	
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();