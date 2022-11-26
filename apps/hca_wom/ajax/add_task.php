<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$wo_id = isset($_POST['wo_id']) ? intval($_POST['wo_id']) : 0; // to create
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0; // to delete

if ($wo_id > 0 && $task_id == 0)
{
	$DBLayer->insert_values('hca_wom_tasks', ['work_order_id' => $wo_id]);

	require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
	$HcaWOM = new HcaWOM;

	$query = array(
		'SELECT'	=> 't.*',
		'FROM'		=> 'hca_wom_tasks AS t',
		'WHERE'		=> 't.work_order_id='.$wo_id,
		'ORDER BY'	=> 't.id',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}

	$wo_tasks = [];

	if (!empty($tasks_info))
	{
		$i = 1;
		foreach($tasks_info as $cur_task)
		{
			$wo_tasks[] = '<h5 class="h5 mb-0">Task '.$i.'</h5>';
			$wo_tasks[] = '<div class="row mb-2 alert-secondary border">';

			$wo_tasks[] = '<div class="row mb-1">';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Type</label>';
			$wo_tasks[] = '<select name="task_type['.$cur_task['id'].']" class="form-select form-select-sm">';
			$wo_tasks[] = '<option value="0" selected>Select one</option>';
			foreach ($HcaWOM->task_type as $key => $value)
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
			$wo_tasks[] = '<select name="task_item['.$cur_task['id'].']" class="form-select form-select-sm">';
			$wo_tasks[] = '<option value="0" selected>Select one</option>';
			foreach ($HcaWOM->task_item as $key => $value)
			{
				if ($cur_task['task_item'] == $key)
					$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
				else
					$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
			}
			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Problem</label>';
			$wo_tasks[] = '<select name="task_problem['.$cur_task['id'].']" class="form-select form-select-sm">';
			$wo_tasks[] = '<option value="0" selected>Select one</option>';
			foreach ($HcaWOM->task_problem as $key => $value)
			{
				if ($cur_task['task_problem'] == $key)
					$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
				else
					$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
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

			++$i;
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
		'SELECT'	=> 't.*',
		'FROM'		=> 'hca_wom_tasks AS t',
		'WHERE'		=> 't.work_order_id='.$wo_id,
		'ORDER BY'	=> 't.id',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}

	$wo_tasks = [];

	if (!empty($tasks_info))
	{
		$i = 1;
		foreach($tasks_info as $cur_task)
		{
			$wo_tasks[] = '<h5 class="h5 mb-0">Task '.$i.'</h5>';
			$wo_tasks[] = '<div class="row mb-2 alert-secondary border">';

			$wo_tasks[] = '<div class="row mb-1">';
			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Type</label>';
			$wo_tasks[] = '<select name="task_type['.$cur_task['id'].']" class="form-select form-select-sm">';
			$wo_tasks[] = '<option value="0" selected>Select one</option>';
			foreach ($HcaWOM->task_type as $key => $value)
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
			$wo_tasks[] = '<select name="task_item['.$cur_task['id'].']" class="form-select form-select-sm">';
			$wo_tasks[] = '<option value="0" selected>Select one</option>';
			foreach ($HcaWOM->task_item as $key => $value)
			{
				if ($cur_task['task_item'] == $key)
					$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
				else
					$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
			}
			$wo_tasks[] = '</select>';
			$wo_tasks[] = '</div>';

			$wo_tasks[] = '<div class="col-md-3">';
			$wo_tasks[] = '<label class="form-label">Problem</label>';
			$wo_tasks[] = '<select name="task_problem['.$cur_task['id'].']" class="form-select form-select-sm">';
			$wo_tasks[] = '<option value="0" selected>Select one</option>';
			foreach ($HcaWOM->task_problem as $key => $value)
			{
				if ($cur_task['task_problem'] == $key)
					$wo_tasks[] = '<option value="'.$key.'" selected>'.$value.'</option>';
				else
					$wo_tasks[] = '<option value="'.$key.'">'.$value.'</option>';
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

			++$i;
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
