<?php

class HcaWOM
{
	var $cur_work_order_info = [];
	var $cur_task_info = [];
	var $wo_tasks_info = [];

	var $template_type = [
		1 => 'Standard',
		2 => 'Make Ready',
	];

	var $wo_status = [
		1 => 'Open',
		2 => 'Closed',
		3 => 'Canceled'
	];

	var $task_status = [
		1 => 'Assigned',
		2 => 'Accepted by technician',
		3 => 'Waiting for review',
		4 => 'Closed',
	];

	var $priority = [
		//0 => 'Select one',
		1 => 'Low',
		2 => 'Medium',
		3 => 'High',
	];

/*
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
		5 => 'Repair',
		4 => 'Replace',
		7 => 'Replace Bulb',
		11 => 'Treat'//last one
	];
*/

	function getActions($hca_wom_problems, $ids = '')
	{
		$output = [];
		if ($ids != '')
		{
			$array = explode(',', $ids);

			if (!empty($array))
			{
				foreach($array as $id)
				{
					if (isset($hca_wom_problems[$id]))
						$output[] = $hca_wom_problems[$id];
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
			'SELECT'	=> 'w.*, p.pro_name, pu.unit_number, u1.realname AS requested_name, u1.email AS requested_email',
			'FROM'		=> 'hca_wom_work_orders AS w',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'p.id=w.property_id'
				],
				[
					'INNER JOIN'	=> 'sm_property_units AS pu',
					'ON'			=> 'pu.id=w.unit_id'
				],
				[
					'INNER JOIN'	=> 'users AS u1',
					'ON'			=> 'u1.id=w.requested_by'
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
			'SELECT'	=> 't.*, w.property_id, w.unit_id, w.wo_message, w.priority, w.enter_permission, w.has_animal, p.pro_name, pu.unit_number, u1.realname AS requested_name, u1.email AS requested_email, i.item_name, pb.problem_name, u2.realname AS assigned_name, u2.email AS assigned_email',
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
					'INNER JOIN'	=> 'users AS u1',
					'ON'			=> 'u1.id=w.requested_by'
				],
				[
					// LEFT if unit_id - 0
					'LEFT JOIN'		=> 'sm_property_units AS pu',
					'ON'			=> 'pu.id=w.unit_id'
				],
				[
					'LEFT JOIN'		=> 'hca_wom_items AS i',
					'ON'			=> 'i.id=t.item_id'
				],
				[
					'LEFT JOIN'		=> 'hca_wom_problems AS pb',
					'ON'			=> 'pb.id=t.task_action'
				],
				[
					'LEFT JOIN'		=> 'users AS u2',
					'ON'			=> 'u2.id=t.assigned_to'
				],
			],
			'WHERE'		=> 't.id='.$id,
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$this->cur_task_info = $DBLayer->fetch_assoc($result);

		if ($this->cur_task_info['unit_id'] == 0 || $this->cur_task_info['unit_id'] == '')
			$this->cur_task_info['unit_number'] = 'Common area';

		return $this->cur_task_info;
	}

	function getWOTasks($work_order_id)
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 't.*, i.item_name',
			'FROM'		=> 'hca_wom_tasks AS t',
			'JOINS'		=> [
				[
					'LEFT JOIN'		=> 'hca_wom_items AS i',
					'ON'			=> 'i.id=t.item_id'
				],
			],
			'WHERE'		=> 't.work_order_id='.$work_order_id,
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$this->wo_tasks_info[] = $row;
		}

		return $this->wo_tasks_info;
	}

	function areTasksClosed($arr = []){
		$ident = false;
		if (!empty($arr)){
			$ident = true;
			foreach($arr as $cur_info){
				if ($cur_info['task_status'] < 4)
					return false;
			}
		}
		return $ident;
	}

	function areTasksCompleted($arr = []){
		$ident = false;
		if (!empty($arr)){
			$ident = true;
			foreach($arr as $cur_info){
				if ($cur_info['task_status'] < 3)
					return false;
			}
		}
		return $ident;
	}

	function getWorkOrderStatus($wo_status)
	{
		$output = [];
		// Canceled
		if ($wo_status == 3){
			$output[] = '<div class="callout callout-danger mb-2">';
			$output[] = 'The Work Order has been canceled.';
			$output[] = '<button type="submit" name="reopen_wo" class="badge bg-primary">Reopen WO</button>';
			$output[] = '</div>';
		}
		// Completed
		if ($wo_status == 2){
			$output[] = '<div class="callout callout-success mb-2">';
			$output[] = 'The Work Order has been closed.';
			$output[] = '<button type="submit" name="reopen_wo" class="badge bg-primary">Reopen WO</button>';
			$output[] = '</div>';
		}
		// Active
		else if ($wo_status == 1)
		{
			$output[] = '<div class="callout callout-warning mb-2">';
			$output[] = 'The Work Order is open.';
			//$output[] = '<button type="submit" name="complete_wo" class="badge badge-primary">Complete</button>';
			$output[] = '</div>';
		}
		// On-Hold
		else if ($wo_status == 0)
		{
			$output[] = '<div class="callout callout-secondary mb-2">';
			$output[] = 'The Work Order is On-Hold.';
			$output[] = '<button type="submit" name="reopen_wo" class="badge bg-primary">Reopen WO</button>';
			$output[] = '</div>';
		}

		return implode("\n", $output);
	}
}
