<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

// Add a new task
if ($task_id > 0)
{
	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 'tt.*, i.item_name, i.item_actions, i.item_type',
		'FROM'		=> 'hca_wom_tpl_tasks AS tt',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=tt.item_id'
			),
		),
		'WHERE'		=> 'tt.id='.$task_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$task_info = $DBLayer->fetch_assoc($result);

	// Check info before doing something
	if (empty($task_info))
	{
		echo json_encode(array(
			'modal_title' => 'Task #'.$task_id,
			'modal_body' => '<div class="callout callout-danger mb-2">Task not exists or was removed.</div>',
		));
	}

	$query = array(
		'SELECT'	=> 'i.*, tp.type_name',
		'FROM'		=> 'hca_wom_items AS i',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'hca_wom_types AS tp',
				'ON'			=> 'tp.id=i.item_type'
			],
		],
		'ORDER BY'	=> 'i.item_type, i.item_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_items[] = $row;
	}

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

	$modal_body = $modal_footer = [];

	$modal_body[] = '<input type="hidden" name="task_id" value="'.$task_id.'">';
	
	$modal_body[] = '<div class="input-group mb-3">';
	$modal_body[] = '<span class="input-group-text w-25" for="fld_item_id">Item</span>';
	$modal_body[] = '<select name="item_id" class="form-select form-select-sm fw-bold" id="fld_item_id" onchange="getActions('.$task_id.')">';
	$modal_body[] = '<option value="0" selected disabled>Select one</option>';
	if (!empty($hca_wom_items))
	{
		$optgroup = 0;
		foreach($hca_wom_items as $cur_info)
		{
			if ($cur_info['item_type'] != $optgroup) {
				if ($optgroup) {
					$modal_body[] = '</optgroup>';
				}
				$modal_body[] = '<optgroup label="'.html_encode($cur_info['type_name']).'">';
				$optgroup = $cur_info['item_type'];
			}

			if ($task_info['item_id'] == $cur_info['id'])
				$modal_body[] = '<option value="'.$cur_info['id'].'" selected class="alert-success">'.html_encode($cur_info['item_name']).'</option>';
			else
				$modal_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>';
		}
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';


	$modal_body[] = '<div class="input-group mb-3">';
	$modal_body[] = '<span class="input-group-text w-25" for="fld_task_action">Action</span>';
	$modal_body[] = '<select name="task_action" class="form-select form-select-sm fw-bold" id="fld_task_action">';
	$modal_body[] = '<option value="0" selected disabled>Select one</option>';
	$item_actions = ($task_info['item_actions'] != '') ? explode(',', $task_info['item_actions']) : [];
	if (!empty($item_actions))
	{
		foreach($hca_wom_problems as $key => $value)
		{
			if (in_array($key, $item_actions))
			{
				if ($task_info['task_action'] == $key)
					$modal_body[] = '<option value="'.$key.'" selected>'.html_encode($value).'</option>';
				else
					$modal_body[] = '<option value="'.$key.'">'.html_encode($value).'</option>';
			}
		}
	}
	$modal_body[] = '</select>';

	$modal_body[] = '</div>';
	

	$modal_body[] = '<div class="mb-2">';
	$modal_body[] = '<label class="form-label" for="fld_task_message">Details</label>';
	$modal_body[] = '<textarea name="task_message" class="form-control" placeholder="Enter details here" id="fld_task_message">'.html_encode($task_info['task_message']).'</textarea>';
	$modal_body[] = '</div>';


	$modal_footer[] = '<button type="submit" name="update_task" class="btn btn-sm btn-primary">Save changes</button>';
	$modal_footer[] = '<button type="submit" name="delete_task" class="btn btn-sm btn-danger">Delete task</button>';

	echo json_encode(array(
		'modal_title' => 'Task #'.$task_id,
		'modal_body' => implode('', $modal_body),
		'modal_footer' => implode('', $modal_footer),
	));
}
else
{
	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 'i.*, tp.type_name',
		'FROM'		=> 'hca_wom_items AS i',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'hca_wom_types AS tp',
				'ON'			=> 'tp.id=i.item_type'
			],
		],
		'ORDER BY'	=> 'i.item_type, i.item_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_items[] = $row;
	}

	$modal_body = $modal_footer = [];
	
	$modal_body[] = '<div class="input-group mb-3">';
	$modal_body[] = '<span class="input-group-text w-25" for="fld_item_id">Item</span>';
	$modal_body[] = '<select name="item_id" class="form-select form-select-sm fw-bold" id="fld_item_id" onchange="getActions()" required>';
	$modal_body[] = '<option value="" selected disabled>Select one</option>';

	$optgroup = 0;
	foreach ($hca_wom_items as $cur_info)
	{
		if ($cur_info['item_type'] != $optgroup) {
			if ($optgroup) {
				$modal_body[] = '</optgroup>';
			}
			$modal_body[] = '<optgroup label="'.html_encode($cur_info['type_name']).'">';
			$optgroup = $cur_info['item_type'];
		}
		
		$modal_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>'."\n";
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';


	$modal_body[] = '<div class="input-group mb-3">';
	$modal_body[] = '<span class="input-group-text w-25" for="fld_task_action">Action</span>';
	$modal_body[] = '<select name="task_action" class="form-select form-select-sm fw-bold" id="fld_task_action">';
	$modal_body[] = '<option value="0" selected disabled>Select one</option>';
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';
	

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label" for="fld_task_message">Details</label>';
	$modal_body[] = '<textarea name="task_message" class="form-control" placeholder="Enter details here" id="fld_task_message"></textarea>';
	$modal_body[] = '</div>';

	$modal_footer[] = '<button type="submit" name="add_task" class="btn btn-sm btn-primary">Save changes</button>';

	echo json_encode(array(
		'modal_title' => 'A new task',
		'modal_body' => implode('', $modal_body),
		'modal_footer' => implode('', $modal_footer),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
