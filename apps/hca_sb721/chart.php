<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sb721', 3)) ? true : false;
$permission_level2 = ($User->checkAccess('hca_sb721', 12)) ? true : false;
//if (!$access)
//	message($lang_common['No permission']);

$HcaSB721Chart = new HcaSB721Chart;

$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_performed_by = isset($_GET['performed_by']) ? intval($_GET['performed_by']) : 0;

$search_query = [];
$search_query[] = 'pj.project_status!=0';
if ($search_by_property_id > 0)
	$search_query[] = 'pj.property_id='.$search_by_property_id;
if ($search_by_performed_by > 0)
	$search_query[] = 'pj.performed_by='.$search_by_performed_by;

$work_statuses = array(
	//0 => 'Removed',
	1 => 'Bid Phase',
	2 => 'Active Phase',
	3 => 'Completion Phase',
	//4 => 'On Hold',
	//5 => 'Completed',
);

$query = array(
	'SELECT'	=> 'COUNT(pj.id)',
	'FROM'		=> 'hca_sb721_projects AS pj',
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// Show only WORK STARTED - BID - ON HOLD
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u.realname',
	'FROM'		=> 'hca_sb721_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=pj.performed_by'
		),
	),
	'ORDER BY'	=> 'pt.pro_name',
	'LIMIT'		=> $PagesNavigator->limit()
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$main_info[$fetch_assoc['id']] = $fetch_assoc;
	$projects_ids[] = $fetch_assoc['id'];
}
$PagesNavigator->num_items($main_info);

$follow_up_info = array();
if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'hca_sb721_projects\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$uploader_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}

	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.date_time, e.message',
		'FROM'		=> 'sm_calendar_events AS e',
		'WHERE'		=> 'e.project_id IN('.implode(',', $projects_ids).') AND project_name=\'hca_sb721\'',
		'ORDER BY'	=> 'e.time'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$follow_up_info[] = $row;
	}
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'u.id, u.realname',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'user_access AS a',
			'ON'			=> 'u.id=a.a_uid'
		),
	),
	'ORDER BY'	=> 'u.realname',
	'WHERE'		=> 'a.a_to=\'hca_sb721\' AND a.a_key=16 AND a.a_value=1'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_managers = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$project_managers[] = $row;
}

$Core->set_page_id('hca_sb721_chart', 'hca_sb721');
require SITE_ROOT.'header.php';
?>

	<nav class="navbar container-fluid search-box py-3">
		<form method="get" accept-charset="utf-8" action="">
			<div class="row">
				<div class="col pe-0">
					<select name="property_id" class="form-select-sm">
						<option value="">Display All Properties</option>
<?php
foreach ($property_info as $val) {
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="performed_by" class="form-select-sm">
						<option value="">All Managers</option>
<?php 
foreach ($project_managers as $user_info)
{
	if ($search_by_performed_by == $user_info['id'])
		echo '<option value="'.$user_info['id'].'" selected>'.$user_info['realname'].'</option>';
	else
		echo '<option value="'.$user_info['id'].'">'.$user_info['realname'].'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="year" class="form-select-sm">
						<option value="0">All Years</option>
<?php for ($year = 2022; $year <= date('Y', time()); $year++){
			if ($search_by_year == $year)
				echo '<option value="'.$year.'" selected>'.$year.'</option>';
			else
				echo '<option value="'.$year.'">'.$year.'</option>';
} ?>
					</select>
				</div>
				<div class="col pe-0">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				</div>
				<div class="col pe-0">
					<a href="<?php echo $URL->link('hca_sb721_chart', 0) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</form>
	</nav>
<?php
if (!empty($main_info))
{
	$HcaSB721Chart->addAllProjects($main_info);

	$query = array(
		'SELECT'	=> 'v2.*, v1.vendor_name, v1.phone_number, v1.email',
		'FROM'		=> 'hca_sb721_vendors AS v2',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_vendors AS v1',
				'ON'			=> 'v1.id=v2.vendor_id'
			),
		),
		'ORDER BY'	=> 'v1.vendor_name',
		'WHERE'		=> 'v2.project_id IN('.implode(',', $projects_ids).')'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$sb721_vendors = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$sb721_vendors[] = $row;
	}
	$HcaSB721Chart->addAllVendors($sb721_vendors);

	if ($pid > 0)
	{
?>
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Project Progress</h6>
		</div>
		<div class="card-body py-3">
			<div class="mb-3">
				<span class="badge bg-info text-info">-----</span> Pre-Inspection
				<span class="badge bg-primary text-primary ms-2">-----</span> Engineer Inspection
				<span class="badge bg-warning text-warning ms-2">-----</span> Vendor Bid Phase
				<span class="badge bg-success text-success ms-2">-----</span> Vendor Job Phase
				<span class="badge bg-pink text-pink ms-2">-----</span> Completion Phase
			</div>
			<div id="chart_1"></div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Follow-Up Dates</h6>
		</div>
		<div class="card-body py-3">
			<div class="chart chart-sm">
				<div id="chart_2"></div>
			</div>
		</div>
	</div>
<?php
	}
	else
	{
?>
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Projects</h6>
		</div>
		<div class="card-body py-3">
			<div class="mb-3">
				<span class="badge bg-info text-info">-----</span> Pre-Inspection
				<span class="badge bg-primary text-primary ms-2">-----</span> Engineer Inspection
				<span class="badge bg-warning text-warning ms-2">-----</span> Vendor Bid Phase
				<span class="badge bg-success text-success ms-2">-----</span> Vendor Job Phase
				<span class="badge bg-pink text-pink ms-2">-----</span> Completion Phase
			</div>
			<div id="chart_3"></div>
		</div>
	</div>
<?php	
	}
?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead class="sticky-under-menu">
				<tr class="ta-center">
					<th class="th1">Property</th>
					<th>Pre-Inspection</th>
					<th>Symptoms</th>
					<th>Action</th>
					<th>Engineer Inspection</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
		$page_param['td'] = array();
		$page_param['td']['unit_number'] = ($cur_info['unit_number'] != '') ? '<p>Unit#: '.html_encode($cur_info['unit_number']).'</p>' : '';
		$page_param['td']['locations'] = ($cur_info['locations'] != '') ? '<p>'.html_encode($cur_info['locations']).'</p>' : '';

		$cur_info['realname'] = '<p class="fw-bold">'.html_encode($cur_info['realname']).'</p>';
		$cur_info['date_preinspection_start'] = (format_date($cur_info['date_preinspection_start']) != '') ? '<p>Start: '.format_date($cur_info['date_preinspection_start'], 'n/j/y').'</p>' : '';
		$cur_info['date_preinspection_end'] = (format_date($cur_info['date_preinspection_end']) != '') ? '<p>End: '.format_date($cur_info['date_preinspection_end'], 'n/j/y').'</p>' : '';

		$cur_info['city_engineer'] ='<p class="fw-bold">'.html_encode($cur_info['city_engineer']).'</p>';
		$cur_info['date_city_inspection_start'] = (format_date($cur_info['date_city_inspection_start']) != '') ? '<p>Start: '.format_date($cur_info['date_city_inspection_start'], 'n/j/y').'</p>' : '';
		$cur_info['date_city_inspection_end'] = (format_date($cur_info['date_city_inspection_end']) != '') ? '<p>End: '.format_date($cur_info['date_city_inspection_end'], 'n/j/y').'</p>' : '';
?>
				<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($cur_info['id'] == $pid) ? 'active' : '' ?>">
					<td class="td1">
						<?php echo html_encode($cur_info['pro_name']) ?>
						<?php echo $page_param['td']['unit_number'] ?>
						<?php echo $page_param['td']['locations'] ?>
						<p><a href="<?php echo $URL->link('hca_sb721_chart', $cur_info['id']) ?>" class="badge bg-primary text-white">View Chart</a></p>
					</td>
					<td>
						<?php echo $cur_info['realname'] ?>
						<?php echo $cur_info['date_preinspection_start'] ?>
						<?php echo $cur_info['date_preinspection_end'] ?>
					</td>
					<td class="min-150"><?php echo html_encode($cur_info['symptoms']) ?></td>
					<td class="min-150"><?php echo html_encode($cur_info['action']) ?></td>
					<td>
						<?php echo $cur_info['city_engineer'] ?>
						<?php echo $cur_info['date_city_inspection_start'] ?>
						<?php echo $cur_info['date_city_inspection_end'] ?>
					</td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
<?php
} else {
?>
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<?php
if ($pid > 0)
{
	$query = array(
		'SELECT'	=> 'pj.*, pt.pro_name, u.realname',
		'FROM'		=> 'hca_sb721_projects AS pj',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=pj.property_id'
			),
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=pj.performed_by'
			),
		),
		'WHERE'	=> 'pj.id='.$pid
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$project_info = $DBLayer->fetch_assoc($result);
	$HcaSB721Chart->addProject($project_info);

	$query = array(
		'SELECT'	=> 'v2.*, v1.vendor_name, v1.phone_number, v1.email',
		'FROM'		=> 'hca_sb721_vendors AS v2',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_vendors AS v1',
				'ON'			=> 'v1.id=v2.vendor_id'
			),
		),
		'ORDER BY'	=> 'v1.vendor_name',
		'WHERE'		=> 'v2.project_id='.$pid
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$project_vendors = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$project_vendors[] = $row;
	}
	$HcaSB721Chart->addVendors($project_vendors);

	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.date_time, e.message',
		'FROM'		=> 'sm_calendar_events AS e',
		'WHERE'		=> 'e.project_id='.$pid.' AND project_name=\'hca_sb721\'',
		'ORDER BY'	=> 'e.time'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$project_events = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$project_events[] = $row;
	}
	$HcaSB721Chart->addEvents($project_events);
?>
<script type="text/javascript">
function drawChart1() {
    var container = document.getElementById('chart_1');
    var chart = new google.visualization.Timeline(container);
    var dataTable = new google.visualization.DataTable();

    dataTable.addColumn({ type: 'string', id: 'Role' });
    dataTable.addColumn({ type: 'string', id: 'Name' });
	dataTable.addColumn({type: 'string', id: 'style', role: 'style'});
    dataTable.addColumn({ type: 'date', id: 'Start' });
    dataTable.addColumn({ type: 'date', id: 'End' });
    dataTable.addRows([
<?php 
			echo $HcaSB721Chart->genChartTimeline();
?>
	]);

    var options = {
		height: (dataTable.getNumberOfRows() * 44) + 42,
		colors: ['#f2a600', '#4FC3F7', '#00C853'],
    	//timeline: { groupByRowLabel: false }
    };
    chart.draw(dataTable, options);
}
google.charts.load("current", {packages:["timeline"]});
google.charts.setOnLoadCallback(drawChart1);
</script>

<script type="text/javascript">
// Caledar of events
function drawChart2()
{
	var dataTable = new google.visualization.DataTable();
	dataTable.addColumn({ type: 'date', id: 'Date' });
	dataTable.addColumn({ type: 'number', id: 'Won/Loss' });
	dataTable.addColumn({type: 'string', role: 'tooltip'});

	dataTable.addRows([
<?php 
			echo $HcaSB721Chart->genCalendarFollowUp();
?>
	]);

	var chart = new google.visualization.Calendar(document.getElementById('chart_2'));

	var options = {
		title: "",
		legend: 'none',
		height: 220,
		width: 1200,
	//minValue: 0,
		//colors: ['#f2a600', '#4FC3F7']
		calendar: { 
			cellSize: 20,
			cellColor: {
			stroke: '#76a7fa',
			strokeOpacity: 0.5,
			strokeWidth: 1,
		}
		}
	};

	google.visualization.events.addListener(chart, 'ready', function () {
		$($('#chart_2 text')[0]).text('');
		$($('#chart_2 text')[1]).text('');
		$($('#chart_2 text')[2]).text('');
	});

    chart.draw(dataTable, options);
}
google.charts.load("current", {packages:["calendar"]});
google.charts.setOnLoadCallback(drawChart2);
</script>
<?php
}
else
{
?>
<script type="text/javascript">
// List of Projects
function drawChart3()
{
	var container = document.getElementById('chart_3');
	var chart = new google.visualization.Timeline(container);
	var dataTable = new google.visualization.DataTable();
    dataTable.addColumn({ type: 'string', id: 'Role' });
    dataTable.addColumn({ type: 'string', id: 'Name' });
	dataTable.addColumn({type: 'string', role: 'style'});
    dataTable.addColumn({ type: 'date', id: 'Start' });
    dataTable.addColumn({ type: 'date', id: 'End' });

	dataTable.addRows([
<?php
			 echo $HcaSB721Chart->getAllProjects();
?>
  ]);

    var options = {
		height: (dataTable.getNumberOfRows() * 35) + 30,
		colors: ['#f2a600', '#4FC3F7', '#00C853'],

        annotations: {
            style: 'line'
        }
    };
    chart.draw(dataTable, options);
}
google.charts.load("current", {packages:["timeline"]});
google.charts.setOnLoadCallback(drawChart3);
</script>

<?php
}
require SITE_ROOT.'footer.php';
