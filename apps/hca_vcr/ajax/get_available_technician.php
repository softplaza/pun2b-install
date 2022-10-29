<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$access = (!$User->is_guest()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$start_date = isset($_POST['start_date']) ? intval($_POST['start_date']) : 0;
$gid = isset($_POST['gid']) ? intval($_POST['gid']) : 0;
$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;

if ($gid > 0 && $start_date > 0)
{
	$technician_list = [];
	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.realname',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.group_id='.$gid,
		'ORDER BY'	=> 'u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tech_list = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tech_list[] = $row;
	}
	
	if (!empty($tech_list))
	{
		$technician_list[] = '<input id="tech_start_date" type="hidden" name="start_date" value="'.$start_date.'">';
		$technician_list[] = '<div class="mb-3">';
		$technician_list[] = '<input type="date" value="'.format_time($start_date, 1, 'Y-m-d').'" class="form-control" disabled>';
		$technician_list[] = '</div>';
		$technician_list[] = '<div class="mb-3">';
		$technician_list[] = '<label class="form-label" for="fld_maint_id">Technician list</label>';
		$technician_list[] = '<select name="maint_id" class="form-select" id="fld_maint_id" onchange="getAvailableTimeSlots()">';
		$technician_list[] = '<option value="0">Select technician</option>';
		foreach($tech_list as $cur_info)
		{
			/*
			$busy_slots = array();
			if (!empty($busy_users))
			{
				foreach($busy_users as $busy_user)
				{
					if ($cur_info['id'] == $busy_user['employee_id'])
						$busy_slots[] = $busy_user['time_slot'];
				}
			}
			*/
			/*
			if (in_array(1, $busy_slots) || (in_array(2, $busy_slots) && in_array(3, $busy_slots)))
				$technician_list[] = '<option value="'.$cur_info['id'].'" disabled style="color:red">'.$cur_info['realname'].'</option>';
			else if (!in_array(1, $busy_slots) && !in_array(2, $busy_slots) && in_array(3, $busy_slots))
				$technician_list[] = '<option value="'.$cur_info['id'].'" style="color:blue">'.$cur_info['realname'].' (AM Only)</option>';	
			else if (!in_array(1, $busy_slots) && !in_array(3, $busy_slots) && in_array(2, $busy_slots))
				$technician_list[] = '<option value="'.$cur_info['id'].'" style="color:blue">'.$cur_info['realname'].' (PM Only)</option>';
			else
			*/
				$technician_list[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
		}
		$technician_list[] = '</select>';
		$technician_list[] = '</div>';
		$technician_list[] = '<div id="available_slots">';
		$technician_list[] = '</div>';
	}
	else
		$technician_list[] = 'No maintenance available';

	echo json_encode(array(
		'modal_body'		=> implode("\n", $technician_list),
		'modal_footer'		=> '<button type="submit" name="update_technician" class="btn btn-primary">Update</button>',
	));	
}

else if ($uid > 0 && $start_date > 0)
{
	$time_slots = [1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.'];
	$available_slots = [];
	$i = 0;
	$query = array(
		'SELECT'	=> 'r.employee_id, r.time_slot',
		'FROM'		=> 'hca_fs_requests AS r',
		'WHERE'		=> 'r.start_date='.$start_date.' AND r.employee_id='.$uid,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result))
	{
		if ($row['time_slot'] == 1)
		{
			if (isset($time_slots[1])) unset($time_slots[1]);
			if (isset($time_slots[2])) unset($time_slots[2]);
			if (isset($time_slots[3])) unset($time_slots[3]);
		}
		else if ($row['time_slot'] == 2)
		{
			if (isset($time_slots[1])) unset($time_slots[1]);
			if (isset($time_slots[2])) unset($time_slots[2]);
		}
		else if ($row['time_slot'] == 3)
		{
			if (isset($time_slots[1])) unset($time_slots[1]);
			if (isset($time_slots[3])) unset($time_slots[3]);
		}
		++$i;
	}
	
	if (!empty($time_slots))
	{
		$available_slots[] = '<label class="form-label" for="fld_time_slot">Available time</label>';
		$available_slots[] = '<select name="time_slot" class="form-select" id="fld_time_slot">';
		foreach($time_slots as $key => $val)
		{
			$available_slots[] = '<option value="'.$key.'">'.$val.'</option>';
		}
		$available_slots[] = '</select>';
	}
	else
	{
		$available_slots[] = '<label class="form-label" for="fld_time_slot">Available time</label>';
		$available_slots[] = '<input class="form-control text-danger" id="fld_time_slot" value="No time slot available">';
	}

	echo json_encode(array(
		'available_slots'				=> implode("\n", $available_slots),
	));
}
