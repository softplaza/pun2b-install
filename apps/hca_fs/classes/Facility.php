<?php

class Facility
{
	public $action = '';
	public $group_id = 0;
	public $week_of = 0;
	public $first_day_of_week = 0;
	
	public $users_info = [];
	public $permanently_info = [];
	public $property_info = [];
	public $work_orders_info = [];
	public $weekly_info = [];
	public $work_order_counter = [];

	public $dropdown_items1 = [];
	public $dropdown_items2 = [];

	public $Errors = [];
	public $Warnings = [];

	public $days_of_week = array(
		1 => 'Monday',
		2 => 'Tuesday',
		3 => 'Wednesday',
		4 => 'Thursday',
		5 => 'Friday',
		6 => 'Saturday',
		//7 => 'Sunday'
	);
	public $time_slots = array(
		0 => 'ANY TIME',
		1 => 'ALL DAY', 
		2 => 'A.M.', 
		3 => 'P.M.', 
		4 => 'DAY OFF', 
		5 => 'SICK DAY', 
		6 => 'VACATION',
		7 => 'STAND BY'
	);
	
	public $request_statuses = array(
		-1 => 'ON HOLD',
		0 => 'New Request',
		1 => 'In Progress',
		2 => 'Completed',
	//	3 => 'Alert',
	//	4 => 'Denied',
		5 => 'Canceled',
	);

	function getWorkOrder($id)
	{
		global $DBLayer, $Core;
		
		if ($this->first_day_of_week == 0)
			$this->Warnings[] = 'Day of Week is empty.';
		
		$query = array(
			'SELECT'	=> 'r.*, u.realname, u.email, p.pro_name, p.manager_email',
			'FROM'		=> 'hca_fs_requests AS r',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'r.employee_id=u.id'
				),
				array(
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'r.property_id=p.id'
				),
			),
			'WHERE'		=> 'r.id='.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		return $DBLayer->fetch_assoc($result);
	}

	// Get Regular Assignments
	function PermanentAssignments()
	{
		global $DBLayer;
		
		$query = array(
			'SELECT'	=> 'ps.*, u.realname, u.email, p.pro_name, p.manager_email',
			'FROM'		=> 'hca_fs_permanent_assignments AS ps',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'ps.user_id=u.id'
				),
				array(
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'ps.property_id=p.id'
				),
			),
			'WHERE'		=> 'ps.group_id='.$this->group_id,
			'ORDER BY'	=> 'ps.start_time',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$assignments_info = array();
		while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$assignments_info[] = $fetch_assoc;
		}
		
		if (!empty($assignments_info))
			$this->permanent_assignments = $assignments_info;
//		else
//			$this->Warnings[] = 'Permanently Assignments are empty.';
			
		return $assignments_info;
	}
	
	// Get
	function PermanentUserAssignment($id)
	{
		global $DBLayer;
		
		$query = array(
			'SELECT'	=> 'ps.*, u.realname, u.email, p.pro_name, p.manager_email',
			'FROM'		=> 'hca_fs_permanent_assignments AS ps',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'ps.user_id=u.id'
				),
				array(
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'ps.property_id=p.id'
				),
			),
			'WHERE'		=> 'ps.user_id='.$id,
			'ORDER BY'	=> 'ps.start_time',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$assignments_info = array();
		while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$assignments_info[] = $fetch_assoc;
		}
		
		if (!empty($assignments_info))
			$this->permanent_assignments = $assignments_info;
//		else
//			$this->Warnings[] = 'Permanently Assignments are empty.';
			
		return $assignments_info;
	}
	
	function PropertyInfo()
	{
		global $DBLayer, $Core;
		
		$query = array(
			'SELECT'	=> 'id, pro_name, manager_email',
			'FROM'		=> 'sm_property_db',
			'ORDER BY'	=> 'pro_name'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$property_info = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$property_info[$row['id']] = $row;
		}
		
		if (!empty($property_info))
			$this->property_info = $property_info;
		else
			$Core->add_warning( 'Property List is empty.');
			
		return $property_info;
	}
	
	function WeeklyInfo()
	{
		global $DBLayer, $Core;
		
		$query = array(
			'SELECT'	=> 'w.*',
			'FROM'		=> 'hca_fs_weekly AS w',
			'WHERE'		=> 'w.week_of='.$this->first_day_of_week,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$weekly_info = array();
		while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$weekly_info[$fetch_assoc['id']] = $fetch_assoc;
		}
		
		if (!empty($weekly_info))
			$this->weekly_info = $weekly_info;
		//else
		//	$Core->add_warning('Weekly Schedule has not been sent yet to any employee.');
			
		return $weekly_info;
	}
	
	// Getting sent status info of current week
	function getWeeklyInfoById($uid)
	{
		$output = [];
		if (!empty($this->weekly_info))
		{
			foreach($this->weekly_info as $key => $cur_info)
			{
				if ($cur_info['user_id'] == $uid && $cur_info['mailed_time'] > 0)	
					$output = $cur_info;
			}
		}
		
		return $output;
	}

	function WorkOrdersInfo()
	{
		global $DBLayer, $Core;
		
		if ($this->first_day_of_week == 0)
			$this->Warnings[] = 'Day of Week is empty.';
		
		$query = array(
			'SELECT'	=> 'r.*, u.realname, p.pro_name',
			'FROM'		=> 'hca_fs_requests AS r',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'r.employee_id=u.id'
				),
				array(
					// LEFT for day off
					'LEFT JOIN'		=> 'sm_property_db AS p',
					'ON'			=> 'r.property_id=p.id'
				),
			),
			'WHERE'		=> '(r.work_status=1 OR r.work_status=2 OR r.time_slot > 3) AND r.week_of='.$this->first_day_of_week,
		//	'WHERE'		=> 'r.week_of='.$this->first_day_of_week,
			'ORDER BY'	=> 'r.scheduled, r.time_slot'
		);

		if ($this->group_id > 0)
			$query['WHERE'] .= ' AND r.group_id='.$this->group_id;

		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$work_orders_info = array();
		while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$work_orders_info[] = $fetch_assoc;
		}
		
		if (!empty($work_orders_info))
			$this->work_orders_info = $work_orders_info;
		else
			$Core->add_warning('Work Orders List is empty.');
		
		return $work_orders_info;
	}
	
	function UsersInfo()
	{
		global $DBLayer, $User;
		
		$query = array(
			'SELECT'	=> 'u.*',
			'FROM'		=> 'groups AS g',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'g.g_id=u.group_id'
				)
			),
		//	'WHERE'		=> 'g.hca_fs=1 AND u.group_id='.$gid,
			'WHERE'		=> 'u.group_id='.$this->group_id,
		//	'ORDER BY'	=> 'realname'
		);
		
		$query['ORDER BY'] = ($User->get('users_sort_by') == 1) ? 'last_name' : 'realname';
		
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$users_info = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$users_info[$row['id']] = $row;
			
			if ($User->get('users_sort_by') == 1)
				$users_info[$row['id']]['realname'] = $row['last_name'].' '.$row['first_name'];
		}
		
		if (!empty($users_info))
			$this->users_info = $users_info;
		else
			$this->Warnings[] = 'Users List is empty.';
			
		return $users_info;
	}
	
	function GetUserInfo($id)
	{
		$user_info = [];
		if (!empty($this->users_info))
		{
			foreach($this->users_info as $cur_info)
			{
				if ($cur_info['id'] == $id)	
					$user_info = $cur_info;
			}
		}
		
		return $user_info;
	}
	
	function CheckMailedUserStatus($id)
	{
		$status = false;
		if (!empty($this->weekly_info))
		{
			foreach($this->weekly_info as $cur_info)
			{
				if ($cur_info['user_id'] == $id && $cur_info['mailed_time'] > 0)
				{
					$status = true;
					break;
				}
			}
		}
		
		return $status;
	}
	
	function first_day_of_week($date)
	{
		$this->first_day_of_week = $date;
	}
	
	function hasWeekendJob()
	{
		$v = false;
		if (!empty($this->work_orders_info))
		{
			foreach($this->work_orders_info as $work_order_info)
			{
				$day_number = date('N', strtotime($work_order_info['scheduled']));
				if (in_array($day_number, array(6,7)))
					$v = true;
			}
		}
		
		return $v;
	}

	function checkWorkOrderLimit()
	{
		global $Core;
		
		if (!empty($this->work_orders_info))
		{
			foreach($this->work_orders_info as $work_order_info)
			{
				$cur_date = date('Ymd', strtotime($work_order_info['scheduled']));

				if ($work_order_info['scheduled'] > 0 && $this->group_id == $work_order_info['group_id'])
				{
					if (!isset($this->work_order_counter[$work_order_info['employee_id']][$cur_date]))
						$this->work_order_counter[$work_order_info['employee_id']][$cur_date] = 1;
					else
					{
						if ($this->work_order_counter[$work_order_info['employee_id']][$cur_date] < 2)
							$Core->add_warning($work_order_info['realname'].' has more than one scheduled work order on '.date('m/d/Y', strtotime($work_order_info['scheduled'])));

						++$this->work_order_counter[$work_order_info['employee_id']][$cur_date];
					}
				}
			}
		}
	}

	// Add dropdown menu item
	function addDDItem1($item)
	{
		$this->dropdown_items1[] = $item;
	}

	// Generate dropdown menu items
	function getDDMenu1($id = 1)
	{
		$output = $items = [];
		$output[] = '<div class="dropdown dropend">';
		$output[] = '<button class="btn btn-sm dropdown-toggle" type="button" id="dropdownMenu'.$id.'" data-bs-toggle="dropdown">';
		$output[] = '<i class="fas fa-plus-circle fa-lg text-success"></i>';
		$output[] = '</button>';
		$output[] = '<div class="dropdown-menu" aria-labelledby="dropdownMenu'.$id.'">';
		$output[] = '<ul class="list-group">';
		if (!empty($this->dropdown_items1))
		{
			foreach($this->dropdown_items1 as $action)
			{
				if ($action != '')
					$items[] = '<li class="dropdown-item">'.str_replace('$1', $id, $action).'</li>';
			}
		}
		$output[] = implode("\n", $items);
		$output[] = '</ul>';
		$output[] = '</div>';
		$output[] = '</div>';
		
		$this->dropdown_items1 = [];
		
		if (!empty($items))
			return implode("\n", $output);
	}

	// Add dropdown menu item
	function addDDItem2($item)
	{
		$this->dropdown_items2[] = $item;
	}

	// Generate dropdown menu items
	function getDDMenu2($id = 1)
	{
		$output = $items = [];
		$output[] = '<div class="dropdown dropend">';
		$output[] = '<button class="btn btn-sm" type="button" id="dropdownMenu'.$id.'" data-bs-toggle="dropdown">';
		$output[] = '<i class="fas fa-edit fa-lg text-secondary"></i>';
		$output[] = '</button>';
		$output[] = '<div class="dropdown-menu" aria-labelledby="dropdownMenu'.$id.'">';
		$output[] = '<ul class="list-group">';
		if (!empty($this->dropdown_items2))
		{
			foreach($this->dropdown_items2 as $action)
			{
				if ($action != '')
					$items[] = '<li class="dropdown-item">'.str_replace('$1', $id, $action).'</li>';
			}
		}
		$output[] = implode("\n", $items);
		$output[] = '</ul>';
		$output[] = '</div>';
		$output[] = '</div>';
		
		$this->dropdown_items2 = [];
		
		if (!empty($items))
			return implode("\n", $output);
	}
}
