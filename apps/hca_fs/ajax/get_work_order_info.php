<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id > 0)
{
	$json_array = array();
	$query = array(
		'SELECT'	=> '*',
		'FROM'		=> 'hca_fs_requests',
		'WHERE'		=> 'id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$assignment_info = $DBLayer->fetch_assoc($result);
	
	$query = array(
		'SELECT'	=> 'id, pro_name',
		'FROM'		=> 'sm_property_db',
		'ORDER BY'	=> 'pro_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = $json_array = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$property_info[] = $row;
	}
	
	$template_types = [
		0 => 'Work Order',
		1 => 'Property Work',
		2 => 'Make Ready'
	];
	$json_array['template_type'] = isset($template_types[$assignment_info['template_type']]) ? $template_types[$assignment_info['template_type']] : 'Work Order';

	$json_array['properties'] = '';
	if (!empty($property_info))
	{
		$json_array['properties'] = '<select name="edit[property_id]" onchange="getUnits()">'."\n";
		foreach($property_info as $cur_info)
		{
			if ($cur_info['id'] == $assignment_info['property_id'])
//			if ($cur_info['pro_name'] == $assignment_info['property_name'])
				$json_array['properties'] .= '<option value="'.$cur_info['id'].'" selected="selected">'.$cur_info['pro_name'].'</option>'."\n";
			else
				$json_array['properties'] .= '<option value="'.$cur_info['id'].'">'.$cur_info['pro_name'].'</option>'."\n";
		}
		$json_array['properties'] .= '</select>'."\n";
	}
	
	$json_array['units'] = '<input type="text" name="edit[unit_number]" value=""/>';
	if ($assignment_info['property_id'] > 0)
	{
		$query = array(
			'SELECT'	=> 'unit_number',
			'FROM'		=> 'sm_property_units',
			'WHERE'		=> 'property_id='.$assignment_info['property_id'],
//			'ORDER BY'	=> 'id',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$units_info = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$units_info[] = $row;
		}
		
		$json_array['units'] = '<input type="text" name="edit[unit_number]" value="'.html_encode($assignment_info['unit_number']).'"/>';

	}
	
	$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
	
	$json_array['time_slot'] = '<select name="edit[time_slot]">'."\n";
	foreach($time_slots as $key => $val)
	{
		if ($key == $assignment_info['time_slot'])
			$json_array['time_slot'] .= '<option value="'.$key.'" selected>'.$val.'</option>'."\n";
		else
			$json_array['time_slot'] .= '<option value="'.$key.'">'.$val.'</option>'."\n";
	}
	$json_array['time_slot'] .= '</select>'."\n";
	
	$json_array['geo_code'] = '<input type="text" name="edit[geo_code]" value="'.html_encode($assignment_info['geo_code']).'"/>';
	$json_array['msg_for_maint'] = '<textarea name="edit[msg_for_maint]" rows="6">'.html_encode($assignment_info['msg_for_maint']).'</textarea>';

	echo json_encode(array(
		'template_type'			=> $json_array['template_type'],
		'edit_property'			=> $json_array['properties'],
		'edit_unit_number'		=> $json_array['units'],
		'edit_time_slot'		=> $json_array['time_slot'],
		'edit_geo_code'			=> $json_array['geo_code'],
		'edit_msg_for_maint'	=> $json_array['msg_for_maint']
	));	
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();