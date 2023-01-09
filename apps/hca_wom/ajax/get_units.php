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
	'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_units = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_units[] = $row;
}

if (!empty($sm_property_units))
{
	$unit_list = [];
	$unit_list[] = '<select name="unit_id" id="fld_unit_number" class="form-select form-select-sm">';
	$unit_list[] = '<option value="0">Common area</option>';
	foreach($sm_property_units as $cur_info)
	{
		$unit_list[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['unit_number']).'</option>';
	}
	$unit_list[] = '</select>';
	
	echo json_encode(array(
		'unit_list' => implode('', $unit_list),
	));
}
else
{
	echo json_encode(array(
		'unit_list' => '<input type="text" name="unit_id" value="" class="form-control" disabled>',
	));
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
