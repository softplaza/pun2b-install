<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 10))
	message($lang_common['No permission']);

$access6 = ($User->checkAccess('hca_wom', 6)) ? true : false; // view WO

$HcaWOM = new HcaWOM;

$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_assigned_to = isset($_GET['assigned_to']) ? intval($_GET['assigned_to']) : 0;

$search_query = [];
//$search_query[] = 't.task_status=4'; // Exclude completed
if ($is_manager)
{
	$property_ids = explode(',', $User->get('property_access'));
	$search_query[] = 'w.property_id IN ('.implode(',', $property_ids).')';
}

if ($search_by_property_id > 0)
	$search_query[] = 'w.property_id='.$search_by_property_id;

if ($search_by_unit_number != '')
	$search_query[] = 'pu.unit_number=\''.$DBLayer->escape($search_by_unit_number).'\'';

if ($search_by_assigned_to > 0)
	$search_query[] = 't.assigned_to='.$search_by_assigned_to;

$query = [
	'SELECT'	=> 'COUNT(t.id)',
	'FROM'		=> 'hca_wom_tasks AS t',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_wom_work_orders AS w',
			'ON'			=> 'w.id=t.work_order_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=w.property_id'
		],
		[
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
/*
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.assigned_to'
		],
*/

	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 't.*, w.property_id, p.pro_name, pu.unit_number, u2.realname AS requested_name, u2.email AS requested_email', // u1.realname AS assigned_name, u1.email AS assigned_email,
	'FROM'		=> 'hca_wom_tasks AS t',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_wom_work_orders AS w',
			'ON'			=> 'w.id=t.work_order_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=w.property_id'
		],
		[
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
/*
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.assigned_to'
		],
*/
	],
	'LIMIT'		=> $PagesNavigator->limit(),
	'ORDER BY'	=> 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_tasks =  $hca_wom_user_tasks = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$hca_wom_tasks[] = $row;

	if (!isset($hca_wom_user_tasks[$row['assigned_to']][$row['property_id']]))
		$hca_wom_user_tasks[$row['assigned_to']][$row['property_id']] = 1;
	else
		++$hca_wom_user_tasks[$row['assigned_to']][$row['property_id']];
}
$PagesNavigator->num_items($hca_wom_tasks);

$property_names = [];
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
$property_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
	$property_names[$row['id']] = '"'.$row['pro_name'].'"';
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.group_id = 3',// OR u.group_id = 9
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users[] = $fetch_assoc;
}

$Core->set_page_id('hca_wom_work_orders_report', 'hca_fs');
require SITE_ROOT.'header.php';
?>
<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">

<?php if (!$is_manager): ?>
				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select form-select-sm">
						<option value="">Properties</option>
<?php
foreach ($property_info as $val)
{
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
<?php endif; ?>

				<div class="col-md-auto pe-0 mb-1">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Unit #" class="form-control form-control-sm" size="5">
				</div>

				<div class="col-md-auto pe-0 mb-1">
					<select name="assigned_to" class="form-select form-select-sm">
						<option value="0">Technician</option>
<?php
$optgroup = 0;
foreach ($users as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}

	if ($search_by_assigned_to == $cur_user['id'])
		echo '<option value="'.$cur_user['id'].'" selected>'.$cur_user['realname'].'</option>';
	else
		echo '<option value="'.$cur_user['id'].'">'.$cur_user['realname'].'</option>';
}
?>
					</select>
				</div>

				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_wom_work_orders_report') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="card-header">
	<h6 class="card-title mb-0">Availability of Maintenance</h6>
</div>
<div id="chart_work_orders_summary" class="mb-3"></div>

<div class="card-header">
	<h6 class="card-title mb-0">Work Orders Report</h6>
</div>
<?php
if (!empty($hca_wom_tasks))
{
?>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property/Unit #</th>
			<th>Details</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
<?php
	$property_id = 0;
	foreach ($hca_wom_tasks as $cur_info)
	{
		$view_wo = ($access6) ? '<p><a href="'.$URL->link('hca_wom_work_order', $cur_info['work_order_id']).'" class="badge bg-primary text-white">view</a></p>' : '';

		$cur_info['unit_number'] = ($cur_info['unit_number'] != '') ? $cur_info['unit_number'] : 'Common area';

		if ($cur_info['task_status'] == 4)
			$status = '<span class="badge badge-primary">Closed</span>';
		else
			$status = '<span class="badge badge-warning">Open</span>';

		if ($property_id != $cur_info['property_id'])
		{
			echo '<tr class="table-primary"><td colspan="7" class="fw-bold">'.html_encode($cur_info['pro_name']).'</td></tr>';
			$property_id = $cur_info['property_id'];
		}
?>
		<tr>
			<td>
				<span class="fw-bold"><a href="<?=$URL->link('hca_wom_work_order', $cur_info['work_order_id'])?>"><?php echo html_encode($cur_info['unit_number']) ?></a></span>
				<span class="float-end"><?php echo $view_wo ?></span>
			</td>
			<td class="min-100"><?php echo html_encode($cur_info['task_message']) ?></td>
			<td class="min-100 ta-center"><?php echo $status ?></td>
		</tr>
<?php

	}
?>
	</tbody>
</table>

<?php
/*
$date = new \DateTime();
$date->setTime(0, 0, 0);

if ($date->format('N') != 1)
	$date->modify('last monday');
*/
?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
var options = {
	series: [<?php
foreach($users as $cur_user)
{
	echo '{';
	echo 'name: "'.html_encode($cur_user['realname']).'",';
	echo 'data: [';
	foreach($property_info as $property)
	{
		$property_ident = false;
		foreach($hca_wom_user_tasks as $key => $data)
		{
			if ($key == $cur_user['id'] && isset($data[$property['id']]))
			{
				echo $data[$property['id']].',';
				$property_ident = true;
				break;
			}
		}
		if (!$property_ident)
			echo '0,';
	}
	echo ']';
	echo '},';
}
?>
	],
	chart: {
		height: 750,
		type: 'heatmap',
		toolbar: {
            show: false
        }
	},
	stroke: {
		width: 5
	},
	/*
	grid: {
		padding: {
		right: 20
		}
	},*/
	plotOptions: {
		heatmap: {
			radius: 5,
			enableShades: false,
			colorScale: {
				/*inverse: true,*/
				ranges: [{
					from: 0,
					to: 0,
					color: '#eaf2ff',//#9ae7be 
					name: 'No tasks',
				},
				{
					from: 1,
					to: 5,
					color: '#33A1FD',
					name: '1-5 tasks',
				},
				{
					from: 6,
					to: 10,
					color: '#ff7f53',
					name: '6-10 tasks',
				},
				{
					from: 11,
					to: 50,
					color: '#c72121',
					name: '11-50 tasks',
				},


				],
			},
		}
	},
	dataLabels: {
		enabled: true,
		style: {
			colors: ['#eaf2ff']
		}
	},
	xaxis: {
		type: 'category',
		categories: [<?php echo implode(',', $property_names) ?>]
	},
	title: {
		//text: 'Maintenance Availability Schedule'
	},
};

var chart = new ApexCharts(document.querySelector("#chart_work_orders_summary"), options);
chart.render();
</script>



<?php
}
else
{
?>
<div class="card">
	<div class="card-body">
		<div class="alert alert-warning" role="alert">You have no items on this page.</div>
	</div>
</div>
<?php
}
require SITE_ROOT.'footer.php';
