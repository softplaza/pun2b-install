<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package HcaMiPropertyReport
**/

class HcaMiPropertyReport
{
	var $sm_property_units = [];

	function getPropertyUnits()
	{
		global $DBLayer;

		$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

		$query = [
			'SELECT'	=> 'un.*',
			'FROM'		=> 'sm_property_units AS un',
			'WHERE'		=> 'un.property_id='.$search_by_property_id,
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$this->sm_property_units[$row['unit_number']] = $row;
		}
	}

	function getUnitInfo($unit_number = '')
	{
		return [
			'unit_number' => isset($this->sm_property_units[swift_trim($unit_number)]) ? $this->sm_property_units[swift_trim($unit_number)]['unit_number'] : '',
			'pos_x' => isset($this->sm_property_units[swift_trim($unit_number)]) ? $this->sm_property_units[swift_trim($unit_number)]['pos_x'] : '',
			'pos_y' => isset($this->sm_property_units[swift_trim($unit_number)]) ? $this->sm_property_units[swift_trim($unit_number)]['pos_y'] : '',
		];
	}

	function combineData($array1, $array2)
	{
		//if (!empty($fields))
		$output = [];

		// Combine Moisture Projects
		if (!empty($array1))
		{
			foreach($array1 as $array)
			{
				if ($array['job_status'] == 3) $project_status = '<span class="fw-bold text-success">Completed</span>';//completed
				else if ($array['job_status'] == 1) $project_status = '<span class="fw-bold text-primary">In Progress</span>'; // in progress
				else $project_status = '<span class="fw-bold text-secondary">On Hold</span>';

				$output[] = [
					'id' => $array['id'],
					'pro_name' => $array['pro_name'],
					'unit_number' => $array['unit_number'],
					'leak_type' => $array['leak_type'],
					'performed_by' => ($array['project_manager'] != '' ? $array['project_manager'] : $array['mois_performed_by']),
					'date_performed' => ($array['final_performed_date'] > 0 ? format_time($array['final_performed_date'], 1) : ''),
					'vendor_name' => $array['cons_vendor'],
					'start_date' => ($array['cons_start_date'] > 0 ? format_time($array['cons_start_date'], 1) : ''),
					'end_date' => ($array['cons_end_date'] > 0 ? format_time($array['cons_end_date'], 1) : ''),
					'project_status' => $project_status
				];
			}
		}

		// Combine Re-Pipe Projects
		if (!empty($array2))
		{
			foreach($array2 as $array)
			{
				if ($array['status'] == 2) $project_status = '<span class="fw-bold text-primary">Completed for Hot</span>';
				else if ($array['status'] == 3) $project_status = '<span class="fw-bold text-primary">Completed for Cold</span>';
				else if ($array['status'] == 4) $project_status = '<span class="fw-bold text-success">Completed</span>';
				else $project_status = '<span class="fw-bold text-warning">Pending</span>';

				$output[] = [
					'id' => $array['id'],
					'pro_name' => $array['pro_name'],
					'unit_number' => $array['unit_number'],
					'leak_type' => 100,
					'performed_by' => $array['project_manager'],
					'date_performed' => (format_date($array['date_completed']) != '' ? format_date($array['date_completed'], 'Y-m-d') : ''),
					'vendor_name' => $array['vendor_name1'],
					'start_date' => (format_date($array['date_start']) != '' ? format_date($array['date_start'], 'Y-m-d') : ''),
					'end_date' => (format_date($array['date_end']) != '' ? format_date($array['date_end'], 'Y-m-d') : ''),
					'project_status' => $project_status
				];
			}
		}

		return $output;
	}
}
