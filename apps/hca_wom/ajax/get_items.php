<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$type_id = isset($_POST['type_id']) ? intval($_POST['type_id']) : 0;
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

if ($type_id > 0)
{
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'WHERE'		=> 'i.item_type='.$type_id,
		'ORDER BY'	=> 'i.item_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_items[] = $row;
	}

	$task_items = [];
	if (!empty($hca_wom_items))
	{
		//$task_items[] = '<option value="0">Select one</option>';
		foreach($hca_wom_items as $cur_info)
		{
			$task_items[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>';
		}
	}
	
	echo json_encode(array(
		'task_items' => implode('', $task_items),
	));
}
// Getting actions
if ($item_id > 0)
{
	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'WHERE'		=> 'i.id='.$item_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = $DBLayer->fetch_assoc($result);

	$task_items = [];
	if (!empty($hca_wom_items))
	{
		$item_actions = explode(',', $hca_wom_items['item_actions']);
		//$task_items[] = '<option value="0">Select one</option>';
		foreach ($HcaWOM->task_actions as $key => $value)
		{
			if (in_array($key, $item_actions))
				$task_items[] = '<option value="'.$key.'">'.$value.'</option>';
		}
	}
	
	echo json_encode(array(
		'task_items' => implode('', $task_items),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
