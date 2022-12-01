<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

if ($task_id > 0)
{
	$modal_body = $modal_footer = [];

	$modal_body[] = '<input type="hidden" name="task_id" value="'.$task_id.'">';

	$modal_body[] = '<div class="mb-3">';


	$modal_body[] = '</div>';


	$modal_footer[] = '<input type="file" name="files" value="" class="btn btn-secondary">';
	$modal_footer[] = '<button type="submit" name="update_task" class="btn btn-primary">Upload file</button>';
	
	echo json_encode(array(
		'modal_title' => 'Uploaded Images',
		'modal_body' => implode('', $modal_body),
		'modal_footer' => implode('', $modal_footer),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
