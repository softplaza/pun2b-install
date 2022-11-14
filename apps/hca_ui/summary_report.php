<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access_admin = ($User->is_admin()) ? true : false;
$access6 = ($User->checkAccess('hca_ui', 6)) ? true : false;
if (!$access6)
	message($lang_common['No permission']);

$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;
$search_by_inspection_type = isset($_GET['inspection_type']) ? intval($_GET['inspection_type']) : 0; // 0 all, 1 audit, 2 flapper

$HcaUnitInspection = new HcaUnitInspection;
$HcaUISummaryReport = new HcaUISummaryReport;
$HcaUISummaryReport->genSummaryData();

$Core->set_page_id('hca_ui_summary_report', 'hca_ui');
require SITE_ROOT.'header.php';
?>

<nav class="navbar alert-info mb-1">
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
			<th class="bg-warning text-white">Pending Work Orders</th>
			<th class="bg-danger text-white">Pending items</th>
			<th class="bg-primary text-white">Replaced items</th>
			<th class="bg-info text-white">Repaired items</th>
			<th class="bg-secondary text-white">Not Inspected</th>
			<th>Date of Last Inspection</th>
		</tr>
	</thead>
	<tbody>

<?php
foreach($HcaUnitInspection->getProperties() as $cur_info)
{
	$wo_pending = isset($HcaUISummaryReport->wo_pending[$cur_info['id']]) ? $HcaUISummaryReport->wo_pending[$cur_info['id']] : 0;
	$items_pending = isset($HcaUISummaryReport->items_pending[$cur_info['id']]) ? $HcaUISummaryReport->items_pending[$cur_info['id']] : 0;
	$items_replaced = isset($HcaUISummaryReport->items_replaced[$cur_info['id']]) ? $HcaUISummaryReport->items_replaced[$cur_info['id']] : 0;
	$items_repaired = isset($HcaUISummaryReport->items_repaired[$cur_info['id']]) ? $HcaUISummaryReport->items_repaired[$cur_info['id']] : 0;
	$units_never_inspected = isset($HcaUISummaryReport->units_never_inspected[$cur_info['id']]) ? $HcaUISummaryReport->units_never_inspected[$cur_info['id']] : 0;
	$date_inspected = isset($HcaUISummaryReport->date_last_inspected[$cur_info['id']]) ? format_date($HcaUISummaryReport->date_last_inspected[$cur_info['id']], 'Y-m-d') : '';



	if ($items_pending > 0 || $items_replaced > 0 || $items_repaired > 0)
	{
		$sub_link_args = [
			'property_id' => $cur_info['id'],
			'year' => $search_by_year,
		];
		if ($search_by_inspection_type > 0)
			$sub_link_args['inspection_type'] = $search_by_inspection_type;
?>
		<tr>
			<td>
				<span class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></span>
				<a href="<?php echo $URL->genLink('hca_ui_property_report', $sub_link_args) ?>" class="badge bg-primary float-end text-white">View</a>
			</td>
			<td class="ta-center fw-bold"><?php echo $wo_pending ?></td>
			<td class="ta-center fw-bold"><?php echo $items_pending ?></td>
			<td class="ta-center fw-bold"><?php echo $items_replaced ?></td>
			<td class="ta-center fw-bold"><?php echo $items_repaired ?></td>
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
			<td class="ta-center fw-bold"><?php echo $HcaUISummaryReport->total_wo_pending ?></td>
			<td class="ta-center fw-bold"><?php echo $HcaUISummaryReport->total_pending ?></td>
			<td class="ta-center fw-bold"><?php echo $HcaUISummaryReport->total_replaced ?></td>
			<td class="ta-center fw-bold"><?php echo $HcaUISummaryReport->total_repaired ?></td>
			<td class="ta-center fw-bold"><?php echo $HcaUISummaryReport->total_units_never_inspected ?></td>
			<td></td>
		</tr>
	</tfoot>
</table>

<?php
require SITE_ROOT.'footer.php';
