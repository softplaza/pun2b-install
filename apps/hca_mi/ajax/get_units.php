<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$query = array(
	'SELECT'	=> 'u.id, u.unit_number',
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
	$json = [];
	$json[] = '<select name="unit_id" id="fld_unit_id" class="form-select form-select-sm">';
	$json[] = '<option value="0" selected>Common area</option>';
	foreach($units_info as $cur_info)
	{
		$json[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['unit_number']).'</option>';
	}
	$json[] = '</select>';
	
	echo json_encode(array(
		'unit_number' => implode('', $json),
	));
}
else
{
	echo json_encode(array(
		'unit_number' => '<input type="text" class="form-control" disabled>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
