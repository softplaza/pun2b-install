<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$proj_id = isset($_POST['proj_id']) ? intval($_POST['proj_id']) : 0;

$query = array(
	'SELECT'	=> 'unit_number',
	'FROM'		=> 'hca_vcr_projects',
	'WHERE'		=> 'id='.$proj_id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'u.unit_number',
	'FROM'		=> 'sm_property_units AS u',
	'WHERE'		=> 'u.property_id='.$id,
	'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$units_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$units_info[] = $row;
}

$json_array = $json_array['units'] = [];
if (!empty($units_info))
{
	$json_array['units'] = '<input type="text" name="unit_number" value="'.(isset($project_info['unit_number']) ? html_encode($project_info['unit_number']) : '').'" placeholder="Enter Unit#" list="unit_numbers" class="form-control">'."\n";
	$json_array['units'] .= '<datalist id="unit_numbers">'."\n";
	
	foreach($units_info as $cur_info)
	{
		$json_array['units'] .= '<option value="'.$cur_info['unit_number'].'">'."\n";
	}
	
	$json_array['units'] .= '</datalist>'."\n";
	
	echo json_encode(array(
		'unit_number' => $json_array['units'],
	));
}
else
{
	echo json_encode(array(
		'unit_number' => '<input type="text" name="unit_number" class="form-control" value="" placeholder="Enter Unit #"/>',
	));
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
