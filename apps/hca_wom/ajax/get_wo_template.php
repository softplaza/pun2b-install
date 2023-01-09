<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Add a new task
if ($id > 0)
{
	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;
	
	$query = [
		'SELECT'	=> 'tw.*',
		'FROM'		=> 'hca_wom_tpl_wo AS tw',
		'WHERE'		=> 'tw.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_tpl_wo = $DBLayer->fetch_assoc($result);

	$template_body = [];

	$template_body[] = '<div class="card-body property-fields">';

	//$template_body[] = '<div class="row">';
	$template_body[] = '<div class="col-md-2 mb-2">';
	$template_body[] = '<label class="form-label" for="fld_priority">Priority</label>';
	$template_body[] = '<select name="priority" id="fld_priority" class="form-select form-select-sm">';
	$template_body[] = '<option value="0" selected disabled>Select one</option>';
	foreach ($HcaWOM->priority as $key => $val)
	{
		if ($hca_wom_tpl_wo['priority'] == $key)
			$template_body[] = '<option value="'.$key.'" selected>'.$val.'</option>';
		else
			$template_body[] = '<option value="'.$key.'">'.$val.'</option>';
	}
	$template_body[] = '</select>';
	$template_body[] = '</div>';
	//$template_body[] = '</div>';


	//$template_body[] = '<div class="row">';
	$template_body[] = '<div class="mb-2">';
	$template_body[] = '<div class="form-check form-check-inline">';
	$template_body[] = '<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" '.($hca_wom_tpl_wo['has_animal'] == 1 ? ' checked' : '').'>';
	$template_body[] = '<label class="form-check-label" for="fld_has_animal">Pets in Unit</label>';
	$template_body[] = '</div>';
	$template_body[] = '<div class="form-check form-check-inline">';
	$template_body[] = '<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" '.($hca_wom_tpl_wo['enter_permission'] == 1 ? ' checked' : '').'>';
	$template_body[] = '<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>';
	$template_body[] = '</div>';
	$template_body[] = '</div>';
	//$template_body[] = '</div>';

	$template_body[] = '<div class="mb-2">';
	$template_body[] = '<textarea type="text" name="wo_message" class="form-control" placeholder="Enter any special instructions for entry (example: After 2 pm only please)" rows="2">'.html_encode($hca_wom_tpl_wo['wo_message']).'</textarea>';
	$template_body[] = '</div>';

	$template_body[] = '</div>';

	$query = [
		'SELECT'	=> 'tt.*, i.item_name, i.item_actions, i.item_type',
		'FROM'		=> 'hca_wom_tpl_tasks AS tt',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=tt.item_id'
			],
		],
		'WHERE'		=> 'tt.tpl_id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_tpl_tasks = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_tpl_tasks[] = $row;
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
		$hca_wom_problems[] = $row;
	}

	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'group_id=3',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $fetch_assoc;
	}

	$template_body[] = '<div class="card-body badge-secondary property-fields">';
	if (!empty($hca_wom_tpl_tasks))
	{
		$i = 1;
		foreach($hca_wom_tpl_tasks as $task_info)
		{
			$template_body[] = '<h5 class="card-title mb-0">Task #'.$i.'</h5>';
			$template_body[] = '<div class="row">';

			$template_body[] = '<div class="col-md-3 mb-3">';
			$template_body[] = '<label class="form-label" for="fld_item_id'.$i.'">Items</label>';
			$template_body[] = '<select id="fld_item_id'.$i.'" name="item_id['.$i.']" class="form-select form-select-sm">';

			$optgroup = 0;
			foreach ($hca_wom_items as $cur_info)
			{
				if ($cur_info['item_type'] != $optgroup) {
					if ($optgroup) {
						$template_body[] = '</optgroup>';
					}
					$template_body[] = '<optgroup label="'.html_encode($cur_info['type_name']).'">';
					$optgroup = $cur_info['item_type'];
				}
				if ($task_info['item_id'] == $cur_info['id'])
					$template_body[] = '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['item_name']).'</option>';
				else
					$template_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>';
			}
			$template_body[] = '</select>';
			$template_body[] = '</div>';

			$template_body[] = '<div class="col-md-2 mb-3">';
			$template_body[] = '<label class="form-label" for="fld_task_action'.$i.'">Action/Problem</label>';
			$template_body[] = '<select id="fld_task_action'.$i.'" name="task_action['.$i.']" class="form-select form-select-sm">';
			foreach ($hca_wom_problems as $cur_info)
			{
				if ($task_info['task_action'] == $cur_info['id'])
					$template_body[] = '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['problem_name']).'</option>';
				else
					$template_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['problem_name']).'</option>';
			}
			$template_body[] = '</select>';
			$template_body[] = '</div>';

			$template_body[] = '<div class="col-md-3 mb-3">';
			$template_body[] = '<label class="form-label" for="fld_tassigned_to'.$i.'">Assigned to</label>';
			$template_body[] = '<select id="fld_assigned_to'.$i.'" name="assigned_to['.$i.']" class="form-select form-select-sm">';
			$template_body[] = '<option value="0">Select one</option>';
			$optgroup = 0;
			foreach ($users_info as $cur_info)
			{
				if ($cur_info['group_id'] != $optgroup) {
					if ($optgroup) {
						$template_body[] = '</optgroup>';
					}
					$template_body[] = '<optgroup label="'.html_encode($cur_info['g_title']).'">';
					$optgroup = $cur_info['group_id'];
				}
				if ($task_info['assigned_to'] == $cur_info['id'])
					$template_body[] = '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>';
				else
					$template_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
			}
			$template_body[] = '</select>';
			$template_body[] = '</div>';

			$template_body[] = '</div>';

			$template_body[] = '<div class="mb-2">';
			$template_body[] = '<textarea type="text" name="task_message['.$i.']" class="form-control" placeholder="Enter details here" rows="2">'.html_encode($task_info['task_message']).'</textarea>';
			$template_body[] = '</div>';

			++$i;
		}
	}

	$template_body[] = '</div>';

	//$template_body[] = '<div class="card-body mb-3">';
	//$template_body[] = '<button type="submit" name="add" class="btn btn-primary">Submit</button>';
	//$template_body[] = '</div>';

	echo json_encode(array(
		'template_body' => implode('', $template_body),
	));
}
else
{
	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$template_body = [];

	$template_body[] = '<div class="card-body property-fields">';

	//$template_body[] = '<div class="row">';
	$template_body[] = '<div class="col-md-2 mb-2">';
	$template_body[] = '<label class="form-label" for="fld_priority">Priority</label>';
	$template_body[] = '<select name="priority" id="fld_priority" class="form-select form-select-sm">';
	foreach ($HcaWOM->priority as $key => $val)
	{
		$template_body[] = '<option value="'.$key.'">'.$val.'</option>';
	}
	$template_body[] = '</select>';
	$template_body[] = '</div>';
	//$template_body[] = '</div>';


	//$template_body[] = '<div class="row">';
	$template_body[] = '<div class="mb-2">';
	$template_body[] = '<div class="form-check form-check-inline">';
	$template_body[] = '<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1">';
	$template_body[] = '<label class="form-check-label" for="fld_has_animal">Pets in Unit</label>';
	$template_body[] = '</div>';
	$template_body[] = '<div class="form-check form-check-inline">';
	$template_body[] = '<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1">';
	$template_body[] = '<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>';
	$template_body[] = '</div>';
	$template_body[] = '</div>';
	//$template_body[] = '</div>';

	$template_body[] = '<div class="mb-2">';
	$template_body[] = '<textarea type="text" name="wo_message" class="form-control" placeholder="Enter any special instructions for entry (example: After 2 pm only please)" rows="2"></textarea>';
	$template_body[] = '</div>';

	$template_body[] = '</div>';

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
		$hca_wom_problems[] = $row;
	}

	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'group_id=3',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $fetch_assoc;
	}

	$template_body[] = '<div class="card-body badge-secondary property-fields">';

	$template_body[] = '<h5 class="card-title mb-0">Task #1</h5>';
	$template_body[] = '<div class="row">';

	$template_body[] = '<div class="col-md-3 mb-3">';
	$template_body[] = '<label class="form-label" for="fld_item_id1">Items</label>';
	$template_body[] = '<select id="fld_item_id1" name="item_id[1]" class="form-select form-select-sm">';

	$optgroup = 0;
	foreach ($hca_wom_items as $cur_info)
	{
		if ($cur_info['item_type'] != $optgroup) {
			if ($optgroup) {
				$template_body[] = '</optgroup>';
			}
			$template_body[] = '<optgroup label="'.html_encode($cur_info['type_name']).'">';
			$optgroup = $cur_info['item_type'];
		}
		$template_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>';
	}
	$template_body[] = '</select>';
	$template_body[] = '</div>';

	$template_body[] = '<div class="col-md-2 mb-3">';
	$template_body[] = '<label class="form-label" for="fld_task_action1">Action/Problem</label>';
	$template_body[] = '<select id="fld_task_action1" name="task_action[1]" class="form-select form-select-sm">';
	foreach ($hca_wom_problems as $cur_info)
	{
		$template_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['problem_name']).'</option>';
	}
	$template_body[] = '</select>';
	$template_body[] = '</div>';

	$template_body[] = '<div class="col-md-3 mb-3">';
	$template_body[] = '<label class="form-label" for="fld_tassigned_to1">Assigned to</label>';
	$template_body[] = '<select id="fld_assigned_to1" name="assigned_to[1]" class="form-select form-select-sm">';
	$template_body[] = '<option value="0">Select one</option>';
	$optgroup = 0;
	foreach ($users_info as $cur_info)
	{
		if ($cur_info['group_id'] != $optgroup) {
			if ($optgroup) {
				$template_body[] = '</optgroup>';
			}
			$template_body[] = '<optgroup label="'.html_encode($cur_info['g_title']).'">';
			$optgroup = $cur_info['group_id'];
		}
		$template_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
	}
	$template_body[] = '</select>';
	$template_body[] = '</div>';

	$template_body[] = '</div>';

	$template_body[] = '<div class="mb-2">';
	$template_body[] = '<textarea type="text" name="task_message[1]" class="form-control" placeholder="Enter details here" rows="2"></textarea>';
	$template_body[] = '</div>';

	$template_body[] = '</div>';

	//$template_body[] = '<div class="card-body mb-3">';
	//$template_body[] = '<button type="submit" name="add" class="btn btn-primary">Submit</button>';
	//$template_body[] = '</div>';

	echo json_encode(array(
		'template_body' => implode('', $template_body),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
