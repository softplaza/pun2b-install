<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$access = ($User->checkAccess('hca_fs') || $User->get('group_id') == $Config->get('o_hca_fs_painters') || $User->get('group_id') == $Config->get('o_hca_fs_maintenance')) ? true : false;
if (!$access || $id < 1)
	message($lang_common['No permission']);

$time_slots = array(0 => 'ANY TIME', 1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
$execution_priority = [0 => 'Low', 1 => 'Medium', 2 => 'High'];

$SwiftUploader = new SwiftUploader;

if (isset($_POST['form_sent']))
{
	$form_data = array(
		'msg_from_maint'	=> isset($_POST['msg_from_maint']) ? swift_trim($_POST['msg_from_maint']) : '',
		'start_time'		=> isset($_POST['start_time']) ? swift_trim($_POST['start_time']) : '00:00:00',
		'end_time'			=> isset($_POST['end_time']) ? swift_trim($_POST['end_time']) : '00:00:00',
	);

	if ($form_data['start_time'] == '' || $form_data['end_time'] == '')
		$Core->add_error('To complete the Work Order, set up Start Time and End Time.');

	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{
		//$form_data['work_status'] = 2;
		$DBLayer->update('hca_fs_requests', $form_data, $id);

		$SwiftUploader->uploadFiles('hca_fs_requests', $id);	
		$Core->add_errors($SwiftUploader->getErrors());

		// Add flash message
		$flash_message = 'Work Order has been submitted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['close_task']))
{
	$task_id = intval(key($_POST['close_task']));

	$form_data = array(
		'completion_text'	=> isset($_POST['completion_text'][$task_id]) ? swift_trim($_POST['completion_text'][$task_id]) : '',
		'time_start'			=> isset($_POST['time_start'][$task_id]) ? swift_trim($_POST['time_start'][$task_id]) : '',
		'time_end'			=> isset($_POST['time_end'][$task_id]) ? swift_trim($_POST['time_end'][$task_id]) : '',
	);

	if ($form_data['time_start'] == '' || $form_data['time_end'] == '')
		$Core->add_error('To complete the task, set up Start Time and End Time.');

	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{

		$DBLayer->update('hca_fs_tasks', $form_data, $task_id);

		$SwiftUploader->uploadFiles('hca_fs_requests', $id);	
		$Core->add_errors($SwiftUploader->getErrors());

		// Add flash message
		$flash_message = 'Task has been submitted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['upload_files']))
{
	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{
		$SwiftUploader->uploadFiles('hca_fs_requests', $id);	
		$Core->add_errors($SwiftUploader->getErrors());

		// Add flash message
		$flash_message = 'Files have been uploaded';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['cancel']))
{
	$uid = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

	// Add flash message
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_fs_worker_schedule', $uid), $flash_message);
}

$query = array(
	'SELECT'	=> 'r.*, u.realname, p.pro_name',
	'FROM'		=> 'hca_fs_requests AS r',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'r.employee_id=u.id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'r.property_id=p.id'
		),
	),
	'WHERE'		=> 'r.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$wo_info = $DBLayer->fetch_assoc($result);

$access2 = ($User->checkAccess('hca_fs') || $wo_info['employee_id'] == $User->get('id')) ? true : false;
if (!$access2)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 't.*',
	'FROM'		=> 'hca_fs_tasks AS t',
	'WHERE'		=> 't.request_id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$tasks_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$tasks_info[] = $row;
}

$SwiftUploader->getProjectFiles('hca_fs_requests', $id);
$uploaded_images = $SwiftUploader->getCurProjectFiles($id);

$wo_info['start_time'] = ($wo_info['start_time'] != '00:00:00') ? $wo_info['start_time'] : '';
$wo_info['end_time'] = ($wo_info['end_time'] != '00:00:00') ? $wo_info['end_time'] : '';

$Core->set_page_id('hca_fs_edit_work_order', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<style>
label {font-weight: bold;}
</style>

	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<input type="hidden" name="employee_id" value="<?php echo $wo_info['employee_id'] ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Work Order # <?php echo $wo_info['id'] ?></h6>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-4">
						<h6 class="card-title mb-0">Property: <?php echo html_encode($wo_info['pro_name']) ?></h6>
					</div>
					<div class="col-md-4">
						<h6 class="card-title mb-0">Unit #: <?php echo html_encode($wo_info['unit_number']) ?></h6>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<h6 class="card-title mb-0"><?php echo ($wo_info['permission_enter'] == 1 ? 'Permission to Enter - YES' : 'Permission to Enter - NO') ?></h6>
					</div>
					<div class="col-md-4">
						<h6 class="card-title mb-0"><?php echo ($wo_info['has_animal'] == 1 ? 'Animal in Unit - YES' : 'No Pets') ?></h6>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
					<h6 class="card-title mb-0">Priority: <?php echo (isset($execution_priority[$wo_info['execution_priority']]) ? $execution_priority[$wo_info['execution_priority']] : 'n/a') ?></h6>
					</div>
				</div>
			</div>

			<div class="card-header">
				<h6 class="card-title mb-0">Uploaded files</h6>
			</div>
			<div class="card-body">
<?php if ($uploaded_images != ''): ?>
				<div class="mb-3">
					<?php echo $uploaded_images ?>
				</div>
<?php else: ?>
				<div class="mb-3">
					<div class="alert alert-warning" role="alert">No uploaded files</div>
				</div>
<?php endif; ?>
				<hr>
				<?php $SwiftUploader->setForm() ?>
				<button type="submit" name="upload_files" class="btn btn-primary">Upload files</button>
			</div>

			<div class="card-header">
				<h6 class="card-title mb-0">Tasks</h6>
			</div>
			<div class="card-body">
				<div class="alert alert-light border" role="alert">
					<h6 class="card-title mb-0">Task #1:</h6>
					<div class="alert alert-info" role="alert"><?php echo html_encode($wo_info['msg_for_maint']) ?></div>
					<div class="mb-3">
						<textarea name="msg_from_maint" class="form-control" placeholder="Your comment"><?php echo (isset($_POST['msg_from_maint']) ? html_encode($_POST['msg_from_maint']) : html_encode($wo_info['msg_from_maint'])) ?></textarea>
					</div>
					<div class="row mb-3">
						<div class="col-md-3">
							<label class="form-label" for="start_time">Start Time</label>
							<input type="time" name="start_time" value="<?php echo isset($_POST['start_time']) ? html_encode($_POST['start_time']) : html_encode($wo_info['start_time']) ?>" class="form-control" id="start_time">
						</div>
						<div class="col-md-3">
							<label class="form-label" for="end_time">End Time</label>
							<input type="time" name="end_time" value="<?php echo isset($_POST['end_time']) ? html_encode($_POST['end_time']) : html_encode($wo_info['end_time']) ?>" class="form-control" id="end_time">
						</div>
					</div>
					<div class="">
						<button type="submit" name="form_sent" class="btn btn-primary">Save changes</button>
					</div>
				</div>
<?php
$i = 2;
if (!empty($tasks_info))
{
	foreach($tasks_info as $cur_task)
	{
?>

				<div class="alert alert-light border" role="alert">
					<h6 class="card-title mb-0">Task #<?php echo $i ?>:</h6>
					<div class="alert alert-info" role="alert"><?php echo html_encode($cur_task['request_text']) ?></div>
					<div class="mb-3">
						<textarea name="completion_text[<?php echo $cur_task['id'] ?>]" class="form-control" placeholder="Your comment"><?php echo html_encode($cur_task['completion_text']) ?></textarea>
					</div>
					<div class="row mb-3">
						<div class="col-md-3">
							<label class="form-label">Start Time</label>
							<input type="time" name="time_start[<?php echo $cur_task['id'] ?>]" value="<?php echo format_date($cur_task['time_start'], 'H:i') ?>" class="form-control">
						</div>
						<div class="col-md-3">
							<label class="form-label">End Time</label>
							<input type="time" name="time_end[<?php echo $cur_task['id'] ?>]" value="<?php echo format_date($cur_task['time_end'], 'H:i') ?>" class="form-control">
						</div>
					</div>
					<button type="submit" name="close_task[<?php echo $cur_task['id'] ?>]" class="btn btn-primary">Save changes</button>
				</div>
<?php
		++$i;
	}
}
?>

			</div>
		</div>

	</form>
	
<script>
function checkFormSubmit(form)
{
	$('form button[name="form_sent"]').css("pointer-events","none");
	$('form button[name="form_sent"]').val("Processing...");
}
</script>

<?php
require SITE_ROOT.'footer.php';