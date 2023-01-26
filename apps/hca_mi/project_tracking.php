<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$permission9 = ($User->checkPermissions('hca_mi', 9)) ? true : false; // Follow Up Dates

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Project does not exist or has been removed.');

$HcaMi = new HcaMi;
$Moisture = new Moisture;

if (isset($_POST['update_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$event_datetime = isset($_POST['time']) ? strtotime($_POST['time']) : 0;
	$event_date = date('Ymd', $event_datetime);
	$event_message = isset($_POST['message']) ? swift_trim($_POST['message']) : '';
	
	$form_data = [
		'time'		=> isset($_POST['time']) ? strtotime($_POST['time']) : 0,
		'message'	=> isset($_POST['message']) ? swift_trim($_POST['message']) : ''
	];

	if ($event_message == '')
		$Core->add_error('Event message can not by empty. Write your message.');
	if ($event_datetime == 0)
		$Core->add_error('Incorrect Date of Event. Set the date for the event.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'SELECT'	=> 'id',
			'FROM'		=> 'sm_calendar_dates',
			'WHERE'		=> 'year_month_day='.$event_date.' AND poster_id='.$User->get('id'),
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$calendar_dates_info = $DBLayer->fetch_assoc($result);
		
		$calendar_date_id = (isset($calendar_dates_info['id']) && $calendar_dates_info['id'] > 0) ? $calendar_dates_info['id'] : 0;
		
		if ($calendar_date_id == 0)
		{
			$query = array(
				'INSERT'	=> 'year_month_day, poster_id, num_events',
				'INTO'		=> 'sm_calendar_dates',
				'VALUES'	=> 
					'\''.$DBLayer->escape($event_date).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\'1\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$calendar_date_id = $DBLayer->insert_id();
		}
		
		if ($event_id > 0)
		{
			$query = array(
				'UPDATE'	=> 'sm_calendar_events',
				'SET'		=> 'time=\''.$DBLayer->escape($event_datetime).'\', date=\''.$DBLayer->escape($event_date).'\', message=\''.$DBLayer->escape($event_message).'\', date_id='.$calendar_date_id,
				'WHERE'		=> 'id='.$event_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			//Count and Upodate
			$query = array(
				'SELECT'	=> 'COUNT(id)',
				'FROM'		=> 'sm_calendar_events',
				'WHERE'		=> 'date_id='.$calendar_date_id,
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$num_events = $DBLayer->result($result);

			$query = array(
				'UPDATE'	=> 'sm_calendar_dates',
				'SET'		=> 'num_events='.$num_events,
				'WHERE'		=> 'id='.$calendar_date_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$flash_message = 'Event #'.$event_id.' has been updated.';
		}
		else
		{
			$query = array(
				'INSERT'	=> 'project_name, project_id, poster_id, time, date, subject, message, date_id',
				'INTO'		=> 'sm_calendar_events',
				'VALUES'	=> '\'hca_5840\',
					\''.$DBLayer->escape($project_id).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\''.$DBLayer->escape($event_datetime).'\',
					\''.$DBLayer->escape($event_date).'\',
					\'Moisture Inspection\',
					\''.$DBLayer->escape($event_message).'\',
					\''.$DBLayer->escape($calendar_date_id).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$event_id = $DBLayer->insert_id();
			
			//Count and Upodate
			$query = array(
				'SELECT'	=> 'COUNT(id)',
				'FROM'		=> 'sm_calendar_events',
				'WHERE'		=> 'date_id='.$calendar_date_id,
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$num_events = $DBLayer->result($result);

			$query = array(
				'UPDATE'	=> 'sm_calendar_dates',
				'SET'		=> 'num_events='.$num_events,
				'WHERE'		=> 'id='.$calendar_date_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$flash_message = 'Event #'.$event_id.' has been created.';
		}
		
		$HcaMi->addAction($project_id, $flash_message);

		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	
}

else if (isset($_POST['delete_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

	if ($event_id > 0)
	{
		$query = array(
			'DELETE'	=> 'sm_calendar_events',
			'WHERE'		=> 'id='.$event_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
		$flash_message = 'Event #'.$event_id.' has been deleted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = [
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, un.unit_number, u1.realname AS project_manager1, u2.realname AS project_manager2, u3.realname AS created_name, u4.realname AS updated_name',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		// Get Project Managers
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.performed_uid2'
		],
		[
			'LEFT JOIN'		=> 'users AS u3',
			'ON'			=> 'u3.id=pj.created_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u4',
			'ON'			=> 'u4.id=pj.updated_by'
		],
	],
	'WHERE'		=> 'pj.id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'a.*, u.realname',
	'FROM'		=> 'hca_mi_actions AS a',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=a.submitted_by'
		],
	],
	'WHERE'		=> 'a.project_id='.$id ,
	'ORDER BY'	=> 'a.time_submitted DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_mi_actions = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_mi_actions[] = $row;
}

$query = array(
	'SELECT'	=> 'e.*, u.realname',
	'FROM'		=> 'sm_calendar_events AS e',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=e.poster_id'
		],
	],
	'WHERE'		=> 'e.project_id='.$id.' AND e.project_name=\'hca_5840\'',
	'ORDER BY'	=> 'e.time DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_calendar_events = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_calendar_events[] = $row;
}

$Core->set_page_id('hca_mi_project_tracking', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<div class="card mb-3">
	<div class="card-header d-flex justify-content-between">
		<h6 class="card-title mb-0">Moisture Project Tracking</h6>
		<div>
			<a href="<?=$URL->link('hca_5840_manage_project', $id)?>" class="badge bg-primary text-white">Project</a>
			<a href="<?=$URL->link('hca_5840_manage_files', $id)?>" class="badge bg-primary text-white">Files</a>
			<a href="<?=$URL->link('hca_5840_manage_invoice', $id)?>" class="badge bg-primary text-white">Invoice</a>
			<a href="<?=$URL->link('hca_5840_manage_appendixb', $id)?>" class="badge bg-primary text-white">+ Appendix-B</a>
		</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-md-6">
				<div>
					<span class="">Property:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['pro_name']) ?></span>
				</div>
				<div>
					<span class="">Unit #</span>
					<span class="fw-bold"><?php echo html_encode($main_info['unit_number']) ?></span>
				</div>
				<div>
					<span class="">Location:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['location']) ?></span>
				</div>
				<div>
					<span class="">Project Manager:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['project_manager1']) ?></span>
				</div>
<?php if ($main_info['project_manager2'] != ''): ?>
				<div>
					<span class="">Project Manager 2:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['project_manager2']) ?></span>
				</div>
<?php endif; ?>
			</div>

			<div class="col-sm-6 d-flex justify-content-end">
				<div>
					<div>
						<span class="text-muted">Created by:</span>
						<span class="text-muted fw-bold"><?php echo html_encode($main_info['created_name']) ?></span>
					</div>
					<div>
						<span class="text-muted">Created on</span>
						<span class="text-muted fw-bold"><?php echo format_time($main_info['time_created'], 1) ?></span>
					</div>
					<div>
						<span class="text-muted">Updated by:</span>
						<span class="text-muted fw-bold"><?php echo html_encode($main_info['updated_name']) ?></span>
					</div>
					<div>
						<span class="text-muted">Last updated:</span>
						<span class="text-muted fw-bold"><?php echo format_time($main_info['time_updated'], 0) ?></span>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>

<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Project Tracking</h6>
	</div>
	<div class="card-body py-3">
		<div class="chart chart-sm">
			<div id="chart_project_tracking"></div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="card-header">
			<h6 class="card-title mb-0">Project actions</h6>
		</div>
<?php

if (!empty($hca_mi_actions)) 
{
?>
		<table class="table table-striped table-bordered table-sm">
			<thead>
				<tr>
					<th>Date/Time</th>
					<th>Submitted by</th>
					<th>Message</th>
				</tr>
			</thead>
			<tbody>
<?php

	foreach ($hca_mi_actions as $cur_info)
	{
?>
				<tr>
					<td><p class=""><?php echo format_time($cur_info['time_submitted'], 0) ?></p></td>
					<td><?php echo html_encode($cur_info['realname']) ?></td>
					<td><p><?php echo html_encode($cur_info['message']) ?></p></td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
<?php
} else {
?>
		<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
?>
	</div>

	<div class="col-md-6">
		<div class="card-header mb-1 d-flex justify-content-between">
			<h6 class="card-title mb-0">Project Follow Up Dates</h6>
			<p style="float:right" onclick="getEvent(<?=$id?>, 0)" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-plus-circle fa-lg"></i></p>
		</div>
<?php
$events_info = [];
$query = array(
	'SELECT'	=> 'e.id, e.project_id, e.time, e.message',
	'FROM'		=> 'sm_calendar_events AS e',
	'WHERE'		=> 'e.project_id='.$id.' AND project_name=\'hca_5840\'',
	'ORDER BY'	=> 'e.time DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$events_info[] = $fetch_assoc;
}
$follow_up_dates = $Moisture->get_events($events_info, $id);;
if (!empty($follow_up_dates)) 
{
	echo implode("\n", $follow_up_dates);
} else {
?>
	<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
?>
	</div>
</div>


<?php if ($permission9): ?>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Follow Up Date</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="clearModalWindowFields()"></button>
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

<script>
function getEvent(pid,id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_5840_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_5840_ajax_get_events') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({pid:pid,id:id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.modal .modal-body').empty().html('<div class="alert alert-danger" role="alert"><p class="fw-bold">Warning:</p> <p>Internet connection may have been lost. Refresh the page and try again.</p></div>');
			$('.modal .modal-footer"]').empty().html('');
		}
	});
}
function clearModalWindowFields(){
	//$('#modalWindow .modal-body"]').empty().html('');
	//$('#modalWindow .modal-footer"]').empty().html('');
}
</script>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
        var options = {
          series: [
          {
            data: [	
			  {
                x: 'Delivery/PickUp Equipment',
                y: [
					new Date('<?=format_time($main_info['delivery_equip_comment'], 1, 'Y-m-d')?>').getTime(),
                  	new Date('<?=format_time($main_info['pickup_equip_date'], 1, 'Y-m-d')?>').getTime()
                ],
				fillColor: '#00E396'
              },
              {
                x: 'Remediation',
                y: [
                  new Date('<?=format_time($main_info['rem_start_date'], 1, 'Y-m-d')?>').getTime(),
                  new Date('<?=format_time($main_info['rem_end_date'], 1, 'Y-m-d')?>').getTime()
                ],
				fillColor: '#00E396'
              },
              {
                x: 'Constructions',
                y: [
                  new Date('<?=format_time($main_info['cons_start_date'], 1, 'Y-m-d')?>').getTime(),
                  new Date('<?=format_time($main_info['cons_end_date'], 1, 'Y-m-d')?>').getTime()
                ],
				fillColor: '#775DD0'
              },
			  {
                x: 'Move Out/Move In',
                y: [
                  new Date('<?=format_time($main_info['moveout_date'], 1, 'Y-m-d')?>').getTime(),
                  new Date('<?=format_time($main_info['movein_date'], 1, 'Y-m-d')?>').getTime()
                ],
				fillColor: '#FEB019'
              },
			  {
                x: 'Duration',
                y: [
                  new Date('<?=format_time($main_info['mois_report_date'], 1, 'Y-m-d')?>').getTime(),
                  new Date('<?=format_time($main_info['final_performed_date'], 1, 'Y-m-d')?>').getTime()
                ],
				fillColor: '#008FFB'
              },
            ]
          }
        ],
          chart: {
          height: 350,
          type: 'rangeBar'
        },
        plotOptions: {
          bar: {
            horizontal: true
          }
        },
        xaxis: {
          type: 'datetime'
        }
        };

  var chart = new ApexCharts(document.querySelector("#chart_project_tracking"), options);
  chart.render();
</script>

<?php
require SITE_ROOT.'footer.php';
