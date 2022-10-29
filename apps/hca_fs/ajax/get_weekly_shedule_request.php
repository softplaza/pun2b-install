<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$type = isset($_POST['type']) ? intval($_POST['type']) : 0;
$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
$time = isset($_POST['time']) ? intval($_POST['time']) : 0;
$day = isset($_POST['day']) ? intval($_POST['day']) : 0;
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// NEW TASK
if ($uid > 0 && $type == 0)
{
	$template_types = [0 => 'Work Order', 1 => 'Property Work', 2 => 'Make Ready'];
	$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
	$query = array(
		'SELECT'	=> 'id, pro_name',
		'FROM'		=> 'sm_property_db',
		'ORDER BY'	=> 'pro_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$property_info[] = $row;
	}

	$json[] = '<input type="hidden" name="employee_id" value="'.$uid.'">';
	$json[] = '<input type="hidden" name="start_date" value="'.$time.'">';

	$json[] = '<label class="form-label">Template type</label>';

	$json[] = '<div class="mb-1">';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_0" value="0" checked onclick="templateType(0)">';
	$json[] = '<label class="form-check-label badge bg-primary" for="fld_template_type_0">Work order</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_1" value="1" onclick="templateType(0)">';
	$json[] = '<label class="form-check-label badge bg-primary" for="fld_template_type_1">Property work</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_2" value="2" onclick="templateType(0)">';
	$json[] = '<label class="form-check-label badge bg-primary" for="fld_template_type_2">Make ready</label>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_4" value="4" onclick="templateType(4)">';
	$json[] = '<label class="form-check-label badge bg-success" for="fld_template_type_4">Day off</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_5" value="5" onclick="templateType(5)">';
	$json[] = '<label class="form-check-label badge bg-danger" for="fld_template_type_5">Sick day</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_6" value="6" onclick="templateType(6)">';
	$json[] = '<label class="form-check-label badge bg-info" for="fld_template_type_6">Vacation</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_7" value="7" onclick="templateType(7)">';
	$json[] = '<label class="form-check-label badge bg-warning" for="fld_template_type_7">Stand by</label>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div id="template_body">';
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label" for="fld_property_id">Property name</label>';
	$json[] = '<select name="property_id" class="form-select" id="fld_property_id" onchange="getUnits()">';
	$json[] = '<option value="0">Select property</option>'; 
	foreach($property_info as $cur_info)
	{
		$json[] = '<option value="'.$cur_info['id'].'">'.$cur_info['pro_name'].'</option>'; 
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Unit number</label>';
	$json[] = '<div id="property_units">';
	$json[] = '<input type="text" name="unit_number" value="" class="form-control" placeholder="Enter unit #">';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="row mb-3">';
	$json[] = '<div class="col">';
	$json[] = '<label class="form-label">Time</label>';
	$json[] = '<select name="time_slot" class="form-select">';
	foreach($time_slots as $key => $val)
	{
		$json[] = '<option value="'.$key.'">'.$val.'</option>'; 
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="col">';
	$json[] = '<label class="form-label">GL code</label>';
	$json[] = '<input type="text" name="geo_code" value="" class="form-control">';
	$json[] = '</div>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="msg_for_maint"" rows="4" placeholder="Leave your comment" class="form-control"></textarea>';
	$json[] = '</div>';

	$btns = [
		'<button type="submit" name="create_task" class="btn btn-primary">Create</button>',
	];

	echo json_encode([
			'modal_title'	=> 'New task',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> implode("\n", $btns)
	]);
}
// EDIT TASK
else if ($id > 0 && $type == 1)
{
	$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
	$query = array(
		'SELECT'	=> 'id, pro_name',
		'FROM'		=> 'sm_property_db',
		'ORDER BY'	=> 'pro_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$property_info[] = $row;
	}

	$query = array(
		'SELECT'	=> 'r.*, p.pro_name, u.realname',
		'FROM'		=> 'hca_fs_requests AS r',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'r.employee_id=u.id'
			),
			array(
				// LEFT for day off
				'LEFT JOIN'		=> 'sm_property_db AS p',
				'ON'			=> 'r.property_id=p.id'
			),
		),
		'WHERE'		=> 'r.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$request_info = $DBLayer->fetch_assoc($result);

	$json[] = '<input type="hidden" name="request_id" value="'.$id.'">';
	$json[] = '<input type="hidden" name="employee_id" value="'.$request_info['employee_id'].'">';

	$json[] = '<label class="form-label">Template type</label>';

	$json[] = '<div class="mb-1">';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_0" value="0" onclick="templateType(0)" '.($request_info['template_type'] == 0 && $request_info['time_slot'] < 4 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label badge bg-primary" for="fld_template_type_0">Work order</label>';
	$json[] = '</div>';

	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_1" value="1" onclick="templateType(0)" '.($request_info['template_type'] == 1 && $request_info['time_slot'] < 4 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label badge bg-primary" for="fld_template_type_1">Property work</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_2" value="2" onclick="templateType(0)" '.($request_info['template_type'] == 2 && $request_info['time_slot'] < 4 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label badge bg-primary" for="fld_template_type_2">Make ready</label>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_4" value="4" onclick="templateType(4)" '.($request_info['template_type'] == 4 || $request_info['time_slot'] == 4 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label badge bg-success" for="fld_template_type_4">Day off</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_5" value="5" onclick="templateType(5)" '.($request_info['template_type'] == 5 || $request_info['time_slot'] == 5 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label badge bg-danger" for="fld_template_type_5">Sick day</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_6" value="6" onclick="templateType(6)" '.($request_info['template_type'] == 6 || $request_info['time_slot'] == 6 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label badge bg-info" for="fld_template_type_6">Vacation</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="template_type" id="fld_template_type_7" value="7" onclick="templateType(7)" '.($request_info['template_type'] == 7 || $request_info['time_slot'] == 7 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label badge bg-warning" for="fld_template_type_7">Stand by</label>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div id="template_body" '.($request_info['template_type'] > 2 || $request_info['time_slot'] > 3 ? 'style="display:none"' : '').'>';
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label" for="fld_property_id">Property name</label>';
	$json[] = '<select name="property_id" class="form-select" id="fld_property_id" onchange="getUnits()">';
	foreach($property_info as $cur_info)
	{
		if ($request_info['property_id'] == $cur_info['id'])
			$json[] = '<option value="'.$cur_info['id'].'" selected>'.$cur_info['pro_name'].'</option>'; 
		else
			$json[] = '<option value="'.$cur_info['id'].'">'.$cur_info['pro_name'].'</option>'; 
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Unit number</label>';
	$json[] = '<div id="property_units">';
	$json[] = '<input type="text" name="unit_number" value="'.html_encode($request_info['unit_number']).'" class="form-control">';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="row mb-3">';
	$json[] = '<div class="col">';
	$json[] = '<label class="form-label">Time</label>';
	$json[] = '<select name="time_slot" class="form-select">';
	foreach($time_slots as $key => $val)
	{
		if ($key == $request_info['time_slot'])
			$json[] = '<option value="'.$key.'" selected>'.$val.'</option>'; 
		else
			$json[] = '<option value="'.$key.'">'.$val.'</option>'; 
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="col">';
	$json[] = '<label class="form-label">GL code</label>';
	$json[] = '<input type="text" name="geo_code" value="'.html_encode($request_info['geo_code']).'" class="form-control">';
	$json[] = '</div>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="msg_for_maint"" rows="4" placeholder="Leave your comment" class="form-control">'.html_encode($request_info['msg_for_maint']).'</textarea>';
	$json[] = '</div>';

	$btns = [
		'<button type="submit" name="update_task" class="btn btn-primary">Save changes</button>',
		'<button type="submit" name="delete_task" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to remove it?\')">Delete task</button>'
	];

	echo json_encode([
			'modal_title'	=> 'Task information',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> implode("\n", $btns)
	]);
}
// COPY / MOVE TASK
else if ($id > 0 && $type == 2)
{
	$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
	$query = array(
		'SELECT'	=> 'id, pro_name',
		'FROM'		=> 'sm_property_db',
		'ORDER BY'	=> 'pro_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$property_info[] = $row;
	}

	$query = array(
		'SELECT'	=> 'r.*, p.pro_name, u.realname',
		'FROM'		=> 'hca_fs_requests AS r',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'r.employee_id=u.id'
			),
			array(
				// LEFT for day off
				'LEFT JOIN'		=> 'sm_property_db AS p',
				'ON'			=> 'r.property_id=p.id'
			),
		),
		'WHERE'		=> 'r.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$request_info = $DBLayer->fetch_assoc($result);

	$json[] = '<input type="hidden" name="request_id" value="'.$id.'">';
	$json[] = '<input type="hidden" name="employee_id" value="'.$request_info['employee_id'].'">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Property</label>';
	$json[] = '<input type="text" value="'.html_encode($request_info['pro_name']).'" class="form-control" disabled>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Unit number</label>';
	$json[] = '<input type="text" value="'.html_encode($request_info['unit_number']).'" class="form-control" disabled>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea rows="4" class="form-control" disabled>'.html_encode($request_info['msg_for_maint']).'</textarea>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label text-danger">Date</label>';
	$json[] = '<input type="date" name="start_date" class="form-control">';
	$json[] = '</div>';

	$btns = [
		'<button type="submit" name="copy_task" class="btn btn-primary">Copy task</button>',
		'<button type="submit" name="move_task" class="btn btn-warning">Move task</button>',
	];

	echo json_encode([
			'modal_title'	=> 'Copy/Move task to',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> implode("\n", $btns)
	]);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();