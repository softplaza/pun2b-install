<?php

class VCRVendors
{
	public $vendors_info = [];
	public $vendors_schedule = [];
	public $is_email = false;
	public $services = [
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
	];
	public $Errors = [];
	
	function fetch()
	{
		global $DBLayer;
		
		$vendors_info = array();
		$query = array(
			'SELECT'	=> 'v.*, v2.group_id, v2.enabled',
			'FROM'		=> 'sm_vendors AS v',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'	=> 'hca_vcr_vendors AS v2',
					'ON'		=> 'v2.vendor_id=v.id'
				),
			),
			'ORDER BY'	=> 'v.vendor_name'
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
	
	function get_service_name($group_id)
	{
		if (isset($this->services[$group_id]))
			return $this->services[$group_id];
		else
			$this->Errors = 'No service found.';
	}
	//remove
	function get_servise_name($group_id)
	{
		if (isset($this->services[$group_id]))
			return $this->services[$group_id];
		else
			$this->Errors = 'No service found.';
	}

	function IsEmail()
	{
		return $this->is_email = true;
	}
	
	function AddSchedule($vendors_schedule)
	{
		return $this->vendors_schedule = $vendors_schedule;
	}
	
	function CheckDate($project_id, $group_id)
	{
		$is_date = false;
		
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'] && $cur_info['date_time'] > 0)
				{
					$is_date = true;
					break;
				}
			}
		}
		
		return $is_date;
	}

	function GetVendorInfo($project_id, $group_id)
	{
		$output = [];
		
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'] && $cur_info['date_time'] > 0)
				{
					$output[] = '<p class="fw-bold text-darkblue">'.format_time($cur_info['date_time'], 1).'</p>';

					if ($cur_info['in_house'] == 1)
						$output[] = ($cur_info['realname'] != '') ? '<p class="fw-bold text-darkred">'.html_encode($cur_info['realname']).'</p>' : '<p class="fw-bold text-pink">In-House</p>';
					else
						$output[] = ($cur_info['vendor_name'] != '') ? '<p class="fw-bold text-darkred">'.html_encode($cur_info['vendor_name']).'</p>' : '<p class="fw-bold text-danger">No vendor</p>';

					$output[] = '<p>'.html_encode($cur_info['remarks']).'</p>';
					
					break;
				}
			}
		}
		
		return implode("\n", $output);
	}

	function GetVendorDate($project_id, $group_id, $vendor_date = '')
	{
		if ($this->is_email)
			$vendor_date = '';
		
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'] && $cur_info['date_time'] > 0)
				{
					$vendor_date = format_time($cur_info['date_time'], 1);
					
					break;
				}
			}
		}
		
		return $vendor_date;
	}
	
	function GetVendorName($project_id, $group_id)
	{
		$vendor_name = '';
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'] && $cur_info['date_time'] > 0)
				{
					$vendor_name = ($cur_info['in_house'] == 1) ? 'In House' : html_encode($cur_info['vendor_name']);
					break;
				}
			}
		}
		
		return $vendor_name;
	}
	
	function GetVendorComment($project_id, $group_id)
	{
		$vendor_comment = '';
		if (!empty($this->vendors_schedule))
		{
			foreach($this->vendors_schedule as $cur_info)
			{
				if ($project_id == $cur_info['project_id'] && $group_id == $cur_info['vendor_group_id'] && $cur_info['date_time'] > 0)
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
