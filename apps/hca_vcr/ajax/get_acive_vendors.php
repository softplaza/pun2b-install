<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
$type = isset($_POST['type']) ? intval($_POST['type']) : 0;

$access = (!$User->is_guest()) ? true : false;
if (!$access || $pid == 0)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name',
	'FROM'		=> 'hca_vcr_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'WHERE'		=> 'pj.id='.$pid,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_vcr_invoices',
	'WHERE'		=> 'project_id='.$pid.' AND project_name=\'hca_vcr_projects\''
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_schedule = $vendors_schedule_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_schedule[$row['vendor_group_id']] = $row;
}

for($i = 1; $i < 10; $i++)
{
	if (isset($vendors_schedule[$i]))
		$vendors_schedule_info[$i] = $vendors_schedule[$i];
	else
		$vendors_schedule_info[$i] = array(
			'id'			=> 0,
			'vendor_id'		=> 0,
			'date_time'		=> 0,
			'remarks'		=> '',
			'shift'			=> 0,
			'in_house'		=> 0
		);
}

$time_slots = array(0 => 'ANY TIME', 1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
$json = [];

if ($type == 8) // MAINTENANCE
{
	$query = array(
		'SELECT'	=> 'u.id, u.realname',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'hca_fs_permanent_assignments AS pa',
				'ON'			=> 'pa.user_id=u.id '
			),
		),
		'WHERE'		=> 'pa.property_id='.$main_info['property_id'],
		'ORDER BY'	=> 'u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$assignments_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$assignments_info[$row['id']] = $row;
	}


	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[8]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="8">';
	$json[] = '<input type="hidden" name="in_house" value="1">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[8]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	if (!empty($assignments_info))
	{
		$json[] = '<div class="mb-3">';
		$json[] = '<label class="form-label">Technician</label>';
		$json[] = '<select name="vendor_id" class="form-select">';
		$json[] = '<option value="0" selected>Any technician</option>';
		foreach($assignments_info as $cur_info)
		{
			if ($vendors_schedule_info[8]['vendor_id'] == $cur_info['id'])
				$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>';
			else
				$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>';
		}
		$json[] = '</select>';
		$json[] = '</div>';
	}

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[8]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'In-House Maintenance',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}

else if ($type == 1) // URINE SCAN
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=1 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[1]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="1">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[1]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[1]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[1]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Urine Scan',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}
else if ($type == 2) // PAINTER
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=2 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[2]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="2">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[2]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="in_house" id="fld_in_house_0" value="0" onchange="inHousePainter(0)" '.($vendors_schedule_info[2]['in_house'] == 0 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label" for="fld_in_house_0">Vendor</label>';
	$json[] = '</div>';
	$json[] = '<div class="form-check form-check-inline">';
	$json[] = '<input class="form-check-input" type="radio" name="in_house" id="fld_in_house_1" value="1" onchange="inHousePainter(1)" '.($vendors_schedule_info[2]['in_house'] == 1 ? 'checked' : '').'>';
	$json[] = '<label class="form-check-label" for="fld_in_house_1">In-House Painter</label>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3" id="painter_vendors" '.($vendors_schedule_info[2]['in_house'] == 1 ? 'style="display:none"' : '').'>';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[2]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[2]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Painter',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}
else if ($type == 6) // Cleaning Service
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=6 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[6]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="6">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[6]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[6]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[6]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Cleaning Service',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}
else if ($type == 3) // Vinyl
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=3 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[3]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="3">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[3]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[3]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[3]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Vinyl Service',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}
else if ($type == 4) // Carpet Service
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=4 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[4]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="4">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[4]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[4]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[4]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Carpet Service',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}

else if ($type == 9) // Carpet Clean
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=9 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[9]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="9">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[9]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[9]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[9]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Carpet Clean',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}

else if ($type == 7) // Refinish
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=7 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[7]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="7">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[7]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[7]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[7]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Refinish',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}

else if ($type == 5) // Pest Control
{
	$query = array(
		'SELECT'	=> 'v.id, v.vendor_name',
		'FROM'		=> 'sm_vendors AS v',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'hca_vcr_vendors AS v2',
				'ON'			=> 'v2.vendor_id=v.id'
			),
		),
		'WHERE'		=> 'v.hca_vcr=1 AND v2.group_id=5 AND v2.enabled=1',
		'ORDER BY'	=> 'v.vendor_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

	$json[] = '<input type="hidden" name="project_id" value="'.$pid.'">';
	$json[] = '<input type="hidden" name="invoice_id" value="'.$vendors_schedule_info[5]['id'].'">';
	$json[] = '<input type="hidden" name="vendor_group_id" value="5">';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Start Date</label>';
	$json[] = '<input type="date" name="date_time" value="'.format_time($vendors_schedule_info[5]['date_time'], 1, 'Y-m-d').'" class="form-control">';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Vendor list</label>';
	$json[] = '<select name="vendor_id" class="form-select">';
	$json[] = '<option value="0" selected>Select vendor</option>';
	foreach ($vendors_info as $cur_info)
	{
		if ($vendors_schedule_info[5]['vendor_id'] == $cur_info['id'])
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>';
		else
			$json[] = "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>';
	}
	$json[] = '</select>';
	$json[] = '</div>';

	$json[] = '<div class="mb-3">';
	$json[] = '<label class="form-label">Comment</label>';
	$json[] = '<textarea name="remarks" rows="6" placeholder="Leave your comment" class="form-control">'.html_encode($vendors_schedule_info[5]['remarks']).'</textarea>';
	$json[] = '</div>';

	echo json_encode([
			'modal_title'	=> 'Pest Control',
			'modal_body'	=> implode("\n", $json),
			'modal_footer'	=> '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>'
	]);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
