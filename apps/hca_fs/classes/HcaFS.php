<?php

class HcaFS
{
	var $time_slot = [
		0 => 'ANY TIME',
		1 => 'ALL DAY', 
		2 => 'A.M.', 
		3 => 'P.M.', 
		4 => 'DAY OFF', 
		5 => 'SICK DAY', 
		6 => 'VACATION',
		7 => 'STAND BY'
	];
	
	function getTimeSlot($key){
		return isset($this->time_slot[$key]) ? $this->time_slot[$key] : '';
	}
}
