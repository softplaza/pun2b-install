<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_cc')) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaCC = new HcaCC;
$HcaCCChart = new HcaCCChart;

$query = [
	'SELECT'	=> 'i.*, pt.pro_name, u.realname',//pj.date_last_completed, pj.date_completed, pj.notes
	'FROM'		=> 'hca_cc_items AS i',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'hca_cc_items_tracking AS pj',
			'ON'			=> 'pj.id=i.last_tracking_id'
		],	
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=i.action_owner'
		],	
		[
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=i.property_id'
		],
		//
	],
	'ORDER BY'	=> 'i.date_due',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $main_ids = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
	$main_ids[] = $row['id'];
}

$Core->set_page_id('hca_cc_project', 'hca_cc');
require SITE_ROOT.'header.php';
?>

<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0 text-primary">Summary Report</h6>
			</div>
			<div class="card-body py-3">
				<div class="chart chart-sm">
					<div id="piechart" style="width: 900px; height: 500px;"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="card-header">
	<h6 class="card-title mb-0">Items Tracking (<?php echo count($main_info) ?>)</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="min-w-10">Frequency</th>
			<th>Department</th>
			<th class="min-w-10">Item/Description</th>
			<th>Required By</th>
			<th class="min-w-6">Date Last Completed</th>
			<th class="min-w-6">Date Completed</th>
			<th class="min-w-6">Due Date</th>
		</tr>
	</thead>
	<tbody>

<?php
	foreach($main_info as $cur_info)
	{
		$Core->add_dropdown_item('<a href="#" onclick="editItem('.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="far fa-check-circle"></i> Complete</a>');
		$Core->add_dropdown_item('<a href="'.$URL->link('hca_cc_item', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit item</a>');

		$item_desc = ($cur_info['item_desc'] != '') ? '<p class="float-end"><a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="'.html_encode($cur_info['item_desc']).'"><i class="fas fa-info-circle"></i></a></p>' : '';

		$frequency = ($cur_info['frequency'] > 0) ? $HcaCC->frequency[$cur_info['frequency']] : 'n/a';
		$department = ($cur_info['department'] > 0) ? $HcaCC->departments[$cur_info['department']] : '';
		$required_by = ($cur_info['required_by'] > 0) ? $HcaCC->required_by[$cur_info['required_by']] : '';

		$months = ($cur_info['months_due'] != '') ? '<p>'.$HcaCC->getMonths($cur_info['months_due']).'</p>' : '';


		$time_now = time();
		$date_due_time = strtotime($cur_info['date_due']);
		$next_month = $date_due_time - 2592000;

		$last_notified = ($cur_info['last_notified'] > 0) ? '<p class="float-end"><a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="Last notified on '.format_time($cur_info['last_notified']).'"><i class="fas fa-info-circle"></i></a></p>' : '';

		if ($time_now > $date_due_time)
			$status = '<div class="mb-1 p-1 alert-danger"><p>'.format_date($cur_info['date_due'], 'F Y').'</p></div>';
		else if ($time_now > $next_month)
			$status = '<div class="mb-1 p-1 alert-warning"><p>'.format_date($cur_info['date_due'], 'F Y').'</p></div>';	
		else
			$status = '<div class="mb-1 p-1 alert-success"><p>'.format_date($cur_info['date_due'], 'F Y').'</p></div>';
?>
		<tr>
			<td>
				<p class="fw-bold"><?php echo html_encode($frequency) ?></p>
				<?php echo $months ?>
				<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
			</td>
			<td class="ta-center"><?php echo html_encode($department) ?></td>
			<td class="ta-center">
				<p class="fw-bold text-primary"><?php echo html_encode($cur_info['item_name']) ?></p>
				<?php echo $item_desc ?>
			</td>
			<td class="ta-center"><?php echo $required_by ?></td>
			<td class="ta-center"><?php echo format_date($cur_info['date_last_completed'], 'F Y') ?></td>
			<td class="ta-center"><?php echo format_date($cur_info['date_completed'], 'F Y') ?></td>
			<td class="ta-center">
				<?php echo $last_notified ?>
				<?php echo $status ?>
				
			</td>
		</tr>
<?php
	}

?>
	</tbody>
</table>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {

var data = google.visualization.arrayToDataTable([
	['Task', 'Projects'],
	['Expired', <?=$HcaCCChart->num_expired?>],
	['Upcoming', <?=$HcaCCChart->num_upcoming?>],
	['Completed', <?=$HcaCCChart->num_completed?>],
]);

var options = {
	title: 'Project Summary',
	//legend: 'none',
	pieSliceText: 'value',
	//pieStartAngle: 135,
	//tooltip: { trigger: 'none' },
	slices: {
		0: { color: 'red' },
		1: { color: 'orange' },
		2: { color: 'green' }
	}
}

var chart = new google.visualization.PieChart(document.getElementById('piechart'));

chart.draw(data, options);
}
</script>

<?php
require SITE_ROOT.'footer.php';
