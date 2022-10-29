<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';



$property_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;

$json_units = '';
if ($property_id > 0)
{
	$query = array(
		'SELECT'	=> 'u.id, u.unit_number, u.key_number',
		'FROM'		=> 'sm_property_units AS u',
		'WHERE'		=> 'u.property_id='.$property_id,
		'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$units_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$units_info[] = $row;
	}

	$json_units .= '<select id="unit_numbers" name="unit_id" class="form-select" onchange="getUnitKey()" required>'."\n";
	foreach($units_info as $cur_info)
	{
		$json_units .= '<option value="'.$cur_info['id'].'">'.$cur_info['unit_number'].'</option>'."\n";

		if (!isset($first_key_number))
			$first_key_number = $cur_info['key_number'];
	}
	$json_units .= '</select>'."\n";

	$key_number = (isset($first_key_number) && $first_key_number != '') ? $first_key_number : 'No key';
	echo json_encode(array(
		'unit_number' => $json_units,
		'key_number' => isset($first_key_number) ? '<input type="password" value="'.html_encode($key_number).'" class="form-control" id="fld_key_number" disabled>' : '',
	));
}
else if ($unit_id > 0)
{
	$query = array(
		'SELECT'	=> 'u.id, u.unit_number, u.key_number',
		'FROM'		=> 'sm_property_units AS u',
		'WHERE'		=> 'u.id='.$unit_id,
		'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$unit_info = $DBLayer->fetch_assoc($result);

	$key_number = ($unit_info['key_number'] != '') ? $unit_info['key_number'] : 'No key';
	echo json_encode(array(
		'key_number' => '<input type="password" value="'.html_encode($key_number).'" class="form-control" id="fld_key_number" disabled>',
	));
}
else
{
	echo json_encode(array(
		'unit_number' => '<input type="text" name="unit_number" value="" placeholder="Enter Unit #"/>',
		'key_number' => 'No key'
	));
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
