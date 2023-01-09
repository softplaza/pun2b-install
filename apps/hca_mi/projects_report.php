<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$permission2 = ($User->checkPermissions('hca_mi', 2)) ? true : false; // view

$SwiftUploader = new SwiftUploader;
$Moisture = new Moisture;
$Hca5840Chart = new Hca5840Chart;
$HcaMi = new HcaMi;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$work_statuses = array(1 => 'IN PROGRESS', 2 => 'ON HOLD', 3 => 'COMPLETED', 0 => 'DELETE');
/*
if (isset($_POST['send_email']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$subject = isset($_POST['subject']) ? swift_trim($_POST['subject']) : '';
	$email_list = isset($_POST['email_list']) ? swift_trim($_POST['email_list']) : '';
	$mail_message = isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : '';
	
	$mailing_fields = isset($_POST['hca_5840_mailing_fields']) ? array_keys($_POST['hca_5840_mailing_fields']) : array();

	if ($project_id > 0)
	{
		$query = array(
			'SELECT'	=> 'pj.*, p.pro_name, u.realname AS performed_by',
			'FROM'		=> 'hca_5840_projects AS pj',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'p.id=pj.property_id'
				],
				[
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'u.id=pj.performed_uid'
				],
			],
			'WHERE'		=> 'pj.id='.$project_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$project_info = $DBLayer->fetch_assoc($result);
		
		$mail_subject = ($subject != '') ? $subject : 'HCA: Moisture Inspection';
		$mail_message = $mail_message."\n\n";
		
		if (!empty($project_info))
		{
			if (in_array('property_name', $mailing_fields))
				$mail_message .= 'Property: '.$project_info['pro_name']."\n\n";
			if (in_array('unit_number', $mailing_fields))
				$mail_message .= 'Unit #: '.$project_info['unit_number']."\n\n";
			if (in_array('location', $mailing_fields))
				$mail_message .= 'Location: '.$project_info['location']."\n\n";
			if (in_array('mois_report_date', $mailing_fields))
				$mail_message .= 'Report Date: '.date('m/d/Y', $project_info['mois_report_date'])."\n\n";
			if (in_array('mois_performed_by', $mailing_fields))
				$mail_message .= 'Performed by: '.$project_info['performed_by']."\n\n";
			if (in_array('mois_inspection_date', $mailing_fields))
				$mail_message .= 'Inspection Date: '.date('m/d/Y', $project_info['mois_inspection_date'])."\n\n";
			if (in_array('mois_source', $mailing_fields))
				$mail_message .= 'Source: '.$project_info['mois_source']."\n\n";
			if (in_array('symptoms', $mailing_fields))
				$mail_message .= 'Symptoms: '.$project_info['symptoms']."\n\n";
			if (in_array('action', $mailing_fields))
				$mail_message .= 'Action: '.$project_info['action']."\n\n";
			if (in_array('remarks', $mailing_fields))
				$mail_message .= 'Remarks: '.$project_info['remarks']."\n\n";
		}
	}
	
	if ($email_list != '' && $mail_message != '')
	{
		$SwiftMailer = new SwiftMailer;
		$SwiftMailer->isHTML();
		$SwiftMailer->send($email_list, $mail_subject, $mail_message);
		
		$query = array(
			'UPDATE'	=> 'hca_5840_projects',
			'SET'		=> 'email_status=1',
			'WHERE'		=> 'id='.$project_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$flash_message = 'Email has been sent to '.$email_list;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	else
		$Core->add_error('Your message is empty or there are no sender addresses.');
}
*/
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$search_by_unit = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_key_words = isset($_GET['key_words']) ? swift_trim($_GET['key_words']) : '';
$search_by_move_out_date = isset($_GET['move_out_date']) ? intval($_GET['move_out_date']) : 0;
$search_by_appendixb = isset($_GET['appendixb']) ? intval($_GET['appendixb']) : 0;
$search_by_leak_type = isset($_GET['leak_type']) ? intval($_GET['leak_type']) : -1;
$search_by_symptom_type = isset($_GET['symptom_type']) ? intval($_GET['symptom_type']) : 0;
$search_by_order_by = isset($_GET['order_by']) ? intval($_GET['order_by']) : 0;

$search_query = $order_by_query = $projects_info = $projects_ids = [];

$next_year = $search_by_year + 1;
$next_year = strtotime($next_year.'-01-01');

// SEARCH BY SECTION //
if ($search_by_year > 0)
	$search_query[] = 'pj.mois_report_date > '.strtotime($search_by_year.'-01-01').' AND pj.mois_report_date < '.$next_year;
if ($search_by_property_id > 0)
	$search_query[] = 'pj.property_id='.$search_by_property_id;
if ($search_by_leak_type > -1)
	$search_query[] = 'pj.leak_type='.$search_by_leak_type;
if ($search_by_symptom_type > 0)
	$search_query[] = 'pj.symptom_type='.$search_by_symptom_type;
if ($search_by_unit != '')
	$search_query[] = 'pj.unit_number LIKE \''.$DBLayer->escape('%'.$search_by_unit.'%').'\'';
if ($search_by_move_out_date > 0)
	$search_query[] = 'pj.moveout_date > 0';
if ($search_by_appendixb == 1)
	$search_query[] = 'pj.appendixb=1';

if ($search_by_order_by > 0 || ($search_by_year > 0 && $search_by_property_id > 0))
{
	$order_by_query[] = 'pj.mois_inspection_date';
	$search_by_order_by = 1;
}

$query = array(
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, p.pro_name, un.unit_number, u.realname',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=pj.performed_uid'
		],
	],
	'ORDER BY'	=> 'p.pro_name, LENGTH(pj.unit_number)',
	//'WHERE'		=> 'job_status!=0',
//		'LIMIT'		=> $PagesNavigator->limit(),
);
if (!empty($search_query))
{
	$search_query[] = '(pj.job_status=1 OR pj.job_status=3)'; // in-progress OR completed
	$query['WHERE'] = implode(' AND ', $search_query);

	if (!empty($order_by_query)) $query['ORDER BY'] = implode(', ', $order_by_query);

	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	while ($row = $DBLayer->fetch_assoc($result))
	{
		$projects_info[] = $row;
		$projects_ids[] = $row['id'];
	}
}

$query = array(
	'SELECT'	=> 'id, realname, email',
	'FROM'		=> 'users',
	'ORDER BY'	=> 'realname',
	//'WHERE'		=> 'hca_5840_access > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_manager = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$project_manager[$fetch_assoc['id']] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'ORDER BY'	=> 'p.pro_name',
	'WHERE'		=> 'p.enabled=1'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$Core->set_page_id('hca_mi_projects_report', 'hca_mi');
require SITE_ROOT.'header.php';
?>
	
<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="<?php echo get_current_url() ?>">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<select name="year" class="form-select form-select-sm">
						<option value="0">All Years</option>
<?php
for ($year = 2019; $year <= date('Y'); $year++)
{
	if ($search_by_year == $year)
		echo '<option value="'.$year.'" selected>'.$year.'</option>';
	else
		echo '<option value="'.$year.'">'.$year.'</option>';
}
?>
					</select>
				</div>
				<div class="col">
					<select name="property_id" class="form-select form-select-sm">
						<option value="">All Properties</option>
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
				<div class="col">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? html_encode($_GET['unit_number']) : '' ?>" placeholder="Enter Unit #" class="form-control form-control-sm">
				</div>
				<div class="col hidden">
					<input name="key_words" type="text" value="<?php echo $search_by_key_words ?>" placeholder="Key words" class="form-control form-control-sm">
				</div>
				<div class="col">
					<select name="leak_type" class="form-select form-select-sm">
						<option value="-1" selected>Source of Moisture</option>
<?php
foreach ($Hca5840Chart->leak_types as $key => $value)
{
	if ($search_by_leak_type == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}

if ($search_by_leak_type == 0)
{
	echo "\t\t\t\t\t\t\t".'<option value="0" selected>Unknown/Other</option>'."\n";
	$Hca5840Chart->leak_types[0] = 'Unknown/Other';
}
else
	echo "\t\t\t\t\t\t\t".'<option value="0">Unknown/Other</option>'."\n";
?>
					</select>
				</div>
				<div class="col">
					<select name="order_by" class="form-select form-select-sm">
<?php
$order_by = [
	0 => 'Sort by Unit #',
	1 => 'Inspection Date'
];
foreach ($order_by as $key => $value)
{
	if ($search_by_order_by == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col">
					<button class="btn btn-sm btn-outline-success" type="submit" name="search">Search</button>
					<a href="<?php echo $URL->link('hca_5840_projects_report') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
			<div class="row">
				<div class="col-md-auto">
					<input name="symptom_type" type="checkbox" value="1" <?php echo ($search_by_symptom_type == 1) ? 'checked' : '' ?> class="form-check-input" id="fld_symptom_type"> 
					<label class="form-check-label" for="fld_symptom_type">Discoloration</label>
				</div>	
				<div class="col-md-auto">
					<input name="move_out_date" type="checkbox" value="1" <?php echo ($search_by_move_out_date == 1) ? 'checked' : '' ?> class="form-check-input" id="form_move_out_date"> 
					<label class="form-check-label" for="form_move_out_date">Relocated</label>
				</div>
				<div class="col-md-auto">
					<input name="appendixb" type="checkbox" value="1" <?php echo ($search_by_appendixb == 1) ? 'checked' : '' ?> class="form-check-input" id="form_appendixb">  
					<label class="form-check-label" for="form_appendixb">Appendix-B</label>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php
if (!empty($projects_info)) 
{
?>

<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Number of Projects per Month</h6>
	</div>
	<div class="card-body py-3">
		<div class="chart chart-sm">
			<canvas id="chartjs-dashboard-line"></canvas>
		</div>
	</div>
</div>
<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Number of Projects per Property</h6>
	</div>
	<div class="card-body py-3">
		<div class="chart chart-sm">
			<canvas id="chartjs-dashboard-bar"></canvas>
		</div>
	</div>
</div>
<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Source of moisture & Symptoms (The total number of symptoms in all projects)</h6>
	</div>
	<div class="card-body py-3">
		<div class="chart chart-sm">
			<canvas id="chartjs-dashboard-pie-pillars"></canvas>
		</div>
	</div>
</div>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Total: <?php echo count($projects_ids) ?> (Limit 100 items on this page)</h6>
		</div>
	</div>
	<table class="table table-striped my-0">
		<thead>
			<tr>
				<th class="th1">Property/Unit#</th>
				<th>Date Reported</th>
				<th>Move Out Date</th>
				<th>Date of inspection</th>
				<th>Source of moisture</th>
				<th>Symptoms</th>
				<th>Action</th>
				<th>Remarks</th>
				<th>Job Status</th>
			</tr>
		</thead>
		<tbody>
<?php
	$time_now = time();
	$job_titles = [
		1 => '<span class="badge badge-warning">IN PROGRESS</span>', 
		2 => '<span class="badge badge-secondary">ON HOLD</span>', 
		3 => '<span class="badge badge-success">COMPLETED</span>', 
		0 => '<span class="badge badge-danger">REMOVED</span>'
	];

	if (!empty($projects_ids))
	{
		$query = array(
			'SELECT'	=> 'id, table_id',
			'FROM'		=> 'sm_uploader',
			'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'hca_5840_projects\''
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$uploader_info = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$uploader_info[] = $row['table_id'];
		}
	}

	$i = 0;
	foreach ($projects_info as $cur_info)
	{
		if ($i < 100)
		{
			$td = $page_param['td'] = [];

			$td['property_info']['pro_name'] = '<p>'.html_encode($cur_info['pro_name']).'</p>';
			
			$cur_info['unit_number'] = ($cur_info['unit_number'] != '') ? '<p>Unit: '.html_encode($cur_info['unit_number']).'</p>' : '<p class="text-danger">'.html_encode($cur_info['unit']).'</p>';
			$td['property_info'][] = $cur_info['unit_number'];

			$td['property_info'][] = '<p>'.str_replace(',', ', ', $cur_info['location']).'</p>';
			
			$page_param['td']['mois_report_date'] = format_time($cur_info['mois_report_date'], 1);
			$page_param['td']['moveout_date'] = format_time($cur_info['moveout_date'], 1);
			
			$td['mois_inspection'][] = '<p class="fw-bold">'.format_time($cur_info['mois_inspection_date'], 1).'</p>';
			$td['mois_inspection'][] = '<p>'.($cur_info['performed_uid'] > 0 ? html_encode($cur_info['realname']) : html_encode($cur_info['mois_performed_by'])).'</p>';

			$page_param['td']['leak_type'] = ($cur_info['leak_type'] > 0 ? '<p class="fw-bold">'.html_encode($HcaMi->leak_types[$cur_info['leak_type']]).'</p>' : '');

			$page_param['td']['mois_source'] = html_encode($cur_info['mois_source']);
			$page_param['td']['symptoms'] = html_encode($cur_info['symptoms']);
			$page_param['td']['action'] = html_encode($cur_info['action']);
			$page_param['td']['remarks'] = html_encode($cur_info['remarks']);

			$td['property_info'][] = ($User->checkAccess('hca_mi', 14) && in_array($cur_info['id'], $uploader_info)) ? '<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'" class="btn btn-sm btn-success text-white">Files</a>' : '';

			if ($permission2)
			{
				if ($User->checkAccess('hca_mi', 12))
					$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_project', $cur_info['id']).'"><i class="fas fa-edit"></i> View Project</a>');
				if ($User->checkAccess('hca_mi', 14))
					$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'"><i class="far fa-image"></i> View Files</a>');
				if ($User->checkAccess('hca_mi', 13))
				//	$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_invoice', $cur_info['id']).'"><i class="fas fa-file-invoice-dollar"></i> Invoice</a>');
				if ($User->checkAccess('hca_mi', 16))
				{
					$email_param = [];
					$email_param[] = 'mailto:'.html_encode($Config->get('o_hca_5840_mailing_list'));
					$email_param[] = '?subject=HCA: Moisture Inspection';
					$email_param[] = '&amp;body=Property: '.html_encode($cur_info['pro_name']);
					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Unit #: '.html_encode($cur_info['unit_number']);
					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Location: '.html_encode(str_replace(',', ', ', $cur_info['location']));

					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Inspection Date: '.format_time($cur_info['mois_inspection_date'], 1);
					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Inspected by: '.($cur_info['performed_uid'] > 0 ? html_encode($cur_info['realname']) : html_encode($cur_info['mois_performed_by']));

					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Source: '.html_encode($cur_info['mois_source']);
					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Symptoms: '.html_encode($cur_info['symptoms']);	
					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Action: '.html_encode($cur_info['action']);	
					$email_param[] = '%0D%0A%0D%0A'; // 2 lines spaces
					$email_param[] = 'Remarks: '.html_encode($cur_info['remarks']);

					$Core->add_dropdown_item('<a href="'.implode('', $email_param).'"><i class="far fa-envelope"></i> Send Email</a>');
					//$Core->add_dropdown_item('<a href="#!" onclick="emailWindow('.$cur_info['id'].')"><i class="far fa-envelope"></i> Send Email</a>');
				}
			}

			$td['property_info'][] = '<span class="float-end">'.$Core->get_dropdown_menu($cur_info['id']).'</span>';
?>
			<tr id="row<?php echo $cur_info['id'] ?>">
				<td class="td1"><?php echo implode("\n", $td['property_info']) ?></td>
				<td><?php echo $page_param['td']['mois_report_date'] ?></td>
				<td><?php echo $page_param['td']['moveout_date'] ?></td>
				
				<td><?php echo implode("\n", $td['mois_inspection']) ?></td>

				<td>
					<?php echo $page_param['td']['leak_type'] ?>
					<p><?php echo $page_param['td']['mois_source'] ?></p>
				</td>
				<td>
					<p class="fw-bold"><?php echo (isset($HcaMi->symptoms[$cur_info['symptom_type']]) ? $HcaMi->symptoms[$cur_info['symptom_type']] : '') ?></p>
					<?php echo $page_param['td']['symptoms'] ?>
				</td>
				<td><?php echo $page_param['td']['action'] ?></td>
				<td><?php echo $page_param['td']['remarks'] ?></td>
				<td><?php echo $job_titles[$cur_info['job_status']] ?></td>
			</tr>
<?php
		}

		// Build Chart information
		$Hca5840Chart->addToMonth($cur_info['mois_report_date']);
		$Hca5840Chart->addProperty($cur_info['pro_name']);

		if ($cur_info['symptom_type'] == 1)
			$Hca5840Chart->addKeyWord('Discoloration');

		if ($cur_info['moveout_date'] > 0)
			$Hca5840Chart->addKeyWord('Relocation');

		$Hca5840Chart->addSoM($cur_info['leak_type']);

		++$i;
	}
?>
		</tbody>
	</table>
</form>
<?php
}
else
{
?>
<div class="card">
	<div class="card-body">
		<div class="alert alert-warning" role="alert">You have no items on this page or not found within your search criteria.</div>
	</div>
</div>
<?php
}

$MonthlyFrequency = $Hca5840Chart->getMonthlyFrequency();
$monthly_names = array_keys($MonthlyFrequency);
$monthly_counters = array_values($MonthlyFrequency);

$PropertyFrequency = $Hca5840Chart->getPropertyFrequency();
$property_names = array_keys($PropertyFrequency);
$property_counters = array_values($PropertyFrequency);

$PropertySymptoms = $Hca5840Chart->getSoM();
$symptom_names = array_keys($PropertySymptoms);
$symptom_counters = array_values($PropertySymptoms);

//print_dump($PropertySymptoms);
?>

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
				label: "Projects",
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
				label: "Projects",
				backgroundColor: window.theme.info,
				borderColor: window.theme.info,
				hoverBackgroundColor: window.theme.info,
				hoverBorderColor: window.theme.info,
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
			labels: [<?php echo implode(', ', $symptom_names) ?>],

			datasets: [{
				data: [<?php echo implode(', ', $symptom_counters) ?>],
				backgroundColor: [
<?php
foreach($Hca5840Chart->symptoms_colors as $key => $color){
	echo $color.',';
}
?>
				],
				borderWidth: 5
			}]
		},
		options: {
			//indexAxis: 'y',
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

<script>

function emailWindow(id){
	var pos = $("#row"+id).position();
	var posT = pos.top - 145;
	
	$(".email-window").slideToggle("2000");
	$(".email-window").css("top", posT + "px");
	
	$('.email-window input[name="project_id"]').val(id);
}

function closeWindows(){
	$(".email-window").css("display","none");
	$('.email-window input[name="project_id"]').val("0");
}
window.onload = function(){
	$(document).mouseup(function(e) 
	{
		var container = $(".pop-up-window, .email-window");
		if (!container.is(e.target) && container.has(e.target).length === 0) {
			closeWindows();
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';