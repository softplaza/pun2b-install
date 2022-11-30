<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$wo_id = isset($_POST['wo_id']) ? intval($_POST['wo_id']) : 0; // to create
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0; // to delete

// Add a new task
if ($wo_id > 0 && $task_id == 0)
{
	$DBLayer->insert_values('hca_wom_tasks', ['work_order_id' => $wo_id]);

	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 't.*, i.item_actions',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.task_item'
			)
		),
		'WHERE'		=> 't.work_order_id='.$wo_id,
		'ORDER BY'	=> 't.id',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'ORDER BY'	=> 'i.item_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_items[] = $row;
	}

	$wo_tasks = [];

	if (!empty($tasks_info))
	{
		foreach($tasks_info as $cur_task)
		{
			$wo_tasks[] = '<h6 class="h6 mb-0">Task '.$cur_task['id'].'</h6>';
			$wo_tasks[] = '<div class="row mb-2 alert-secondary border">';
			$wo_tasks[] = '<input type="hidden" name="task['.$cur_task['id'].']" value="'.$cur_task['id'].'">';
			$wo_tasks[] = '<div class="row mb-1">';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Type</label>';
			$wo_tasks[] = '<select name="task_type['.$cur_task['id'].']" class="form-select form-select-sm" id="fld_task_type_'.$cur_task['id'].'" onchange="getItems('.$cur_task['id'].')">';
			$wo_tasks[] = '<option value="0" selected disabled>Select one</option>';
			foreach ($HcaWOM->item_types as $key => $value)
			{
				if ($cur_task['task_type'] == $key)
					$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
				else
					$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
			}
			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Item</label>';
			$wo_tasks[] = '<select name="task_item['.$cur_task['id'].']" class="form-select form-select-sm" id="fld_task_item_'.$cur_task['id'].'" onchange="getActions('.$cur_task['id'].')">';

			$wo_tasks[] = '<option value="0" selected disabled>Select one</option>';
			if (!empty($hca_wom_items) && $cur_task['task_type'] > 0)
			{
				foreach($hca_wom_items as $cur_item)
				{
					if ($cur_item['item_type'] == $cur_task['task_type'])
					{
						if ($cur_task['task_item'] == $cur_item['id'])
							$wo_tasks[] = '<option value="'.$cur_item['id'].'" selected>'.html_encode($cur_item['item_name']).'</option>';
						else
							$wo_tasks[] = '<option value="'.$cur_item['id'].'">'.html_encode($cur_item['item_name']).'</option>';
					}
				}
			}

			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Problem</label>';
			$wo_tasks[] = '<select name="task_action['.$cur_task['id'].']" class="form-select form-select-sm" id="fld_task_action_'.$cur_task['id'].'">';

			$wo_tasks[] = '<option value="0" selected disabled>Select one</option>';
			$item_actions = explode(',', $cur_task['item_actions']);
			if (!empty($item_actions))
			{
				foreach($HcaWOM->task_actions as $key => $value)
				{
					if (in_array($key, $item_actions))
					{
						if ($cur_task['task_action'] == $key)
							$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
						else
							$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
					}
				}
			}

			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '</div>';
			

			$wo_tasks[] = '<div class="mb-3">';
			$wo_tasks[] = '<label class="form-label" for="fld_task_message'.$cur_task['id'].'">Details</label>';
			$wo_tasks[] = '<textarea name="task_message['.$cur_task['id'].']" class="form-control" placeholder="Enter details here" id="fld_task_message'.$cur_task['id'].'">'.html_encode($cur_task['task_message']).'</textarea>';
			$wo_tasks[] = '<span class="float-end"><button type="button" class="badge bg-danger" onclick="deleteTask('.$wo_id.','.$cur_task['id'].')">Delete task</button></span>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '</div>';
		}
	}
	else
		$wo_tasks[] = '<div class="callout callout-warning mb-3">No tasks available.</div>';

	echo json_encode(array(
		'wo_tasks' => implode('', $wo_tasks),
	));
}

else if ($wo_id > 0 && $task_id > 0)
{
	$DBLayer->delete('hca_wom_tasks', $task_id);

	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 't.*, i.item_actions',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.task_item'
			)
		),
		'WHERE'		=> 't.work_order_id='.$wo_id,
		'ORDER BY'	=> 't.id',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'ORDER BY'	=> 'i.item_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_items[] = $row;
	}

	$wo_tasks = [];

	if (!empty($tasks_info))
	{
		foreach($tasks_info as $cur_task)
		{
			$wo_tasks[] = '<h6 class="h6 mb-0">Task '.$cur_task['id'].'</h6>';
			$wo_tasks[] = '<div class="row mb-2 alert-secondary border">';
			$wo_tasks[] = '<input type="hidden" name="task['.$cur_task['id'].']" value="'.$cur_task['id'].'">';
			$wo_tasks[] = '<div class="row mb-1">';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Type</label>';
			$wo_tasks[] = '<select name="task_type['.$cur_task['id'].']" class="form-select form-select-sm" id="fld_task_type_'.$cur_task['id'].'" onchange="getItems('.$cur_task['id'].')">';
			$wo_tasks[] = '<option value="0" selected disabled>Select one</option>';
			foreach ($HcaWOM->item_types as $key => $value)
			{
				if ($cur_task['task_type'] == $key)
					$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
				else
					$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
			}
			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Item</label>';
			$wo_tasks[] = '<select name="task_item['.$cur_task['id'].']" class="form-select form-select-sm" id="fld_task_item_'.$cur_task['id'].'" onchange="getActions('.$cur_task['id'].')">';

			$wo_tasks[] = '<option value="0" selected disabled>Select one</option>';
			if (!empty($hca_wom_items) && $cur_task['task_type'] > 0)
			{
				foreach($hca_wom_items as $cur_item)
				{
					if ($cur_item['item_type'] == $cur_task['task_type'])
					{
						if ($cur_task['task_item'] == $cur_item['id'])
							$wo_tasks[] = '<option value="'.$cur_item['id'].'" selected>'.html_encode($cur_item['item_name']).'</option>';
						else
							$wo_tasks[] = '<option value="'.$cur_item['id'].'">'.html_encode($cur_item['item_name']).'</option>';
					}
				}
			}

			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Problem</label>';
			$wo_tasks[] = '<select name="task_action['.$cur_task['id'].']" class="form-select form-select-sm" id="fld_task_action_'.$cur_task['id'].'">';

			$wo_tasks[] = '<option value="0" selected disabled>Select one</option>';
			$item_actions = explode(',', $cur_task['item_actions']);
			if (!empty($item_actions))
			{
				foreach($HcaWOM->task_actions as $key => $value)
				{
					if (in_array($key, $item_actions))
					{
						if ($cur_task['task_action'] == $key)
							$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
						else
							$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
					}
				}
			}

			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '</div>';
			
			$wo_tasks[] = '<div class="mb-3">';
			$wo_tasks[] = '<label class="form-label" for="fld_task_message'.$cur_task['id'].'">Details</label>';
			$wo_tasks[] = '<textarea name="task_message['.$cur_task['id'].']" class="form-control" placeholder="Enter details here" id="fld_task_message'.$cur_task['id'].'">'.html_encode($cur_task['task_message']).'</textarea>';
			$wo_tasks[] = '<span class="float-end"><button type="button" class="badge bg-danger" onclick="deleteTask('.$wo_id.','.$cur_task['id'].')">Delete task</button></span>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '</div>';
		}
	}
	else
		$wo_tasks[] = '<div class="callout callout-warning mb-3">No tasks available.</div>';

	echo json_encode(array(
		'wo_tasks' => implode('', $wo_tasks),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
