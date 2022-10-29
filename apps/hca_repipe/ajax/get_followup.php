<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

$modal_body = $modal_footer = [];
if ($id > 0)
{
	$cur_info = $DBLayer->select('hca_repipe_actions', 'id='.$id);
	
	$modal_body[] = '<input type="hidden" name="id" value="'.$id.'" class="form-control">';
	$modal_body[] = '<input type="hidden" name="project_id" value="'.$project_id.'" class="form-control">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Date</label>';
	$modal_body[] = '<input type="date" name="date_submitted" value="'.format_date($cur_info['date_submitted'], 'Y-m-d').'" class="form-control">';
	$modal_body[] = '</div>';
	
	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Comment</label>';
	$modal_body[] = '<textarea name="comment" class="form-control" rows="7">'.html_encode($cur_info['comment']).'</textarea>';
	$modal_body[] = '</div>';

	$modal_footer[] = '<button type="submit" name="update_action" class="btn btn-primary">Update</button>';
	$modal_footer[] = '<button type="submit" name="delete_action" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to remove it?\')">Delete</button>';

	echo json_encode(array(
		'modal_title' => 'Edit Follow-up',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => implode("\n", $modal_footer),
	));
}
else
{
	$modal_body[] = '<input type="hidden" name="project_id" value="'.$project_id.'" class="form-control">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Date</label>';
	$modal_body[] = '<input type="date" name="date_submitted" value="" class="form-control">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Comment</label>';
	$modal_body[] = '<textarea name="comment" class="form-control" rows="7"></textarea>';
	$modal_body[] = '</div>';

	$modal_footer[] = '<button type="submit" name="add_action" class="btn btn-primary">Create</button>';

	echo json_encode(array(
		'modal_title' => 'Add Follow-up',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => implode("\n", $modal_footer),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();