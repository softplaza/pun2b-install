<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$id = isset($_POST['row']) ? intval($_POST['row']) : 0;
$col = isset($_POST['col']) ? intval($_POST['col']) : 0;

if ($id  > 0)
{
	$query = array(
		'SELECT'	=> 'pj.*, pt.pro_name',
		'FROM'		=> 'hca_pvcr_projects AS pj',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=pj.property_id'
			),
		),
		'WHERE'		=> 'pj.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$project_info = $DBLayer->fetch_assoc($result);

	$json_title = 'Editor';
	$json = [];
	$json[] = '<input type="hidden" name="id" value="'.$id.'">';
	$json[] = '<p>Unit # <strong>'.html_encode($project_info['unit_number']).'</strong></p>';
	$json[] = '<p>Unit size: <strong>'.html_encode($project_info['unit_size']).'</strong></p>';

	if ($col == 2)
	{
		$json_title = 'MoveOut Date';
		$json[] = '<p>Moveout date</p>';
		$json[] = '<p><input type="date" name="move_out_date" value="'.format_time($project_info['move_out_date'], 1, 'Y-m-d').'"></p>';
		$json[] = '<p>Comment</p>';
		$json[] = '<p><textarea name="move_out_comment" rows="5">'.html_encode($project_info['move_out_comment']).'</textarea></p>';
	}
	else if ($col == 3){

	}

	$json[] = '<p class="btn-action"><span class="submit primary"><input type="submit" name="update" value="Update"/></span></p>';

	echo json_encode(array(
		'title'			=> $json_title,
		'fields'		=> implode("\n", $json))
	);
}
else
{
	echo json_encode(array(
		'error' => 'Wrong id number',
	));
}

exit();
