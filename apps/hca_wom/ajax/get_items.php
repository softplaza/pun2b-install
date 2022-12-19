<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$type_id = isset($_POST['type_id']) ? intval($_POST['type_id']) : 0;
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
/*
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

	$first_item = true;
	$item_list = $item_actions = [];
	if (!empty($hca_wom_items))
	{
		//$task_items[] = '<option value="0">Select one</option>';
		foreach($hca_wom_items as $cur_info)
		{
			$item_list[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>';

			if ($first_item)
			{
				$item_actions = explode(',', $cur_info['item_actions']);
				$first_item = false;
			}
		}
	}
	
	foreach ($HcaWOM->task_actions as $key => $value)
	{
		if (in_array($key, $item_actions))
			$item_actions[] = '<option value="'.$key.'">'.$value.'</option>';
	}

	echo json_encode(array(
		'item_list' => implode('', $item_list),
		'item_actions' => implode('', $item_actions),
	));
}
*/
// Get item actions
if ($item_id > 0)
{
	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 'pr.*',
		'FROM'		=> 'hca_wom_problems AS pr',
		'ORDER BY'	=> 'pr.problem_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_problems = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_problems[$row['id']] = $row['problem_name'];
	}

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'WHERE'		=> 'i.id='.$item_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = $DBLayer->fetch_assoc($result);

	$item_actions = [];
	if (isset($hca_wom_items['item_actions']) && ($hca_wom_items['item_actions'] != ''))
	{
		$actions = explode(',', $hca_wom_items['item_actions']);
		foreach ($hca_wom_problems as $key => $value)
		{
			if (in_array($key, $actions))
				$item_actions[] = '<option value="'.$key.'">'.$value.'</option>';
		}
	}
	else
	{
		foreach ($hca_wom_problems as $key => $value)
			$item_actions[] = '<option value="'.$key.'">'.$value.'</option>';
	}

	echo json_encode(array(
		'item_actions' => implode('', $item_actions),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
