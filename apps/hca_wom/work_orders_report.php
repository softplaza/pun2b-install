<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 1)) // WO List
	message($lang_common['No permission']);

$access6 = ($User->checkAccess('hca_wom', 6)) ? true : false; // View

$HcaWOM = new HcaWOM;

$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_assigned_to = isset($_GET['assigned_to']) ? intval($_GET['assigned_to']) : 0;
$search_by_task_status = isset($_GET['task_status']) ? intval($_GET['task_status']) : -1;
$search_by_date = isset($_GET['date']) ? swift_trim($_GET['date']) : '';
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;

$search_query = $sort_by_query = [];
$search_query[] = 't.task_status!=0'; // Exclude canceled
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

if ($search_by_task_status > -1)
{
	if ($search_by_task_status == 2)
		$search_query[] = '(t.task_status=1 OR t.task_status=2)';
	else if ($search_by_task_status == 4)
		$search_query[] = '(t.task_status=3 OR t.task_status=4)';
}

if ($search_by_date != '')
	$search_query[] = 'DATE(t.dt_completed)=\''.$DBLayer->escape($search_by_date).'\'';

if ($sort_by == 1)
	$sort_by_query[] = 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number';
else if ($sort_by == 2)
	$sort_by_query[] = 'p.pro_name, LENGTH(pu.unit_number) DESC, pu.unit_number DESC';
else if ($sort_by == 3)
	$sort_by_query[] = 'w.priority';
else if ($sort_by == 4)
	$sort_by_query[] = 'w.priority DESC';
else if ($sort_by == 5)
	$sort_by_query[] = 't.dt_completed';
else if ($sort_by == 6)
	$sort_by_query[] = 't.dt_completed DESC';

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
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 't.*, w.property_id, w.priority, w.wo_status, w.dt_created, p.pro_name, pu.unit_number, u1.realname AS requested_name, u2.realname AS assigned_name, i.item_name, tp.type_name, pb.problem_name',
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
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=t.assigned_to'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_items AS i',
			'ON'			=> 'i.id=t.item_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_types AS tp',
			'ON'			=> 'tp.id=i.item_type'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_problems AS pb',
			'ON'			=> 'pb.id=t.task_action'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
	//'ORDER BY'	=> 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number, t.task_status DESC',
	'ORDER BY'	=> 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number',
];

if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
if (!empty($sort_by_query)) $query['ORDER BY'] = implode(', ', $sort_by_query);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_tasks = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_tasks[] = $row;
}
$PagesNavigator->num_items($hca_wom_tasks);

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
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
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
	'WHERE'		=> 'u.group_id=3',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users[] = $fetch_assoc;
}

$Core->set_page_id('hca_wom_work_orders_report', 'hca_wom');
require SITE_ROOT.'header.php';
?>
<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">

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
				<div class="col-md-auto pe-0 mb-1">
					<select name="task_status" class="form-select form-select-sm">
						<option value="-1">Task status</option>
<?php
$task_statuses = [
	2 => 'Open',
	//3 => 'Ready for Review',
	4 => 'Closed',
	//0 => 'Canceled'
];
foreach ($task_statuses as $key => $val)
{
	if ($search_by_task_status == $key)
		echo '<option value="'.$key.'" selected>'.$val.'</option>';
	else
		echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<input name="date" type="date" value="<?php echo isset($_GET['date']) ? $_GET['date'] : '' ?>" class="form-control form-control-sm">
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_wom_work_orders_report') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php

$query = [
	'SELECT'	=> 't.*, w.dt_created, p.pro_name, tp.type_name, pb.problem_name',
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
			'LEFT JOIN'		=> 'hca_wom_items AS i',
			'ON'			=> 'i.id=t.item_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_types AS tp',
			'ON'			=> 'tp.id=i.item_type'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_problems AS pb',
			'ON'			=> 'pb.id=t.task_action'
		],
	],
	'ORDER BY'	=> 't.dt_completed, t.time_created',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$opened_tasks = $completed_tasks = $pie_data = $statuses = [];
$found_dates = $found_opened = $found_completed = [];
$opened_properties = $closed_properties = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$time_created = '"'.date('Y-m-d', $row['time_created']).'"';
	$dt_completed = '"'.format_date($row['dt_completed'], 'Y-m-d').'"';

	$type_name = ($row['type_name'] != '') ? '"'.$row['type_name'].'"' : '';
	if ($type_name != '')
		$pie_data[$type_name] = (isset($pie_data[$type_name])) ? ++$pie_data[$type_name] : 1;

	if ($row['task_status'] == 1 || $row['task_status'] == 2)
		$statuses['"Open"'] = isset($statuses['"Open"']) ? ++$statuses['"Open"'] : 1;
	else if ($row['task_status'] == 3 || $row['task_status'] == 4)
		$statuses['"Closed"'] = isset($statuses['"Closed"']) ? ++$statuses['"Closed"'] : 1;


	$pro_name = '"'.$row['pro_name'].'"';
	if ($row['task_status'] == 1 || $row['task_status'] == 2)
		$opened_properties[$pro_name] = isset($opened_properties[$pro_name]) ? ++$opened_properties[$pro_name] : 1;
	else if (!isset($opened_properties[$pro_name]))
		$opened_properties[$pro_name] = 0;

	if ($row['task_status'] == 3 || $row['task_status'] == 4)
		$closed_properties[$pro_name] = isset($closed_properties[$pro_name]) ? ++$closed_properties[$pro_name] : 1;
	else if (!isset($closed_properties[$pro_name]))
		$closed_properties[$pro_name] = 0;

	$date = (strtotime($row['dt_completed']) > 0) ? format_date($row['dt_completed'], 'Y-m-d') : format_date($row['dt_created'], 'Y-m-d');
	$date_key = '"'.$date.'"';
	//$found_dates[$date_key] = '<a href="'.$URL->genLink('hca_wom_work_orders_report', ['date' => $date, 'task_status' => 4]).'" class="badge badge-primary">'.format_date($date, 'M, d').'</a>';
	$found_dates[$date_key] = '<a href="'.$URL->addParam(['date' => $date, 'task_status' => 4]).'" class="badge badge-primary">'.format_date($date, 'M, d').'</a>';

	if (strtotime($row['dt_completed']) > 0)
		$found_completed[$date_key] = (isset($found_completed[$date_key])) ? ++$found_completed[$date_key] : 1;
	else
		$found_opened[$date_key] = (isset($found_opened[$date_key])) ? ++$found_opened[$date_key] : 1;
}

ksort($pie_data);
krsort($statuses);
ksort($found_dates);

foreach($found_dates as $key => $val)
{
	if (!isset($found_completed[$key]))
		$found_completed[$key] = 0;

	if (!isset($found_opened[$key]))
		$found_opened[$key] = 0;
}

ksort($found_completed);
ksort($found_opened);

ksort($opened_properties);
ksort($closed_properties);

//print_dump($found_dates);
//print_dump($found_opened);
?>

<div class="row">
	<div class="col-4">
		<div id="chart_2"></div>
	</div>
	<div class="col-4">
		<div id="chart_3"></div>
	</div>
	<div class="col-4">
		<div id="chart_4"></div>
	</div>
</div>

<?php if ($search_by_date == ''): ?>
<div id="chart_5" class="ms-1 me-3"></div>
<div class="d-flex justify-content-between ms-3 mb-3">
	<?php echo implode('', $found_dates) ?>
</div>
<?php endif; ?>

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
			<th>WO #</th>
			<th><?php echo $URL->sortBy('Unit#/Location', 1, 2) ?></th>
			<th>Type/Item</th>
			<th>Task Information</th>
			<th>Completed by</th>
			<th class="min-w-10"><?php echo $URL->sortBy('Completed on', 5, 6) ?></th>
			<th>Status</th>
			<th>Print</th>
		</tr>
	</thead>
	<tbody>
<?php

	$property_id = 0;
	foreach ($hca_wom_tasks as $cur_info)
	{
		$cur_info['unit_number'] = ($cur_info['unit_number'] != '') ? $cur_info['unit_number'] : 'Common area';

		if ($cur_info['priority'] == 4)
			$priority = '<span class="badge-danger text-danger fw-bold p-1 border border-danger">Emergency</span>';
		else if ($cur_info['priority'] == 3)
			$priority = '<span class="text-danger fw-bold">High</span>';
		else if ($cur_info['priority'] == 2)
			$priority = '<span class="text-warning fw-bold">Medium</span>';
		else
			$priority = '<span class="text-primary fw-bold">Low</span>';

		if ($cur_info['task_status'] == 4)
			$status = '<span class="badge badge-primary">Closed</span>';
		else if ($cur_info['task_status'] == 3)
			$status = '<span class="badge badge-success">Ready for Review</span>';
		else if ($cur_info['task_status'] == 2 || $cur_info['task_status'] == 1)
			$status = '<span class="badge badge-warning">Open</span>';
		else if ($cur_info['task_status'] == 0)
			$status = '<span class="badge badge-danger">Canceled</span>';
		else	
			$status = '';

		if ($property_id != $cur_info['property_id'])
		{
			echo '<tr class="table-primary"><td colspan="8" class="fw-bold">'.html_encode($cur_info['pro_name']).'</td></tr>';
			$property_id = $cur_info['property_id'];
		}

		$task_info = [];
		$task_info[] = '<span class="fw-bold">'.html_encode($cur_info['type_name']).'</span>';
		if ($cur_info['item_name'] != '')
		{
			$task_info[] = ' -> <span class="fw-bold">'.html_encode($cur_info['item_name']).'</span>';
			$task_info[] = ' ('.html_encode($cur_info['problem_name']).')';
		}

		$task_message = ($cur_info['tech_comment'] != '') ? html_encode($cur_info['tech_comment']) : html_encode($cur_info['task_message']);
		$task_message .= ($cur_info['task_init_closed'] != '') ? ' <span class="text-muted">['.html_encode($cur_info['task_init_closed']).']</span>' : '';

		$view_wo = ($access6) ? '<a href="'.$URL->link('hca_wom_work_order', $cur_info['work_order_id']).'" class="badge badge-primary">#'.$cur_info['work_order_id'].'-'.$cur_info['id'].'</a>' : '<span class="badge badge-primary">#'.$cur_info['work_order_id'].'-'.$cur_info['id'].'</span>';
?>
		<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id'] ? ' anchor' : '') ?>">
			<td class="min-100"><?php echo $view_wo ?></td>
			<td class="min-100 ta-center fw-bold"><a href="<?php echo $URL->link('hca_wom_work_order', $cur_info['work_order_id']) ?>"><?php echo html_encode($cur_info['unit_number']) ?></a></td>
			<td class="min-100"><p><?php echo implode('', $task_info) ?></p></td>
			<td class="min-100"><?php echo $task_message ?></td>
			<td class="min-100"><?php echo html_encode($cur_info['assigned_name']) ?></td>
			<td class="min-100 ta-center"><?php echo format_date($cur_info['dt_completed'], 'm/d/Y') ?></td>
			<td class="min-100 ta-center"><?php echo $status ?></td>
			<td class="ta-center">
				<a href="<?=$URL->genLink('hca_wom_print', ['section' => 
'work_order', 'id' => $cur_info['work_order_id']])?>" target="_blank"><i class="fas fa-print fa-lg" aria-hidden="true"></i></a>
			</td>
		</tr>
<?php
	}
?>
	</tbody>
</table>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Follow Up Date</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="clearModalFields()"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>



<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
var options2 = 
{
	title: {
		text: 'Type Summary'
	},
	series: [<?php echo implode(',', array_values($pie_data)) ?>],
		chart: {
		height: 270,
		width: 450,
		type: 'donut',
	},
	labels: [<?php echo implode(',', array_keys($pie_data)) ?>],
	colors: [
		'#fd767c',
		'#0d6efd',
		'#fd7e14',
		'#ffc107',
		'#9762ff',
		'#20c997',
		'#0dcaf0',
		'#df649d',
		'#bc4c00',
		'#087990',
	],
	responsive: [{
		breakpoint: 480,
		options: {
		chart: {
			width: 450,
		},
		legend: {
			position: 'bottom'
		}
		}
	}],
	dataLabels: {
        formatter: function (val, opts) {
            return opts.w.config.series[opts.seriesIndex]
        },
    },
	plotOptions: {
		pie: {
		donut: {
			labels: {
			show: true,
			total: {
				showAlways: true,
				show: true
			}
			}
		}
		}
	},
	legend: {
		formatter: function(val, opts) {
			return val + " - " + opts.w.globals.series[opts.seriesIndex]
		}
	},
};

var chart = new ApexCharts(document.querySelector("#chart_2"), options2);
chart.render();
</script>

<script>
var options3 = 
{
	title: {
		text: 'Status Summary'
	},
	series: [<?php echo implode(',', array_values($statuses)) ?>],
		chart: {
		height: 260,
		width: 450,
		type: 'pie',
	},
	labels: [<?php echo implode(',', array_keys($statuses)) ?>],
	colors: [
<?php
	if ($search_by_task_status != 4)
		echo ("'#ffc107','#0d6efd'");
	else
		echo ("'#0d6efd'");
?>
	],
	responsive: [{
		breakpoint: 480,
		options: {
		chart: {
			width: 450,
		},
		legend: {
			position: 'bottom'
		}
		}
	}],
	dataLabels: {
        formatter: function (val, opts) {
            return opts.w.config.series[opts.seriesIndex]
        },
    },
	plotOptions: {
		pie: {
			donut: {
				labels: {
					show: true,
					total: {
						showAlways: true,
						show: true
					}
				}
			}
		}
	},
	legend: {
        formatter: function(val, opts) {
            return val + " - " + opts.w.globals.series[opts.seriesIndex]
        }
    },
};

var chart = new ApexCharts(document.querySelector("#chart_3"), options3);
chart.render();
</script>


<script>
var options4 = {
    series: [
		{
        	name: 'Opened Tasks',
        	data: [<?php echo implode(',', array_values($opened_properties)) ?>]
		},
		{
        	name: 'Closed Tasks',
        	data: [<?php echo implode(',', array_values($closed_properties)) ?>]
		}
	],
    chart: {
        type: 'bar',
        height: 265,
        width: '100%',
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
        	horizontal: false,
        }
    },
    //dataLabels: {enabled: false},
    title: {
        text: 'Opened & Closed Tasks per Property'
    },
    dataLabels: {
        enabled: true,
		formatter: function (val) {
            return val;
        },
		offsetY: 13,
        style: {
        	fontSize: '13px',
        	colors: ["#304758"]
        },
    },
    stroke: {
        show: true,
        width: 1,
        colors: ['#fff']
    },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
        offsetX: 50
    },
    xaxis: {
        categories: [<?php echo implode(',', array_keys($opened_properties)) ?>],
    },
	colors: ['#ffc107', '#0d6efd'],
};

var chart = new ApexCharts(document.querySelector("#chart_4"), options4);
chart.render();
</script>

<script>
var options5 = {
	series: [
		{
			name: 'Closed Tasks',
			data: [<?php echo implode(',', array_values($found_completed)) ?>]
		}
	],
	chart: {
		height: 200,
		type: 'line',
		toolbar: {show: false},
		zoom: {enabled: false}
	},
	colors: ['#0d6efd'],
	dataLabels: {
		enabled: true,
    },
	stroke: {
		curve: 'smooth'
	},
	title: {
		text: 'Number of Closed Tasks per Day',
		align: 'left'
	},
	grid: {
		borderColor: '#e7e7e7',
		row: {
			colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
			opacity: 0.5
		},
	},
	markers: {size: 1},
	xaxis: {
		//categories: [<?php echo implode(',', array_keys($found_dates)) ?>],
		//title: {text: 'Month'},
		labels: {show: false},
		tooltip: {enabled: false},
	},
	yaxis: {
		title: {
		//	text: 'Num tasks'
		},
		tooltip: {enabled: false},
	},
	legend: {
        position: 'top',
        horizontalAlign: 'left',
        offsetX: 5
    },
	tooltip: {enabled: false}
/*
		custom: function({series, seriesIndex, dataPointIndex, w}) {
			return '<div>' +
				'<a href="" class="badge bg-primary">' + series[seriesIndex][dataPointIndex] + '</a>' +
				'</div>';
		}
	}
*/
};

var chart = new ApexCharts(document.querySelector("#chart_5"), options5);
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
