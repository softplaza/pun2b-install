<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

//$access_admin = ($User->is_guest()) ? true : false;
$access6 = ($User->checkAccess('hca_hvac_inspections', 6)) ? true : false;
//if (!$access6)
//	message($lang_common['No permission']);

$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_job_type  = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0; // 0 - pending, 1 - completed
$search_by_datetime_inspection_start = isset($_GET['datetime_inspection_start']) ? swift_trim($_GET['datetime_inspection_start']) : '';
//$search_by_item_id  = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
//$search_by_filter_id  = isset($_GET['filter_id']) ? intval($_GET['filter_id']) : 0;

$HcaHVACInspections = new HcaHVACInspections;
$HcaHVACPropertyReport = new HcaHVACPropertyReport;

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

	$HcaHVACPropertyReport->genChecklistData();

	$Core->set_page_id('hca_hvac_inspections_property_report', 'hca_hvac_inspections');
	require SITE_ROOT.'header.php';
?>

<nav class="navbar alert-info mb-1 <?=($User->is_guest() ? 'hidden' : '')?>">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
				<select name="year" class="form-select form-select-sm">
<?php
	foreach($HcaHVACInspections->getPeriods(2022) as $key => $value)
	{
		if ($search_by_year == $key)
			echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
		else
			echo '<option value="'.$key.'">'.$value.'</option>';
	}
?>
					</select>
					<p class="text-muted">Period</p>
				</div>
				<div class="col-md-auto pe-0 mb-1 hidden">
					<select name="property_id" class="form-select-sm" id="fld_property_id">
<?php
	foreach ($HcaHVACInspections->getProperties() as $property)
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
					<a href="<?php echo $URL->genLink('hca_hvac_inspections_property_report', ['property_id' => $search_by_property_id]) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="row mb-3">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0 text-primary"><?php echo html_encode($cur_property_info['pro_name']) ?> Property Report</h6>
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
		<?php echo $HcaHVACPropertyReport->implodeInspectedDates() ?>
	</div>
</div>	


<?php
	//if ($search_by_job_type == 1)
	//{
		//print_dump()
		$HcaHVACPropertyReport->genFiltersData();

		$hca_hvac_inspections_filters = [];
		$query = [
			'SELECT'	=> 'f.*',
			'FROM'		=> 'hca_hvac_inspections_filters AS f',
			'WHERE'		=> 'f.property_id='.$search_by_property_id
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while($row = $DBLayer->fetch_assoc($result))
		{
			$hca_hvac_inspections_filters[] = $row;
		}

?>
<div class="card-header">
	<h6 class="card-title mb-0 text-primary">AC Filters</h6>
</div>	
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Filter Size</th>
			<th class="">Total</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($hca_hvac_inspections_filters as $cur_info)
	{
		$num_replaced = isset($HcaHVACPropertyReport->replaced_filters[$cur_info['filter_size']]) ? $HcaHVACPropertyReport->replaced_filters[$cur_info['filter_size']] : 0;
		if ($num_replaced > 0)
		{
?>
		<tr>
			<td class="ta-center fw-bold"><?php echo html_encode($cur_info['filter_size']) ?></td>
			<td class="ta-center fw-bold"><?=$num_replaced?></td>
		</tr>
<?php
		}
	}
?>
	</tbody>
	<tfoot>
		<tr>
			<td class="ta-center fw-bold"></td>
			<td class="ta-center fw-bold"><?=$HcaHVACPropertyReport->num_filters_replaced?></td>
		</tr>
	</tfoot>
</table>
<?php
	//}


	if (!empty($HcaHVACPropertyReport->pending_work_orders_info) && $search_by_job_type == 0)
	{
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">Pending Work Orders (<?php echo count($HcaHVACPropertyReport->pending_work_orders_info) ?>)</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property, Unit#</th>
			<th>Owner</th>
			<th>Comment</th>
			<th>Filter size</th>
		</tr>
	</thead>
	<tbody>

<?php
		foreach($HcaHVACPropertyReport->pending_work_orders_info as $cur_info)
		{



?>
		<tr>
			<td class="fw-bold">
				<p><?php echo html_encode($cur_info['pro_name']) ?>, <?php echo html_encode($cur_info['unit_number']) ?></p>
				<p><a href="<?=$URL->link('hca_hvac_inspections_work_order', $cur_info['id'])?>" class="badge badge-primary">Pending WO</a></p>
			</td>
			<td class="ta-center fw-bold"><?php echo html_encode($cur_info['inspected_name']) ?></td>
			<td class=""><?php echo html_encode($cur_info['work_order_comment']) ?></td>
			<td class="ta-center"><?php echo html_encode($cur_info['filter_size']) ?></td>
		</tr>
<?php
		}
?>
	</tbody>
</table>
<?php
	}
	else if (!empty($HcaHVACPropertyReport->completed_work_orders_info) && $search_by_job_type == 1)
	{
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">Completed Work Orders (<?php echo count($HcaHVACPropertyReport->completed_work_orders_info) ?>)</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property, Unit#</th>
			<th>Owner</th>
			<th>Comment</th>
			<th>Filter size</th>
		</tr>
	</thead>
	<tbody>

<?php

		foreach($HcaHVACPropertyReport->completed_work_orders_info as $cur_info)
		{



?>
		<tr>
			<td class="fw-bold">
				<p><?php echo html_encode($cur_info['pro_name']) ?>, <?php echo html_encode($cur_info['unit_number']) ?></p>
				<p><a href="<?=$URL->link('hca_hvac_inspections_work_order', $cur_info['id'])?>" class="badge badge-success">Completed WO</a></p>
			</td>
			<td class="ta-center fw-bold"><?php echo html_encode($cur_info['inspected_name']) ?></td>
			<td class=""><?php echo html_encode($cur_info['work_order_comment']) ?></td>
			<td class="ta-center"><?php echo html_encode($cur_info['filter_size']) ?></td>
		</tr>
<?php
		}
?>
	</tbody>
</table>
<?php
	}


	if (!empty($HcaHVACPropertyReport->pending_inspections_info) && $search_by_job_type == 0)
	{
?>

<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of Pending Inspections (<?php echo count($HcaHVACPropertyReport->pending_inspections_info) ?>)</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property, unit#</th>
			<th>Inspected by</th>
			<th>Comment</th>
			<th>Filter size</th>
		</tr>
	</thead>
	<tbody>

<?php

		foreach($HcaHVACPropertyReport->pending_inspections_info as $cur_info)
		{
?>
		<tr>
			<td class="fw-bold">
				<p><?php echo html_encode($cur_info['pro_name']) ?>, <?php echo html_encode($cur_info['unit_number']) ?></p>
				<p><a href="<?=$URL->link('hca_hvac_inspections_checklist2', $cur_info['id'])?>" class="badge badge-warning">Pending Inspection</a></p>
			</td>
			<td class="ta-center fw-bold"><?php echo html_encode($cur_info['inspected_name']) ?></td>
			<td class=""><?php echo html_encode($cur_info['work_order_comment']) ?></td>
			<td class="ta-center"><?php echo html_encode($cur_info['filter_size']) ?></td>
		</tr>
<?php
		}
?>
	</tbody>
</table>
<?php
	}

	if ($search_by_job_type == 0)
	{
		$HcaHVACPropertyReport->genNeverInspected();
?>
<div class="card-header">
	<h6 class="card-title mb-0 text-primary">List of never inspected units (<?php echo $HcaHVACPropertyReport->num_never_inspected ?>)</h6>
</div>
<div class="mb-3">
	<div class="callout callout-info">
		<p class="">This unit list displays never inspected units for the selected period. This takes into all Plumbing Inspections and any Work Order statuses.</p>
	</div>
	<div class="alert alert-warning" role="alert">
		<p class="fw-bold"><?php echo implode(' ', $HcaHVACPropertyReport->never_ispected_units) ?></p>
	</div>
</div>

<?php
	}

	$chart_labels = $chart_numbers = $chart_colors = [];
	// Completed: WO, items, repaired
	if ($search_by_job_type == 1)
	{
		$chart_labels[] = '"Completed Work Orders"';
		$chart_labels[] = '"Replaced Filters"';

		$chart_numbers[] = $HcaHVACPropertyReport->num_completed_work_orders;
		$chart_numbers[] = $HcaHVACPropertyReport->num_filters_replaced;

		$chart_colors[] = 'window.theme.success';
		$chart_colors[] = 'window.theme.primary';
	}
	else // Pending WO, items, never inspected units
	{
		$chart_labels[] = '"Pending Inspections"';
		$chart_labels[] = '"Pending Work Orders"';
		$chart_labels[] = '"Not Replaced Filters"';
		$chart_labels[] = '"Never inspected units"';

		$chart_numbers[] = $HcaHVACPropertyReport->num_pending_inspections;
		$chart_numbers[] = $HcaHVACPropertyReport->num_pending_work_orders;
		$chart_numbers[] = $HcaHVACPropertyReport->num_filters_replaced;
		$chart_numbers[] = $HcaHVACPropertyReport->num_never_inspected;

		$chart_colors[] = 'window.theme.coral';
		$chart_colors[] = 'window.theme.warning';
		$chart_colors[] = 'window.theme.danger';
		$chart_colors[] = 'window.theme.secondary';
	}

?>

<script src="<?=BASE_URL?>/vendor/chartjs/dist/chart.js?v=<?=time()?>"></script>
<script src="<?=BASE_URL?>/vendor/app.js?v=<?=time()?>"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
	// Pie chart
	new Chart(document.getElementById("chartjs-dashboard-pie-pillars"), {
		type: "bar",
		data: {
			labels: [<?php echo implode(',', $chart_labels) ?>],
			datasets: [{
				data: [<?php echo implode(',', $chart_numbers) ?>],
				backgroundColor: [<?php echo implode(',', $chart_colors) ?>],
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
