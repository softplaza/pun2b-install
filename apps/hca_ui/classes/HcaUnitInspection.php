<?php

class HcaUnitInspection
{
	var $locations = [
		//0 => '',
		1 => 'Kitchen',
		2 => 'Guest Bathroom',
		3 => 'Master Bathroom',
		4 => 'Half Bathroom',
		100 => 'Appliances',
	];

	var $equipments = [
		0 => 'No equipment',
		15 => 'APPLIANCES',//Appliances
		8 => 'ANGLE STOPS',//remove
		9 => 'ASSEMBLY POP-UP',
		10 => 'CEILING',
		11 => 'COUNTERTOP',
		2 => 'DISHWASHER',
		6 => 'DRAIN PIPE',
		1 => 'FAUCET',
		14 => 'FLOOR',
		12 => 'FAN',
		3 => 'GARBAGE DISPOSAL',
		4 => 'SHOWER/TUB',
		5 => 'SINK',
		7 => 'TOILET',
		13 => 'WALLS',

		// Last # 15
	];

	var $elements = [
		0 => '',
		//55 => 'Angle Stops',// remove
		2 => 'CEILING',
		3 => 'COUNTERTOP',
		4 => 'DISHWASHER',
		1 => 'DISHWASHER - Air-Gap',
		5 => 'DISHWASHER - Supply Lines',
		//50 => 'Drain Pipe', //remove
		49 => 'FAN',
		61 => 'SWITCH FAN',
		6 => 'GARBAGE DISPOSAL',
		7 => 'GARBAGE DISPOSAL - Hose',
		51 => 'GARBAGE DISPOSAL - Strainer',
		8 => 'FAUCET',
		9 => 'FAUCET - Aerator',
		10 => 'FAUCET - Handle',
		11 => 'FAUCET - Spout',
		12 => 'FAUCET - Cabinet Deck',
		13 => 'FAUCET - Cartridge',
		14 => 'FAUCET - Handles',
		15 => 'FAUCET - Angle Stops',
		16 => 'FAUCET - Supply Lines',
		60 => 'FAUCET - Slip Joint Nut',
		17 => 'SINK',
		18 => 'SINK - Assembly Pop-Up',
		19 => 'SINK - Caulking',
		20 => 'SINK - Drain Pipe',
		21 => 'SINK - Rod Ball',
		22 => 'SINK - Slip Joint Nut',
		54 => 'SINK - Strainer',
		59 => 'SINK - Stopper',
		58 => 'SHOWER/TUB',
		23 => 'SHOWER/TUB- Shower Head',
		24 => 'SHOWER/TUB - Shower Head Neck',
		25 => 'SHOWER/TUB - Handles',
		26 => 'SHOWER/TUB - Escutcheon Plate',
		27 => 'SHOWER/TUB - Cartridge',
		28 => 'SHOWER/TUB - Tub',
		56 => 'SHOWER/TUB - Pan',
		53 => 'SHOWER/TUB - Slip Joint Nut',
		//52 => 'Supply Lines', //remove
		29 => 'SHOWER/TUB - Diverter Spout',
		30 => 'SHOWER/TUB - Overflow Plate',
		31 => 'SHOWER/TUB - Drain',
		32 => 'SHOWER/TUB - Caulking',
		33 => 'SHOWER/TUB - Splash Guards',
		34 => 'TOILET',
		35 => 'TOILET - Angle Stops',
		36 => 'TOILET - Wax Seal',
		37 => 'TOILET - Tank to Bowl Gasket',
		38 => 'TOILET - Tank to Bowl Bolts',
		39 => 'TOILET - Water Level in Tank',
		40 => 'TOILET - Flapper 2 inches',
		57 => 'TOILET - Flapper 3 inches',
		41 => 'TOILET - Fill Valve',
		42 => 'TOILET - Pressure Tank',
		65 => 'TOILET - Cartridge',
		43 => 'TOILET - Lid',
		44 => 'TOILET - Handle',
		45 => 'TOILET - Flush Valve',
		46 => 'TOILET - Caulking',
		47 => 'TOILET - Supply Lines',
		62 => 'VINYL',
		48 => 'WALLS',
		63 => 'Washer',
		64 => 'Water Heater',

		// Last ID = 65
	];

	var $job_types = [
		0 => 'Pending',
		1 => 'Replaced',
		2 => 'Repaired',
		3 => 'Reset',
		4 => 'Tied',
	];

	function getProblems() 
	{
		return [
			22 => 'Broken', // emergency
			1 => 'Clogged',
			2 => 'Corroded',
			3 => 'Cracked',
			4 => 'Dirty',
			5 => 'Discolored',
			6 => 'Dripping',
			7 => 'Failing',
			15 => 'High',
			8 => 'Leaking', // emergency
			9 => 'Loose',
			12 => 'Low',
			10 => 'Missing',
			11 => 'Not Working',
			21 => 'Paint peeling',
			//19 => 'Replace',
			14 => 'Rusted',
			13 => 'Wet',
			23 => 'Need to be tied into single switch',
			0 => 'Other',

			// Last id 22
		];
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

	function getJobType($id) 
	{
		return isset($this->job_types[$id]) ? $this->job_types[$id] : '';
	}

	function getLocation($id) {
		$locations = [
			1 => 'Kitchen',
			2 => 'GBath',
			3 => 'MBath',
			4 => 'HBath',
			100 => 'Appliances',
		];
		return (isset($locations[$id]) && $id > 0) ? $locations[$id] : '';
	}

	function getEquipment($id) {
		return (isset($this->equipments[$id]) && $id > 0) ? $this->equipments[$id] : '';
	}

	function getElement($id) {
		return isset($this->elements[$id]) ? $this->elements[$id] : '';
	}

	function get_never_inspected($property_id)
	{
		$i = 0;
		if (!empty($this->properties_info)) 
		{
			foreach($this->properties_info as $cur_info)
			{
				if ($cur_info['property_id'] == $property_id && format_date($cur_info['date_inspected']) == '')
					++$i;
			}
		}

		return $i;
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

	function getItemProblems($problem_ids)
	{
		$output = [];
		$problems = explode(',', $problem_ids);

		foreach($this->getProblems() as $key => $value)
		{
			if (in_array($key, $problems))
				$output[] = $value;
		}

		return implode(', ', $output);
	}
}
