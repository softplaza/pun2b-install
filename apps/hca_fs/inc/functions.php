<?php

//CHECK EXPIRED REQUESTS
function hca_fs_check_expired_requests()
{
	global $DBLayer, $Config, $FlashMessenger;

	$date_last_sunday = date('Y-m-d', strtotime('last sunday'));
	$time_last_sunday = strtotime('last sunday');
	
	$query = array(
		'SELECT'	=> 'r.id',
		'FROM'		=> 'hca_fs_requests AS r',
//		'WHERE'		=> 'r.date_start < NOW() - INTERVAL 7 DAY', // for 
		'WHERE'		=> 'r.work_status=1 AND r.start_date < '.$time_last_sunday.'',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$expired_ids = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$expired_ids[] = $row['id'];
	}

	if (!empty($expired_ids))
	{
		$query = array(
			'UPDATE'	=> 'hca_fs_requests',
			'SET'		=> 'work_status=2',
			'WHERE'		=> 'id IN('.implode(',', $expired_ids).')'
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$FlashMessenger->add_info('Expired work orders marked as completed.');
	}
}


//CHECK ALL ON HOLD REQUESTS
function hca_fs_check_on_hold()
{
	global $DBLayer, $Config;

}

function hca_fs_get_assigned_users($work_orders_info, $request_id, $time_slots, $remove = true)
{
	$users = array();

	foreach ($work_orders_info as $order_info)
	{
		$remove_action = ($remove) ? '<input type="image" src="'.BASE_URL.'/img/close.png" name="delete_work_order['.$order_info['id'].']" onclick="return confirm(\'Are you sure you want to unassign this worker?\')">' : '';
		$time_slot = isset($time_slots[$order_info['time_slot']]) ? $time_slots[$order_info['time_slot']] : '';
		
		$msg_from_maint = (!$remove && $order_info['msg_from_maint'] != '') ? '<span class="mnt-msg">Maintenance comment: '.html_encode($order_info['msg_from_maint']).'</span>' : '';
		
		if ($request_id == $order_info['request_id'])
			$users[] = '<p class="assigned-user">'.$order_info['employee_name'].' ('.$time_slot.') '.$remove_action.'</p>'.$msg_from_maint;
	}
	
	return $users;

}

function hca_fs_show_unassigned()
{
	global $DBLayer, $User;
	
	$output = '';
	$count_new = $count_on_hold = $count_approved = 0;
	$query = array(
		'SELECT'	=> 'status',
		'FROM'		=> 'hca_fs_requests',
		'WHERE'		=> 'status < 2',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result))
	{
		if ($fetch_assoc['status'] == -1)
			++$count_new;
		else if ($fetch_assoc['status'] == 1)
			++$count_approved;
		else if ($fetch_assoc['status'] == 0)
			++$count_on_hold;
	}
	
	if ($count_new > 0)
		$output = '<span class="circle-notify cn-pink">'.$count_new.'</span>';
	else if ($count_approved > 0)
		$output = '<span class="circle-notify cn-orange">'.$count_approved.'</span>';
	else if ($count_on_hold > 0)
		$output = '<span class="circle-notify cn-peachpuff">'.$count_on_hold.'</span>';
	
	return $output;
}

function hca_fs_get_time_slot()
{

	$day_hours = array(
/*		1 => '12:00 AM',
		2 => '12:30 AM',
		3 => '1:00 AM',
		4 => '1:30 AM',
		5 => '2:00 AM',
		6 => '2:30 AM',
		7 => '3:00 AM',
		8 => '3:30 AM',
		9 => '4:00 AM',
		10 => '4:30 AM',
		11 => '5:00 AM',
		12 => '5:30 AM',
		13 => '6:00 AM',
		14 => '6:30 AM',
		15 => '7:00 AM',
		16 => '7:30 AM',
		17 => '8:00 AM',*/
		18 => '8:30 AM',
		19 => '9:00 AM',
		20 => '9:30 AM',
		21 => '10:00 AM',
		22 => '10:30 AM',
		23 => '11:00 AM',
		24 => '11:30 AM',
		25 => '12:00 PM',
		26 => '12:30 PM',
		27 => '1:00 PM',
		28 => '1:30 PM',
		29 => '2:00 PM',
		30 => '2:30 PM',
		31 => '3:00 PM',
		32 => '3:30 PM',
		33 => '4:00 PM',
		34 => '4:30 PM',
		35 => '5:00 PM',
/*		36 => '5:30 PM',
		37 => '6:00 PM',
		38 => '6:30 PM',
		39 => '7:00 PM',
		40 => '7:30 PM',
		41 => '8:00 PM',
		42 => '8:30 PM',
		43 => '9:00 PM',
		44 => '9:30 PM',
		45 => '10:00 PM',
		46 => '10:30 PM',
		47 => '11:00 PM',
		48 => '11:30 PM'*/
	);
	
/*
$day_hours = array(
		0 => '12:00 AM',
		1 => '12:30 AM',
		2 => '1:00 AM',
		3 => '1:30 AM',
		4 => '2:00 AM',
		5 => '2:30 AM',
		6 => '3:00 AM',
		7 => '3:30 AM',
		8 => '4:00 AM',
		9 => '4:30 AM',
		10 => '5:00 AM',
		11 => '5:30 AM',
		12 => '6:00 AM',
		13 => '6:30 AM',
		14 => '7:00 AM',
		15 => '7:30 AM',
		16 => '8:00 AM',
		17 => '8:30 AM',
		18 => '9:00 AM',
		19 => '9:30 AM',
		20 => '10:00 AM',
		21 => '10:30 AM',
		22 => '11:00 AM',
		23 => '11:30 AM',
		24 => '12:00 PM',
		25 => '12:30 PM',
		26 => '1:00 PM',
		27 => '1:30 PM',
		28 => '2:00 PM',
		29 => '2:30 PM',
		30 => '3:00 PM',
		31 => '3:30 PM',
		32 => '4:00 PM',
		33 => '4:30 PM',
		34 => '5:00 PM',
		35 => '5:30 PM',
		36 => '6:00 PM',
		37 => '6:30 PM',
		38 => '7:00 PM',
		39 => '7:30 PM',
		40 => '8:00 PM',
		41 => '8:30 PM',
		42 => '9:00 PM',
		43 => '9:30 PM',
		44 => '10:00 PM',
		45 => '10:30 PM',
		46 => '11:00 PM',
		47 => '11:30 PM'
	);
*/
	return $day_hours;
}