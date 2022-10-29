<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$access = (!$User->is_guest()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;


if ($id > 0)
{
	$json = $items_info = [];
	$query = [
		'SELECT'	=> 'i.*, e.equipment_name, l.location_name',
		'FROM'		=> 'punch_list_management_maint_items AS i',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'punch_list_management_maint_equipments AS e',
				'ON'			=> 'e.id=i.equipment_id'
			],
			[
				'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
				'ON'			=> 'l.id=i.location_id'
			],
		],
		'ORDER BY'	=> 'e.equipment_name, i.item_name',
		'WHERE'		=> 'l.id='.$id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$items_info[] = $row;
	}
	
	if (!empty($items_info))
	{
		$equipment_id = 0;
		$json[] = '<select name="item_id" class="form-select form-select-sm">';
		$json[] = '<option value="0">Select one</option>';
		foreach($items_info as $cur_info)
		{
			//$json[] = '<option value="'.$cur_info['id'].'">'.$cur_info['item_name'].'</option>';
			
			if ($cur_info['equipment_id'] != $equipment_id) {
				if ($equipment_id) {
					$json[] = '</optgroup>';
				}
				$json[] = '<optgroup label="'.html_encode($cur_info['equipment_name']).'">';
				$equipment_id = $cur_info['equipment_id'];
			}

			//$property_exceptions = !empty($cur_info['property_exceptions']) ? explode(',', $cur_info['property_exceptions']) : [];
			//if (!in_array($form_info['property_id'], $property_exceptions))
				$json[] = "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>'."\n";
		}
		$json[] = '</select>';
	}
	else
		$json[] = '<input type="text" name="item_description" class="form-control" placeholder="Enter description">';

	echo json_encode(array(
		'form_item_id'		=> implode("\n", $json),
	));	
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
