<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package pan_framework
 */

function smTrimArray($form_data, $arr_fields)
{
	$new_data = array();
	if (!empty($form_data))
	{
		foreach($form_data as $key => $val)
		{
			if (in_array($key, $arr_fields)) {
				$val = ($val != '') ? swift_trim($val) : '';
				$new_data[$key] = $val;
			}
		}
	}
	
	return $new_data;
}

function smIntvalArray($form_data, $arr_fields)
{
	$new_data = array();
	if (!empty($form_data))
	{
		foreach($form_data as $key => $val)
		{
			if (in_array($key, $arr_fields)) {
				$val = is_numeric($val) ? intval($val) : 0;
				$new_data[$key] = $val;
			}
		}
	}
	
	return $new_data;
}

function smFormatTtimeInputToTime($form_data, $arr_fields)
{
	$new_data = array();
	if (!empty($form_data))
	{
		foreach($form_data as $key => $val)
		{
			if (in_array($key, $arr_fields)) {
				$val = ($val != '') ? strtotime($val) : 0;
				$new_data[$key] = $val;
			}
		}
	}
	
	return $new_data;
}

// Update DB Table by ID. Must content array of data
function sm_update_table($table_name, $id, $arr_data)
{
	global $DBLayer;
	
	if (($table_name != '') && ($id > 0) && !empty($arr_data))
	{
		$set_str = '';
		foreach($arr_data as $key => $val)
		{
			if ($set_str == '')
				$set_str = $key.'=\''.$DBLayer->escape($val).'\'';
			else
				$set_str .= ', '.$key.'=\''.$DBLayer->escape($val).'\'';
		}
		
		if ($set_str != '')
		{
			$query = array(
				'UPDATE'	=> $table_name,
				'SET'	=> $set_str,
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}
}

// Convert time format to date input
function sm_date_input($timestamp)
{
	$new_data = '';
	
	if ($timestamp != '')
	{
		if (is_numeric($timestamp) && $timestamp > 0) 
			$new_data = date('Y-m-d', $timestamp);
	}
	return $new_data;
}

// Convert time format to time input
function sm_time_input($timestamp)
{
	$new_data = '';
	
	if ($timestamp != '')
	{
		if (is_numeric($timestamp) && $timestamp > 0) 
		$new_data = date('H:i', $timestamp);
	}
	return $new_data;
}

// Convert time format to date and time input
function sm_datetime_input($timestamp)
{
	$new_data = '';
	
	if ($timestamp != '')
	{
		if (is_numeric($timestamp) && $timestamp > 0) 
		$new_data = date('Y-m-d\TH:i', $timestamp);
	}
	return $new_data;
}

// Get a row's data by ID
function sm_get_data_by_id($array, $id, $col_name = 'id')
{
	$new_data = array();
	
	if (!empty($array)) 
	{
		foreach($array as $data)
		{
			if (isset($data[$col_name]) && $data[$col_name] == $id)
				$new_data = $data;
		}
	}
	
	return $new_data;
}

//обновить настройки
function pan_update_options( $form )
{
	global $DBLayer, $Config, $Cachinger;
	
	foreach ($form as $key => $input)
	{
		// Only update option values that have changed
		if ($Config->key_exists('o_'.$key) && $Config->get('o_'.$key) != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$DBLayer->escape($input).'\'';
			else
				$value = 'NULL';

			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value='.$value,
				'WHERE'		=> 'conf_name=\'o_'.$DBLayer->escape($key).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$Cachinger->clear('cache_config.php');
			
			// Regenerate the config cache
			$Cachinger->gen_config();
		}
	}
}
