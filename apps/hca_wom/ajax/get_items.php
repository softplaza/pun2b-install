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

	$item_list = $item_actions = [];
	if (!empty($hca_wom_items))
	{
		foreach($hca_wom_items as $cur_info)
		{
			$item_list[] = '<option value="'.$cur_info['id'].'">'.$cur_info['item_name'].'</option>';
		}
	}

	echo json_encode(array(
		'item_list' => implode("\n", $item_list),
		'actions' => '',
	));
}

// Get item actions
else if ($item_id > 0)
{
	$query = array(
		'SELECT'	=> 'i.item_actions',
		'FROM'		=> 'hca_wom_items AS i',
		'WHERE'		=> 'i.id='.$item_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_item = $DBLayer->fetch_assoc($result);
	$item_actions = ($hca_wom_item['item_actions'] != '') ? explode(',', $hca_wom_item['item_actions']) : [];

	$query = array(
		'SELECT'	=> 'pr.*',
		'FROM'		=> 'hca_wom_problems AS pr',
		'ORDER BY'	=> 'pr.problem_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$actions = [];
	while ($row = $DBLayer->fetch_assoc($result))
	{
		if (in_array($row['id'], $item_actions))
			$actions[] = '<option value="'.$row['id'].'">'.html_encode($row['problem_name']).'</option>';
	}	

	echo json_encode(array(
		'actions' => implode("\n", $actions),
	));
}
else
{
	echo json_encode(array(
		'item_list' => '',
		'actions' => '',
	));
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
