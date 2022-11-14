<?php

class HcaHVACInspections
{
	var $locations = [
		1 => 'Hallways',
		2 => 'AC Cabinet',
		3 => 'Guest Bedroom',
	];

	var $equipments = [
		10 => 'AC Cabinet',
		20 => 'Cabinet Walls',
		30 => 'Cabinet Ceiling',
		40 => 'AC Filter',
		50 => 'Owerflow Switch',
		60 => 'Coils',
		61 => 'Service sticker',
		70 => 'Fungal Tablet',
		80 => 'Water Alarm',
		85 => 'Thermostat',
		90 => 'Carbon Monoxide "CO" Alarm',
		91 => 'Smoke Alarm',
		//100 => 'Thermostat 2',
	];

	var $elements = [
		// A
		1 => 'AC Cabinet',
		2 => 'AC Cabinet Door',
		3 => 'AC Cabinet Door Lock',
		4 => 'AC Filter',
		5 => 'AC Vent',
		// B 11-20
		// C 21-30
		21 => 'Cabinet Ceiling',
		22 => 'Cabinet Walls',
		23 => 'CO Alarm',
		24 => 'CO Alarm Battery',
		25 => 'Coils',
		26 => 'Condensate Line',
		// D 31-40
		// E 41-50
		// F 51-60
		27 => 'Fundal Tablet',
		// G 61-70
		// H 71-80
		// I 81-90
		// J 91-100
		// K 101-110
		// L 111-120
		// M 121-130
		// N 131-140
		// O 141-150
		141 => 'Owerflow Switch',
		// P 151-160
		// Q 161-170
		// R 171-180
		// S 181-190
		// T 191-200
		191 => 'Thermostat',
		// U 201-210
		// V 211-220
		// W 221-230
		221 => 'Water Alarm',
		222 => 'Water Alarm Battery',
		// X 231-240
		// Y 241-250
		// Z 251-260
	];

	var $problems = [
		1 => 'Broken',
		2 => 'Clogged',
		3 => 'Corroded',
		4 => 'Cracked',
		5 => 'Dirty',
		6 => 'Discolored',
		7 => 'Dripping',
		8 => 'Failing',
		//9 => 'High',
		10 => 'Leaking',
		11 => 'Loose',
		12 => 'Low',
		13 => 'Missing',
		14 => 'Not Working',
		15 => 'Paint peeling',
		16 => 'Rusted',
		//17 => 'Switch tide with Fan',
		18 => 'Wet',

		//0 => 'Other',
	];

	var $actions = [
		//0 => 'Pending',
		1 => 'Replaced',
		2 => 'Installed',
		3 => 'Cleaned',
		4 => 'Tested',
		5 => 'Inspected',
		6 => 'Marked',
		7 => 'Repared',
		8 => '5840 informed',
		10 => 'Replaced - Tested',
		11 => 'Installed & Marked Out',
		
		// hold this item on last position
		9 => 'Other',
	];

	var $statuses = [
		0 => 'Pending',
		1 => 'Replaced',
	];

	function getElement($id) {
		return isset($this->elements[$id]) ? $this->elements[$id] : '';
	}

	function getEquipment($id) {
		return (isset($this->equipments[$id]) && $id > 0) ? $this->equipments[$id] : '';
	}

	function genItemName($cur_info)
	{
		$output = [];

		if ($cur_info['equipment_id'] > 0 && isset($this->equipments[$cur_info['equipment_id']]))
			$output[] = $this->equipments[$cur_info['equipment_id']];
		
		if ($cur_info['item_name'] != '')
			$output[] = html_encode($cur_info['item_name']);
		else if ($cur_info['element_id'] > 0 && isset($this->elements[$cur_info['element_id']]))
			$output[] = html_encode($this->elements[$cur_info['element_id']]);

		return implode(' -> ', $output);
	}

	function getItemProblems($problem_ids)
	{
		$output = [];
		$problems = explode(',', $problem_ids);

		foreach($this->problems as $key => $value)
		{
			if (in_array($key, $problems))
				$output[] = $value;
		}

		return implode(', ', $output);
	}

	function get_not_inspected($property_id)
	{
		$i = 0;
		if (!empty($this->properties_info)) 
		{
			foreach($this->properties_info as $cur_info)
			{
				// 1 is >
				$date_inspected = strtotime($cur_info['date_inspected']) + 31536000;
				if ($cur_info['property_id'] == $property_id && format_date($cur_info['date_inspected']) != '' && compare_dates(date('Y-m-d'), date('Y-m-d', $date_inspected), 1))
					++$i;
			}
		}

		return $i;
	}

	function getProperties($excludes = [])
	{
		global $DBLayer, $User;

		$query = array(
			'SELECT'	=> 'p.*',
			'FROM'		=> 'sm_property_db AS p',
			'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
			'ORDER BY'	=> 'p.pro_name'
		);
		if ($User->get('property_access') != '' && $User->get('property_access') != 0)
		{
			$property_ids = explode(',', $User->get('property_access'));
			$query['WHERE'] .= ' AND p.id IN ('.implode(',', $property_ids).')';
		}
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$sm_property_db = [];
		while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$sm_property_db[] = $fetch_assoc;
		}

		return $sm_property_db;
	}

	function getPeriods($start = 2000)
	{
		$output = [];
		$output[12] = 'Last 12 months';
		$output[6] = 'Last 6 months';
		$output[3] = 'Last 3 months';
		$output[1] = 'Last month';

		for ($year = $start; $year <= date('Y'); $year++)
		{
			$output[$year] = $year;
		}

		return $output;
	}
}
