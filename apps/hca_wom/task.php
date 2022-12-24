<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 5))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Wrong task number.');

$HcaWOM = new HcaWOM;

$SwiftUploader = new SwiftUploader;

// Set permissions to view, download and delete files
$SwiftUploader->access_view_files = true;
if ($User->checkPermissions('hca_wom', 10))
	$SwiftUploader->access_upload_files = true;

// Get current task info
//$task_info = $HcaWOM->getTaskInfo($id);
$query = [
	'SELECT'	=> 't.*, w.property_id, w.unit_id, w.wo_message, w.priority, w.enter_permission, w.has_animal, p.pro_name, pu.unit_number, u1.realname AS requested_name, u1.email AS requested_email, i.item_name, pb.problem_name, u2.realname AS assigned_name, u2.email AS assigned_email',
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
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.requested_by'
		],
		[
			// LEFT if unit_id - 0
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_items AS i',
			'ON'			=> 'i.id=t.item_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_problems AS pb',
			'ON'			=> 'pb.id=t.task_action'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=t.assigned_to'
		],
	],
	'WHERE'		=> 't.id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$task_info = $DBLayer->fetch_assoc($result);

if (empty($task_info))
	message('The task does not exist or has been deleted.');

if ($task_info['unit_id'] == 0 || $task_info['unit_id'] == '')
	$task_info['unit_number'] = 'Common area';

if (isset($_POST['complete']))
{
	$form_data = [
		'time_start'		=> isset($_POST['time_start']) ? swift_trim($_POST['time_start']) : '',
		'time_end'			=> isset($_POST['time_end']) ? swift_trim($_POST['time_end']) : '',
		'parts_installed'	=> isset($_POST['parts_installed']) ? intval($_POST['parts_installed']) : 0,
		'completed'			=> isset($_POST['completed']) ? intval($_POST['completed']) : 0,
		'tech_comment'		=> isset($_POST['tech_comment']) ? swift_trim($_POST['tech_comment']) : '',
		'dt_completed'		=> date('Y-m-d\TH:i:s'),
		'task_status'		=> 3,
	];

	if ($form_data['time_start'] == $form_data['time_end'])
		$Core->add_error('The start time of the task cannot be the same as the end time.');
	else if (strtotime($form_data['time_start']) > strtotime($form_data['time_end']))
		$Core->add_error('The end time of the task must be later than the start time.');

	if ($task_info['task_status'] == 4)
		$Core->add_error('This task has been already closed by the property manager, you cannot make any changes.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_wom_tasks', $form_data, $id);

		// !!! CHECK ALL COMPLETED TASKS BEFORE SENT EMAIL
		$wo_tasks = $HcaWOM->getWOTasks($task_info['work_order_id']);
		$are_tasks_closed = $HcaWOM->areTasksCompleted($wo_tasks);

		if (isset($task_info['requested_email']) && $are_tasks_closed && ($Config->get('o_hca_wom_notify_managers') == 1))
		{
			$SwiftMailer = new SwiftMailer;
			//$SwiftMailer->isHTML();

			$mail_subject = 'Property Work Order #'.$task_info['work_order_id'];
			$mail_message = [];
			$mail_message[] = 'Hello '.$task_info['requested_name'];
			$mail_message[] = 'Work Order #'.$task_info['work_order_id'].' is ready for review.';
			$mail_message[] = 'Property: '.$task_info['pro_name'];
			$mail_message[] = 'Location: '.$task_info['unit_number'];
			
			if ($form_data['tech_comment'] != '')
				$mail_message[] = 'Comment: '.$form_data['tech_comment'];

			$mail_message[] = 'To view follow this link:';
			$mail_message[] = $URL->link('hca_wom_work_order', $task_info['work_order_id']);

			$SwiftMailer->send($task_info['requested_email'], $mail_subject, implode("\n", $mail_message));
		}

		// Add flash message
		$flash_message = 'Task #'.$id.' has been completed.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->genLink('hca_wom_tasks', ['section' => 'active']), $flash_message);
		//redirect('', $flash_message);
	}
}

$Core->set_page_id('hca_wom_task', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="row">
		<div class="col-md-4">

			<div class="card-header">
				<h6 class="card-title mb-0">Work Order #<?php echo $task_info['work_order_id'] ?></h6>
				<h6 class="card-title mb-0 hidden">Task #<?php echo $task_info['id'] ?></h6>
			</div>

			<div class="card">
				<div class="card-body">
					<div class="mb-2 d-flex justify-content-between">
						<h5 class="mb-0"><?php echo html_encode($task_info['pro_name']) ?></h5>
						<h5 class="mb-0"><?php echo ($task_info['unit_id'] > 0) ? 'Unit: '.html_encode($task_info['unit_number']) : 'Common area'; ?></h5>
					</div>

				<?php if ($task_info['task_status'] == 4): ?>
					<div class="callout callout-success mb-2">The task has been closed by property manager.</div>
				<?php elseif ($task_info['task_status'] == 3): ?>
					<div class="callout callout-warning mb-2">The task sent for review by property manager.</div>
				<?php endif; ?>

				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<div class="d-flex justify-content-between mb-2">
						<h5 class="mb-1"><?php echo $task_info['enter_permission'] == 1 ? '<i class="fa-solid fa-circle-exclamation text-warning"></i> Permission to Enter' : '<i class="fa-solid fa-circle-check text-primary"></i> OK to Enter' ?> </h5>
						<h5 class="mb-1"><?php echo $task_info['has_animal'] == 1 ? '<i class="fa-solid fa-circle-exclamation text-warning"></i> Pets in Unit' : '<i class="fa-solid fa-circle-check text-primary"></i> NO Pets' ?></h5>
					</div>

				<?php if ($task_info['wo_message'] != ''): ?>
					<div class="mb-0">
						<div class="border bg-light p-1">
							<?php echo html_encode($task_info['wo_message']) ?>
						</div>
					</div>
				<?php endif; ?>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<div class="d-flex justify-content-between mb-2">
						<h6 class="mb-0"><?php echo html_encode($task_info['item_name']).' ('.html_encode($task_info['problem_name']) ?>)</h6>
						<h6 class="mb-0">Priority: <?php echo (isset($HcaWOM->priority[$task_info['priority']]) ? $HcaWOM->priority[$task_info['priority']] : 'Low') ?></h6>
					</div>

				<?php if ($task_info['task_message'] != ''): ?>
					<div class="mb-0">
						<div class="border bg-light p-1">
							<?php echo html_encode($task_info['task_message']) ?>
						</div>
					</div>
				<?php endif; ?>
				</div>
			</div>
<?php
if (isset($_POST['time_start']))
	$time_start = $_POST['time_start'];
else if ($task_info['time_start'] != '00:00:00')
	$time_start = format_date($task_info['time_start'], 'H:i');
else
	$time_start = '';//date('H:i');

if (isset($_POST['time_end']))
	$time_end = $_POST['time_end'];
else if ($task_info['time_end'] != '00:00:00')
	$time_end = format_date($task_info['time_end'], 'H:i');
else
	$time_end = '';
?>

			<div class="card">
				<div class="card-body">
					<div class="input-group mb-2">
						<div class="input-group-text w-25 px-1">Clock-In</div>
						<input class="form-control" type="time" name="time_start" id="fld_time_start" value="<?php echo $time_start ?>" required onclick="this.showPicker()">
						<button type="button" class="btn btn-primary hidden" onclick="setTimeNow('fld_time_start')">Now</button>
					</div>
					<div class="input-group">
						<div class="input-group-text w-25 px-1">Clock-Out</div>
						<input class="form-control" type="time" name="time_end" id="fld_time_end" value="<?php echo $time_end ?>" required onclick="this.showPicker()">
						<button type="button" class="btn btn-primary hidden" onclick="setTimeNow('fld_time_end')">Now</button>
					</div>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
<?php
if ($task_info['task_status'] < 4)
{
	$SwiftUploader->uploadImage('hca_wom_tasks', $id);
}
?>
					<h6 class="mb-0"><?=$SwiftUploader->getUploadedImagesLink('hca_wom_tasks', $id)?></h6>
					
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<div class="row row-cols-2 mb-2 bg-light border p-2">
						<div class="col-1">
							<input class="form-check-input" type="checkbox" name="parts_installed" id="fld_parts_installed" value="1" <?php echo ($task_info['parts_installed'] == 1 ? ' checked' : '') ?>>
						</div>
						<div class="col-10">
							<label class="form-check-label fw-bold float-start" for="fld_parts_installed">Replacement Parts Installed</label>
						</div>
					</div>
					<div class="row row-cols-2 bg-light border p-2">
						<div class="col-1">
							<input class="form-check-input" type="checkbox" name="completed" id="fld_completed" value="1" <?php echo ($task_info['completed'] == 1 ? ' checked' : '') ?> onclick="checkCompletion()">
						</div>
						<div class="col-10">
							<label class="form-check-label fw-bold float-start" for="fld_completed">Task Completed</label>
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<label class="form-label" for="fld_tech_comment">Closing comments</label>
					<textarea type="text" name="tech_comment" class="form-control" id="fld_tech_comment" placeholder="Required if task not completed" <?php echo ($task_info['completed'] == 0 ? ' required' : '') ?>><?php echo (isset($_POST['tech_comment']) ? html_encode($_POST['tech_comment']) : html_encode($task_info['tech_comment'])) ?></textarea>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<div class="d-flex justify-content-between mb-3">
						<button type="submit" name="complete" class="btn btn-primary" <?php echo ($task_info['task_status'] == 4) ? 'disabled' : '' ?>><i class="fa-solid fa-circle-check"></i> Save and Close</button>
						<a href="<?php echo $URL->genLink('hca_wom_tasks', ['section' => 'active']) ?>" class="btn btn-secondary text-white"><i class="fa-solid fa-circle-xmark"></i> To-Do List</a>
					</div>
				</div>
			</div>

		</div>
	</div>
</form>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Uploaded Images</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="closeModalWindow()"></button>
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
function setTimeNow(id){
	var date = new Date();

    hour = date.getHours();
    min  = date.getMinutes();  
	
	h = (hour < 10 ? "0" : "") + hour;
	m = (min < 10 ? "0" : "") + min;

	$('#'+id).val(h+':'+m);
}
function checkCompletion(){
	if(document.getElementById('fld_completed').checked) {
		$("#fld_tech_comment").prop('required', false);
	} else {
		$("#fld_tech_comment").prop('required', true);
	}
}
</script>

<?php
$SwiftUploader->addJS();

require SITE_ROOT.'footer.php';
