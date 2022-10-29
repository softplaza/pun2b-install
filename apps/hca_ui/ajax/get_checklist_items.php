<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$lid = isset($_POST['lid']) ? intval($_POST['lid']) : 0;
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

$modal_body = [];
if ($lid > 0)
{
	require SITE_ROOT.'apps/hca_ui/classes/HcaUnitInspection.php';
	$HcaUnitInspection = new HcaUnitInspection;
	$problems = $HcaUnitInspection->getProblems();

	$query = [
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_ui_items AS i',
		'WHERE'		=> 'i.display_in_checklist=1 AND i.location_id='.$lid,
		'ORDER BY'	=> 'i.display_position'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$checklist_items = [];
	while($row = $DBLayer->fetch_assoc($result))
	{
		$checklist_items[] = $row;
	}

	$modal_body[] = '<input type="hidden" name="lid" value="'.$lid.'">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label text-danger">Items</label>';
	$modal_body[] = '<select name="item_id" class="form-select form-select-sm" id="checklist_items" required onchange="getChecklistItemId()">';
	$optgroup = 0;
	$modal_body[] = '<option value="" selected disabled>Select an inspected item</option>';
	foreach($checklist_items as $checklist_item)
	{
		$equipment_item_name = $HcaUnitInspection->getEquipment($checklist_item['equipment_id']).' - '.html_encode($checklist_item['item_name']);
		$modal_body[] = '<option value="'.$checklist_item['id'].'">'.$equipment_item_name.'</option>';
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';

	$modal_body[] = '<label class="form-label text-danger">Problems:</label>';
	$modal_body[] = '<div class="mb-3" id="checklist_problems">';
	$modal_body[] = '<input class="form-control" type="text" value="- - -" disabled>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Comment</label>';
	$modal_body[] = '<textarea name="comment" class="form-control" id="fld_comment"></textarea>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'New inspected item',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="add_item" class="btn btn-primary" id="btn_add_item" disabled>Save changes</button>',
	));
}
else if ($item_id > 0)
{
	require SITE_ROOT.'apps/hca_ui/classes/HcaUnitInspection.php';
	$HcaUnitInspection = new HcaUnitInspection;
	$problems = $HcaUnitInspection->getProblems();

	$query = [
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_ui_items AS i',
		'WHERE'		=> 'i.id='.$item_id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item = $DBLayer->fetch_assoc($result);
	$allowed_problems = explode(',', $item['problems']);
	$num_problems = count($allowed_problems);

	//$modal_body[] = '<label class="form-label">Problems:</label>';
	$modal_body[] = '<div class="mb-3">';
	foreach($problems as $key => $value)
	{
		if (in_array($key, $allowed_problems) || ($item['problems'] == ''))
		{
			$checked = '';
			//($num_problems == 1) ? 'checked' : '';

			$modal_body[] = '<div class="form-check form-check-inline">';
			$modal_body[] = '<input type="hidden" name="problem_ids['.$key.']" value="0">';
			$modal_body[] = '<input class="form-check-input" id="fld_problem_ids'.$key.'" type="checkbox" name="problem_ids['.$key.']" value="1" '.$checked.'>';
			$modal_body[] = '<label class="form-check-label" for="fld_problem_ids'.$key.'">'.$value.'</label>';
			$modal_body[] = '</div>';
		}
	}
	$modal_body[] = '</div>';

	echo json_encode(array(
		'checklist_problems' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="add_item" class="btn btn-primary" id="btn_add_item" disabled>Save changes</button>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
