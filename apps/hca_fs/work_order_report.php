<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_fs', 7))
	message($lang_common['No permission']);

$HcaFsStatistic = new HcaFsStatistic;

$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_start_date = isset($_GET['start_date']) ? strtotime($_GET['start_date']) : 0;
$search_by_end_date = isset($_GET['end_date']) ? strtotime($_GET['end_date']) : 0;

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.hca_fs_perms, u.hca_fs_group, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.group_id = 7 OR u.group_id='.$Config->get('o_hca_fs_painters').' OR u.group_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$search_query = [];
if ($search_by_user_id > 0)
	$search_query[] = 'r.employee_id='.$search_by_user_id;
else if ($search_by_user_id == -3)
	$search_query[] = 'r.group_id=3';
else if ($search_by_user_id == -9)
	$search_query[] = 'r.group_id=9';
if ($search_by_property_id > 0)
	$search_query[] = 'r.property_id='.$search_by_property_id;
if ($search_by_start_date > 0)
	$search_query[] = 'r.start_date > '.$search_by_start_date;
if ($search_by_end_date > 0)
	$search_query[] = 'r.start_date < '.$search_by_end_date;

$query = array(
	'SELECT'	=> 'r.*, u.realname, p.pro_name',
	'FROM'		=> 'hca_fs_requests AS r',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'r.employee_id=u.id'
		),
		array(
			// LEFT for day off
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'r.property_id=p.id'
		),
	),
//	'WHERE'		=> '(r.work_status=1 OR r.work_status=2 OR r.time_slot > 3) AND r.week_of='.$this->first_day_of_week,
//	'WHERE'		=> 'r.employee_id='.$this->first_day_of_week,
	'ORDER BY'	=> 'r.start_date DESC'
);

if (!empty($search_query))
	$query['WHERE'] = implode(' AND ', $search_query);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$work_orders_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$work_orders_info[] = $row;
}

$Core->set_page_id('hca_fs_work_order_report', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm" id="hca_fs_work_order_report">

	<nav class="navbar navbar-light" style="background-color: #cff4fc">
		<form method="get" accept-charset="utf-8" action="" class="d-flex">
			<div class="container-fluid justify-content-between">
				<div class="row">
					<div class="col">
						<select name="user_id" class="form-control">
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" selected>All Employees</option>'."\n";
echo "\t\t\t\t\t\t".'<option value="-3" '.($search_by_user_id == -3 ? 'selected' : '').'>Maintenance Only</option>'."\n";
echo "\t\t\t\t\t\t".'<option value="-9" '.($search_by_user_id == -9 ? 'selected' : '').'>Painters Only</option>'."\n";
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	if ($search_by_user_id == $cur_user['id'])
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
						</select>
					</div>
					<div class="col">
						<select name="property_id" class="form-control">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected>All Properties</option>'."\n";
foreach ($HcaFsStatistic->property_info as $cur_info)
{
	if ($search_by_property_id == $cur_info['id'])
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
						</select>
					</div>
					<div class="col">
						<input type="date" name="start_date" value="<?php echo ($search_by_start_date > 0) ? date('Y-m-d', $search_by_start_date) : '' ?>" class="form-control">
					</div>
					<div class="col">
						<input type="date" name="end_date" value="<?php echo ($search_by_end_date > 0) ? date('Y-m-d', $search_by_end_date) : date('Y-m-d') ?>" class="form-control">
					</div>
					<div class="col">
						<button class="btn btn-outline-success" type="submit" name="search">Search</button>
					</div>
				</div>
			</div>
		</form>	
	</nav>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Monthly Attendance</h6>
		</div>
		<div class="card-body py-3">
			<div class="chart chart-sm">
				<canvas id="chartjs-dashboard-line"></canvas>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Property Attendance</h6>
		</div>
		<div class="card-body py-3">
			<div class="chart chart-sm">
				<canvas id="chartjs-dashboard-bar"></canvas>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-6">
			<div class="card">
				<div class="card-header">
					<h6 class="card-title mb-0">Work Order Statistic</h6>
				</div>
				<div class="card-body py-3">
					<div class="chart chart-sm">
						<canvas id="chartjs-dashboard-pie-pillars"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">List of Work Orders</h6>
		</div>
		<table class="table table-striped my-0">
			<thead>
				<tr>
					<th>Employee</th>
					<th>Property</th>
					<th>Date</th>
					<th>Type</th>
					<th>Message for maintenance</th>
				</tr>
			</thead>
			<tbody>
<?php
$i = 0;
foreach($work_orders_info as $cur_info)
{
	$work_status = $HcaFsStatistic->getStatusTitle($cur_info);
	if ($i < 100) {
?>
				<tr>
					<td><?php echo $cur_info['realname'] ?></td>
					<td><?php echo $cur_info['pro_name'] ?></td>
					<td style="min-width: 100px;"><?php echo format_time($cur_info['start_date'], 1) ?></td>
					<td><?php echo $work_status ?></td>
					<td><?php echo $cur_info['msg_for_maint'] ?></td>
				</tr>
<?php
	}
	++$i;
}
?>
			</tbody>
		</table>
		<div class="card-header">
			<h6 class="card-title mb-0">Total: <?php echo $i ?> (Limit 100 items on this page)</h6>
		</div>
	</div>
<?php

$attendance = $HcaFsStatistic->getPropertyAttendance();
$property_names = array_keys($attendance);
$property_counters = array_values($attendance);

$monthly_visits = $HcaFsStatistic->getMonthlyVisits();
$monthly_names = array_keys($monthly_visits);
$monthly_counters = array_values($monthly_visits);

//print_dump($HcaFsStatistic->getMonthlyVisits());
?>
</div>

<script src="<?=BASE_URL?>/vendor/chartjs/dist/chart.js"></script>
<script src="<?=BASE_URL?>/vendor/app.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
	var ctx = document.getElementById("chartjs-dashboard-line").getContext("2d");
	var gradient = ctx.createLinearGradient(0, 0, 0, 225);
	gradient.addColorStop(0, "rgba(215, 227, 244, 1)");
	gradient.addColorStop(1, "rgba(215, 227, 244, 0)");
	// Line chart
	new Chart(document.getElementById("chartjs-dashboard-line"), {
		type: "line",
		data: {
			labels: [<?php echo implode(', ', $monthly_names) ?>],
			datasets: [{
				label: "Visits",
				fill: true,
				backgroundColor: gradient,
				borderColor: window.theme.primary,
				data: [<?php echo implode(', ', $monthly_counters) ?>]
			}]
		},
		options: {
			maintainAspectRatio: false,
			legend: {
				display: false
			},
			tooltips: {
				enabled: true
			},
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

<script>
document.addEventListener("DOMContentLoaded", function() {
	// Bar chart
	new Chart(document.getElementById("chartjs-dashboard-bar"), {
		type: "bar",
		data: {
			labels: [<?php echo implode(', ', $property_names) ?>],
			datasets: [{
				label: "This period",
				backgroundColor: window.theme.primary,
				borderColor: window.theme.primary,
				hoverBackgroundColor: window.theme.primary,
				hoverBorderColor: window.theme.primary,
				data: [<?php echo implode(', ', $property_counters) ?>],
				barPercentage: .75,
				categoryPercentage: .5
			}]
		},
		options: {
			maintainAspectRatio: false,
			legend: {
				display: false
			},
			tooltips: {
				enabled: true
			},
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

<script>
document.addEventListener("DOMContentLoaded", function() {
	// Pie chart
	new Chart(document.getElementById("chartjs-dashboard-pie-pillars"), {
		type: "bar",
		data: {
			labels: [<?php echo $HcaFsStatistic->getLabelStatuses() ?>],
			datasets: [{
				data: [<?php echo $HcaFsStatistic->getNumStatuses() ?>],
				backgroundColor: [
					window.theme.primary,
					window.theme.success,
					window.theme.danger,
					window.theme.info,
					window.theme.warning
				],
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
							var y_pos = model.y - 1;//top padding
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
require SITE_ROOT.'footer.php';