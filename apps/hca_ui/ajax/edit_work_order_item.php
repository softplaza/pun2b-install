<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$modal_body = [];
if ($id > 0)
{
	require SITE_ROOT.'apps/hca_ui/classes/HcaUnitInspection.php';
	$HcaUnitInspection = new HcaUnitInspection;
	$problems = $HcaUnitInspection->getProblems();

	$query = [
		'SELECT'	=> 'ci.*, i.item_name, i.location_id',
		'FROM'		=> 'hca_ui_checklist_items AS ci',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_ui_checklist AS ch',
				'ON'			=> 'ch.id=ci.checklist_id'
			],
			[
				'LEFT JOIN'		=> 'hca_ui_items AS i',
				'ON'			=> 'i.id=ci.item_id'
			],
		],
		'WHERE'		=> 'ci.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$main_info = $DBLayer->fetch_assoc($result);

	$query = [
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_ui_items AS i',
		'WHERE'		=> 'i.display_in_checklist=1',
		'ORDER BY'	=> 'i.location_id, i.display_position'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$checklist_items = [];
	while($row = $DBLayer->fetch_assoc($result))
	{
		$checklist_items[] = $row;
	}

	// Set hidden fields
	$modal_body[] = '<input type="hidden" name="checklist_item_id" value="'.$id.'">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Items</label>';
	$modal_body[] = '<select name="item_id" class="form-select form-select-sm" id="checklist_items">';
	foreach($checklist_items as $checklist_item)
	{
		$location = isset($HcaUnitInspection->locations[$checklist_item['location_id']]) ? $HcaUnitInspection->locations[$checklist_item['location_id']].'->' : '';
		$equipment_item_name = $location . $HcaUnitInspection->getEquipment($checklist_item['equipment_id']).'->'.$checklist_item['item_name'];
		if ($checklist_item['id'] == $main_info['item_id'])
			$modal_body[] = '<option value="'.$checklist_item['id'].'" selected>'.html_encode($equipment_item_name).'</option>';
		else
			$modal_body[] = '<option value="'.$checklist_item['id'].'">'.html_encode($equipment_item_name).'</option>';
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Update Work Order item',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="update_item" class="btn btn-primary" id="btn_add_item">Save changes</button>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
