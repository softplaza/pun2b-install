<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;

$access = (!$User->is_guest()) ? true : false;
if (!$access || $pid == 0)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'p.*, u.realname',
	'FROM'		=> 'swift_projects AS p',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=p.requested_by'
		),
	),
	'WHERE'		=> 'p.id='.$pid,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$statuses = [0 => 'ACTIVE', 1 => 'COMPLETED', 2 => 'ON HOLD', 5 => 'DELETE'];
$json = [];

$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';

$json[] = '<div class="mb-3">';
$json[] = '<label class="form-label">Title</label>';
$json[] = '<input type="text" value="'.html_encode($main_info['project_desc']).'" class="form-control">';
$json[] = '</div>';

$json[] = '<div class="mb-3">';
$json[] = '<label class="form-label">Requested work</label>';
$json[] = '<textarea name="move_out_comment" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($main_info['requested_work']).'</textarea>';
$json[] = '</div>';

$json[] = '<div class="mb-3">';
$json[] = '<label class="form-label">Requested work</label>';
$json[] = '<textarea name="move_out_comment" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($main_info['completed_work']).'</textarea>';
$json[] = '</div>';


$project_statuses = [
	0 => 'Not started',
	1 => 'In progress',
	2 => 'On Hold',
	3 => 'Completed',
	4 => 'Rejected'
];

$json[] = '<div class="mb-3">';
$json[] = '<label class="form-label">Work status</label>';
$json[] = '<select name="project_status">'."\n";
foreach($project_statuses as $key => $value)
{
	if ($key == $main_info['project_status'])
		$json[] = '<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		$json[] = '<option value="'.$key.'">'.$value.'</option>'."\n";
}

$json[] = '</select>'."\n";
$json[] = '</div>';

$buttons = [];
$buttons[] = '<button type="submit" name="update_project" class="btn btn-primary">Update</button>';
if ($User->is_admin())
	$buttons[] = '<button type="submit" name="delete_project" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to remove it?\')">Delete project</button>';

echo json_encode([
		'modal_title'	=> 'Project status',
		'modal_body'	=> implode("\n", $json),
		'modal_footer'	=> implode("\n", $buttons)
]);

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
