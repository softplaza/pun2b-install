<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// FIST OF EVENTS
if ($id > 0)
{
	$ajax_content = [];

	$query = [
		'SELECT'	=> 'e.*, u.realname',
		'FROM'		=> 'swift_events AS e',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=e.user_id'
			],
		],
		'WHERE'		=> 'e.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$event_info = $DBLayer->fetch_assoc($result);

	if ($event_info['event_type'] == 1)
		$modal_title = 'Edit task';
	else if ($event_info['event_type'] == 2)
		$modal_title = 'Edit reminder';
	else
		$modal_title = 'Edit note';

	$ajax_content[] = '<input type="hidden" name="event_id" value="'.$event_info['id'].'">';

	$ajax_content[] = '<div class="form-check form-check-inline">';
	$ajax_content[] = '<input class="form-check-input" type="radio" name="event_type" id="fld_event_type0" value="0" '.($event_info['event_type'] == 0 ? 'checked' : '').'>';
	$ajax_content[] = '<label class="form-check-label" for="fld_event_type0">Note</label>';
	$ajax_content[] = '</div>';

	$ajax_content[] = '<div class="form-check form-check-inline">';
	$ajax_content[] = '<input class="form-check-input" type="radio" name="event_type" id="fld_event_type1" value="1" '.($event_info['event_type'] == 1 ? 'checked' : '').'>';
	$ajax_content[] = '<label class="form-check-label" for="fld_event_type1">Task</label>';
	$ajax_content[] = '</div>';

	$ajax_content[] = '<div class="form-check form-check-inline">';
	$ajax_content[] = '<input class="form-check-input" type="radio" name="event_type" id="fld_event_type2" value="2" '.($event_info['event_type'] == 2 ? 'checked' : '').'>';
	$ajax_content[] = '<label class="form-check-label" for="fld_event_type2">Reminder</label>';
	$ajax_content[] = '</div>';

	$ajax_content[] = '<div class="mb-2">';
	$ajax_content[] = '<label class="form-label" for="fld_datetime_created">Date</label>';
	$ajax_content[] = '<input class="form-control" id="fld_datetime_created" type="datetime-local" name="datetime_created" value="'.format_date($event_info['datetime_created'], 'Y-m-d H:i').'" required>';
	$ajax_content[] = '</div>';
	
	$ajax_content[] = '<div class="mb-2">';
	$ajax_content[] = '<label class="form-label" for="fld_message">Message</label>';
	$ajax_content[] = '<textarea class="form-control" id="fld_message" name="message" rows="7" placeholder="Leave your message" required>'.html_encode($event_info['message']).'</textarea>';
	$ajax_content[] = '</div>';

	if ($event_info['event_type'] > 0)
	{
		$ajax_content[] = '<div class="form-check">';
		$ajax_content[] = '<input type="hidden" name="event_status" value="0">';
		$ajax_content[] = '<input class="form-check-input" type="checkbox" name="event_status" id="fld_event_status" value="1" '.($event_info['event_status'] == 1 ? 'checked' : '').'>';
		$ajax_content[] = '<label class="form-check-label" for="fld_event_status">Completed</label>';
		$ajax_content[] = '</div>';
	}

	$modal_footer = ['<div class="mb-3"><button type="submit" name="update_event" class="btn btn-sm btn-primary">Update</button></div>'];

	if ($User->get('id') == $event_info['user_id'])
		$modal_footer[] = '<div class="mb-3"><button type="submit" name="delete_event" class="btn btn-sm btn-danger">Delete</button></div>';

	echo json_encode(
		[
			'modal_title' => $modal_title,
			'modal_body' => implode('', $ajax_content),
			'modal_footer' => implode('', $modal_footer)
		]
	);
}
else
{
	$ajax_content = [];

	$ajax_content[] = '<input type="hidden" name="event_id" value="0">';

	$ajax_content[] = '<div class="form-check form-check-inline">';
	$ajax_content[] = '<input class="form-check-input" type="radio" name="event_type" id="fld_event_type0" value="0" checked>';
	$ajax_content[] = '<label class="form-check-label" for="fld_event_type0">Note</label>';
	$ajax_content[] = '</div>';

	$ajax_content[] = '<div class="form-check form-check-inline">';
	$ajax_content[] = '<input class="form-check-input" type="radio" name="event_type" id="fld_event_type1" value="1">';
	$ajax_content[] = '<label class="form-check-label" for="fld_event_type1">Task</label>';
	$ajax_content[] = '</div>';

	$ajax_content[] = '<div class="form-check form-check-inline">';
	$ajax_content[] = '<input class="form-check-input" type="radio" name="event_type" id="fld_event_type2" value="2">';
	$ajax_content[] = '<label class="form-check-label" for="fld_event_type2">Reminder</label>';
	$ajax_content[] = '</div>';

	$ajax_content[] = '<div class="mb-2">';
	$ajax_content[] = '<label class="form-label" for="fld_datetime_created">Date</label>';
	$ajax_content[] = '<input class="form-control" id="fld_datetime_created" type="datetime-local" name="datetime_created" value="'.date('Y-m-d\TH:i').'" required>';
	$ajax_content[] = '</div>';
	
	$ajax_content[] = '<div class="mb-2">';
	$ajax_content[] = '<label class="form-label" for="fld_message">Message</label>';
	$ajax_content[] = '<textarea class="form-control" id="fld_message" name="message" rows="7" placeholder="Leave your message" required></textarea>';
	$ajax_content[] = '</div>';

	echo json_encode(
		[
			'modal_title' => 'Add an action',
			'modal_body' => implode('', $ajax_content),
			'modal_footer' => '<div class="mb-3"><button type="submit" name="create_event" class="btn btn-sm btn-primary">Submit</button></div>'
		]
	);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();