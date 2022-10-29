<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

$modal_body = [];
if ($id > 0)
{
	$query = [
		'SELECT'	=> 'a.*, i.last_track_id',
		'FROM'		=> 'hca_cc_actions AS a',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'hca_cc_items AS i',
				'ON'			=> 'i.id=a.item_id'
			]
		],
		'WHERE'		=> 'a.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);


	$modal_body[] = '<input class="hidden" type="hidden" name="item_id" value="'.$item_id.'">';
	$modal_body[] = '<input class="hidden" type="hidden" name="track_id" value="'.$item_info['last_track_id'].'">';
	$modal_body[] = '<input class="hidden" type="hidden" name="action_id" value="'.$id.'">';
	
	$modal_body[] = '<div class="mb-3" id="fld_time_updated">';
	$modal_body[] = '<label class="form-label">Date & time</label>';
	$modal_body[] = '<input class="form-control" type="datetime-local" name="time_updated" value="'.format_time($item_info['time_updated'], 0,  'Y-m-d', 'H:i').'">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Notes</label>';
	$modal_body[] = '<textarea name="notes" class="form-control" rows="5">'.html_encode($item_info['notes']).'</textarea>';
	$modal_body[] = '</div>';

	$modal_footer[] = '<button type="submit" name="update_action" class="btn btn-primary">Update</button>';
	$modal_footer[] = '<button type="submit" name="delete_action" class="btn btn-danger">Delete</button>';

	echo json_encode(array(
		'modal_title' => 'Edit an action',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => implode("\n", $modal_footer),
	));
}
else if ($item_id > 0)
{
	$modal_body[] = '<input class="hidden" type="hidden" name="item_id" value="'.$item_id.'">';

	$modal_body[] = '<div class="mb-3" for="fld_time_updated">';
	$modal_body[] = '<label class="form-label">Date</label>';
	$modal_body[] = '<input class="form-control" id="fld_time_updated" type="datetime-local" name="time_updated" value="'.date('Y-m-d H:i').'">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Notes</label>';
	$modal_body[] = '<textarea name="notes" class="form-control" rows="5"></textarea>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Add an action',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="update_action" class="btn btn-primary">Update</button>',
	));
}