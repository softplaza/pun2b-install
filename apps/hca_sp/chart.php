<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$permission_level2 = ($User->checkAccess('hca_sp', 12)) ? true : false;

$HcaSPChart = new HcaSPChart;

$work_statuses = array(
	//0 => 'All Statuses',
	1 => 'Active',
	2 => 'Bid Phase',
	6 => 'Contract Phase',
	7 => 'Job Phase',
	3 => 'Pending',
	4 => 'On Hold',
	5 => 'Completed',
);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_work_status = isset($_GET['work_status']) ? intval($_GET['work_status']) : 0;
$search_by_start_project = isset($_GET['start']) ? swift_trim($_GET['start']) : '';
$search_by_end_project = isset($_GET['end']) ? swift_trim($_GET['end']) : '';

$search_query = [];
$search_query[] = 'sp.work_status!=0';
if ($search_by_property_id > 0)
	$search_query[] = 'sp.property_id='.$search_by_property_id;

if ($search_by_user_id > 0)
	$search_query[] = 'sp.project_manager_id='.$search_by_user_id;

if ($search_by_work_status > 0)
	$search_query[] = 'sp.work_status='.$search_by_work_status;
else
	$search_query[] = 'sp.work_status!=5';

if ($search_by_start_project != '')
	$search_query[] = 'sp.date_bid_start >= \''.$search_by_start_project.'-01'.'\'';

if ($search_by_end_project != '')
	$search_query[] = 'sp.date_job_end <= \''.$search_by_end_project.'-01'.'\'';

$query = array(
	'SELECT'	=> 'sp.*, pt.pro_name, u1.realname AS first_manager, u2.realname AS second_manager',
	'FROM'		=> 'sm_special_projects_records AS sp',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=sp.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=sp.project_manager_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=sp.second_manager_id'
		),
	),
	'ORDER BY'	=> 'pt.pro_name',
	'LIMIT'		=> $Config->get('o_max_items_on_page')
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

$projects_info = $projects_ids = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$projects_info[] = $fetch_assoc;
	$projects_ids[] = $fetch_assoc['id'];
}

$project_managers = $User->getUserAccess('hca_sp', 14, 1);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="mailto:@hcares?subject=Project & Construction Department&amp;body='.get_current_url().'" target="_blank"><i class="fas fa-share-alt"></i> Share link</a></li>');

$Core->set_page_id('sm_special_projects_chart', 'sm_special_projects');
require SITE_ROOT.'header.php';
?>

<style>
.table td {min-width:120px}
.table .comment {min-width:250px}

.table .ta-center {text-align:center}
.mw-200 {min-width:200px}
</style>

	<div class="card" style="background: #a9d9c5;">
		<div class="card-header">
			<h5 class="card-title mb-0">Project & Construction Department</h5>
		</div>
	</div>
	<nav class="search-bar navbar-light py-3 mb-3" style="background-color: #cff4fc;">
		<form method="get" accept-charset="utf-8" action="<?php echo get_current_url() ?>">
			<input name="id" type="hidden" value="0"/>
			<div class="container-fluid justify-content-between">
				<div class="row">
					<div class="col">
						<label>Properties</label>
						<select name="property_id" class="form-select">
							<option value="0">All Properties</option>
<?php
foreach ($property_info as $val)
{
			if ($search_by_property_id == $val['id'])
				echo '<option value="'.$val['id'].'" selected="selected">'.$val['pro_name'].'</option>';
			else
				echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
						</select>
					</div>
					<div class="col">
						<label>Project Managers</label>
						<select name="user_id" class="form-select">
							<option value="0">All Project Managers</option>
<?php 
foreach ($project_managers as $user)
{
			if ($search_by_user_id == $user['id'])
				echo '<option value="'.$user['id'].'" selected>'.$user['realname'].'</option>';
			else
				echo '<option value="'.$user['id'].'">'.$user['realname'].'</option>';
} 
?>
						</select>
					</div>

					<div class="col">
						<label>Start Date</label>
						<input type="month" name="start" value="<?php echo $search_by_start_project ?>" class="form-control">
					</div>
					<div class="col">
						<label>End Date</label>
						<input type="month" name="end" value="<?php echo $search_by_end_project ?>" class="form-control">
					</div>
					<div class="col">
						<label>Focus on</label>
						<select name="work_status" class="form-select">
<?php
echo '<option value="0" selected>All Statuses</option>';
foreach ($work_statuses as $key => $value)
{
			if ($search_by_work_status == $key)
				echo '<option value="'.$key.'" selected>'.$value.'</option>';
			else
				echo '<option value="'.$key.'">'.$value.'</option>';
} 
?>
						</select>
					</div>

					<div class="col" style="padding-top: 1.4em;">
						<button class="btn btn-outline-success" type="submit">Search</button>
						<a href="<?php echo $URL->link('sm_special_projects_chart', [0,0,0,'','',0]) ?>" class="btn btn-outline-secondary">Reset</a>
					</div>
				</div>
			</div>
		</form>
	</nav>

<?php
if (!empty($projects_info))
{
	if ($id > 0) 
	{
		$query = array(
			'SELECT'	=> 'sp.*, pt.pro_name, u1.realname AS first_manager, u2.realname AS second_manager',
			'FROM'		=> 'sm_special_projects_records AS sp',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'	=> 'sm_property_db AS pt',
					'ON'			=> 'pt.id=sp.property_id'
				),
				array(
					'LEFT JOIN'		=> 'users AS u1',
					'ON'			=> 'u1.id=sp.project_manager_id'
				),
				array(
					'LEFT JOIN'		=> 'users AS u2',
					'ON'			=> 'u2.id=sp.second_manager_id'
				),
			),
			'ORDER BY'	=> 'sp.property_name, pt.pro_name',
			'WHERE'		=> 'sp.id='.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$project_info = $DBLayer->fetch_assoc($result);

		$HcaSPChart->addDetailedData($project_info);
?>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Project of <?php echo $project_info['first_manager'] ?></h6>
		</div>
		<div class="card-body py-3">
			<div class="chart chart-sm">
				<img src="img/chart_gantt_legends.png" style="height:29px"/>
				<div id="detailed_project" style="height: 230px"></div>
			</div>
		</div>
	</div>

<?php
	}
?>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Progress of Projects</h6>
		</div>
		<div class="card-body py-3">
			<div class="chart chart-sm">
				<img src="img/chart_legends.png"/>
				<div id="project_list"></div>
			</div>
		</div>
	</div>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">List of Projects (<?php echo count($projects_info) ?>)</h6>
			</div>
		</div>
		<table class="table table-striped table-bordered my-0">
			<thead>
				<tr>
					<th class="th1">Property</th>
					<th>Project Number</th>
					<th>Project Description</th>
					<th>Action Owner</th>
					<th>Action Date</th>
					<th>Bid Dates</th>
					<th>Contract Dates</th>
					<th>Job Dates</th>
					<th>Cost</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($projects_info as $cur_info)
	{
		if ($permission_level2)
		{
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_files', $cur_info['id']).'"><i class="far fa-image"></i> Upload Files</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_invoice', $cur_info['id']).'"><i class="fas fa-file-invoice-dollar"></i> Invoice</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_follow_up', $cur_info['id']).'"><i class="far fa-calendar-alt"></i> Follow-Up Dates</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_recommendations', $cur_info['id']).'"><i class="fas fa-info-circle"></i> Recomendations</a>');

			$dropdown_menu = '<span class="float-end">'.$Core->get_dropdown_menu($cur_info['id']).'</span>';
		}
		else
			$dropdown_menu = '';

		$property_name = $cur_info['property_name'] != '' ? $cur_info['property_name'] : $cur_info['pro_name'];
		$unit_number = ($cur_info['unit_number'] != '') ? '<p>Unit#: '.html_encode($cur_info['unit_number']).'</p>' : '';
		$project_manager = $cur_info['first_manager'] != '' ? $cur_info['first_manager'] : $cur_info['second_manager'];
		$job_status = isset($job_titles[$cur_info['work_status']]) ? $job_titles[$cur_info['work_status']] : '';

		$project_number = ($User->checkAccess('hca_sp', 12)) ? '<a href="'.$URL->link('sm_special_projects_manage', $cur_info['id']).'">'.html_encode($cur_info['project_number']).'</a>' : html_encode($cur_info['project_number']);

		$date_bid_start = (format_date($cur_info['date_bid_start'], 'n/j/y') != '') ? 'Start: <span style="font-weight:bold">'.format_date($cur_info['date_bid_start'], 'n/j/y').'</span>' : '';
		$date_bid_end = (format_date($cur_info['date_bid_end'], 'n/j/y') != '') ? 'End: <span style="font-weight:bold">'.format_date($cur_info['date_bid_end'], 'n/j/y').'</span>' : '';

		$date_contract_start = (format_date($cur_info['date_contract_start'], 'n/j/y') != '') ? 'Start: <span style="font-weight:bold">'.format_date($cur_info['date_contract_start'], 'n/j/y').'</span>' : '';
		$date_contract_end = (format_date($cur_info['date_contract_end'], 'n/j/y') != '') ? 'End: <span style="font-weight:bold">'.format_date($cur_info['date_contract_end'], 'n/j/y').'</span>' : '';

		$date_job_start = (format_date($cur_info['date_job_start'], 'n/j/y') != '') ? 'Start: <span style="font-weight:bold">'.format_date($cur_info['date_job_start'], 'n/j/y').'</span>' : '';
		$date_job_end = (format_date($cur_info['date_job_end'], 'n/j/y') != '') ? 'End: <span style="font-weight:bold">'.format_date($cur_info['date_job_end'], 'n/j/y').'</span>' : '';

		$work_status_title = isset($work_statuses[$cur_info['work_status']]) ? $work_statuses[$cur_info['work_status']] : '';
		if ($cur_info['work_status'] == 2)
			$css_status = 'bg-warning fw-bold';
		else if ($cur_info['work_status'] == 6)
			$css_status = 'bg-primary fw-bold text-white';
		else if ($cur_info['work_status'] == 7)
			$css_status = 'bg-success fw-bold text-white';
		else if ($cur_info['work_status'] == 3)
			$css_status = 'bg-info fw-bold';
		else if ($cur_info['work_status'] == 4)
			$css_status = 'bg-secondary fw-bold text-white';
		else
			$css_status = 'fw-bold';
?>
				<tr id="row<?php echo $cur_info['id'] ?>">
					<td class="td1">
						<?php echo html_encode($property_name) ?>
						<?php echo $unit_number ?>
						<?php echo $dropdown_menu ?>
					</td>
					<td class="ta-center"><?php echo $project_number ?></td>
					<td><?php echo html_encode($cur_info['project_desc']) ?></td>
					<td class="ta-center fw-bold"><?php echo html_encode($project_manager) ?></td>
					<td class="ta-center"><?php echo format_date($cur_info['date_action_start'], 'n/j/y') ?></td>
					<td class="ta-center">
						<?php echo $date_bid_start ?>
						<p><?php echo $date_bid_end ?></p>
					</td>
					<td class="ta-center">
						<?php echo $date_contract_start ?>
						<p><?php echo $date_contract_end ?></p>
					</td>
					<td class="ta-center">
						<?php echo $date_job_start ?>
						<p><?php echo $date_job_end ?></p>
					</td>
					<td class="ta-center">$<?php echo gen_number_format($cur_info['cost'], 2) ?></td>
					<td class="ta-center <?php echo $css_status ?>"><?php echo $work_status_title ?></td>
				</tr>
<?php
		$HcaSPChart->addTimeLineProgressData($cur_info);
	}

?>
			</tbody>
		</table>
		<div class="card-header">
			<h6 class="card-title mb-0">Total: <?php echo count($projects_info) ?> (Limit <?php echo $Config->get('o_max_items_on_page') ?> items on this page)</h6>
		</div>
	</form>
<?php

} else {
?>
	<div class="card">
		<div class="card-body">
		<div class="alert alert-warning" role="alert">You have no items on this page or not found within your search criteria.</div>
		</div>
	</div>
<?php
}
?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
// Gantt Chart
function detailedChart()
{
	var otherData = new google.visualization.DataTable();
	otherData.addColumn("string", "Task ID");
	otherData.addColumn("string", "Task Name");
	otherData.addColumn("string", "Project Manager");
	otherData.addColumn("date", "Start");
	otherData.addColumn("date", "End");
	otherData.addColumn("number", "Duration");
	otherData.addColumn("number", "Percent Complete");
	otherData.addColumn("string", "Dependencies");

	otherData.addRows([
		<?php echo $HcaSPChart->getDetailedChart() ?>
	]);

	var options = {
		colors: ['#f2a600', '#4FC3F7', '#00C853'],
		height: 350,
		gantt: {
			trackHeight: 50,
			palette: [
				{//Grey
					"color": "#6c757d",
					"dark": "#6c757d",
					"light": "#6c757d"
				},
				{//orange
					"color": "#ffbc00",
					"dark": "#ffbc00",
					"light": "#ffbc00"
				},
				{//blue
					"color": "#5e97f6",
					"dark": "#2a56c6",
					"light": "#c6dafc"
				},
				{//green
					"color": "#0f9d58",
					"dark": "#0b8043",
					"light": "#b7e1cd"
				},
			]
		},
	};

	var chart = new google.visualization.Gantt(
		document.getElementById("detailed_project")
	);

	chart.draw(otherData, options);
}
google.charts.load('current', {'packages':['gantt']});
google.charts.setOnLoadCallback(detailedChart);
</script>

<script type="text/javascript">
// List of Projects
google.charts.load('current', {packages:['timeline']}).then(function ()
{
	var container = document.getElementById('project_list');
	var chart = new google.visualization.Timeline(container);
	var dataTable = new google.visualization.DataTable();
	dataTable.addColumn({type: 'string', id: 'Category'});
	dataTable.addColumn({type: 'string', id: 'Project'});
	dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
	dataTable.addColumn({type: 'string', id: 'style', role: 'style'});
	dataTable.addColumn({type: 'date', id: 'Start'});
	dataTable.addColumn({type: 'date', id: 'End'});

	dataTable.addRows([
		<?php echo $HcaSPChart->getTimeLineLinkedData() ?>
  ]);

  var height = dataTable.getNumberOfRows() * 33 + 60;

	var options = {
		colors: ['#f2a600', '#4FC3F7', '#00C853'],
		height: (dataTable.getNumberOfRows() * 44) + 42,
		tooltip: {isHtml: true},
		timeline: {
			rowLabelStyle: {
				color: '#3399cc',
			}
		},

		// Customizing
		'height': height,
		//'chartArea': {'width': '100%', 'height': '80%'},
		//'legend': {'position': 'bottom'}

	};

	function readyHandler() {
		var labels = container.getElementsByTagName('text');
		Array.prototype.forEach.call(labels, function(label) {
			if (label.getAttribute('fill') === options.timeline.rowLabelStyle.color) {
				label.addEventListener('click', clickHandler);
				label.setAttribute('style', 'cursor: pointer; font-weight: bold;');
			}
		});
	}

	function clickHandler(sender) {
		var rowLabel = sender.target.textContent;
		var dataRows = dataTable.getFilteredRows([{
			column: 0,
			value: rowLabel
		}]);
		if (dataRows.length > 0) {
			var link = dataTable.getProperty(dataRows[0], 0, 'link');
			window.open(link, '_self');
		}
	}

	google.visualization.events.addListener(chart, 'ready', readyHandler);
	chart.draw(dataTable, options);
});
</script>

<?php
require SITE_ROOT.'footer.php';