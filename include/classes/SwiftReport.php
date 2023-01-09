<?php

/**
 * @author SwiftProjectManager.Com
 * @copyright (C) 2021 SwiftManager license GPL
 * @package SwiftReport
**/

class SwiftReport
{
	var $content = [];
	var $js_code = [];

	var $periods = [
		12 => 'Last 12 months',
		6 => 'Last 6 months',
		3 => 'Last 3 months',
		1 => 'Last month',
		13 => 'Today',
		14 => 'This week',
		15 => 'This month',
		16 => 'This year',
	];

	function addContent($content){
		$this->content[] = $content;
	}

	function getContent(){
		if (!empty($this->content))
			return implode("\n", $this->content);
	}

	function addJS($js_code){
		$this->js_code[] = $js_code;
	}

	function getJS(){
		if (!empty($this->js_code))
			return implode("\n", $this->js_code);
	}

	function getPeriods($start = 2000)
	{
		$output = [];
		foreach($this->periods as $key => $value)
		{
			$output[$key] = $value;
		}

		if ($start > 2000)
		{
			for ($year = $start; $year <= date('Y'); $year++){
				$output[$year] = $year;
			}
		}

		return $output;
	}
}
