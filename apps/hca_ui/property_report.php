<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

//$access_admin = ($User->is_guest()) ? true : false;
$access6 = ($User->checkAccess('hca_ui', 6)) ? true : false;
//if (!$access6)
//	message($lang_common['No permission']);

$search_by_inspection_type = isset($_GET['inspection_type']) ? intval($_GET['inspection_type']) : 0; // 0 all, 1 audit, 2 flapper
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_item_id  = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$search_by_job_type  = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0; // 0 - pending, 1 - completed
$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;
$search_by_date_inspected = isset($_GET['date_inspected']) ? swift_trim($_GET['date_inspected']) : '';

$HcaUnitInspection = new HcaUnitInspection;
$HcaUIPropertyReport = new HcaUIPropertyReport;

// IF PROPERTY SELECTED
if ($search_by_property_id > 0)
{
	$query = [
		'SELECT'	=> 'p.pro_name, p.manager_email',
		'FROM'		=> 'sm_property_db AS p',
		'WHERE'		=> 'p.id='.$search_by_property_id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$cur_property_info = $DBLayer->fetch_assoc($result);

	// Get all items found in inspections
	$HcaUIPropertyReport->genItemsData();

	// Generate parameters for Printing List of Items
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

	$email_param = [];
	$email_param[] = 'mailto:'.html_encode($cur_property_info['manager_email']);
	$email_param[] = '?subject=HCA: Plumbing Inspections - Summary Report';
	$email_param[] = '&amp;body=Hello,';
	$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
	$email_param[] = 'This email contains a link to Plumbing Inspections of '.html_encode($cur_property_info['pro_name']).'. To view the report, follow the link below:';
	$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
	$email_param[] = str_replace('&', '%26', $URL->genLink('hca_ui_property_report', $sub_link_args));

	$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="'.implode('', $email_param).'" target="_blank"><i class="fa fa-at" aria-hidden="true"></i> Send Email</a></li>');

	$Core->set_page_id('hca_ui_property_report', 'hca_ui');
	require SITE_ROOT.'header.php';
?>


<nav class="navbar alert-info mb-1 <?=($User->is_guest() ? 'hidden' : '')?>">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
				<select name="year" class="form-select form-select-sm">
<?php
	foreach($HcaUnitInspection->getPeriods(2022) as $key => $value)
	{
		if ($search_by_year == $key)
			echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
		else
			echo '<option value="'.$key.'">'.$value.'</option>';
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
	foreach ($HcaUnitInspection->getProperties() as $property)
	{
		if ($search_by_property_id == $property['id'])
			echo '<option value="'.$property['id'].'" selected>'.html_encode($property['pro_name']).'</option>';
		else
			echo '<option value="'.$property['id'].'">'.html_encode($property['pro_name']).'</option>';
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
					<a href="<?php echo $URL->genLink('hca_ui_property_report', ['property_id' => $search_by_property_id, 'inspection_type' => $search_by_inspection_type]) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
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


<div class="card-header">
	<h6 class="card-title mb-0 text-primary">Inspection dates</h6>
</div>	
<div class="card mb-3">
	<div class="card-body">
		<?php echo $HcaUIPropertyReport->implodeInspectedDates() ?>
	</div>
</div>	


<?php
$total_items_pending = $total_items_replaced = $total_items_repaired = 0;
if ($search_by_job_type == 1)
{
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of Replaced & Repaired items</h6>
</div>	
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Part #</th>
			<th>Location - Item name</th>
			<th class="bg-primary">Replaced</th>
			<th class="bg-info">Repaired</th>
		</tr>
	</thead>
	<tbody>

<?php
	foreach($HcaUIPropertyReport->hca_ui_items as $cur_info)
	{
		//if ($cur_info['location_id'] < 3 || $cur_info['location_id'] == 100 || ($HcaUIPropertyReport->has_mbath && $cur_info['location_id'] == 3) || ($HcaUIPropertyReport->has_hbath && $cur_info['location_id'] == 4))
		//{
			$num_items_replaced = isset($HcaUIPropertyReport->num_items_replaced[$cur_info['item_id']]) ? $HcaUIPropertyReport->num_items_replaced[$cur_info['item_id']] : 0;

			$num_items_repaired = isset($HcaUIPropertyReport->num_items_repaired[$cur_info['item_id']]) ? $HcaUIPropertyReport->num_items_repaired[$cur_info['item_id']] : 0;

			if ($num_items_replaced > 0 || $num_items_repaired > 0)
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
			<td class="fw-bold"><a href="<?=$URL->genLink('hca_ui_property_report', $sub_link_args)?>"><?=$item_name?></a></td>
			<td class="ta-center fw-bold"><?=$num_items_replaced?></td>
			<td class="ta-center fw-bold"><?=$num_items_repaired?></td>
		</tr>
<?php
				$total_items_replaced = $total_items_replaced + $num_items_replaced;
				$total_items_repaired = $total_items_repaired + $num_items_repaired;
			}
		//}
	}
?>
	</tbody>
	<tfoot>
		<tr>
			<td></td>
			<td class="ta-right fw-bold">Total: </td>
			<td class="ta-center fw-bold"><?php echo $total_items_replaced ?></td>
			<td class="ta-center fw-bold"><?php echo $total_items_repaired ?></td>
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
?>

<div class="card-header d-flex justify-content-between">
	<h6 class="card-title mb-0">List of pending items</h6>
	<a href="<?=$URL->genLink('hca_ui_print', $sub_link_args)?>" target="_blank"><i class="fas fa-print fa-lg"></i></a>
</div>	
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Part #</th>
			<th>Location - Item name</th>
			<th class="bg-danger">Pending</th>
		</tr>
	</thead>
	<tbody>

<?php
	foreach($HcaUIPropertyReport->hca_ui_items as $cur_info)
	{
		//if ($cur_info['location_id'] < 3 || $cur_info['location_id'] == 100 || ($HcaUIPropertyReport->has_mbath && $cur_info['location_id'] == 3) || ($HcaUIPropertyReport->has_hbath && $cur_info['location_id'] == 4))
		//{
			if (isset($HcaUIPropertyReport->num_items_pending[$cur_info['item_id']]))
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
			<td class="fw-bold"><a href="<?=$URL->genLink('hca_ui_property_report', $sub_link_args)?>"><?=$item_name?></a></td>
			<td class="ta-center fw-bold"><?=$HcaUIPropertyReport->num_items_pending[$cur_info['item_id']]?></td>
		</tr>
<?php
				$total_items_pending = $total_items_pending + $HcaUIPropertyReport->num_items_pending[$cur_info['item_id']];
			}
		//}
	}
?>
	</tbody>
	<tfoot>
		<tr>
			<td></td>
			<td class="ta-right fw-bold">Total: </td>
			<td class="ta-center fw-bold"><?php echo $total_items_pending ?></td>
		</tr>
	</tfoot>
</table>
<?php 
}
	// 1. Get all Inspections & WO first
	$HcaUIPropertyReport->getInspectionsData();
	// 2. Get WO by ids
	$HcaUIPropertyReport->getWorkOrderData();



	//$HcaUIPropertyReport->getChecklistData();

	$number_work_orders = $wo_total_pending = $wo_total_replaced = $wo_total_repaired = 0;

	if (!empty($HcaUIPropertyReport->work_orders_info))
	{
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of Work Orders (<?php echo count($HcaUIPropertyReport->work_orders_info) ?>)</h6>
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

		foreach($HcaUIPropertyReport->work_orders_info as $cur_info)
		{
			$num_pending = $num_replaced = $num_repaired = 0;

			if ($cur_info['work_order_completed'] == 2)
				$status = '<a href="'.$URL->link('hca_ui_work_order', $cur_info['id']).'" class="badge badge-success">Completed</a>';
			//else if ($cur_info['num_problem'] == 0 && $cur_info['inspection_completed'] == 2)
			//	$status = '<a href="'.$URL->link('hca_ui_checklist', $cur_info['id']).'" class="badge badge-success text-white">Completed</a>';
			//else if ($cur_info['num_problem'] > 0 && $cur_info['work_order_completed'] == 1)
			//	$status = '<a href="'.$URL->link('hca_ui_work_order', $cur_info['id']).'" class="badge badge-primary text-white">Pending</a>';
			else
				$status = '<a href="'.$URL->link('hca_ui_work_order', $cur_info['id']).'" class="badge badge-primary">Pending</a>';

			$list_of_problems = [];
			if (!empty($HcaUIPropertyReport->hca_ui_checklist_items))
			{
				foreach($HcaUIPropertyReport->hca_ui_checklist_items as $checklist_items)
				{
					if ($cur_info['id'] == $checklist_items['checklist_id'])
					{
						$status_OR_problems = ($checklist_items['job_type'] > 0) ? ' (<span class="text-success">'.$HcaUnitInspection->getJobType($checklist_items['job_type']).'</span>)' : ' (<span class="text-danger">'.$HcaUnitInspection->getItemProblems($checklist_items['problem_ids']).'</span>)';
	
						$item_title = [
							$HcaUnitInspection->getLocation($checklist_items['location_id']),
							$HcaUnitInspection->getEquipment($checklist_items['equipment_id']),
							$checklist_items['item_name'],
						];
						$list_of_problems[] = '<p class="text-primary">'.implode(' -> ', $item_title) . $status_OR_problems.'</p>';

						if ($checklist_items['job_type'] == 0)
							++$num_pending;
						else if ($checklist_items['job_type'] == 1)
							++$num_replaced;
						else if ($checklist_items['job_type'] == 2)
							++$num_repaired;
					}
				}
			}

			//if ($search_by_job_type == 1 && $cur_info['work_order_completed'] == 2 || $search_by_job_type == 0 && $num_pending > 0)
			//{
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
				++$number_work_orders;
			//}

			$wo_total_pending = $wo_total_pending + $num_pending;
			$wo_total_repaired = $wo_total_repaired + $num_repaired;
			$wo_total_replaced = $wo_total_replaced + $num_replaced;
		}
?>
	</tbody>
	<tfoot>
		<tr>
			<td class="ta-center fw-bold"><?php echo $number_work_orders ?></td>
			<td class="ta-right fw-bold" colspan="4"></td>
<?php if ($search_by_job_type == 0): ?>
			<td class="ta-center fw-bold"><?php echo $wo_total_pending ?></td>
<?php endif; ?>

<?php if ($search_by_job_type == 1): ?>
			<td class="ta-center fw-bold"><?php echo $wo_total_replaced ?></td>
			<td class="ta-center fw-bold"><?php echo $wo_total_repaired ?></td>
<?php endif; ?>
		</tr>
	</tfoot>
</table>
<?php
	}
	else
	{
?>
<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of Work Orders</h6>
</div>
<div class="mb-3">
	<div class="alert alert-warning py-2" role="alert">No Work Orders found.</div>
</div>
<?php
	}

	
	if (!empty($HcaUIPropertyReport->inspections_info))
	{
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of Pending Inspections (<?php echo count($HcaUIPropertyReport->inspections_info) ?>)</h6>
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
		</tr>
	</thead>
	<tbody>

<?php

		$i = $inspection_items_pending = 0;
		foreach($HcaUIPropertyReport->inspections_info as $cur_info)
		{
			$num_pending = 0;

			//if ($cur_info['num_problem'] > 0 && $cur_info['work_order_completed'] == 2)
			//	$status = '<a href="'.$URL->link('hca_ui_work_order', $cur_info['id']).'" class="badge badge-success">Completed WO</a>';
			if ($cur_info['inspection_completed'] == 2)
				$status = '<a href="'.$URL->link('hca_ui_checklist', $cur_info['id']).'" class="badge badge-success">Completed</a>';
			//else if ($cur_info['num_problem'] > 0 && $cur_info['work_order_completed'] == 1)
			//	$status = '<a href="'.$URL->link('hca_ui_work_order', $cur_info['id']).'" class="badge badge-primary">Pending WO</a>';
			else
				$status = '<a href="'.$URL->link('hca_ui_checklist', $cur_info['id']).'" class="badge badge-warning">Pending</a>';

			$list_of_problems = [];
			if (!empty($HcaUIPropertyReport->hca_ui_checklist_items))
			{
				foreach($HcaUIPropertyReport->hca_ui_checklist_items as $checklist_items)
				{
					if ($cur_info['id'] == $checklist_items['checklist_id'])
					{
						$status_OR_problems = ($checklist_items['job_type'] > 0) ? ' (<span class="text-success">'.$HcaUnitInspection->getJobType($checklist_items['job_type']).'</span>)' : ' (<span class="text-danger">'.$HcaUnitInspection->getItemProblems($checklist_items['problem_ids']).'</span>)';
	
						$item_title = [
							$HcaUnitInspection->getLocation($checklist_items['location_id']),
							$HcaUnitInspection->getEquipment($checklist_items['equipment_id']),
							$checklist_items['item_name'],
						];
						$list_of_problems[] = '<p class="text-primary">'.implode(' -> ', $item_title) . $status_OR_problems.'</p>';


						if ($checklist_items['job_type'] == 0)
							++$num_pending;
					}
				}
			}

			//if ($search_by_job_type == 1 && $cur_info['work_order_completed'] == 2 || $search_by_job_type == 0 && $num_pending > 0)
			//{
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
		</tr>
<?php
				++$i;
				$inspection_items_pending = $inspection_items_pending + $num_pending;
			//}

		}
?>
	</tbody>
	<tfoot>
		<tr>
			<td class="ta-center fw-bold"><?=$i?></td>
			<td class="ta-right fw-bold" colspan="4"></td>
<?php if ($search_by_job_type == 0): ?>
			<td class="ta-center fw-bold"><?php echo $inspection_items_pending ?></td>
<?php endif; ?>
		</tr>
	</tfoot>
</table>
<?php
	}
	else if ($search_by_job_type == 0)
	{
?>
<div class="card-header">
	<h6 class="card-title mb-0 text-primary">Inspections</h6>
</div>
<div class="mb-3">
	<div class="alert alert-warning py-2" role="alert">No Pending Inspections found.</div>
</div>
<?php
	}


	if ($search_by_job_type == 0)
	{
		$HcaUIPropertyReport->getNeverInspectedUnits();
?>
<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of never inspected units (<?php echo $HcaUIPropertyReport->num_never_inspected ?>)</h6>
</div>
<div class="mb-3">
<?php if (!empty($HcaUIPropertyReport->unispected_units)): ?>
	<div class="callout callout-info">
		<h6 class="text-muted">List of never inspected units for the selected period. To check Unit History click on the links below.</h6>
		<p class="fw-bold"><?php echo implode(' ', $HcaUIPropertyReport->unispected_units) ?></p>
	</div>
<?php else: ?>
	<div class="alert alert-warning py-2" role="alert">
		<p>The property does not have any units that have never been inspected for selected period.</p>
	</div>
<?php endif; ?>
</div>

<?php
	}


	// Completed: WO, items, repaired
	if ($search_by_job_type == 1)
	{
		$chart_column_label_1 = '"Completed Work Orders"';
		$chart_column_label_2 = '"Replaced items"';
		$chart_column_label_3 = '"Repaired items"';

		$chart_column_total_1 = $number_work_orders;
		$chart_column_total_2 = $wo_total_replaced;
		$chart_column_total_3 = $wo_total_repaired;

		$chart_column_color_1 = 'window.theme.success';
		$chart_column_color_2 = 'window.theme.primary';
		$chart_column_color_3 = 'window.theme.info';
	}
	else // Pending WO, items, never inspected units
	{
		$chart_column_label_1 = '"Pending Work Orders"';
		$chart_column_label_2 = '"Pending items"';
		$chart_column_label_3 = '"Never inspected units"';

		$chart_column_total_1 = $number_work_orders;
		$chart_column_total_2 = $total_items_pending;
		$chart_column_total_3 = $HcaUIPropertyReport->num_never_inspected;

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
