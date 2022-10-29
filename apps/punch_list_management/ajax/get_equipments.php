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
		'SELECT'	=> 'e.*',
		'FROM'		=> 'punch_list_management_maint_equipments AS e',
		'ORDER BY'	=> 'e.eq_position',
		'WHERE'		=> 'e.location_id='.$id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$items_info[] = $row;
	}
	
	if (!empty($items_info))
	{
		$json[] = '<select name="equipment_id" class="form-select form-select-sm">';
		$json[] = '<option value="0">Select equipment</option>';
		foreach($items_info as $cur_info)
		{
			$json[] = "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['equipment_name']).'</option>'."\n";
		}
		$json[] = '</select>';
	}
	else
	{
		$json[] = '<select name="equipment_id" class="form-select form-select-sm">';
		$json[] = '<option value="0">Without equipment</option>';
		$json[] = '</select>';
	}

	echo json_encode(array(
		'form_equipment_id'		=> implode("\n", $json),
	));	
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
