<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
$type = isset($_POST['type']) ? intval($_POST['type']) : 0;

$access = (!$User->is_guest()) ? true : false;
if (!$access || $pid == 0)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name',
	'FROM'		=> 'hca_vcr_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'WHERE'		=> 'pj.id='.$pid,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'u.id, u.realname',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.hca_vcr_access > 0',
	'ORDER BY'	=> 'u.realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$statuses = [0 => 'ACTIVE', 1 => 'COMPLETED', 2 => 'ON HOLD', 5 => 'DELETE'];
$json = [];

if ($type == 1)
{
	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Property name</label>';
	$json[] = '<input type="text" value="'.html_encode($main_info['pro_name']).'" class="form-control" disabled>';
	$json[] = '</div>';

	// name="unit_number"
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Unit number</label>';
	$json[] = '<input type="text" value="'.html_encode($main_info['unit_number']).'" class="form-control" disabled>';
	$json[] = '</div>';
/*
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Unit size</label>';
	$json[] = '<input type="text" name="unit_size" value="'.html_encode($main_info['unit_size']).'" class="form-control">';
	$json[] = '</div>';
*/
	$json[] = '<label class="form-label">Project Status</label>';
	$json[] = '<div class="mb-3">';
	$json[] = '<div class="form-check">';
	$json[] = '<input class="form-check-input" type="radio" name="status" id="fld_status_0" value="0" '.($main_info['status'] == 0 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label" for="fld_status_0">Active</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check">';
	$json[] = '<input class="form-check-input" type="radio" name="status" id="fld_status_1" value="1" '.($main_info['status'] == 1 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label" for="fld_status_1">Completed</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check">';
	$json[] = '<input class="form-check-input" type="radio" name="status" id="fld_status_2" value="2" '.($main_info['status'] == 2 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label" for="fld_status_2">On Hold</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check">';
	$json[] = '<input class="form-check-input" type="radio" name="status" id="fld_status_5" value="5" '.($main_info['status'] == 5 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label" for="fld_status_5">Removed</label>';
	$json[] = '</div>';
	$json[] = '</div>';

	$buttons = [];
	$buttons[] = '<button type="submit" name="update_modal" class="btn btn-primary">Update</button>';
	if ($User->is_admin())
		$buttons[] = '<button type="submit" name="delete_project" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to remove it?\')">Delete project</button>';

	echo json_encode([
			'modal_title'	=> 'Project status',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> implode("\n", $buttons)
	]);
}

else if ($type == 2)
{
	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Move Out Date</label>';
	$json[] = '<input type="date" name="move_out_date" value="'.format_time($main_info['move_out_date'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="move_out_comment" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($main_info['move_out_comment']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Move Out Information',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_modal" class="btn btn-primary">Update</button>'
	]);
}

else if ($type == 3)
{
	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Pre Walk Date</label>';
	$json[] = '<input type="date" name="pre_walk_date" value="'.format_time($main_info['pre_walk_date'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Pre Walk Performed</label>';
	$json[] = '<input type="text" name="pre_walk_name" value="'.html_encode($main_info['pre_walk_name']).'" list="pre_walk_name" class="form-control">';
	if (!empty($users_info))
	{
		$json[] = '<datalist id="pre_walk_name">'."\n";
		foreach($users_info as $user_info)
		{
			$json[] = '<option value="'.$user_info['realname'].'">'."\n"; 
		}
		
		$json[] = '</datalist>'."\n";
	}
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="pre_walk_comment" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($main_info['pre_walk_comment']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Pre Walk Information',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_modal" class="btn btn-primary">Update</button>'
	]);
}

else if ($type == 11)
{
	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Final Walk Date</label>';
	$json[] = '<input type="date" name="walk_date" value="'.format_time($main_info['walk_date'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Final Walk Name</label>';
	$json[] = '<input type="text" name="walk" value="'.html_encode($main_info['walk']).'" list="walk" class="form-control">';
	if (!empty($users_info))
	{
		$json[] = '<datalist id="walk">'."\n";
		foreach($users_info as $user_info)
		{
			$json[] = '<option value="'.$user_info['realname'].'">'."\n"; 
		}
		
		$json[] = '</datalist>'."\n";
	}
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="walk_comment" rows="8" placeholder="Leave your comment" class="form-control">'.html_encode($main_info['walk_comment']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Final Walk Information',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_modal" class="btn btn-primary">Update</button>'
	]);
}
// MOVE IN
else if ($type == 12)
{
	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Move In Date</label>';
	$json[] = '<input type="date" name="move_in_date" value="'.format_time($main_info['move_in_date'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="move_in_comment" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($main_info['move_in_comment']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Move In Information',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_modal" class="btn btn-primary">Update</button>'
	]);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
