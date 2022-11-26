<?php

class HcaWOM
{
	var $cur_work_order_info = [];

	var $priority = [
		0 => 'Low',
		1 => 'Medium',
		2 => 'High',
	];

	var $task_type = [
		1 => '5840 Moisture Event',
		2 => 'Appliance',
		3 => 'Electrical',
		4 => 'Exterior',
		5 => 'HVAC',
		6 => 'Interior',
		7 => 'Landscape',
		8 => 'Make Ready',
		9 => 'Pest Control',
		10 => 'Plumbing',
		11 => 'zAdmin',
	];

	var $task_item = [
		1 => 'A/C Closet',
		2 => 'Dining Room',
		3 => 'Guest Bath 1',
		4 => 'Guest Bath 2',
		5 => 'Guest Bedroom 1',
		6 => 'Guest Bedroom 2',
		7 => 'Kitchen',
		8 => 'Living Room',
		9 => 'Master Bath',
		10 => 'Master Bedroom',
		11 => 'Patio',
		12 => 'Water Heater Storage',
	];

	var $task_problem = [
		1 => 'Discoloration',
		2 => 'Flood',
		3 => 'Other',
	];

	function getWorkOrderInfo($id)
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 'w.*, p.pro_name, pu.unit_number, u1.realname AS assigned_name, u1.email AS assigned_email, u2.realname AS requested_name, u2.email AS requested_email',
			'FROM'		=> 'hca_wom_work_orders AS w',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'p.id=w.property_id'
				],
				[
					'LEFT JOIN'		=> 'sm_property_units AS pu',
					'ON'			=> 'pu.id=w.unit_id'
				],
				[
					'LEFT JOIN'		=> 'users AS u1',
					'ON'			=> 'u1.id=w.assigned_to'
				],
				[
					'LEFT JOIN'		=> 'users AS u2',
					'ON'			=> 'u2.id=w.requested_by'
				],
			],
			'WHERE'		=> 'w.id='.$id,
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$this->cur_work_order_info = $DBLayer->fetch_assoc($result);

		if ($this->cur_work_order_info['unit_id'] == 0)
			$this->cur_work_order_info['unit_number'] = 'Common area';

		return $this->cur_work_order_info;
	}

	function getWorkOrderStatus($wo_status)
	{
		$output = [];

		if ($wo_status == 4)
		{
			$output[] = '<div class="callout callout-success mb-2">';
			$output[] = 'The Work Order has been closed by property manager.';
			$output[] = '</div>';
		}
		else if ($wo_status == 3)
		{
			$output[] = '<div class="callout callout-primary mb-2">';
			$output[] = 'The Work Order has been completed by technician.';
			$output[] = '</div>';
		}

		// Not in-use
		else if ($wo_status == 2)
		{
			$output[] = '<div class="callout callout-info mb-2">';
			$output[] = 'The Work Order has been accepted by technician.';
			$output[] = '</div>';
		}

		else if ($wo_status == 1)
		{
			$output[] = '<div class="callout callout-warning mb-2">';
			$output[] = 'The Work Order has been assigned by property manager but not completed by technician.';
			$output[] = '</div>';
		}
		else if ($wo_status == 0)
		{
			$output[] = '<div class="callout callout-danger mb-2">';
			$output[] = 'The Work Order has been canceled by property manager.';
			$output[] = '</div>';
		}

		return implode("\n", $output);
	}
}
