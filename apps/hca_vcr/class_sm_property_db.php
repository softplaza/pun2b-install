<?php

class smProperty
{
	public $main_info = array();
	
	function fetch()
	{
		global $DBLayer;
		
		$info = array();
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'sm_property_db',
			'ORDER BY'	=> 'pro_name'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$info[] = $row;
		}
		
		return $this->main_info = $info;
	}
	
	function get_name($id)
	{
		$name = 'N/A';
		if (!empty($this->main_info))
		{
			foreach($this->main_info as $cur_info)
			{
				if ($id == $cur_info['id']) {
					$name = $cur_info['pro_name'];
					break;
				}
			}
		}
		
		return $name;
	}
}

$smProperty = new smProperty;
$smProperty->fetch();
