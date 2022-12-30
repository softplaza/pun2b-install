<?php 
/**
 * @author SwiftProjectManager.Com
 * @copyright (C) 2021 SwiftManager license GPL
 * @package SwiftDateTime
**/

class SwiftDateTime
{
	public $time_now = 0;
	public $microtime_start = 0;

	// Setup basic date & time formats
	public $time_formats = [];
	public $date_formats = [];

	// Setup User format
	public $time_format = 'H:i:s';
	public $date_format = 'Y-m-d';

	public $date_dst = '';

	function __construct()
	{
		// Record the start time (will be used to calculate the generation time for the page)
		$this->microtime_start = empty($_SERVER['REQUEST_TIME_FLOAT']) ? microtime(true) : (float) $_SERVER['REQUEST_TIME_FLOAT'];
		$this->timeNow();
	}

	function setFormats()
	{
		global $Config, $User;

		$this->date_formats = array($Config->get('o_date_format'), 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');
		$this->time_formats = array($Config->get('o_time_format'), 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');

		$this->date_format = $Config->get('o_date_format') != '' ? $Config->get('o_date_format') : $this->get_date_format($User->get('date_format'));
		$this->time_format = $Config->get('o_time_format') != '' ? $Config->get('o_time_format') : $this->get_time_format($User->get('time_format'));
	}

	function format_time($timestamp, $type = SPM_FT_DATETIME, $date_format = null, $time_format = null, $no_text = true) {
		global $User, $Config, $lang_common;
	
	//	if ($timestamp == '')
		if ($timestamp == '' || $timestamp == 0)
			return ($no_text ? '' : $lang_common['Never']);
	
		if ($date_format === null)
			$date_format = $Config->get('o_date_format') != '' ? $Config->get('o_date_format') : $this->get_date_format($User->get('date_format'));
	
		if ($time_format === null)
			$time_format = $Config->get('o_time_format') != '' ? $Config->get('o_time_format') : $this->get_time_format($User->get('time_format'));
	
	//	$diff = ($User->get('timezone') + $User->get('dst')) * 3600;
		$dst = (date('I', $timestamp) == 1) ? 1 : 0;
		$diff = ($User->get('timezone') + $dst) * 3600;
		
		$timestamp += $diff;
		$now = time();
	
		$formatted_time = '';
	
		if ($type == SPM_FT_DATETIME || $type == SPM_FT_DATE)
		{
			$formatted_time = gmdate($date_format, $timestamp);
	
			if (!$no_text)
			{
				$base = gmdate('Y-m-d', $timestamp);
				$today = gmdate('Y-m-d', $now + $diff);
				$yesterday = gmdate('Y-m-d', $now + $diff - 86400);
	
				if ($base == $today)
					$formatted_time = $lang_common['Today'];
				else if ($base == $yesterday)
					$formatted_time = $lang_common['Yesterday'];
			}
		}
	
		if ($type == SPM_FT_DATETIME)
			$formatted_time .= ' ';
	
		if ($type == SPM_FT_DATETIME || $type == SPM_FT_TIME)
			$formatted_time .= gmdate($time_format, $timestamp);
	
		return $formatted_time;
	}

	// Format datetime as string. Shift: '+ 5 days'
	function strFormat($date, $format = 'Y-m-d H:i:s', $shift = '') {
		return ($shift != '') ? date($format, strtotime($date . $shift)) : date($format, strtotime($date));
	}

	// Checking sent Date and Time forms
	function postToTime($form_name, $else = 0) {
		return isset($_POST[$form_name]) ? strtotime($_POST[$form_name]) : $else;
	}
	function GetToTime($form_name, $else = 0) {
		return isset($_GET[$form_name]) ? strtotime($_GET[$form_name]) : $else;
	}
	function postToDate($form_name, $format = 'Y-m-d', $else = null) {
		return isset($_POST[$form_name]) ? date($format, strtotime($_POST[$form_name])) : $else;
	}
	function getToDate($form_name, $format = 'Y-m-d', $else = null) {
		return isset($_GET[$form_name]) ? date($format, strtotime($_GET[$form_name])) : $else;
	}

	// WE WILL USE NEXT FUNCTION FOR CONVERT DATE AND TIME
	function time_to_db_date($time) {
	}
	function str_to_db_date($date) {
		return date('Y-m-d', strtotime($date));
	}

	function isDst($timezone = '') {
		$timezone = ($timezone != '') ? $timezone : date_default_timezone_get();
		$date = new DateTime('now', new DateTimeZone($timezone));
		return (bool) $date->format('I');
	}

	// use VCR edit project
	function dayOfWeek($time = '')
	{
		if ($time != '')
		{
			// 0 or setup 1970 Year
			if (is_numeric($time) && $time > 0) 
				return date('l', $time);
		}
	}

	// Convert time format to date input
	function timeToDateInput($timestamp)
	{
		$new_data = '';
		
		if ($timestamp != '')
		{
			if(is_numeric($timestamp) && $timestamp > 0) 
				$new_data = date('Y-m-d', $timestamp);
		}
		return $new_data;
	}

	function get_date_format($key) {
		global $Config;
		return isset($this->date_formats[$key]) ? $this->date_formats[$key] : $Config->get('o_date_format');
	}

	/*
	function get_time_format($key) {
		global $Config;
		return isset($this->time_formats[$key]) ? $this->time_formats[$key] : $Config->get('o_time_format');
	}

	function get_time_formats() {
		return $this->time_formats;
	}

	function get_date_formats() {
		return $this->date_formats;
	}

	// Check is today by time
	function is_today($timestamp = 0) {
		$is_today = false;
		$date_input = date('Ymd', $timestamp);
		
		if ($date_input == date('Ymd', time()))
			$is_today = true;
		
		return $is_today;
	}
	
	function format_date($timestamp, $date_format = null) {
		$output = strtotime($timestamp);
		
		if ($output > 0)
			return format_time($output, 1, $date_format);
	}
	
	function strtodate($str) {
		$output = strtotime(swift_trim($str));
		
		if ($output > 0)
			return date('Ymd', $output);
	}

	// Get time slots array - Set interval in minutes
	function genTimeSlots($interval, $start_time = '0:00', $end_time = '23:59') 
	{
		$interval = $interval * 60;
		$num_slots = 86400 / $interval;
		$start = strtotime($start_time);
		$end = strtotime($end_time);
		
		$slots = array();
		for($i = 0; $i < $num_slots; $i++)
		{
			$time = $i * $interval;
//			if ($time > $start && $time < $end)
//				$slots[$i] = gmdate('g:i A', $time).'-'.$start.'-'.$end;
			$slots[$i] = $time.'-'.$start.'-'.$end;
		}
		
		return $slots;
	}

	function genTimeSlot($interval, $start = '0:00', $end = '23:59')
	{
		$start = new DateTime($start);
		$end = new DateTime($end);
		// Get time Format in Hour and minutes
		$start_time = $start->format('H:i');
		$end_time = $end->format('H:i');
		
		$i=0;
		while(strtotime($start_time) <= strtotime($end_time)){
		    $start = $start_time;
		    $end = date('H:i',strtotime('+'.$interval.' minutes',strtotime($start_time)));
		    $start_time = date('H:i',strtotime('+'.$interval.' minutes',strtotime($start_time)));
		    $i++;
		    if(strtotime($start_time) <= strtotime($end_time)){
		        $time[$i]['start'] = $start;
		        $time[$i]['end'] = $end;
		    }
		}
		return $time;
	}
	
	// Convert time format to time input
	function time_input($timestamp)
	{
		$new_data = '';
		
		if ($timestamp != '')
		{
			if(is_numeric($timestamp) && $timestamp > 0) 
			$new_data = date('H:i', $timestamp);
		}
		return $new_data;
	}
	
	// Convert time format to date and time input
	function datetime_input($timestamp)
	{
		$new_data = '';
		
		if ($timestamp != '')
		{
			if (is_numeric($timestamp) && $timestamp > 0) 
			$new_data = date('Y-m-d\TH:i', $timestamp);
		}
		
		return $new_data;
	}


	*/




	function timeNow() {
		$this->time_now = time();
		return $this->time_now;
	}
}
