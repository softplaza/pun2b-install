<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

$json = [];
if ($property_id > 0)
{
	$query = array(
		'SELECT'	=> 'u.id, u.unit_number',
		'FROM'		=> 'sm_property_units AS u',
		'WHERE'		=> 'u.property_id='.$property_id,
		'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$units_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$units_info[] = $row;
	}

	$json[] = '<div class="col-md-4 mb-3">';
	$json[] = '<label class="form-label" for="fld_unit_number">Units</label>';
	//$json[] = '<div class="d-flex">';
	$json[] = '<select id="fld_unit_number" name="unit_id" class="form-select" required>'."\n";
	$json[] = '<option value="0">Select one</option>'."\n";
	foreach($units_info as $cur_info){
		$json[] = '<option value="'.$cur_info['id'].'">'.$cur_info['unit_number'].'</option>'."\n";
	}
	$json[] = '</select>'."\n";
	//$json[] = '<button type="button" class="btn btn-sm btn-primary ms-1" onclick="addUnit()">Add</button>';
	//$json[] = '</div>';
	$json[] = '</div>';

	echo json_encode(array(
		'unitlist_dropdown' => implode("\n", $json),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
