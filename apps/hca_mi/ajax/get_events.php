<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;

$modal_body = $modal_footer = [];
if ($id > 0)
{
	$cur_info = $DBLayer->select('sm_calendar_events', 'id='.$id);
	
	$modal_body[] = '<input type="hidden" name="event_id" value="'.$id.'" class="form-control">';
	$modal_body[] = '<input type="hidden" name="project_id" value="'.$pid.'" class="form-control">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Date & time</label>';
	$modal_body[] = '<input type="datetime-local" name="time" value="'.date('Y-m-d\TH:i', $cur_info['time']).'" class="form-control">';
	$modal_body[] = '</div>';
	
	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Message</label>';
	$modal_body[] = '<textarea name="message" class="form-control" rows="7">'.html_encode($cur_info['message']).'</textarea>';
	$modal_body[] = '</div>';

	$modal_footer[] = '<button type="submit" name="update_event" class="btn btn-primary">Update</button>';
	$modal_footer[] = '<button type="submit" name="delete_event" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to remove this event?\')">Delete</button>';

	echo json_encode(array(
		'modal_title' => 'Edit an action',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => implode("\n", $modal_footer),
	));
}
else
{
	$modal_body[] = '<input type="hidden" name="project_id" value="'.$pid.'" class="form-control">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Date & time</label>';
	$modal_body[] = '<input type="datetime-local" name="time" value="'.date('Y-m-d\TH:i').'" class="form-control">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Message</label>';
	$modal_body[] = '<textarea name="message" class="form-control" rows="7"></textarea>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Edit an action',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="update_event" class="btn btn-primary">Update</button>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();