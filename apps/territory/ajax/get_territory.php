<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
$type = isset($_POST['type']) ? intval($_POST['type']) : 0;

$access = (!$User->is_guest()) ? true : false;
if (!$access || $tid == 0)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 't.*, a.date_started',
	'FROM'		=> 'swift_territories AS t',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'swift_assignments AS a',
			'ON'			=> 'a.id=t.last_aid'
		),
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=t.last_uid'
		),
	),
	'WHERE'		=> 't.id='.$tid,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'u.id, u.realname',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.id>2',
	'ORDER BY'	=> 'u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$user_list = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$user_list[] = $row;
}

$json = [];
$json[] = '<input type="hidden" name="tid" value="'.$tid.'">';

// Edit
if ($type == 1)
{
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Number</label>';
	$json[] = '<input type="text" name="ter_number" value="'.html_encode($main_info['ter_number']).'" class="form-control">';
	$json[] = '</div>';
	
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Description</label>';
	$json[] = '<textarea name="ter_description" class="form-control">'.html_encode($main_info['ter_description']).'</textarea>';
	$json[] = '</div>';
	
	$buttons = [];
	$buttons[] = '<button type="submit" name="update_info" class="btn btn-primary">Update</button>';
	if ($User->is_admin())
		$buttons[] = '<button type="submit" name="delete_project" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete it?\')">Delete</button>';
	
	echo json_encode([
		'modal_title'	=> 'Edit territory',
		'modal_body'	=> implode("\n", $json),
		'modal_footer'	=> implode("\n", $buttons)
	]);
}
// Assign to
else if ($type == 2)
{
	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_started" value="'.format_time($main_info['date_started'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	if (!empty($user_list))
	{
		$json[] = '<div class="mb-3">';
		$json[] = '<label class="form-label">User</label>';
		$json[] = '<select name="user_id" class="form-select">';
		$json[] = '<option value="0" selected>Publisher</option>';
		foreach($user_list as $cur_info)
		{
			if ($main_info['last_uid'] == $cur_info['id'])
				$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>';
			else
				$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
		}
		$json[] = '</select>';
		$json[] = '</div>';
	}

	$buttons = [];
	$buttons[] = '<button type="submit" name="assign" class="btn btn-primary">Assign</button>';
	
	echo json_encode([
		'modal_title'	=> 'Assign territory',
		'modal_body'	=> implode("\n", $json),
		'modal_footer'	=> implode("\n", $buttons)
	]);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
