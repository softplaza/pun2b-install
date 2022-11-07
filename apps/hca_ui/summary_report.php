<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access_admin = ($User->is_admin()) ? true : false;
$access6 = ($User->checkAccess('hca_ui', 6)) ? true : false;
if (!$access6)
	message($lang_common['No permission']);

$search_by_inspection_type = isset($_GET['inspection_type']) ? intval($_GET['inspection_type']) : 0; // 0 all, 1 audit, 2 flapper

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_item_id  = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$search_by_job_type  = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0;
$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$search_by_date_inspected = isset($_GET['date_inspected']) ? swift_trim($_GET['date_inspected']) : '';

$HcaUISummaryReport = new HcaUISummaryReport;
$HcaUISummaryReport->getCheckedItems();

$HcaUnitInspection = new HcaUnitInspection;

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] .= ' AND p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $fetch_assoc;
}

$summary_pending = $summary_repaired = $summary_replaced = 0;
$num_pending = $num_repaired = $num_replaced = [];

$search_query = [];
$search_query[] = 'i.summary_report=1'; // display only preselected in setting of List of items
$search_query[] = 'ch.inspection_completed=2'; // Display only completed Checklists
if ($search_by_property_id > 0)
	$search_query[] = 'ch.property_id='.$search_by_property_id;

if ($search_by_inspection_type == 1)
	$search_query[] = 'ch.type_audit=1';
if ($search_by_inspection_type == 2)
	$search_query[] = 'ch.type_flapper=1';

if ($search_by_item_id > 0)
	$search_query[] = 'ci.item_id='.$search_by_item_id;
if ($search_by_date_inspected != '')
	$search_query[] = 'DATE(ch.date_inspected)=\''.$DBLayer->escape($search_by_date_inspected).'\'';
if ($search_by_year > 0)
	$search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($search_by_year).'\'';

$query = [
	'SELECT'	=> 'ci.item_id, ci.job_type, ci.checklist_id, ch.property_id, ch.num_problem, ch.num_pending, ch.num_replaced, ch.num_repaired, ch.num_reset, ch.inspection_completed, ch.work_order_completed, ch.work_order_comment, p.pro_name, un.unit_number, un.mbath, un.hbath',
	'FROM'		=> 'hca_ui_checklist_items AS ci',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_ui_checklist AS ch',
			'ON'			=> 'ch.id=ci.checklist_id'
		],
		[
			'INNER JOIN'	=> 'hca_ui_items AS i',
			'ON'			=> 'i.id=ci.item_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=ch.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS un',
			'ON'			=> 'un.id=ch.unit_id'
		],
	],
	'ORDER BY'	=> 'i.display_position'
];
$query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

while($row = $DBLayer->fetch_assoc($result))
{
	if ($row['job_type'] == 0)
	{
		++$summary_pending;

		if (isset($num_pending[$row['property_id']]))
			++$num_pending[$row['property_id']];
		else
			$num_pending[$row['property_id']] = 1;
	}
	else if ($row['job_type'] == 1)
	{
		++$summary_replaced;

		if (isset($num_replaced[$row['property_id']]))
			++$num_replaced[$row['property_id']];
		else
			$num_replaced[$row['property_id']] = 1;	
	}
		
	else if ($row['job_type'] == 2)
	{
		++$summary_repaired;

		if (isset($num_repaired[$row['property_id']]))
			++$num_repaired[$row['property_id']];
		else
			$num_repaired[$row['property_id']] = 1;	
	}
}



/**
 * This is start page contants all inspected properties.
 * Not inspected properties are not included.
 * SEARCH AREA: 
 * YEAR - need to be add search by "last 12 months / last 6 months / last 3 months"
 */

if ($search_by_property_id == 0)
{
	$Core->set_page_id('hca_ui_summary_report', 'hca_ui');
	require SITE_ROOT.'header.php';

	$num_work_orders = [];

	$search_query = [];
	if ($search_by_inspection_type == 1)
		$search_query[] = 'ch.type_audit=1';
	if ($search_by_inspection_type == 2)
		$search_query[] = 'ch.type_flapper=1';
	if ($search_by_year > 0)
		$search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($search_by_year).'\'';

	$query = [
		'SELECT'	=> 'ch.*',
		'FROM'		=> 'hca_ui_checklist AS ch',
	];
	if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while($row = $DBLayer->fetch_assoc($result))
	{
		$num_problem = ($row['num_problem'] > 0 && $row['num_pending'] == 0) ? $row['num_problem'] : $row['num_pending'];

		if (isset($num_work_orders[$row['property_id']]))
			++$num_work_orders[$row['property_id']];
		else
			$num_work_orders[$row['property_id']] = 1;
	}
?>

<nav class="navbar alert-info mb-1">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="year" class="form-select form-select-sm">
						<option value="0">All Years</option>
<?php
	for ($year = 2021; $year <= date('Y'); $year++)
	{
				if ($search_by_year == $year)
					echo '<option value="'.$year.'" selected="selected">'.$year.'</option>';
				else
					echo '<option value="'.$year.'">'.$year.'</option>';
	}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="inspection_type" class="form-select-sm">
<?php
	$inspection_types = [
		0 => 'All inspections',
		1 => 'Water Audit',
		2 => 'Flapper Replacement',
	];
	foreach ($inspection_types as $key => $val)
	{
		if ($search_by_inspection_type == $key)
			echo '<option value="'.$key.'" selected>'.$val.'</option>';
		else
			echo '<option value="'.$key.'">'.$val.'</option>';
	}
?>
					</select>
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_ui_summary_report') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">Property Report</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property name</th>
			<th class="bg-danger text-white">Pending items</th>
			<th class="bg-warning text-white">Repaired items</th>
			<th class="bg-success text-white">Replaced items</th>
			<th>Date of Last Inspection</th>
		</tr>
	</thead>
	<tbody>

<?php

	$i = 0;
	foreach($sm_property_db as $cur_info)
	{
		if (isset($num_work_orders[$cur_info['id']]))
		{
			$sub_link_args = ['property_id' => $cur_info['id']];

			if ($search_by_inspection_type > 0)
				$sub_link_args['inspection_type'] = $search_by_inspection_type;
?>
		<tr>
			<td>
				<span class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></span>
				<a href="<?php echo $URL->genLink('hca_ui_summary_report', $sub_link_args) ?>" class="badge bg-primary float-end text-white">View</a>
			</td>
			<td class="ta-center fw-bold"><?php echo (isset($num_pending[$cur_info['id']]) ? $num_pending[$cur_info['id']] : 0) ?></td>
			<td class="ta-center fw-bold"><?php echo (isset($num_repaired[$cur_info['id']]) ? $num_repaired[$cur_info['id']] : 0) ?></td>
			<td class="ta-center fw-bold"><?php echo (isset($num_replaced[$cur_info['id']]) ? $num_replaced[$cur_info['id']] : 0) ?></td>
			<td class=""></td>
		</tr>
<?php

			++$i;
		}
	}
?>
	</tbody>
	<tfoot>
		<tr>
			<td class="fw-bold">Properties (<?=$i?>)</td>
			<td class="ta-center fw-bold"><?php echo $summary_pending ?></td>
			<td class="ta-center fw-bold"><?php echo $summary_repaired ?></td>
			<td class="ta-center fw-bold"><?php echo $summary_replaced ?></td>
			<td></td>
		</tr>
	</tfoot>
</table>

<?php
}
else
{
	$sub_link_args = [
		'section'			=> 'pending_items',
		'property_id'		=> $search_by_property_id,
		'inspection_type'	=> $search_by_inspection_type,
		'job_type'			=> $search_by_job_type,
		'date_inspected'	=> $search_by_date_inspected,
		'year'				=> $search_by_year
	];
	if ($search_by_job_type == 0)
		$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="'.$URL->genLink('hca_ui_print', $sub_link_args).'" target="_blank"><i class="fa fa-file-pdf-o fa-1x" aria-hidden="true"></i> Print as PDF</a></li>');

	$Core->set_page_id('hca_ui_summary_report', 'hca_ui');
	require SITE_ROOT.'header.php';
?>

<nav class="navbar alert-info mb-1">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="year" class="form-select form-select-sm">
						<option value="0">All Years</option>
<?php
	for ($year = 2021; $year <= date('Y'); $year++)
	{
				if ($search_by_year == $year)
					echo '<option value="'.$year.'" selected="selected">'.$year.'</option>';
				else
					echo '<option value="'.$year.'">'.$year.'</option>';
	}
?>
					</select>
					<p class="text-muted" for="fld_date_inspected">Period</p>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="inspection_type" class="form-select-sm">
<?php
	$inspection_types = [
		0 => 'All inspections',
		1 => 'Water Audit',
		2 => 'Flapper Replacement',
	];
	foreach ($inspection_types as $key => $val)
	{
		if ($search_by_inspection_type == $key)
			echo '<option value="'.$key.'" selected>'.$val.'</option>';
		else
			echo '<option value="'.$key.'">'.$val.'</option>';
	}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select-sm" id="fld_property_id">
<?php
	foreach ($sm_property_db as $val)
	{
		if ($search_by_property_id == $val['id'])
			echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
		else
			echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
	}
?>
					</select>
					<p class="text-muted" for="fld_property_id">List of properties</p>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<div class="form-check">
						<input class="form-check-input" type="radio" name="job_type" value="0" id="rd_job_type1" <?=($search_by_job_type == 0 ? 'checked' : '')?>>
						<label class="form-check-label" for="rd_job_type1">Pending</label>
					</div>
					<div class="form-check">
						<input class="form-check-input" type="radio" name="job_type" value="1" id="rd_job_type2" <?=($search_by_job_type == 1 ? 'checked' : '')?>>
						<label class="form-check-label" for="rd_job_type2">Completed</label>
					</div>
				</div>

				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->genLink('hca_ui_summary_report', ['property_id' => $search_by_property_id, 'inspection_type' => $search_by_inspection_type]) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="row mb-3">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0 text-primary">Summary Report</h6>
			</div>
			<div class="card-body py-3">
				<div class="chart chart-sm">
					<canvas id="chartjs-dashboard-pie-pillars"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
	$search_query = [];
	$search_query[] = 'i.summary_report=1';
	if ($search_by_job_type == 0) {
		$search_query[] = 'ch.inspection_completed=2';
		$search_query[] = 'ch.work_order_completed=1';
	}
	$search_query[] = 'ch.num_problem > 0';
	if ($search_by_property_id > 0)
		$search_query[] = 'ch.property_id='.$search_by_property_id;

	if ($search_by_inspection_type == 1)
		$search_query[] = 'ch.type_audit=1';
	if ($search_by_inspection_type == 2)
		$search_query[] = 'ch.type_flapper=1';

	if ($search_by_item_id > 0)
		$search_query[] = 'ci.item_id='.$search_by_item_id;
	if ($search_by_date_inspected != '')
		$search_query[] = 'DATE(ch.date_inspected)=\''.$DBLayer->escape($search_by_date_inspected).'\'';
	if ($search_by_year > 0)
		$search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($search_by_year).'\'';

	if ($search_by_job_type == 0)
		$search_query[] = 'ci.job_type=0';
	else if ($search_by_job_type == 1)
		$search_query[] = 'ci.job_type=1';

	$query = [
		'SELECT'	=> 'ci.item_id, ci.job_type, ci.checklist_id, ch.property_id, ch.num_problem, ch.num_pending, ch.num_replaced, ch.num_repaired, ch.num_reset, ch.inspection_completed, ch.work_order_completed, ch.work_order_comment, ch.date_inspected, p.pro_name, un.unit_number, un.mbath, un.hbath',
		'FROM'		=> 'hca_ui_checklist_items AS ci',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'hca_ui_checklist AS ch',
				'ON'			=> 'ch.id=ci.checklist_id'
			],
			[
				'INNER JOIN'	=> 'hca_ui_items AS i',
				'ON'			=> 'i.id=ci.item_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=ch.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=ch.unit_id'
			],
		],
		'ORDER BY'	=> 'i.display_position'
	];
	$query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$has_mbath = $has_hbath = false;
	$num_pending = $hca_ui_checklist_ids = $hca_ui_checklist_dates = [];
	while($row = $DBLayer->fetch_assoc($result))
	{
		if (isset($num_pending[$row['item_id']]))
			++$num_pending[$row['item_id']];
		else
			$num_pending[$row['item_id']] = 1;

		if (!$has_mbath && $row['mbath'] == 1)
			$has_mbath = true;

		if (!$has_hbath && $row['hbath'] == 1)
			$has_hbath = true;

		$hca_ui_checklist_ids[$row['checklist_id']] = $row['checklist_id'];
		$hca_ui_checklist_dates[$row['date_inspected']] = $row['checklist_id'];
	}
	
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_ui_items AS i',
		'WHERE'		=> 'i.summary_report=1',
		'ORDER BY'	=> 'i.location_id, i.display_position'
	);
	if ($search_by_item_id > 0)
		$query['WHERE'] = 'i.summary_report=1 AND i.id='.$search_by_item_id;
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_ui_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_ui_items[] = $row;
	}

	$checklist_date_links = [];
	if (!empty($hca_ui_checklist_dates))
	{
		ksort($hca_ui_checklist_dates);
		foreach($hca_ui_checklist_dates as $key => $value)
		{
			$sub_link_args = [
				'property_id'		=> $search_by_property_id,
				'inspection_type'	=> $search_by_inspection_type,
				'item_id'			=> $search_by_item_id,
				'job_type'			=> $search_by_job_type,
				'date_inspected'	=> $key
			];

			$checklist_date_links[] = '<a href="'.$URL->genLink('hca_ui_summary_report', $sub_link_args).'" class="btn btn-outline-primary" role="button">'.$key.'</a>';
		}
	}
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">Inspection dates</h6>
</div>	
<div class="card mb-3">
	<div class="card-body">
		<?php echo implode("\n", $checklist_date_links) ?>
	</div>
</div>	


<?php if ($search_by_job_type == 0): ?>
<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of Pending items</h6>
</div>	
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Part #</th>
			<th>Location - Item name</th>
			<th class="<?=($search_by_job_type == 1 ? 'bg-success' : 'bg-danger')?> text-white"><?=($search_by_job_type == 1 ? 'Replaced' : 'Pending')?></th>
		</tr>
	</thead>
	<tbody>
<?php
	$total_pending = 0;
	foreach($hca_ui_items as $cur_info)
	{
		if ($cur_info['location_id'] < 3 || $cur_info['location_id'] == 100 || ($has_mbath && $cur_info['location_id'] == 3) || ($has_hbath && $cur_info['location_id'] == 4))
		{
			if (isset($num_pending[$cur_info['id']]))
			{
				$item_name = $HcaUnitInspection->getLocation($cur_info['location_id']).' -> '.$HcaUnitInspection->getEquipment($cur_info['equipment_id']).' -> '.html_encode($cur_info['item_name']);
				
				$sub_link_args = [
					'property_id'		=> $search_by_property_id,
					'inspection_type'	=> $search_by_inspection_type,
					'item_id'			=> $cur_info['id'],
					'job_type'			=> $search_by_job_type,
				];
?>
		<tr>
			<td class="fw-bold"><?=html_encode($cur_info['part_number'])?></td>
			<td class="fw-bold"><a href="<?=$URL->genLink('hca_ui_summary_report', $sub_link_args)?>"><?=$item_name?></a></td>
			<td class="ta-center fw-bold"><?=$num_pending[$cur_info['id']]?></td>
		</tr>
<?php
				$total_pending = $total_pending + $num_pending[$cur_info['id']];
			}
		}
	}
?>
	</tbody>
<?php if ($access_admin): ?>
	<tfoot>
		<tr>
			<td></td>
			<td class="ta-right fw-bold">Total: </td>
			<td class="ta-center fw-bold"><?php echo $total_pending ?></td>
		</tr>
	</tfoot>
<?php endif; ?>
</table>
<?php endif; ?>


<?php

	$search_query = [];
	if ($search_by_property_id > 0)
		$search_query[] = 'c.property_id='.$search_by_property_id;

	if ($search_by_job_type == 1) // completed
		$search_query[] = 'c.inspection_completed=2 AND c.work_order_completed=2';
	else // pending WO
		$search_query[] = 'c.work_order_completed=1 AND c.inspection_completed=2 AND c.num_problem > 0';

	$hca_ui_checklist = []; //$HcaUISummaryReport->genWorkOrderReport();
	$query = [
		'SELECT'	=> 'c.*, p.pro_name, un.unit_number, u1.realname AS owner_name, u2.realname AS inspected_name, u3.realname AS completed_name, u4.realname AS updated_name, u5.realname AS started_name',
		'FROM'		=> 'hca_ui_checklist as c',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=c.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=c.unit_id'
			],
			[
				'LEFT JOIN'		=> 'users AS u1',
				'ON'			=> 'u1.id=c.owned_by'
			],
			[
				'LEFT JOIN'		=> 'users AS u2',
				'ON'			=> 'u2.id=c.inspected_by'
			],
			[
				'LEFT JOIN'		=> 'users AS u3',
				'ON'			=> 'u3.id=c.completed_by'
			],
			[
				'LEFT JOIN'		=> 'users AS u4',
				'ON'			=> 'u4.id=c.updated_by'
			],
			[
				'LEFT JOIN'		=> 'users AS u5',
				'ON'			=> 'u5.id=c.started_by'
			],
		],
		//'ORDER BY'	=> 'c.completed, c.status, p.pro_name, LENGTH(un.unit_number), un.unit_number',
		'ORDER BY'	=> 'c.inspection_completed, c.work_order_completed, p.pro_name, LENGTH(un.unit_number), un.unit_number', //mycolumn=0 desc, mycolumn desc
	];
	if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_ui_checklist[] = $row;
	}


	$unispected_units = [];
	if (!empty($hca_ui_checklist))
	{
		foreach($hca_ui_checklist as $checklist)
			$projects_ids[] = $checklist['id'];

		$query = array(
			'SELECT'	=> 'ci.*, i.item_name, i.req_appendixb',
			'FROM'		=> 'hca_ui_checklist_items AS ci',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'hca_ui_items AS i',
					'ON'			=> 'i.id=ci.item_id'
				],
			],
			'WHERE'		=> 'i.summary_report=1 AND ci.checklist_id IN ('.implode(',', $projects_ids).')'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$hca_ui_checklist_items[] = $row;
		}
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary"><?=($search_by_job_type == 1 ? 'Replaced' : 'Pending')?> list of work orders (<?php echo count($hca_ui_checklist) ?>)</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property name</th>
			<th>Unit#</th>
			<th>Identified Problems</th>
			<th>Current Owner</th>
			<th>Comment</th>
<?php if ($search_by_job_type == 0): ?>
			<th class="bg-danger text-white">Pending</th>
<?php endif; ?>
<?php if ($search_by_job_type == 1): ?>
			<th class="bg-primary text-white">Replaced</th>
			<th class="bg-info text-white">Repaired</th>
<?php endif; ?>
		</tr>
	</thead>
	<tbody>

<?php
		$wo_total_pending = $wo_total_replaced = $wo_total_repaired = 0;
		foreach($hca_ui_checklist as $cur_info)
		{
			$num_pending = $num_replaced = $num_repaired = 0;

			if ($cur_info['num_problem'] > 0 && $cur_info['work_order_completed'] == 2)
				$status = '<a href="'.$URL->link('hca_ui_work_order', $cur_info['id']).'" class="badge bg-success text-white">Completed</a>';
			else if ($cur_info['num_problem'] == 0 && $cur_info['inspection_completed'] == 2)
				$status = '<a href="'.$URL->link('hca_ui_checklist', $cur_info['id']).'" class="badge bg-success text-white">Completed</a>';
			else if ($cur_info['num_problem'] > 0 && $cur_info['work_order_completed'] == 1)
				$status = '<a href="'.$URL->link('hca_ui_work_order', $cur_info['id']).'" class="badge bg-primary text-white">Pending</a>';
			else
				$status = '<a href="'.$URL->link('hca_ui_checklist', $cur_info['id']).'" class="badge bg-primary text-white">Pending</a>';

			$list_of_problems = [];
			if (!empty($hca_ui_checklist_items))
			{
				foreach($hca_ui_checklist_items as $checklist_items)
				{
					if ($cur_info['id'] == $checklist_items['checklist_id'])
					{
						$status_OR_problems = ($checklist_items['job_type'] > 0) ? ' (<span class="text-success">'.$HcaUnitInspection->getJobType($checklist_items['job_type']).'</span>)' : ' (<span class="text-danger">'.$HcaUnitInspection->getItemProblems($checklist_items['problem_ids']).'</span>)';
	
						$list_of_problems[] = '<p class="text-primary">'.$checklist_items['item_name'].$status_OR_problems.'</p>';

						if ($checklist_items['job_type'] == 0)
							++$num_pending;
						else if ($checklist_items['job_type'] == 1)
							++$num_replaced;
						else if ($checklist_items['job_type'] == 2)
							++$num_repaired;
					}
				}
			}
?>
		<tr>
			<td class="fw-bold">
				<?php echo html_encode($cur_info['pro_name']) ?>
				<span class="float-end"><?php echo $status ?></span>
			</td>
			<td class="ta-center fw-bold"><?php echo html_encode($cur_info['unit_number']) ?></td>
			<td><?php echo implode("\n", $list_of_problems) ?></td>
			<td class="ta-center fw-bold"><?php echo html_encode($cur_info['owner_name']) ?></td>
			<td class=""><?php echo html_encode($cur_info['work_order_comment']) ?></td>
<?php if ($search_by_job_type == 0): ?>
			<td class="ta-center fw-bold"><?php echo $num_pending ?></td>
<?php endif; ?>

<?php if ($search_by_job_type == 1): ?>
			<td class="ta-center fw-bold"><?php echo $num_replaced ?></td>
			<td class="ta-center fw-bold"><?php echo $num_repaired ?></td>
<?php endif; ?>
		</tr>
<?php
			$wo_total_pending = $wo_total_pending + $num_pending;
			$wo_total_repaired = $wo_total_repaired + $num_repaired;
			$wo_total_replaced = $wo_total_replaced + $num_replaced;
		}
?>
	</tbody>

<?php if ($access_admin): ?>
	<tfoot>
		<tr>
			<td class="ta-right fw-bold" colspan="5">Total: </td>
<?php if ($search_by_job_type == 0): ?>
			<td class="ta-center fw-bold"><?php echo $wo_total_pending ?></td>
<?php endif; ?>

<?php if ($search_by_job_type == 1): ?>
			<td class="ta-center fw-bold"><?php echo $wo_total_replaced ?></td>
			<td class="ta-center fw-bold"><?php echo $wo_total_repaired ?></td>
<?php endif; ?>
		</tr>
	</tfoot>
<?php endif; ?>

</table>

<?php

		$unispected_units_checklist = $search_query = [];
		if ($search_by_property_id > 0)
			$search_query[] = 'ch.property_id='.$search_by_property_id;

		if ($search_by_inspection_type == 1)
			$search_query[] = 'ch.type_audit=1';
		if ($search_by_inspection_type == 2)
			$search_query[] = 'ch.type_flapper=1';

		if ($search_by_year > 0)
			$search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($search_by_year).'\'';

		// Get never inspected units
		// Add limit by last period
		$query = array(
			'SELECT'	=> 'ch.unit_id',
			'FROM'		=> 'hca_ui_checklist AS ch',
			//'WHERE'		=> 'ch.property_id='.$search_by_property_id,
		);
		if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$unispected_units_checklist[$row['unit_id']] = $row['unit_id'];
		}

		$query = array(
			'SELECT'	=> 'un.id, un.unit_number',
			'FROM'		=> 'sm_property_units AS un',
			'WHERE'		=> 'un.property_id='.$search_by_property_id,
			'ORDER BY'	=> 'LENGTH(un.unit_number), un.unit_number',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if (!in_array($row['id'], $unispected_units_checklist))
				$unispected_units[] = $row['unit_number'];
		}
?>
<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of never inspected units (<?php echo count($unispected_units) ?>)</h6>
</div>
<div class="mb-3">
	<div class="alert alert-info mb-0 py-2" role="alert">
		<p class="text-muted">This unit list displays never inspected units for the selected period. This takes into all Plumbing Inspections and any Work Order statuses.</p>
	</div>
	<div class="alert alert-warning" role="alert">
		<p class="fw-bold"><?php echo implode(', ', $unispected_units) ?></p>
	</div>
</div>

<?php

	}
	else
	{
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of Work Orders</h6>
</div>
<div class="my-3">
	<div class="alert alert-warning mb-3" role="alert">No Work Orders found.</div>
</div>
<?php
	}

	// Completed: WO, items, repaired
	if ($search_by_job_type == 1)
	{
		$chart_column_label_1 = '"Completed Work Orders"';
		$chart_column_label_2 = '"Replaced items"';
		$chart_column_label_3 = '"Repaired items"';

		$chart_column_total_1 = $summary_pending;
		$chart_column_total_2 = $summary_repaired;
		$chart_column_total_3 = $summary_replaced;

		$chart_column_color_1 = 'window.theme.success';
		$chart_column_color_2 = 'window.theme.primary';
		$chart_column_color_3 = 'window.theme.info';
	}
	else // Pending WO, items, never inspected units
	{
		$chart_column_label_1 = '"Pending Work Orders"';
		$chart_column_label_2 = '"Pending items"';
		$chart_column_label_3 = '"Never inspected units"';

		$chart_column_total_1 = count($hca_ui_checklist);
		$chart_column_total_2 = $summary_pending;
		$chart_column_total_3 = count($unispected_units);

		$chart_column_color_1 = 'window.theme.warning';
		$chart_column_color_2 = 'window.theme.danger';
		$chart_column_color_3 = 'window.theme.secondary';
	}

?>

<script src="<?=BASE_URL?>/vendor/chartjs/dist/chart.js"></script>
<script src="<?=BASE_URL?>/vendor/app.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
	// Pie chart
	new Chart(document.getElementById("chartjs-dashboard-pie-pillars"), {
		type: "bar",
		data: {
			labels: [<?=$chart_column_label_1?>, <?=$chart_column_label_2?>, <?=$chart_column_label_3?>],
			datasets: [{
				data: [<?=$chart_column_total_1?>, <?=$chart_column_total_2?>, <?=$chart_column_total_3?>],
				backgroundColor: [<?=$chart_column_color_1?>, <?=$chart_column_color_2?>, <?=$chart_column_color_3?>],
				borderWidth: 5
			}]
		},
		options: {
			responsive: !window.MSInputMethodContext,
			maintainAspectRatio: false,
			legend: {
				display: false
			},
			cutoutPercentage: 75,
			hover: {
				animationDuration: 1
			},
			/* Dispaly numbers */
			animation: {
				duration: 500,
				easing: "easeOutQuart",
				onComplete: function () {
					var ctx = this.chart.ctx;
					ctx.font = Chart.helpers.fontString(
						Chart.defaults.global.defaultFontFamily, 
						'normal', 
						Chart.defaults.global.defaultFontFamily);
					ctx.textAlign = 'center';
					ctx.textBaseline = 'bottom';

					this.data.datasets.forEach(function (dataset) {
						for (var i = 0; i < dataset.data.length; i++) {
							var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
								scale_max = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._yScale.maxHeight;
							ctx.fillStyle = '#444';
							var y_pos = model.y - 1;
							// Make sure data value does not get overflown and hidden
							// when the bar's value is too close to max value of scale
							// Note: The y value is reverse, it counts from top down
							if ((scale_max - model.y) / scale_max >= 0.93)
								y_pos = model.y + 20; 
							ctx.fillText(dataset.data[i], model.x, y_pos);
						}
					});               
				}
			}
		}
	});
});
</script>
<?php
}
require SITE_ROOT.'footer.php';
