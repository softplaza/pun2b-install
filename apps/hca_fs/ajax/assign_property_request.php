<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

if ($task_id > 0)
{
	require SITE_ROOT.'apps/hca_fs/classes/HcaFS.php';
	$HcaFS = new HcaFS;

	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.realname, g.g_id, g.g_title',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'groups AS g',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'u.group_id=3',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$users[] = $row;	
	}

	$query = [
		'SELECT'	=> 't.*, pt.pro_name, un.unit_number, u.realname',
		'FROM'		=> 'hca_fs_tasks AS t',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=t.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=t.unit_id'
			],
			[
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=t.assigned_to'
			],
		],
		'WHERE'		=> 't.id='.$task_id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_fs_tasks = $DBLayer->fetch_assoc($result);

	$json[] = '<input type="hidden" name="task_id" value="'.$task_id.'">';

	$json[] = '<div class="row">';
	$json[] = '<div class="col mb-3">';
	$json[] = '<label class="form-label">Property</label>';
	$json[] = '<input type="text" value="'.html_encode($hca_fs_tasks['pro_name']).'" class="form-control" readonly>';
	$json[] = '</div>';

	$json[] = '<div class="col mb-3">';
	$json[] = '<label class="form-label">Unit #</label>';
	$json[] = '<input type="text" value="'.html_encode($hca_fs_tasks['unit_number']).'" class="form-control" readonly>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="row">';
	$json[] = '<div class="col mb-3">';
	$json[] = '<label class="form-label">Time</label>';
	$json[] = '<input type="text" value="'.$HcaFS->getTimeSlot($hca_fs_tasks['time_slot']).'" class="form-control" readonly>';
	$json[] = '</div>';

	$json[] = '<div class="col mb-3">';
	$json[] = '<label class="form-label">GL Code</label>';
	$json[] = '<input type="text" value="'.html_encode($hca_fs_tasks['gl_code']).'" class="form-control" readonly>';
	$json[] = '</div>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea rows="2" class="form-control">'.html_encode($hca_fs_tasks['task_details']).'</textarea>';
	$json[] = '</div>';

	$json[] = '<div class="row">';
	$json[] = '<div class="col mb-3">';
	$json[] = '<label class="form-label">Requested Date</label>';
	$json[] = '<input type="date" name="requested_date" class="form-control" value="'.html_encode($hca_fs_tasks['requested_date']).'">';
	$json[] = '</div>';

	$json[] = '<div class="col mb-3">';
	$json[] = '<label class="form-label">Assigned to</label>';

	$json[] = '<select name="assigned_to" class="form-select form-select-sm fw-bold" id="fld_assigned_to">';
	$json[] = '<option value="0" selected disabled>Select one</option>';
	$optgroup = 0;
	foreach($users as $cur_info)
	{
		if ($cur_info['group_id'] != $optgroup) {
			if ($optgroup) {
				$json[] = '</optgroup>';
			}
			$json[] = '<optgroup label="'.html_encode($cur_info['g_title']).'">';
			$optgroup = $cur_info['group_id'];
		}

		if ($hca_fs_tasks['assigned_to'] == $cur_info['id'])
			$json[] = '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>';
		else
			$json[] = '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Assign Property Request',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="assign_task" class="btn btn-primary">Assign</button>'
	]);
}


// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
