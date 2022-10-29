<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$modal_body = [];
if ($id > 0)
{
	$query = [
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_cc_items AS i',
/*
		'JOINS'		=> [
			[
				'LEFT JOIN'	=> 'hca_cc_projects AS p',
				'ON'			=> 'p.id=i.last_tracking_id'
			]
		],
*/
		'WHERE'		=> 'i.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);

	$modal_body[] = '<input class="hidden" type="hidden" name="last_track_id" value="'.$item_info['last_track_id'].'">';
	$modal_body[] = '<input class="hidden" type="hidden" name="item_id" value="'.$item_info['id'].'">';
	$modal_body[] = '<input class="hidden" type="hidden" name="date_last_completed" value="'.$item_info['date_completed'].'">';
	$modal_body[] = '<input class="hidden" type="hidden" name="frequency" value="'.$item_info['frequency'].'">';
	$modal_body[] = '<input class="hidden" type="hidden" name="date_due" value="'.$item_info['date_due'].'">';
	$modal_body[] = '<input class="hidden" type="hidden" name="months_due" value="'.$item_info['months_due'].'">';

	$modal_body[] = '<div class="mb-0">';
	$modal_body[] = '<label class="form-label mb-0">Is it completed?</label>';
	$modal_body[] = '</div>';
	$modal_body[] = '<div class="form-check form-check-inline">';
	$modal_body[] = '<input class="form-check-input" type="radio" name="completed" id="fld_completed0" value="0" checked onclick="showField(\'fld_date_completed\',0)">';
	$modal_body[] = '<label class="form-check-label" for="fld_completed0">NO</label>';
	$modal_body[] = '</div>';
	$modal_body[] = '<div class="form-check form-check-inline mb-3">';
	$modal_body[] = '<input class="form-check-input" type="radio" name="completed" id="fld_completed1" value="1" onclick="showField(\'fld_date_completed\',1)">';
	$modal_body[] = '<label class="form-check-label" for="fld_completed1">YES</label>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3" id="fld_date_completed" style="display:none">';
	$modal_body[] = '<label class="form-label">Completed on</label>';
	$modal_body[] = '<input class="form-control" type="date" name="date_completed" value="'.date('Y-m-d').'">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Notes</label>';
	$modal_body[] = '<textarea name="notes" class="form-control" rows="7">'.html_encode($item_info['notes']).'</textarea>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Edit tracking of item',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="update_project" class="btn btn-primary">Update</button>',
	));
}
