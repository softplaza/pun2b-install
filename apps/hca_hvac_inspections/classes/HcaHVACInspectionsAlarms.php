<?php

class HcaHVACInspectionsAlarms
{
	var $checklist_items = [];

	function __construct()
	{
		global $DBLayer;

		$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
		$query = [
			'SELECT'	=> 'ci.*, ch.property_id, ch.unit_id, ch.datetime_inspection_start, ch.datetime_inspection_end, i.equipment_id, u1.realname AS inspected_name',
			'FROM'		=> 'hca_hvac_inspections_checklist_items AS ci',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'hca_hvac_inspections_checklist AS ch',
					'ON'			=> 'ch.id=ci.checklist_id'
				],
				[
					'INNER JOIN'	=> 'hca_hvac_inspections_items AS i',
					'ON'			=> 'i.id=ci.item_id'
				],
				[
					'INNER JOIN'	=> 'users AS u1',
					'ON'			=> 'u1.id=ch.inspected_by'
				],
			],
			'WHERE'		=> 'ch.property_id='.$search_by_property_id,
			'ORDER BY'	=> 'ci.id DESC' //search last inspected first and break in foreach
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while($row = $DBLayer->fetch_assoc($result))
		{
			$this->checked_items[] = $row;
		}
	}

	function getSmokeTest($unit_id)
	{
		if (!empty($this->checked_items))
		{
			foreach($this->checked_items as $checked_items)
			{
				if ($checked_items['unit_id'] == $unit_id && $checked_items['equipment_id'] == 91 && $checked_items['item_id'] == 30)
				{
					return [
						'date_tested' => (strtotime($checked_items['datetime_inspection_start']) > 0) ? $checked_items['datetime_inspection_start'] : '',
						'tested_by' => isset($checked_items['inspected_name']) ? $checked_items['inspected_name'] : '',
						'working' => ($checked_items['check_type'] == 2) ? 'YES' : 'NO',
						'date_corrected' => (strtotime($checked_items['datetime_inspection_end']) > 0 && $checked_items['check_type'] != 2) ? $checked_items['datetime_inspection_end'] : '',
					];
					break;
				}
			}
		}

		return [
			'date_tested' => '',
			'tested_by' => '',
			'working' => '',
			'date_corrected' => ''
		];
	}

	function getCarbonTest($unit_id)
	{
		if (!empty($this->checked_items))
		{
			foreach($this->checked_items as $checked_items)
			{
				if ($checked_items['unit_id'] == $unit_id && $checked_items['equipment_id'] == 90 && $checked_items['item_id'] == 23)
				{
					return [
						'date_tested' => (strtotime($checked_items['datetime_inspection_start']) > 0) ? $checked_items['datetime_inspection_start'] : '',
						'tested_by' => isset($checked_items['inspected_name']) ? $checked_items['inspected_name'] : '',
						'working' => ($checked_items['check_type'] == 2) ? 'YES' : 'NO',
						'date_corrected' => (strtotime($checked_items['datetime_inspection_end']) > 0 && $checked_items['check_type'] != 2) ? $checked_items['datetime_inspection_end'] : '',
					];
					break;
				}
			}
		}

		return [
			'date_tested' => '',
			'tested_by' => '',
			'working' => '',
			'date_corrected' => ''
		];
	}
}
