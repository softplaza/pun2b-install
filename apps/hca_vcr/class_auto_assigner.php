<?php

class AutoAssigner
{
	public $one_day = 86400;
	public $move_out_date = 0;
	public $next_date = 0;
	
	function setMoveOutDate($date) {
		$this->move_out_date = $date;
	}
	
	//Find first available day Mon - Fri
	function setMaintDate()
	{
		$next_day = $this->move_out_date + $this->one_day;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 6) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	// Painter Days: Mon - Fri
	function setPaintDate() {
		$next_day = $this->next_date;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 6) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	// Cleaning Date: Mon - Sat
	function setCleanDate() {
		$next_day = $this->next_date;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 7) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	// Vinyl Date: Mon - Sat
	function setVinylDate() {
		$next_day = $this->next_date;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 7) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	
	// Carpet Date: Mon - Sat
	function setCarpetDate() {
		$next_day = $this->next_date;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 7) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	
	// Carpet Date: Mon - Sat
	function setCleanCarpetDate() {
		$next_day = $this->next_date;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 7) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	
	function setPestDate() {
		$next_day = $this->next_date;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 6) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	
	function setRefinishDate() {
		$next_day = $this->next_date;
		for($i = 1; $i < 8; ++$i)
		{
			$daynum = date("N", $next_day);
			if ($daynum > 0 && $daynum < 6) {
				break;
			}
			else
				$next_day = $next_day + $this->one_day;
		}
		
		$this->next_date = $next_day + $this->one_day;
		return $next_day;
	}
	
}
$AutoAssigner = new AutoAssigner;