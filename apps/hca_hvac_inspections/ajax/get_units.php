<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
$unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;

$json_units = '';
if ($property_id > 0 && $unit_id > 0)
{
	$unit_numbers = [];
	$query = array(
		'SELECT'	=> 'u.id, u.unit_number, u.key_number',
		'FROM'		=> 'sm_property_units AS u',
		'WHERE'		=> 'u.property_id='.$property_id,
		'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$units_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$units_info[] = $row;
	}
	$unit_numbers[] = '<select id="unit_numbers" name="unit_id" class="form-select form-select-sm" onchange="getUnitKey()" required>'."\n";
	$unit_numbers[] = '<option value="" selected>Select one</option>'."\n";
	foreach($units_info as $cur_info)
	{
		if ($unit_id == $cur_info['id'])
			$unit_numbers[] = '<option value="'.$cur_info['id'].'" selected>'.$cur_info['unit_number'].'</option>'."\n";
		else
			$unit_numbers[] = '<option value="'.$cur_info['id'].'">'.$cur_info['unit_number'].'</option>'."\n";

		if (!isset($first_key_number))
			$first_key_number = $cur_info['key_number'];
	}
	$unit_numbers[] = '</select>'."\n";

	$checklist_dupes = [];
	$query = [
		'SELECT'	=> 'ch.*, p.pro_name, un.unit_number',
		'FROM'		=> 'hca_hvac_inspections_checklist as ch',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=ch.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=ch.unit_id'
			],
		],
		'WHERE'		=> 'ch.unit_id='.$unit_id,
		//'ORDER BY'	=> 'LENGTH(un.unit_number), un.unit_number',

	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$checklist_dupes[] = $row;
	}

	$btn_actions = [];
	if (!empty($checklist_dupes))
	{
		$btn_actions[] = '<div class="mb-3 callout bd-callout-danger alert-warning">Found inspections that were performed in this unit. You can resume an existing inspection or start a new one.</div>';

		$btn_actions[] = '<div class="mb-3">';
		foreach($checklist_dupes as $cur_info)
		{
			$btn_actions[] = '<div class="form-check-inline">';
			$btn_actions[] = '<div class="border border-secondary px-2 py-1">';
			$btn_actions[] = '<h6 class="mb-0">Date: '.format_date($cur_info['datetime_inspection_start'], 'n/j/Y').'</h6>';
			$btn_actions[] = ($cur_info['inspection_completed'] == 2) ? '<p>Status: <span class="text-success fw-bold">Completed</span></p>' : '<p>Status: <span class="text-primary fw-bold">Pending</span></p>';
			$btn_actions[] = '<p><a href="'.$URL->link('hca_hvac_inspections_checklist', $cur_info['id']).'" class="fw-bold">Go to checklist</a></p>';
			$btn_actions[] = '</div>';
			$btn_actions[] = '</div>';
		}
		$btn_actions[] = '</div>';
		$btn_actions[] = '<div class="mb-3"><button type="submit" name="create" class="btn btn-primary">Start inspection anyway</button></div>';
	}
	else
		$btn_actions[] = '<div class="mb-3"><button type="submit" name="create" class="btn btn-primary">Start inspection</button></div>';

	echo json_encode(
		[
			'unit_number' => implode('', $unit_numbers),
			'btn_actions' => implode('', $btn_actions),
		]
	);
}

else if ($property_id > 0)
{
	$query = array(
		'SELECT'	=> 'u.id, u.unit_number, u.key_number',
		'FROM'		=> 'sm_property_units AS u',
		'WHERE'		=> 'u.property_id='.$property_id,
		'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$units_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$units_info[] = $row;
	}

	$json_units .= '<select id="unit_numbers" name="unit_id" class="form-select form-select-sm" onchange="getUnitKey()" required>'."\n";
	$json_units .= '<option value="" selected>Select one</option>'."\n";
	foreach($units_info as $cur_info)
	{
		if ($unit_id == $cur_info['id'])
			$json_units .= '<option value="'.$cur_info['id'].'" selected>'.$cur_info['unit_number'].'</option>'."\n";
		else
			$json_units .= '<option value="'.$cur_info['id'].'">'.$cur_info['unit_number'].'</option>'."\n";

		if (!isset($first_key_number))
			$first_key_number = $cur_info['key_number'];
	}
	$json_units .= '</select>'."\n";

	$key_number = (isset($first_key_number) && $first_key_number != '') ? $first_key_number : '- - -';
	echo json_encode(array(
		'unit_number' => $json_units,
		'key_number' => '<h5>'.$key_number.'</h5>',
	));
}
else if ($unit_id > 0)
{
	$query = array(
		'SELECT'	=> 'u.id, u.unit_number, u.key_number',
		'FROM'		=> 'sm_property_units AS u',
		'WHERE'		=> 'u.id='.$unit_id,
		'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$unit_info = $DBLayer->fetch_assoc($result);

	$checklist_dupes = [];
	$query = [
		'SELECT'	=> 'ch.*, p.pro_name, un.unit_number',
		'FROM'		=> 'hca_hvac_inspections_checklist as ch',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=ch.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=ch.unit_id'
			],
		],
		'WHERE'		=> 'ch.unit_id='.$unit_id,
		'ORDER BY'	=> 'ch.datetime_inspection_start DESC',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$checklist_dupes[] = $row;
	}

	$btn_actions = [];
	if (!empty($checklist_dupes))
	{
		$btn_actions[] = '<div class="mb-3 callout bd-callout-danger alert-warning">Found inspections that were performed in this unit. You can resume an existing inspection or start a new one.</div>';

		$btn_actions[] = '<div class="mb-3">';
		foreach($checklist_dupes as $cur_info)
		{
			$btn_actions[] = '<div class="form-check-inline">';
			$btn_actions[] = '<div class="border border-secondary px-2 py-1">';
			$btn_actions[] = '<h6 class="mb-0">Date: '.format_date($cur_info['datetime_inspection_start'], 'n/j/Y').'</h6>';
			$btn_actions[] = ($cur_info['inspection_completed'] == 2) ? '<p>Status: <span class="text-success fw-bold">Completed</span></p>' : '<p>Status: <span class="text-primary fw-bold">Pending</span></p>';
			$btn_actions[] = '<p><a href="'.$URL->link('hca_hvac_inspections_checklist', $cur_info['id']).'" class="fw-bold">Go to checklist</a></p>';
			$btn_actions[] = '</div>';
			$btn_actions[] = '</div>';
		}
		$btn_actions[] = '</div>';
		$btn_actions[] = '<div class="mb-3"><button type="submit" name="create" class="btn btn-primary">Start inspection anyway</button></div>';
	}
	else
		$btn_actions[] = '<div class="mb-3"><button type="submit" name="create" class="btn btn-primary">Start inspection</button></div>';

	$unit_info['key_number'] = ($unit_info['key_number'] != '') ? $unit_info['key_number'] : 'No key';

	echo json_encode(array(
		'key_number' => '<h5>'.html_encode($unit_info['key_number']).'</h5>',
		'btn_actions' => implode('', $btn_actions),
	));
}
else
{
	echo json_encode(array(
		'unit_number' => '<input type="text" value="" class="form-control" disabled>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
