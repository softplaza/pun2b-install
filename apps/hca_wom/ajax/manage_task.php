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
		'SELECT'	=> 't.*, i.item_name, i.item_actions, i.item_type, u.realname AS assigned_name',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.item_id'
			),
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=t.assigned_to'
			)
		),
		'WHERE'		=> 't.id='.$task_id,
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
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'ORDER BY'	=> 'i.item_type, i.item_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_items[] = $row;
	}

	$modal_body = $modal_footer = [];

	$modal_body[] = '<input type="hidden" name="task_id" value="'.$task_info['id'].'">';
	
	$modal_body[] = '<div class="input-group mb-3">';
	$modal_body[] = '<span class="input-group-text w-25" for="fld_item_id">Item</span>';
	if ($task_info['task_status'] > 2)
	{
		$modal_body[] = '<input type="text" value="'.html_encode($task_info['item_name']).'" class="form-control form-control-sm fw-bold" disabled>';
	}
	else
	{
		$modal_body[] = '<select name="item_id" class="form-select form-select-sm fw-bold" id="fld_item_id" onchange="getActions('.$task_info['id'].')">';
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
					$modal_body[] = '<optgroup label="'.html_encode($HcaWOM->item_types[$cur_info['item_type']]).'">';
					$optgroup = $cur_info['item_type'];
				}

				if ($task_info['item_id'] == $cur_info['id'])
					$modal_body[] = '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['item_name']).'</option>';
				else
					$modal_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>';
			}
		}
		$modal_body[] = '</select>';
	}
	$modal_body[] = '</div>';


	$modal_body[] = '<div class="input-group mb-3">';
	$modal_body[] = '<span class="input-group-text w-25" for="fld_task_action">Action</span>';
	if ($task_info['task_status'] > 2)
	{
		$modal_body[] = '<input type="text" value="'.$HcaWOM->task_actions[$task_info['task_action']].'" class="form-control form-control-sm fw-bold" disabled>';
	}
	else
	{
		$modal_body[] = '<select name="task_action" class="form-select form-select-sm fw-bold" id="fld_task_action">';
		$modal_body[] = '<option value="0" selected disabled>Select one</option>';
		$item_actions = ($task_info['item_actions'] != '') ? explode(',', $task_info['item_actions']) : [];
		if (!empty($item_actions))
		{
			foreach($HcaWOM->task_actions as $key => $value)
			{
				if (in_array($key, $item_actions))
				{
					if ($task_info['task_action'] == $key)
						$modal_body[] = '<option value="'.$key.'" selected>'.$value.'</option>';
					else
						$modal_body[] = '<option value="'.$key.'">'.$value.'</option>';
				}
			}
		}
		$modal_body[] = '</select>';
	}
	$modal_body[] = '</div>';
	

	$modal_body[] = '<div class="mb-2">';
	$modal_body[] = '<label class="form-label" for="fld_task_message">Details</label>';
	$modal_body[] = '<textarea name="task_message" class="form-control" placeholder="Enter details here" id="fld_task_message" rows="5">'.html_encode($task_info['task_message']).'</textarea>';
	$modal_body[] = '</div>';


	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'group_id > 2',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $fetch_assoc;
	}

	$modal_body[] = '<div class="input-group mb-3">';
	if ($task_info['task_status'] > 2)
	{
		$modal_body[] = '<span class="input-group-text w-25" for="fld_assigned_to">Completed by</span>';
		$modal_body[] = '<input type="text" value="'.html_encode($task_info['assigned_name']).'" class="form-control form-control-sm fw-bold" disabled>';
	}
	else
	{
		$modal_body[] = '<span class="input-group-text" for="fld_assigned_to">Assign to</span>';
		$modal_body[] = '<select name="assigned_to" class="form-select form-select-sm fw-bold" id="fld_assigned_to">';
		$modal_body[] = '<option value="0" selected disabled>Select one</option>';

		$optgroup = 0;
		foreach($users_info as $cur_info)
		{
			if ($cur_info['group_id'] != $optgroup) {
				if ($optgroup) {
					$modal_body[] = '</optgroup>';
				}
				$modal_body[] = '<optgroup label="'.html_encode($cur_info['g_title']).'">';
				$optgroup = $cur_info['group_id'];
			}

			if ($task_info['assigned_to'] == $cur_info['id'])
				$modal_body[] = '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>';
			else
				$modal_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
		}
		$modal_body[] = '</select>';
	}
	$modal_body[] = '</div>';

	// Notify technician if status is 1 OPEN, 2 ACCEPTED, 3 READY FOR REVIEW
	if ($task_info['task_status'] > 0 && $task_info['task_status'] < 4)
	{
		$modal_body[] = '<div class="mb-3">';
		$modal_body[] = '<div class="form-check form-check-inline">';
		$modal_body[] = '<input type="hidden" name="notify_technician" value="0">';
		$modal_body[] = '<input class="form-check-input" type="checkbox" name="notify_technician" id="fld_notify_technician" value="1">';
		$modal_body[] = '<label class="form-check-label" for="fld_notify_technician">Notify technician by Email</label>';
		$modal_body[] = '</div>';
		$modal_body[] = '</div>';
	}

	if ($task_info['task_status'] == 4)
	{
		$modal_footer[] = '<button type="submit" name="reopen_task" class="btn btn-sm btn-success">Reopen task</button>';
	}
	else if ($task_info['task_status'] == 3)
	{
		$modal_footer[] = '<button type="submit" name="update_task" class="btn btn-sm btn-primary">Save changes</button>';
		$modal_footer[] = '<button type="submit" name="close_task" class="btn btn-sm btn-success">Approve and close</button>';
	}
	else if ($task_info['task_status'] == 0)
	{
		$modal_footer[] = '<button type="submit" name="reopen_task" class="btn btn-sm btn-success">Reopen task</button>';
	}
	else
	{
		$modal_footer[] = '<button type="submit" name="update_task" class="btn btn-sm btn-primary">Save changes</button>';
		$modal_footer[] = '<button type="submit" name="delete_task" class="btn btn-sm btn-danger">Delete task</button>';
	}

	echo json_encode(array(
		'modal_title' => 'Task #'.$task_info['id'],
		'modal_body' => implode('', $modal_body),
		'modal_footer' => implode('', $modal_footer),
	));
}
else
{
	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
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
			$modal_body[] = '<optgroup label="'.html_encode($HcaWOM->item_types[$cur_info['item_type']]).'">';
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
	$modal_body[] = '<textarea name="task_message" class="form-control" placeholder="Enter details here" id="fld_task_message" rows="5"></textarea>';
	$modal_body[] = '</div>';

	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'group_id > 2',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $fetch_assoc;
	}

	$modal_body[] = '<div class="input-group mb-3">';
	$modal_body[] = '<span class="input-group-text w-25" for="fld_task_action">Assign to</span>';
	$modal_body[] = '<select name="assigned_to" class="form-select form-select-sm fw-bold" id="fld_assigned_to" required>';
	$modal_body[] = '<option value="" selected disabled>Select one</option>';

	$optgroup = 0;
	foreach($users_info as $cur_info)
	{
		if ($cur_info['group_id'] != $optgroup) {
			if ($optgroup) {
				$modal_body[] = '</optgroup>';
			}
			$modal_body[] = '<optgroup label="'.html_encode($cur_info['g_title']).'">';
			$optgroup = $cur_info['group_id'];
		}
		$modal_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<div class="form-check form-check-inline">';
	$modal_body[] = '<input type="hidden" name="notify_technician" value="0">';
	$modal_body[] = '<input class="form-check-input" type="checkbox" name="notify_technician" id="fld_notify_technician" value="1">';
	$modal_body[] = '<label class="form-check-label" for="fld_notify_technician">Notify technician by Email</label>';
	$modal_body[] = '</div>';
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
