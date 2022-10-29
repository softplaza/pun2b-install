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
		'SELECT'	=> 'b.id, b.bldg_number',
		'FROM'		=> 'sm_property_buildings AS b',
		'WHERE'		=> 'b.property_id='.$property_id,
		'ORDER BY'	=> 'LENGTH(b.bldg_number), b.bldg_number',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$sm_property_buildings = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$sm_property_buildings[] = $row;
	}

	if (!empty($sm_property_buildings))
	{
		$json[] = '<div class="col-md-4 mb-3">';
		$json[] = '<label class="form-label" for="fld_building_number">Buildings</label>';
		$json[] = '<div class="d-flex">';
		$json[] = '<select id="fld_building_number" name="building_id" class="form-select" required>';
		foreach($sm_property_buildings as $cur_info){
			$json[] = '<option value="'.$cur_info['id'].'">'.$cur_info['bldg_number'].'</option>';
		}
		$json[] = '</select>';
		$json[] = '<button type="button" class="btn btn-sm btn-primary ms-1" onclick="addBLDG()">Add</button>';
		$json[] = '</div>';
		$json[] = '</div>';
	}
	else
	{
		$json[] = '<div class="col-md-4 mb-3">';
		$json[] = '<label class="form-label" for="fld_building_number">Buildings</label>';
		$json[] = '<input type="text"  class="form-control" id="fld_building_number" value="No Buildings found" disabled>';
		$json[] = '</div>';
	}

	echo json_encode(array(
		'buildinglist_dropdown' => implode("\n", $json),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
