<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$query = array(
	'SELECT'	=> 'p.total_units',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'u.unit_number',
	'FROM'		=> 'sm_property_units AS u',
	'WHERE'		=> 'u.property_id='.$id,
	'ORDER BY'	=> 'u.unit_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$units_info = $json_array = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$units_info[] = $row;
}

if (!empty($units_info))
{
	$json_array['units'] = '<select name="unit_number"  onchange="enterManually()">'."\n";
	$json_array['units'] .= '<option value="0" selected disabled>'.$property_info['total_units'].' numbers</option>'."\n";
	$json_array['units'] .= '<option value="0">Enter Manually</option>'."\n";
	
	foreach($units_info as $cur_info)
	{
		$json_array['units'] .= '<option value="'.$cur_info['unit_number'].'"># '.$cur_info['unit_number'].'</option>'."\n";
	}
	
	$json_array['units'] .= '</select>'."\n";
	
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
