<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access_admin = ($User->is_admin()) ? true : false;
$access6 = ($User->checkAccess('hca_hvac_inspections', 6)) ? true : false;
if (!$access6)
	message($lang_common['No permission']);

$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;

$HcaHVACInspections = new HcaHVACInspections;

$HcaHVACSummaryReport = new HcaHVACSummaryReport;

$Core->set_page_id('hca_hvac_inspections_summary_report', 'hca_hvac_inspections');
require SITE_ROOT.'header.php';
?>

<nav class="navbar alert-info mb-1">
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
			<th class="bg-warning text-white">Pending Inspections</th>
			<th class="bg-success text-white">Completed Inspections</th>
			<th class="bg-primary text-white hidden">Replaced items</th>
			<th class="bg-info text-white hidden">Repaired items</th>
			<th class="bg-secondary text-white">Not Inspected</th>
			<th>Date of Last Inspection</th>
		</tr>
	</thead>
	<tbody>

<?php
foreach($HcaHVACInspections->getProperties() as $cur_info)
{
	$inspections_pending = isset($HcaHVACSummaryReport->inspections_pending[$cur_info['id']]) ? $HcaHVACSummaryReport->inspections_pending[$cur_info['id']] : 0;
	$inspections_completed = isset($HcaHVACSummaryReport->inspections_completed[$cur_info['id']]) ? $HcaHVACSummaryReport->inspections_completed[$cur_info['id']] : 0;

	$items_replaced = isset($HcaHVACSummaryReport->items_replaced[$cur_info['id']]) ? $HcaHVACSummaryReport->items_replaced[$cur_info['id']] : 0;
	$items_repaired = isset($HcaHVACSummaryReport->items_repaired[$cur_info['id']]) ? $HcaHVACSummaryReport->items_repaired[$cur_info['id']] : 0;
	$units_never_inspected = isset($HcaHVACSummaryReport->units_never_inspected[$cur_info['id']]) ? $HcaHVACSummaryReport->units_never_inspected[$cur_info['id']] : 0;
	$date_inspected = isset($HcaHVACSummaryReport->date_last_inspected[$cur_info['id']]) ? format_date($HcaHVACSummaryReport->date_last_inspected[$cur_info['id']], 'Y-m-d') : '';



	if ($inspections_pending > 0 || $inspections_completed > 0)
	{
		$sub_link_args = [
			'property_id' => $cur_info['id'],
			'year' => $search_by_year,
		];
?>
		<tr>
			<td>
				<span class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></span>
				<a href="<?php echo $URL->genLink('hca_ui_property_report', $sub_link_args) ?>" class="badge bg-primary float-end text-white hidden">View</a>
			</td>
			<td class="ta-center fw-bold"><?php echo $inspections_pending ?></td>
			<td class="ta-center fw-bold"><?php echo $inspections_completed ?></td>
			<td class="ta-center fw-bold hidden"><?php echo $items_replaced ?></td>
			<td class="ta-center fw-bold hidden"><?php echo $items_repaired ?></td>
			<td class="ta-center fw-bold"><?php echo $units_never_inspected ?></td>
			<td class="ta-center"><?php echo $date_inspected ?></td>
		</tr>
<?php
	}
}
?>
	</tbody>
	<tfoot>
		<tr>
			<td class="fw-bold"></td>
			<td class="ta-center fw-bold"><?php echo $HcaHVACSummaryReport->total_inspections_pending ?></td>
			<td class="ta-center fw-bold"><?php echo $HcaHVACSummaryReport->total_inspections_completed ?></td>
			<td class="ta-center fw-bold hidden"><?php echo $HcaHVACSummaryReport->total_replaced ?></td>
			<td class="ta-center fw-bold hidden"><?php echo $HcaHVACSummaryReport->total_repaired ?></td>
			<td class="ta-center fw-bold"><?php echo $HcaHVACSummaryReport->total_units_never_inspected ?></td>
			<td></td>
		</tr>
	</tfoot>
</table>

<?php
require SITE_ROOT.'footer.php';
