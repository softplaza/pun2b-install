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
		'WHERE'		=> 'i.display_in_checklist=1 AND i.location_id='.$main_info['location_id'],
		'ORDER BY'	=> 'i.display_position'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$checklist_items = [];
	while($row = $DBLayer->fetch_assoc($result))
	{
		$checklist_items[] = $row;
	}

	$query = [
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_ui_items AS i',
		'WHERE'		=> 'i.id='.$main_info['item_id'],
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item = $DBLayer->fetch_assoc($result);
	$allowed_problems = explode(',', $item['problems']);
	$num_problems = count($allowed_problems);

	// Set hidden fields
	$modal_body[] = '<input type="hidden" name="checklist_item_id" value="'.$id.'">';
	$modal_body[] = '<input type="hidden" name="lid" value="'.$main_info['location_id'].'">';
	
	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Items</label>';
	$modal_body[] = '<select name="item_id" class="form-select form-select-sm" id="checklist_items" required onchange="getChecklistItemId()">';
	$optgroup = 0;
	$modal_body[] = '<option value="" selected disabled>Select an inspected item</option>';
	foreach($checklist_items as $checklist_item)
	{
		$equipment_item_name = $HcaUnitInspection->getEquipment($checklist_item['equipment_id']).' - '.html_encode($checklist_item['item_name']);
		if ($checklist_item['id'] == $main_info['item_id'])
			$modal_body[] = '<option value="'.$checklist_item['id'].'" selected>'.$equipment_item_name.'</option>';
		else
			$modal_body[] = '<option value="'.$checklist_item['id'].'">'.$equipment_item_name.'</option>';
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';

	$modal_body[] = '<label class="form-label">Problems:</label>';
	$modal_body[] = '<div class="mb-3" id="checklist_problems">';
	$cur_problems = explode(',', $main_info['problem_ids']);
	foreach($problems as $key => $value)
	{
		if (in_array($key, $allowed_problems) || ($item['problems'] == ''))
		{
			$checked = in_array($key, $cur_problems) ? 'checked' : '';
			//($num_problems == 1) ? 'checked' : '';

			$modal_body[] = '<div class="form-check form-check-inline">';
			$modal_body[] = '<input type="hidden" name="problem_ids['.$key.']" value="0">';
			$modal_body[] = '<input class="form-check-input" id="fld_problem_ids'.$key.'" type="checkbox" name="problem_ids['.$key.']" value="1" '.$checked.'>';
			$modal_body[] = '<label class="form-check-label" for="fld_problem_ids'.$key.'">'.$value.'</label>';
			$modal_body[] = '</div>';
		}
	}
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Comment</label>';
	$modal_body[] = '<textarea name="comment" class="form-control">'.html_encode($main_info['comment']).'</textarea>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Update inspected item',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="update_item" class="btn btn-primary" id="btn_add_item">Save changes</button>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
