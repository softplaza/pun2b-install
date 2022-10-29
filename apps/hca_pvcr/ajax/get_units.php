<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$query = array(
	'SELECT'	=> 'u.unit_number',
	'FROM'		=> 'sm_property_units AS u',
	'WHERE'		=> 'u.property_id='.$id,
	'ORDER BY'	=> 'u.id',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$units_info = $json_array = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$units_info[] = $row;
}

if (!empty($units_info))
{
	$json_array['units'] = '<input type="text" name="unit_number" list="units_list">'."\n";
	$json_array['units'] .= '<datalist id="units_list">'."\n";
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
		'unit_number' => '<input type="text" name="unit_number" value="" placeholder="Enter Unit #"/>',
	));
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
