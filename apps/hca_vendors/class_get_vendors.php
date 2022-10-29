<?php

class VCRVendors
{
	public $vendors_info = array();
	public $vendors_schedule = array();
	
	function fetch()
	{
		global $DBLayer;
		
		$vendors_info = array();
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'sm_vendors',
			'ORDER BY'	=> 'vendor_name'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$vendors_info[] = $row;
		}
		
		return $this->vendors_info = $vendors_info;
	}
	
	function get_vendor_name($vendor_id)
	{
		$vendor_name = 'n/a';
		if (!empty($this->vendors_info))
		{
			foreach($this->vendors_info as $vendor_info)
			{
				if ($vendor_id == $vendor_info['id']) {
					$vendor_name = $vendor_info['vendor_name'];
					break;
				}
			}
		}
		
		return $vendor_name;
	}
	
	function get_orders_limit($vendor_id)
	{
		$limit = 0;
		if (!empty($this->vendors_info))
		{
			foreach($this->vendors_info as $vendor_info)
			{
				if ($vendor_id == $vendor_info['id']) {
					$limit = $vendor_info['orders_limit'];
					break;
				}
			}
		}
		
		return $limit;
	}
	
	function get_servise_name($group_id)
	{
		$servises = array(
			0 => '',
			1 => 'Urine Scan Service',
			2 => 'Painter Service',
			3 => 'Vinyl Service',
			4 => 'Carpet Service',
			5 => 'Pest Control Service',
			6 => 'Cleaning Service',
			7 => 'Refinish Service',
			8 => 'Maintenance',
			9 => 'Carpet Clean',
		);
		
		return $servises[$group_id];
	}
	
	function AddSchedule($vendors_schedule)
	{
		return $this->vendors_schedule = $vendors_schedule;
	}
	
	function GetVendorName($project_id, $group_id)
	{
		$vendor_name = '';
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'])
				{
					$vendor_name = ($cur_info['in_house'] == 1) ? 'In House' : html_encode($cur_info['vendor_name']);
					break;
				}
			}
		}
		
		return $vendor_name;
	}
	
	function GetVendorDate($project_id, $group_id, $vendor_date = '<p class="default" style="font-weight:bold;color:green">Good</p>')
	{
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'] && $cur_info['date_time'] > 0)
				{
					$vendor_date = '<p class="date">'.format_time($cur_info['date_time'], 1).'</p>';
					break;
				}
			}
		}
		
		return $vendor_date;
	}
	
	function GetVendorComment($project_id, $group_id)
	{
		$vendor_comment = '';
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'])
				{
					$vendor_comment = html_encode($cur_info['remarks']);
					break;
				}
			}
		}
		
		return $vendor_comment;
	}
}

$VCRVendors = new VCRVendors;
$VCRVendors->fetch();
