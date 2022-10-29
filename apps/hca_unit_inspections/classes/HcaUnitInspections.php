<?php

class HcaUnitInspections
{
	var $locations = [
		5 => 'Living Room',
		6 => 'Dining Room',
		1 => 'Kitchen',
		7 => 'Hallway',
		2 => 'Guest Bathroom',
		3 => 'Master Bathroom',
		4 => 'Half Bathroom',
		8 => 'Guest Bedroom 1',
		9 => 'Guest Bedroom 2',
		10 => 'Master Bedroom',
	];

	var $equipments = [
		// A 1-10
		1 => 'A/C CABINET',
		2 => 'AIR-GAP',
		3 => 'ANGLE STOPS',
		// B 11-20
		11 => 'BATH',
		// C 21-30
		21 => 'CEILING',
		22 => 'COUNTERTOP',
		// D 31-40
		31 => 'DISHWASHER',
		32 => 'DRAIN PIPES',
		33 => 'DRIER',
		// E 41-50
		// F 51-60
		51 => 'FAN',
		52 => 'FAUCET',
		53 => 'FURNACE',
		// G 61-70
		61 => 'GARBAGE DISPOSAL',
		// H 71-80
		// I 81-90
		// J 91-100
		// K 101-110
		// L 111-120
		// M 121-130
		// N 131-140
		// O 141-150
		// P 151-160
		151 => 'PAN',
		// Q 161-170
		// R 171-180
		// S 181-190
		181 => 'SHOWER',
		182 => 'SINK',
		183 => 'STRAINER BASKET',
		184 => 'SUPPLY LINES',
		// T 191-200
		191 => 'TOILET',
		192 => 'TUB',
		// U 201-210
		// V 211-220
		// W 221-230
		221 => 'WALLS',
		222 => 'WASHER',
		223 => 'WATER HEATER',
		224 => 'WINDOW SILLS',
		// X 231-240
		// Y 241-250
		// Z 251-260
	];

	var $elements = [
		// A B C D E F G H I J K L M N O P Q R S T U V W X Y Z
		1 => 'Aerator',
		2 => 'Air-Gap',
		3 => 'Angle Stops',
		4 => 'Assembly Pop-Up',
		// B 11-20
		// C 21-30
		21 => 'Cabinet Deck',
		22 => 'Caulking',
		23 => 'Cartridge',
		// D 31-40
		31 => 'Diverter Spout',
		32 => 'Drain',
		33 => 'Drain Pipe',
		// E 41-50
		41 => 'Escutcheon Plate',
		// F 51-60
		51 => 'Fill Valve',
		52 => 'Flapper 2 inches',
		53 => 'Flapper 3 inches',
		54 => 'Flush Valve',
		// G 61-70
		// H 71-80
		71 => 'Handle',
		72 => 'Handles',
		73 => 'Hose',
		// I 81-90
		// J 91-100
		// K 101-110
		// L 111-120
		111 => 'Lid',
		// M 121-130
		// N 131-140
		// O 141-150
		141 => 'Overflow Plate',
		// P 151-160
		151 => 'Pressure Tank',
		151 => 'Pop-up',
		// Q 161-170
		// R 171-180
		171 => 'Rod Ball',
		// S 181-190
		181 => 'Shower Head',
		182 => 'Shower Head Neck',
		183 => 'Slip Joint Nut',
		184 => 'Splash Guards',
		185 => 'Spout',
		186 => 'Stopper',
		187 => 'Strainer',
		188 => 'Supply Lines',
		// T 191-200
		191 => 'Tank to Bowl Bolts',
		192 => 'Tank to Bowl Gasket',
		// U 201-210
		// V 211-220
		// W 221-230
		// X 231-240
		231 => 'Water Level in Tank',
		232 => 'Wax Seal',
		// Y 241-250
		// Z 251-260
	];

	var $problems = [
		22 => 'Broken',
		1 => 'Clogged',
		2 => 'Corroded',
		3 => 'Cracked',
		4 => 'Dirty',
		5 => 'Discolored',
		6 => 'Dripping',
		7 => 'Failing',
		15 => 'High',
		8 => 'Leaking',
		9 => 'Loose',
		12 => 'Low',
		10 => 'Missing',
		11 => 'Not Working',
		21 => 'Paint peeling',
		//19 => 'Replace',
		14 => 'Rusted',
		19 => 'Switch tide with Fan',
		13 => 'Wet',

		0 => 'Other',
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
}
