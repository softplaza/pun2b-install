<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;

$query = array(
	'SELECT'	=> 'u.id, u.unit_number',
	'FROM'		=> 'sm_property_units AS u',
	'WHERE'		=> 'u.property_id='.$pid,
	'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_units = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_units[] = $row;
}

$json_units = [];
if (!empty($sm_property_units))
{
	$json_units[] = '<select id="unit_numbers" name="unit_id" class="form-select" required>'."\n";
	foreach($sm_property_units as $cur_info)
	{
		$json_units[] = '<option value="'.$cur_info['id'].'">'.$cur_info['unit_number'].'</option>'."\n";
	}
	$json_units[] = '</select>'."\n";

	echo json_encode(array(
		'unit_number' => implode("\n", $json_units),
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
