<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

require SITE_ROOT.'apps/hca_wom/classes/HcaWOM.php';
$HcaWOM = new HcaWOM;

$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id='.$pid,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = $DBLayer->fetch_assoc($result);

$query = [
	'SELECT'	=> 'tp.*',
	'FROM'		=> 'hca_wom_types AS tp',
	'ORDER BY'	=> 'tp.type_name',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_types = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_types[] = $row;
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

$random_id = rand();
$task_body = $modal_footer = [];

$task_body[] = '<div class="card-body badge-secondary mb-2" id="task_'.$random_id.'">';
$task_body[] = '<h6 class="card-title mb-0">Task</h6>';

$task_body[] = '<div class="row mb-1">';

$task_body[] = '<div class="col-md-2">';
$task_body[] = '<select name="type_id[]" class="form-select form-select-sm fw-bold" id="fld_type_id_'.$random_id.'" onchange="getTaskTypeID('.$random_id.')">';
$task_body[] = '<option value="0" selected>Items</option>';
if (!empty($hca_wom_types))
{
	foreach($hca_wom_types as $cur_info)
	{
		$task_body[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['type_name']).'</option>';
	}
}
$task_body[] = '</select>';
$task_body[] = '</div>';


$task_body[] = '<div class="col-md-2">';
$task_body[] = '<select name="item_id[]" class="form-select form-select-sm fw-bold" id="fld_item_id_'.$random_id.'" onchange="getTaskItemID('.$random_id.')">';
$task_body[] = '<option value="0" selected>Items</option>';
$task_body[] = '</select>';
$task_body[] = '</div>';


$task_body[] = '<div class="col-md-2">';
$task_body[] = '<select name="task_action[]" class="form-select form-select-sm fw-bold" id="fld_task_action_'.$random_id.'">';
$task_body[] = '<option value="0" selected>Action/Problem</option>';
$task_body[] = '</select>';
$task_body[] = '</div>';


$task_body[] = '<div class="col-md-2">';
$task_body[] = '<select name="assigned_to[]" class="form-select form-select-sm fw-bold">';
$task_body[] = '<option value="0" selected>Assigned to</option>';

foreach($users_info as $cur_user)
{
	if (isset($property_info['default_maint']) && $property_info['default_maint'] == $cur_user['id'])
		$task_body[] = '<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>';
	else
		$task_body[] = '<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>';
}

$task_body[] = '</select>';
$task_body[] = '</div>';

$task_body[] = '<div class="col">';
$task_body[] = '<button type="button" class="btn btn-sm btn-danger" onclick="deleteTask('.$random_id.')">Delete</button>';
$task_body[] = '</div>';

$task_body[] = '</div>';// row end

$task_body[] = '<div class="mb-0">';
$task_body[] = '<textarea name="task_message[]" class="form-control" placeholder="Enter details here"></textarea>';
$task_body[] = '</div>';

$task_body[] = '</div>';

echo json_encode(array(
	'task_title' => 'Task',
	'task_body' => implode('', $task_body),
));

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
