<?php

class HcaFsStatistic
{
	public $time_slots = array(
		1 => 'ALL DAY', 
		2 => 'A.M.', 
		3 => 'P.M.', 
		4 => 'DAY OFF', 
		5 => 'SICK DAY', 
		6 => 'VACATION',
		7 => 'STAND BY'
	);

	public $property_info = [];
	public $properties = [];

	public $monthly_orders = [];

	public $num_work_orders = 0;
	public $num_sick_days = 0;
	public $num_day_off = 0;
	public $num_vacations = 0;
	public $num_stand_by = 0;

	function __construct()
	{
		global $DBLayer;

		$query = array(
			'SELECT'	=> 'id, pro_name',
			'FROM'		=> 'sm_property_db',
			'ORDER BY'	=> 'pro_name'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$property_info = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			if (!in_array($row['id'], [113,115]))
				$property_info[] = $row;
		}

		$this->property_info = $property_info;
	}

	function addPropertyJob($id){
		if (isset($this->properties[$id]))
			++$this->properties[$id];
		else
			$this->properties[$id] = 1;
	}
	function addWorkOrder(){
		++$this->num_work_orders;
	}
	function addSickDay(){
		++$this->num_sick_days;
	}
	function addDayOff(){
		++$this->num_day_off;
	}
	function addVacation(){
		++$this->num_vacations;
	}
	function addStandBy(){
		++$this->num_stand_by;
	}

	function getStatusTitle($cur_info)
	{
		$title = isset($this->time_slots[$cur_info['time_slot']]) ? $this->time_slots[$cur_info['time_slot']] : 'n/a';

		if ($cur_info['time_slot'] < 4){
			$status = '<span class="badge bg-primary">'.$title.'</span>';
			$this->addWorkOrder();

			if ($cur_info['start_date'] > 0)
				$this->addToMonth($cur_info);
		}	
		else if ($cur_info['time_slot'] == 4){
			$status = '<span class="badge bg-success">'.$title.'</span>';
			$this->addDayOff();
		}
		else if ($cur_info['time_slot'] == 5){
			$status = '<span class="badge bg-danger">'.$title.'</span>';
			$this->addSickDay();
		}
		else if ($cur_info['time_slot'] == 6){
			$status = '<span class="badge bg-info">'.$title.'</span>';
			$this->addVacation();
		}
		else if ($cur_info['time_slot'] == 7){
			$status = '<span class="badge bg-warning">'.$title.'</span>';
			$this->addStandBy();
		}
		else
			$status = '<span class="badge bg-warning">n/a</span>';

		if ($cur_info['property_id'] > 0)
			$this->addPropertyJob($cur_info['property_id']);

		return $status;
	}

	function getLabelStatuses(){
		$output = [
			'"WORK ORDERS"', 
			'"DAYS OFF"', 
			'"SICK DAYS"', 
			'"VACATIONS"',
			'"STAND BY"'
		];
		return implode(', ', $output);
	}

	function getNumStatuses(){
		$output = [
			$this->num_work_orders,
			$this->num_day_off,
			$this->num_sick_days,
			$this->num_vacations,
			$this->num_stand_by
		];
		return implode(',', $output);
	}
	function getPropertyAttendance()
	{
		$output = [];
		foreach($this->property_info as $cur_info)
		{
			if(!empty($this->properties))
			{
				foreach($this->properties as $pid => $numbers)
				{
					if ($cur_info['id'] == $pid)
						$output['"'.$cur_info['pro_name'].'"'] = $numbers;
				}
			}
		}
		return $output;
	}

	function addToMonth($cur_info)
	{
		if ($cur_info['start_date'] > 0)
		{
			//$m = date('m', $cur_info['start_date']);
			$m = date('Ym01', $cur_info['start_date']);
			if (isset($this->monthly_orders[$m]))
				++$this->monthly_orders[$m];
			else
				$this->monthly_orders[$m] = 1;
		}
	}
	function getMonthlyVisits()
	{
		$output = [];
		if (!empty($this->monthly_orders))
		{
			ksort($this->monthly_orders);
			foreach($this->monthly_orders as $key => $num)
			{
				//$month = '"'.date("F", mktime(null, null, null, $key)).'"';
				$time = strtotime($key);
				$month = '"'.date('F, Y', $time).'"';
				$output[$month] = $num;
			}
		}
		return $output;
	}
}
