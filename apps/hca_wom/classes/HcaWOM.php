<?php

class HcaWOM
{
	var $cur_work_order_info = [];

	var $priority = [
		0 => 'Low',
		1 => 'Medium',
		2 => 'High',
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
					'INNER JOIN'	=> 'sm_property_units AS pu',
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