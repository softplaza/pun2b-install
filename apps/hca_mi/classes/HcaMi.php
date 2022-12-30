<?php

/**
 * @author SwiftProjectManager.Com
 * @copyright (C) 2021 SwiftManager license GPL
 * @package HcaMi
**/

class HcaMi
{
	var $job_status = [
		0 => 'REMOVED',
		1 => 'IN PROGRESS', 
		2 => 'ON HOLD', 
		3 => 'COMPLETED', 
		//4 => 'REMOVED',
	];

	var $locations = [
		1 => 'L/ROOM',
		2 => 'D/ROOM',
		3 => 'KITCHEN',
		4 => 'HALLWAY',
		5 => 'BATHROOM',
		6 => 'G/BATHROOM',
		7 => 'M/BATHROOM',
		8 => 'G/BEDROOM',
		9 => 'M/BEDROOM',
		10 => 'LAUNDRY',
		11 => 'BALCONY',
		12 => 'WHATER HEATER CLOSET',
		13 => 'ATTICK',
		14 => 'ENTIRE UNIT',
		15 => 'BOTH BATH',
		16 => 'WASHER/DRIER CLOSET',
		17 => 'L/MASTER BATH',
		18 => 'R/MASTER BATH',
		19 => 'A/C CABINET',
		20 => 'VANITY AREA',
		21 => 'M/BEDROOM CLOSET',
		22 => 'GARAGE'
	];

	var $leak_types = [
		//0 => 'Unknown/Other',
		1 => 'ABS Leak/Cracked',
		2 => 'AC Leak',
		3 => 'Angle Stop Leak',
		4 => 'Copper line Leak',
		5 => 'Dishwasher Leak',
		6 => 'Drain Back Up',
		7 => 'Drain Pipe Cracked',
		8 => 'Drain Pipe Leak',
		9 => 'Exterior Line Leak',
		27 => 'Exterior Rain Affected Unit/Rain Penetration',
		24 => 'Exterior Stucco Cracked',
		10 => 'Fire Sprinkler Leak',
		11 => 'Garbage Disposal Leak',
		12 => 'Irrigation Leak',
		13 => 'Roof Leak',
		28 => 'Sink Faucet Leak',
		14 => 'Sink Overflow',
		15 => 'Slab Leak',
		16 => 'Shower Arm Leak/Broken',
		29 => 'Shower Cartridge Leak/Broken',
		30 => 'Shower Head Leak',
		25 => 'Shower Tile Cracked',
		17 => 'Supply Line Leak',
		18 => 'Toilet Leak',
		19 => 'Toilet Overflow',
		20 => 'Tub Cracked',
		31 => 'Tub Diverter Leak',
		21 => 'Tub Overflow',
		32 => 'Tub Overflow Plate',
		33 => 'Tub Spout Leak',
		23 => 'Unventilated Household',
		22 => 'Washing Machine Leak',
		26 => 'Water Heater Leak',
		// last ID - 33
	];

	var $symptoms = [
		1 => 'Discoloration',
		2 => 'Wet Cabinets',
		3 => 'Wet Drywall',
		4 => 'Wet Subfloor',
		5 => 'Wet Carpet'
	];

	var $default_services = [
		1 => 'Services',
		2 => 'Scope of Work/Asbestos',
		3 => 'Remediation',
		4 => 'Construction',
	];


	function check_vendor($default_vendors, $vendor_id, $key)
	{
		if (!empty($default_vendors))
		{
			foreach($default_vendors as $cur_info)
			{
				if ($vendor_id == $cur_info['vendor_id'] && $key == $cur_info['group_id'])
					return $cur_info;
			}
			return [];
		}
		return [];
	}

	function addAction($project_id, $message = '')
	{
		global $User, $DBLayer;

		$project_actions = [
			'project_id' => $project_id,
			'submitted_by' => $User->get('id'),
			'time_submitted' => time(),
			'message' => $message,
		];
		$DBLayer->insert('hca_mi_actions', $project_actions);
	}
}
