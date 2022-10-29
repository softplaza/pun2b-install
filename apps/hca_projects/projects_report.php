<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 2) || $User->get('hca_5840_access') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;
$Moisture = new Moisture;
$Hca5840Chart = new Hca5840Chart;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$work_statuses = array(1 => 'IN PROGRESS', 2 => 'ON HOLD', 3 => 'COMPLETED', 0 => 'DELETE');

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
			'SELECT'	=> '*',
			'FROM'		=> 'hca_5840_projects',
			'WHERE'		=> 'id='.$project_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$project_info = $DBLayer->fetch_assoc($result);
		
		$mail_subject = ($subject != '') ? $subject : 'HCA: Moisture Inspection';
		$mail_message = $mail_message."\n\n";
		
		if (!empty($project_info))
		{
			if (in_array('property_name', $mailing_fields))
				$mail_message .= 'Property: '.$project_info['property_name']."\n\n";
			if (in_array('unit_number', $mailing_fields))
				$mail_message .= 'Unit #: '.$project_info['unit_number']."\n\n";
			if (in_array('location', $mailing_fields))
				$mail_message .= 'Location: '.$project_info['location']."\n\n";
			if (in_array('mois_report_date', $mailing_fields))
				$mail_message .= 'Report Date: '.date('m/d/Y', $project_info['mois_report_date'])."\n\n";
			if (in_array('mois_performed_by', $mailing_fields))
				$mail_message .= 'Performed by: '.$project_info['mois_performed_by']."\n\n";
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

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$search_by_unit = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_key_words = isset($_GET['key_words']) ? swift_trim($_GET['key_words']) : '';
$search_by_move_out_date = isset($_GET['move_out_date']) ? intval($_GET['move_out_date']) : 0;
$search_by_appendixb = isset($_GET['appendixb']) ? intval($_GET['appendixb']) : 0;

$projects_info = $projects_ids = array();

if ($search_by_property_id > 0 || $search_by_unit != '' || $search_by_key_words != '' || $search_by_move_out_date > 0 || $search_by_year > 0 || $search_by_appendixb == 1)
{
	$next_year = $search_by_year + 1;
	$next_year = strtotime($next_year.'-01-01');

	$query = array(
		'SELECT'	=> '*',
		'FROM'		=> 'hca_5840_projects',
		'ORDER BY'	=> 'property_name, unit_number',
		'WHERE'		=> 'job_status!=0',
//		'LIMIT'		=> $PagesNavigator->limit(),
	);
	// SEARCH BY SECTION //
	if ($search_by_property_id > 0)
		$query['WHERE'] .= ' AND property_id='.$search_by_property_id;
	
	if ($search_by_year > 0) {
		$query['WHERE'] .= ' AND mois_report_date > '.strtotime($search_by_year.'-01-01').' AND mois_report_date < '.$next_year;
	}
	if ($search_by_unit != '') {
		$search_by_unit2 = '%'.$search_by_unit.'%';
		$query['WHERE'] .= ' AND unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
	}
	if ($search_by_key_words != '') {
		$search_by_key_words2 = '%'.$search_by_key_words.'%';
		$query['WHERE'] .= ' AND (mois_source LIKE \''.$DBLayer->escape($search_by_key_words2).'\'';
		$query['WHERE'] .= ')';
	}
	if ($search_by_move_out_date > 0)
		$query['WHERE'] .= ' AND moveout_date > 0';
	if ($search_by_appendixb == 1)
		$query['WHERE'] .= ' AND appendixb=1';
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$projects_info[] = $fetch_assoc;
		$projects_ids[] = $fetch_assoc['id'];
	}
}

$query = array(
	'SELECT'	=> 'id, realname, email',
	'FROM'		=> 'users',
	'ORDER BY'	=> 'realname',
	'WHERE'		=> 'hca_5840_access > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_manager = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$project_manager[$fetch_assoc['id']] = $fetch_assoc;
}

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

$Core->set_page_id('hca_5840_projects_report', 'hca_5840');
require SITE_ROOT.'header.php';
?>

<style>
.email-window {width: 300px;position: absolute;background: #dcf5e5;padding: 5px;line-height: 23px;border-radius: 10px;border: 1px solid #A5A5A5;z-index: 300;opacity: 0.9;right: 200px;}
.email-window .btn-action{text-align: center;}
.mailing-fields{columns: 2;padding-left: 5px;}
.subject input, .email-window textarea{width:97%;}
.close-window{cursor: pointer;float: right;position: relative;z-index: 300;}
.search-light{color:#ff5e00;background:#03ffc5;}
table .th-date{width: 100px;}
table .td1{max-width: 160px;}
</style>
	
	<nav class="navbar navbar-light" style="background-color: #cff4fc">
		<form method="get" accept-charset="utf-8" action="<?php echo get_current_url() ?>">
			<input name="section" type="hidden" value="<?php echo $section ?>"/>
			<div class="container-fluid justify-content-between">
				<div class="row">
					<div class="col">
						<select name="year" class="form-control">
							<option value="0">All Years</option>
<?php for ($year = 2019; $year <= date('Y', time()); $year++){
			if ($search_by_year == $year)
				echo '<option value="'.$year.'" selected="selected">'.$year.'</option>';
			else
				echo '<option value="'.$year.'">'.$year.'</option>';
} ?>
						</select>
					</div>
					<div class="col">
						<select name="property_id" class="form-control">
							<option value="">All Properties</option>
<?php foreach ($property_info as $val){
			if ($search_by_property_id == $val['id'])
				echo '<option value="'.$val['id'].'" selected="selected">'.$val['pro_name'].'</option>';
			else
				echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
} ?>
						</select>
					</div>
					<div class="col">
						<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? html_encode($_GET['unit_number']) : '' ?>" placeholder="Enter Unit #" size="10" class="form-control">
					</div>
					<div class="col">
						<input name="key_words" type="text" value="<?php echo $search_by_key_words ?>" placeholder="Key words" size="20" class="form-control">
					</div>
					<div class="col">
						<div class="form-check">
							<input name="move_out_date" type="checkbox" value="1" <?php echo ($search_by_move_out_date == 1) ? 'checked="checked"' : '' ?> class="form-check-input" id="form_move_out_date"> 
							<label class="form-check-label" for="form_move_out_date">Relocated</label>
						</div>
						<div class="form-check">
							<input name="appendixb" type="checkbox" value="1" <?php echo ($search_by_appendixb == 1) ? 'checked="checked"' : '' ?> class="form-check-input" id="form_appendixb">  
							<label class="form-check-label" for="form_appendixb">App-xB</label>
						</div>
					</div>
					<div class="col">
						<button class="btn btn-outline-success" type="submit" name="search">Search</button>
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
			<h6 class="card-title mb-0">Months</h6>
		</div>
		<div class="card-body py-3">
			<div class="chart chart-sm">
				<canvas id="chartjs-dashboard-line"></canvas>
			</div>
		</div>
	</div>
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Properties</h6>
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
					<h6 class="card-title mb-0">Source of moisture</h6>
				</div>
				<div class="card-body py-3">
					<div class="chart chart-sm">
						<canvas id="chartjs-dashboard-pie-pillars"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Moisture Report</h6>
			</div>
		</div>
		<table class="table table-striped my-0">
			<thead>
				<tr>
					<th class="th1">Property/Unit#</th>
					<th class="th-date">Date Reported</th>
					<th class="th-date">Move-Out Date</th>
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
		1 => '<span class="badge bg-warning">IN PROGRESS</span>', 
		2 => '<span class="badge bg-secondary">ON HOLD</span>', 
		3 => '<span class="badge bg-success">COMPLETED</span>', 
		0 => '<span class="badge bg-danger">DELETED</span>'
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
			$page_param['td'] = array();
			$page_param['td']['property_name'] = html_encode($cur_info['property_name']);
			$page_param['td']['unit_number'] = !empty($cur_info['unit_number'])? 'Unit: '.html_encode($cur_info['unit_number']) : '';
			$page_param['td']['location'] = html_encode($cur_info['location']);
			
			$page_param['td']['mois_report_date'] = format_time($cur_info['mois_report_date'], 1);
			$page_param['td']['moveout_date'] = format_time($cur_info['moveout_date'], 1);
			
			$page_param['td']['mois_inspection_date'] = format_time($cur_info['mois_inspection_date'], 1);
			$page_param['td']['mois_performed_by'] = html_encode($cur_info['mois_performed_by']);
			
			if ($search_by_key_words != '')
			{
				$search_str = '<span class="search-light">'.$search_by_key_words.'</span>';
				$page_param['td']['mois_source'] = preg_replace('/'.$search_by_key_words.'/i', $search_str, html_encode($cur_info['mois_source']));
				$page_param['td']['symptoms'] = preg_replace('/'.$search_by_key_words.'/i', $search_str, html_encode($cur_info['symptoms']));
				$page_param['td']['action'] = preg_replace('/'.$search_by_key_words.'/i', $search_str, html_encode($cur_info['action']));
				$page_param['td']['remarks'] = preg_replace('/'.$search_by_key_words.'/i', $search_str, html_encode($cur_info['remarks']));
			} else {
				$page_param['td']['mois_source'] = html_encode($cur_info['mois_source']);
				$page_param['td']['symptoms'] = html_encode($cur_info['symptoms']);
				$page_param['td']['action'] = html_encode($cur_info['action']);
				$page_param['td']['remarks'] = html_encode($cur_info['remarks']);
			}
			
			$view_files = ($User->checkAccess('hca_mi', 14) && in_array($cur_info['id'], $uploader_info)) ? '<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'" class="btn btn-sm btn-success text-white">Files</a>' : '';

			if ($access)
			{
				if ($User->checkAccess('hca_mi', 12))
					$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_project', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');
				if ($User->checkAccess('hca_mi', 14))
					$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_files', $cur_info['id']).'"><i class="far fa-image"></i> View Files</a>');
				if ($User->checkAccess('hca_mi', 13))
					$Core->add_dropdown_item('<a href="'.$URL->link('hca_5840_manage_invoice', $cur_info['id']).'"><i class="fas fa-file-invoice-dollar"></i> View Invoice</a>');
				if ($User->checkAccess('hca_mi', 16))
					$Core->add_dropdown_item('<a href="#!" onclick="emailWindow('.$cur_info['id'].')"><i class="far fa-envelope"></i> Send Email</a>');
			}
?>
				<tr id="row<?php echo $cur_info['id'] ?>">
					<td class="td1">
						<p><?php echo $page_param['td']['property_name'] ?></p>
						<p><?php echo $page_param['td']['unit_number'] ?></p>
						<p><?php echo $page_param['td']['location'] ?></p>
						<?php echo $view_files ?>
						<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
					</td>
					<td><?php echo $page_param['td']['mois_report_date'] ?></td>
					<td><?php echo $page_param['td']['moveout_date'] ?></td>
					<td>
						<p><?php echo $page_param['td']['mois_performed_by'] ?></p>
						<p><?php echo $page_param['td']['mois_inspection_date'] ?></p>
					</td>
					<td><?php echo $page_param['td']['mois_source'] ?></td>
					<td><?php echo $page_param['td']['symptoms'] ?></td>
					<td><?php echo $page_param['td']['action'] ?></td>
					<td><?php echo $page_param['td']['remarks'] ?></td>
					<td><?php echo $job_titles[$cur_info['job_status']] ?></td>
				</tr>
<?php
		}

		// Build Chart information
		$Hca5840Chart->addToMonth($cur_info['mois_report_date']);
		$Hca5840Chart->addProperty($cur_info['property_name']);

		if ($search_by_key_words != '')
			$Hca5840Chart->addSymptom($cur_info['mois_source'], $search_by_key_words);
		else
		{
			$Hca5840Chart->addSymptoms($cur_info['mois_source']);

			if ($cur_info['moveout_date'] > 0)
				$Hca5840Chart->addRelocation();
		}
		++$i;
	}
?>
			</tbody>
		</table>
		<div class="card-header">
			<h6 class="card-title mb-0">Total: <?php echo $i ?> (Limit 100 items on this page)</h6>
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

$hca_5840_mailing_fields_details = $Moisture->get_email_details();
$o_hca_5840_mailing_fields = explode(',', $Config->get('o_hca_5840_mailing_fields'));

$hca_5840_mailing_fields = array();
foreach($hca_5840_mailing_fields_details as $key => $value) {
	if (in_array($key, $o_hca_5840_mailing_fields))
		$hca_5840_mailing_fields[] = '<p><input type="checkbox" value="1" checked="checked" name="hca_5840_mailing_fields['.$key.']"> '.$value.'</p>';
	else
		$hca_5840_mailing_fields[] = '<p><input type="checkbox" value="0" name="hca_5840_mailing_fields['.$key.']"> '.$value.'</p>';
}
?>

	<div class="email-window" style="display:none">
		<label class="close-window"><img src="./img/close.png" width="16px" onclick="closeWindows()"/></label>
		
		<form method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
				<input type="hidden" name="project_id" value="0" />
			</div>
			
			<div class="edit-assign">
				<p>Subject</p>
				<p class="subject"><input type="text" name="subject" value="HCA: Moisture Inspection"></p>
				<p>Comma-separated email addresses</p>
				<p><textarea name="email_list" rows="3" placeholder="Enter emails separated by commas"><?php echo $Config->get('o_hca_5840_mailing_list') ?></textarea></p>
				<p><textarea name="mail_message" rows="3" placeholder="Write your message">Hello. </textarea></p>
				
				<div class="mailing-fields">
					<?php echo implode(' ', $hca_5840_mailing_fields) ?>
				</div>
				
				<p class="btn-action"><span class="submit primary"><input type="submit" name="send_email" value="Send Email"/></span></p>
			</div>
		</form>
	</div>

<?php

$MonthlyFrequency = $Hca5840Chart->getMonthlyFrequency();
$monthly_names = array_keys($MonthlyFrequency);
$monthly_counters = array_values($MonthlyFrequency);

$PropertyFrequency = $Hca5840Chart->getPropertyFrequency();
$property_names = array_keys($PropertyFrequency);
$property_counters = array_values($PropertyFrequency);

$PropertySymptoms = $Hca5840Chart->getSymptoms();
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
				label: "Cases",
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
				label: "Cases",
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