<?php

class HcaWOM
{
	var $cur_work_order_info = [];
	var $cur_task_info = [];

	var $priority = [
		//0 => 'Select one',
		1 => 'Low',
		2 => 'Medium',
		3 => 'High',
	];

	var $item_types = [
		//0 => '',
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

	var $task_actions = [
		3 => 'Clean',
		9 => 'Complete',
		1 => 'Discoloration',
		2 => 'Flood',
		10 => 'Gig List',
		6 => 'Inspect',
		12 => 'Leak',
		8 => 'Mulch', 
		0 => 'Other',
		5 => 'Repaire',
		4 => 'Replace',
		7 => 'Replace Bulb',
		11 => 'Treat'//last one
	];

	function getActions($ids = '')
	{
		$output = [];
		if ($ids != '')
		{
			$array = explode(',', $ids);

			if (!empty($array))
			{
				foreach($array as $id)
				{
					if (isset($this->task_actions[$id]))
						$output[] = $this->task_actions[$id];
				}

				return implode(', ', $output);
			}
		}
	}

	function getTaskActions($ids = '')
	{
		$output = [];
		if ($ids != '')
		{
			$array = explode(',', $ids);

			if (!empty($array))
			{
				foreach($array as $id)
				{
					if (isset($this->item_actions[$id]))
						$output[] = $this->item_actions[$id];
				}

				return implode(', ', $output);
			}
		}
	}

	function getWorkOrderInfo($id)
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 'w.*, p.pro_name, pu.unit_number, u2.realname AS requested_name, u2.email AS requested_email', // , u1.realname AS assigned_name, u1.email AS assigned_email
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
				/*
				[
					'LEFT JOIN'		=> 'users AS u1',
					'ON'			=> 'u1.id=w.assigned_to'
				],
				*/
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

	function getTaskInfo($id)
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 't.*, w.wo_message, p.pro_name, pu.unit_number, u1.realname AS assigned_name, u1.email AS assigned_email, u2.realname AS requested_name, u2.email AS requested_email', // 
			'FROM'		=> 'hca_wom_tasks AS t',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'hca_wom_work_orders AS w',
					'ON'			=> 'w.id=t.work_order_id'
				],
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
					'ON'			=> 'u1.id=t.assigned_to'
				],
				[
					'LEFT JOIN'		=> 'users AS u2',
					'ON'			=> 'u2.id=w.requested_by'
				],
			],
			'WHERE'		=> 't.id='.$id,
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$this->cur_task_info = $DBLayer->fetch_assoc($result);

		if ($this->cur_task_info['unit_id'] == 0)
			$this->cur_task_info['unit_number'] = 'Common area';

		return $this->cur_task_info;
	}

	function getWorkOrderStatus($wo_status)
	{
		$output = [];

		// Completed
		if ($wo_status == 4)
		{
			$output[] = '<div class="callout callout-success mb-2">';
			$output[] = 'The Work Order has been closed by property manager.';
			$output[] = '<button type="submit" name="reopen_wo" class="badge badge-primary">Complete</button>';
			$output[] = '</div>';
		}
/*
		else if ($wo_status == 3)
		{
			$output[] = '<div class="callout callout-primary mb-2">';
			$output[] = 'The Work Order has been completed by technician.';
			$output[] = '</div>';
		}
		else if ($wo_status == 2)
		{
			$output[] = '<div class="callout callout-info mb-2">';
			$output[] = 'The Work Order has been accepted by technician.';
			$output[] = '</div>';
		}
*/
		// Active
		else if ($wo_status == 1)
		{
			$output[] = '<div class="callout callout-warning mb-2">';
			$output[] = 'The Work Order is active.';
			$output[] = '</div>';
		}
		// Canceled
		else if ($wo_status == 0)
		{
			$output[] = '<div class="callout callout-danger mb-2">';
			$output[] = 'The Work Order has been canceled by property manager.';
			$output[] = '<button type="submit" name="reopen_wo" class="badge bg-primary">Reopen WO</button>';
			$output[] = '</div>';
		}

		return implode("\n", $output);
	}
}
