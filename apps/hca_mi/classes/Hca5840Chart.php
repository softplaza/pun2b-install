<?php

class Hca5840Chart
{
	public $search_by_keywords = false;

	public $monthly_info = [];
	public $property_info = [];
	public $symptoms_info = [];
	public $sources_of_moisture = [];

	public $symptoms_titles = [
		0 => 'Slab Leak',
		//1 => 'Relocation',
		2 => 'Discoloration',
		3 => 'Roof Leak',
		4 => 'Discoloration',
	];
	public $symptoms_keywords = [
		0 => 'slab leak',
		//1 => '_______',
		2 => 'discoloration',
		3 => 'roof leak',
		4 => 'mold',
	];

	public $leak_types = [
		
		//1 => 'ABS Leak/Cracked',
		2 => 'AC Leak',//
		//3 => 'Angle Stop Leak',
		4 => 'Copper line Leak',//
		//5 => 'Dishwasher Leak',
		//6 => 'Drain Back Up',
		//7 => 'Drain Pipe Cracked',
		//8 => 'Drain Pipe Leak',
		//9 => 'Exterior Line Leak',
		//24 => 'Exterior Stucco Cracked',
		//10 => 'Fire Sprinkler Leak',
		//11 => 'Garbage Disposal Leak',
		//12 => 'Irrigation Leak',
		13 => 'Roof Leak',//
		//14 => 'Sink Overflow',
		15 => 'Slab Leak',//
		//16 => 'Shower Arm Leak/Broken',
		//25 => 'Shower Tile Cracked',
		//17 => 'Supply Line Leak',
		//18 => 'Toilet Leak',
		//19 => 'Toilet Overflow',
		//20 => 'Tub Cracked',
		//21 => 'Tub Overflow',
		//23 => 'Unventilated Household',
		//22 => 'Washing Machine Leak',
		//26 => 'Water Heater Leak',
		// last ID - 26
		//0 => 'Unknown/Other',
		//100 => 'Relocation',
		//101 => 'discoloration',
	];

	public $symptoms_colors = [
		0 => 'window.theme.primary',
		1 => 'window.theme.warning',
		2 => 'window.theme.info',
		3 => 'window.theme.success',
		4 => 'window.theme.secondary',
		5 => 'window.theme.danger',
		6 => 'window.theme.light',
		7 => 'window.theme.dark',
	];

	function __construct(){}

	// Monthly statistic
	function addToMonth($date)
	{
		if ($date > 0)
		{
			$m = date('Ym01', $date);
			if (isset($this->monthly_info[$m]))
				++$this->monthly_info[$m];
			else
				$this->monthly_info[$m] = 1;
		}
	}

	function getMonthlyFrequency()
	{
		$output = [];
		if (!empty($this->monthly_info))
		{
			ksort($this->monthly_info);
			foreach($this->monthly_info as $key => $num)
			{
				$time = strtotime($key);
				$month = '"'.date('F, Y', $time).'"';
				$output[$month] = $num;
			}
		}
		return $output;
	}

	// Property statistic
	function addProperty($name){
		if (isset($this->property_info[$name]))
			++$this->property_info[$name];
		else
			$this->property_info[$name] = 1;
	}

	function getPropertyFrequency(){
		$output = [];
		foreach($this->property_info as $pro_name => $numbers){
			$output['"'.$pro_name.'"'] = $numbers;
		}
		return $output;
	}

	function addSymptom($text, $search_keywords = '')
	{
		if (!$this->search_by_keywords)
		{
			$this->symptoms_titles = [0 => $search_keywords];
			$this->symptoms_keywords = [0 => $search_keywords];

			$this->search_by_keywords = true;
		}

		$search_keywords = strtolower($search_keywords);
		$text = strtolower($text);

		if (strpos($text, $search_keywords) !== false)
		{
			if (isset($this->sources_of_moisture[$search_keywords]))
				++$this->sources_of_moisture[$search_keywords];
			else
				$this->sources_of_moisture[$search_keywords] = 1;
		}
	}

	function addSymptoms($text)
	{
		$text = strtolower($text);
		foreach($this->symptoms_keywords as $key => $keyword)
		{
			$title = $this->symptoms_titles[$key];
			if (strpos($text, $keyword) !== false)
			{
				if (isset($this->sources_of_moisture[$title]))
					++$this->sources_of_moisture[$title];
				else
					$this->sources_of_moisture[$title] = 1;
			}
		}
	}

	function addKeyWord($key_word)
	{
		if (isset($this->sources_of_moisture[$key_word]))
			++$this->sources_of_moisture[$key_word];
		else
			$this->sources_of_moisture[$key_word] = 1;
	}

	function getSymptoms(){
		$output = [];
		foreach($this->symptoms_info as $title => $numbers){
			$output['"'.$title.'"'] = $numbers;
		}
		return $output;
	}

	function addSoM($key)
	{
		foreach($this->leak_types as $id => $title)
		{
			if ($key == $id)
			{
				if (isset($this->sources_of_moisture[$title]))
					++$this->sources_of_moisture[$title];
				else
					$this->sources_of_moisture[$title] = 1;
			}
		}
	}

	function getSoM()
	{
		$output = [];
		foreach($this->sources_of_moisture as $title => $total)
		{
			//if (isset($this->leak_types[$key]))
				$output['"'.$title.'"'] = $total;
		}
		return $output;
	}

}
