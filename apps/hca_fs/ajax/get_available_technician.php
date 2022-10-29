<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$access = (!$User->is_guest()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$start_date = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : 0;
$gid = isset($_POST['gid']) ? intval($_POST['gid']) : 0;
$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;

if ($gid > 0 && $start_date > 0)
{
	$query = array(
		'SELECT'	=> 'r.*, u.realname',
		'FROM'		=> 'hca_fs_requests AS r',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'r.employee_id=u.id'
			),
		),
		'WHERE'	=> 'r.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$main_info = $DBLayer->fetch_assoc($result);

	$availability = [];
	$i = 0;
	$query = array(
		'SELECT'	=> 'r.employee_id, r.time_slot, r.work_status',
		'FROM'		=> 'hca_fs_requests AS r',
		'WHERE'		=> 'r.start_date='.$start_date,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result))
		$availability[] = $row;

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
		$time_slots = [
			1 => 'ALL DAY', //
			2 => 'A.M.', 
			3 => 'P.M.', 
			4 => 'DAY OFF', 
			5 => 'SICK DAY', 
			6 => 'VACATION',
			7 => 'STAND BY'
		];

		$technician_list[] = '<select name="employee_id" class="form-select" id="fld_employee_id">';
		$technician_list[] = '<option value="0" selected>Select technician</option>';
		foreach($tech_list as $cur_user)
		{
			$slot = '';
			$css = 'style="color:green"';
			$am = $pm = false;

			if (!empty($availability))
			{
				foreach($availability as $available)
				{
					if ($cur_user['id'] == $available['employee_id'])
					{
						if ($available['time_slot'] == 1)
						{
							$slot = ' (No time slots available)';
							$css = 'style="color:red"';
							break;
						}
						else if ($available['time_slot'] == 4)
						{
							$slot = ' (Day off)';
							$css = 'style="color:orange"';
							break;
						}
						else if ($available['time_slot'] == 5)
						{
							$slot = ' (Sick day)';
							$css = 'style="color:orange"';
							break;
						}
						else if ($available['time_slot'] == 6)
						{
							$slot = ' (Vacation)';
							$css = 'style="color:orange"';
							break;
						}
						else if ($available['time_slot'] == 7)
						{
							$slot = ' (Stand by)';
							$css = 'style="color:orange"';
							break;
						}
						else if ($available['time_slot'] == 2)
						{
							if ($pm)
							{
								$slot = ' (No time slots available)';
								$css = 'style="color:red"';
								break;
							}
							else
							{
								$slot = ' ( P.M. only)';
								$css = 'style="color:darkblue"';
								$am = true;
							}
						}
						else if ($available['time_slot'] == 3)
						{
							if ($am)
							{
								$slot = ' (No time slots available)';
								$css = 'style="color:red"';
								break;
							}
							else
							{
								$slot = ' ( A.M. only)';
								$css = 'style="color:darkblue"';
								$pm = true;
							}
						}
						else
						{
							$css = 'style="color:green"';
						}
					}
				}
			}

			if ($main_info['employee_id'] == $cur_user['id'] && $start_date == $main_info['start_date'])
				$technician_list[] = '<option value="'.$cur_user['id'].'" selected '.$css.'>'.html_encode($cur_user['realname']).$slot.'</option>';
			else
				$technician_list[] = '<option value="'.$cur_user['id'].'" '.$css.'>'.html_encode($cur_user['realname']).$slot.'</option>';
		}
		$technician_list[] = '</select>';
	}
	else
		$technician_list[] = 'No maintenance available';

	echo json_encode(array(
		'technician_list'		=> implode("\n", $technician_list),
	));	
}
else
{
	echo json_encode(array(
		'technician_list'		=> '<input class="form-control text-danger" id="fld_time_slot" value="Requsted date was not setup." disabled>'
	));
}