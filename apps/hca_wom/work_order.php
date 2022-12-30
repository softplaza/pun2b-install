<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 6))
	message($lang_common['No permission']);

$access7 = ($User->checkAccess('hca_wom', 7)) ? true : false; // Update WO info

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Wrong Work Order ID number.');

$SwiftUploader = new SwiftUploader;
$HcaWOM = new HcaWOM;

if (isset($_POST['update_wo']))
{
	$form_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
	);

	if (empty($Core->errors))
	{
		// Update Work Order
		$DBLayer->update('hca_wom_work_orders', $form_data, $id);

		// Add flash message
		$flash_message = 'Work Order #'.$id.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['complete_wo']))
{
	$form_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
		'closed_by'			=> $User->get('id'),
		'dt_closed'			=> date('Y-m-d\TH:i:s'),
		'wo_status'			=> 2
	);

	if (empty($Core->errors))
	{
		// Update Work Order
		$DBLayer->update('hca_wom_work_orders', $form_data, $id);

		$query = array(
			'UPDATE'	=> 'hca_wom_tasks',
			'SET'		=> 'task_status=4',
			'WHERE'		=> 'work_order_id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'Work Order #'.$id.' has been completed.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_wom_work_orders'), $flash_message);
	}
}

else if (isset($_POST['reopen_wo']))
{
	$form_wo_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
		'wo_status'			=> 1
	);
	$DBLayer->update('hca_wom_work_orders', $form_wo_data, $id);

	// Add flash message
	$flash_message = 'Work Order #'.$id.' has been reopened.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['cancel_wo']))
{
	$DBLayer->update('hca_wom_work_orders', ['wo_status' => 3], $id);

	$DBLayer->update('hca_wom_tasks', ['task_status' => 0], 'work_order_id='.$id);

	// Add flash message
	$flash_message = 'Work Order #'.$id.' has been canceled.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

// TASK ACTIONS
else if (isset($_POST['add_task']))
{
	$form_wo_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
	);
	$DBLayer->update('hca_wom_work_orders', $form_wo_data, $id);

	$form_data = array(
		'work_order_id' => $id,
		'item_id'		=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'task_action'	=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'assigned_to'	=> isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'time_created'	=> time(),
		'task_status'	=> 2 // set as already accepted
	);

	//if ($form_data['assigned_to'] == 0)
	//	$Core->add_error('Select technician.');

	if (empty($Core->errors))
	{
		// Get old data


		// Create task of Work Order
		$new_tid = $DBLayer->insert_values('hca_wom_tasks', $form_data);

		$query = array(
			'UPDATE'	=> 'hca_wom_work_orders',
			'SET'		=> 'num_tasks=num_tasks+1, last_task_id='.$new_tid,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// notify when task assigned
		if ($form_data['assigned_to'] > 0 && $Config->get('o_hca_wom_notify_technician') == 1)
		{
			$task_info = $HcaWOM->getTaskInfo($new_tid);

			if (isset($task_info['assigned_email']))
			{
				$SwiftMailer = new SwiftMailer;
				//$SwiftMailer->isHTML();

				$mail_subject = 'Property Task #'.$task_info['id'];
				$mail_message = [];
				$mail_message[] = 'Hello '.$task_info['assigned_name'];
				$mail_message[] = 'You have been assigned to a new task.';
				$mail_message[] = 'Property: '.$task_info['pro_name'];
				$mail_message[] = 'Unit: '.$task_info['unit_number'];
				
				if ($task_info['task_message'] != '')
					$mail_message[] = 'Details: '.$task_info['task_message']."\n";

				$mail_message[] = 'To complete the task follow the link:';
				$mail_message[] = $URL->link('hca_wom_task', $task_info['id']);

				$SwiftMailer->send($task_info['assigned_email'], $mail_subject, implode("\n", $mail_message));
			}
		}

		// Add flash message
		$flash_message = 'Task #'.$new_tid.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['update_task']))
{
	$form_wo_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
	);
	$DBLayer->update('hca_wom_work_orders', $form_wo_data, $id);

	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
	$notify_technician = isset($_POST['notify_technician']) ? intval($_POST['notify_technician']) : 0;

	$form_data = [
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
	];

	if (isset($_POST['item_id']))
		$form_data['item_id'] = intval($_POST['item_id']);
	if (isset($_POST['task_action']))
		$form_data['task_action'] = intval($_POST['task_action']);
	if (isset($_POST['assigned_to']))
		$form_data['assigned_to'] = intval($_POST['assigned_to']);

	//if ($form_data['assigned_to'] == 0)
	//	$Core->add_error('Select technician.');

	if (empty($Core->errors) && $task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

		// notify when task assigned
		if (isset($form_data['assigned_to']) && $form_data['assigned_to'] > 0 && $Config->get('o_hca_wom_notify_technician') == 1)
		{
			$task_info = $HcaWOM->getTaskInfo($task_id);

			$SwiftMailer = new SwiftMailer;
			//$SwiftMailer->isHTML();

			$mail_subject = 'Property Task #'.$task_info['id'];
			$mail_message = [];
			$mail_message[] = 'Hello '.$task_info['assigned_name'];
			$mail_message[] = 'You have been assigned to a new task.';
			$mail_message[] = 'Property: '.$task_info['pro_name'];
			$mail_message[] = 'Unit: '.$task_info['unit_number'];
			
			if ($task_info['task_message'] != '')
				$mail_message[] = 'Details: '.$task_info['task_message']."\n";

			$mail_message[] = 'To complete the task follow the link:';
			$mail_message[] = $URL->link('hca_wom_task', $task_info['id']);

			$SwiftMailer->send($task_info['assigned_email'], $mail_subject, implode("\n", $mail_message));
		}
		
		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['close_task']))
{
	$form_wo_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
	);
	$DBLayer->update('hca_wom_work_orders', $form_wo_data, $id);

	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
	$notify_technician = isset($_POST['notify_technician']) ? intval($_POST['notify_technician']) : 0;

	$form_data = [
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'task_status'	=> 4,
	];

	if (empty($Core->errors) && $task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);
/*
		// notify_technician
		if ($notify_technician == 1)
		{
			$task_info = $HcaWOM->getTaskInfo($task_id);
			if (isset($task_info['assigned_email']))
			{
				$SwiftMailer = new SwiftMailer;
				//$SwiftMailer->isHTML();

				$mail_subject = 'Property Task #'.$task_info['id'];
				$mail_message = [];
				$mail_message[] = 'Hello '.$task_info['assigned_name'];
				$mail_message[] = 'Your Task #'.$task_info['id'].' has been approved and closed by Property Manager.';
				$mail_message[] = 'Property: '.$task_info['pro_name'];
				$mail_message[] = 'Unit: '.$task_info['unit_number'];
				
				if ($task_info['task_message'] != '')
					$mail_message[] = 'Details: '.$task_info['task_message'];

				$mail_message[] = 'To view your active tasks follow the link:';
				$mail_message[] = $URL->genLink('hca_wom_tasks', ['section' => 'active']);

				$SwiftMailer->send($task_info['assigned_email'], $mail_subject, implode("\n", $mail_message));
			}
		}
*/
		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been closed.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['reopen_task']))
{
	$form_wo_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
	);
	$DBLayer->update('hca_wom_work_orders', $form_wo_data, $id);

	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
	$form_data = [
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'task_status'	=> 1,
	];

	if ($task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been reopened.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_task']))
{
	$form_wo_data = array(
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
	);
	$DBLayer->update('hca_wom_work_orders', $form_wo_data, $id);

	$notify_technician = isset($_POST['notify_technician']) ? intval($_POST['notify_technician']) : 0;
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	if ($task_id > 0)
	{
		$DBLayer->delete('hca_wom_tasks', $task_id);

		$query = array(
			'UPDATE'	=> 'hca_wom_work_orders',
			'SET'		=> 'num_tasks=num_tasks-1',
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

/*
		// notify_technician
		if ($notify_technician == 1)
		{
			$task_info = $HcaWOM->getTaskInfo($task_id);
			if (isset($task_info['assigned_email']))
			{
				$SwiftMailer = new SwiftMailer;
				//$SwiftMailer->isHTML();

				$mail_subject = 'Property Task #'.$task_info['id'];
				$mail_message = [];
				$mail_message[] = 'Hello '.$task_info['assigned_name'];
				$mail_message[] = 'Your Task #'.$task_info['id'].' has been deleted by Property Manager.';
				$mail_message[] = 'Property: '.$task_info['pro_name'];
				$mail_message[] = 'Unit: '.$task_info['unit_number'];
				
				if ($task_info['task_message'] != '')
					$mail_message[] = 'Details: '.$task_info['task_message'];

				$mail_message[] = 'To view your active tasks follow the link:';
				$mail_message[] = $URL->genLink('hca_wom_tasks', ['section' => 'active']);

				$SwiftMailer->send($task_info['assigned_email'], $mail_subject, implode("\n", $mail_message));
			}
		}
*/
		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'ORDER BY'	=> 'p.display_position',
	//'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] = 'p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
}

$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="'.$URL->genLink('hca_wom_print', ['section' => 
'work_order', 'id' => $id]).'" target="_blank"><i class="fas fa-file-pdf fa-1x" aria-hidden="true"></i> Print as PDF</a></li>');

$Core->set_page_id('hca_wom_work_order', 'hca_fs');
require SITE_ROOT.'header.php';

$query = [
	'SELECT'	=> 'w.*, p.pro_name, pu.unit_number, u1.realname AS requested_name, u1.email AS requested_email',
	'FROM'		=> 'hca_wom_work_orders AS w',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=w.property_id'
		],
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
	],
	'WHERE'		=> 'w.id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$wo_info = $DBLayer->fetch_assoc($result);

// Check unit #
$wo_info['unit_number'] = ($wo_info['unit_id'] > 0) ? $wo_info['unit_number'] : 'Common area';

if (empty($wo_info))
	message('The Work Order does not exist.');

$query = [
	'SELECT'	=> 't.*, i.item_name, i.item_actions, i.item_type, tp.type_name, pb.problem_name, u1.realname AS assigned_name',
	'FROM'		=> 'hca_wom_tasks AS t',
	'JOINS'		=> [
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
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=t.assigned_to'
		],
	],
	'WHERE'		=> 't.work_order_id='.$id,
	'ORDER BY'	=> 't.task_status DESC',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$tasks_info = $task_ids = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$tasks_info[] = $row;
	$task_ids[] = $row['id'];
}
?>

<form method="post" accept-charset="utf-8" action="" id="form_main">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card mb-3">
		<div class="card-header d-flex justify-content-between">
			<h6 class="card-title mb-0">Work Order #<?php echo $wo_info['id'] ?></h6>
			<h6 class="card-title mb-0"><a href="<?=$URL->genLink('hca_wom_print', ['section' => 
'work_order', 'id' => $id])?>" target="_blank" class="text-white"><i class="fas fa-file-pdf"></i> Print WO</a></h6>
		</div>
		<div class="card-body">

			<?php echo $HcaWOM->getWorkOrderStatus($wo_info['wo_status']) ?>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label">Property</label>
					<h5 class="mb-0"><?php echo html_encode($wo_info['pro_name']) ?></h5>
				</div>
				<div class="col-md-2">
					<label class="form-label">Unit #</label>
					<h5 class="mb-0"><?php echo html_encode($wo_info['unit_number']) ?></h5>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_priority">Priority</label>
					<select name="priority" id="fld_priority" class="form-select form-select-sm">
<?php
	foreach ($HcaWOM->priority as $key => $val)
	{
		if ($wo_info['priority'] == $key)
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label">Submit Date & Time</label>
					<h5 class="mb-0"><?php echo format_date($wo_info['dt_created'], 'm/d/Y H:i') ?></h5>
				</div>
			</div>

<?php if($wo_info['unit_id'] > 0): ?>
			<div class="row mb-2">
				<div class="col-md-4">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" <?php echo ($wo_info['has_animal'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_has_animal">Pets in Unit</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" <?php echo ($wo_info['enter_permission'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>
					</div>
				</div>
			</div>

<?php endif; ?>

			<div class="mb-2">
				<textarea type="text" name="wo_message" class="form-control" placeholder="Enter any special instructions for entry (example: After 2 pm only please)"><?php echo html_encode($wo_info['wo_message']) ?></textarea>
			</div>

<?php if ($access7): ?>
			<div class="mb-1">
				<button type="submit" name="update_wo" class="btn btn-sm btn-primary">Update</button>
	<?php if ($wo_info['wo_status'] < 2): ?>
				<button type="submit" name="cancel_wo" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel it? All tasks will be closed.')">Cancel</button>

		<?php if ($HcaWOM->areTasksCompleted($tasks_info)): ?>
				<button type="submit" name="complete_wo" class="btn btn-sm btn-success">Approve Work Order</button>
		<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>
			</div>
		</div>
	</div>


<div class="card-header">
<?php if ($access7): ?>
	<span class="badge bg-primary" role="button" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="manageTask(0)"><i class="fas fa-plus"></i> new task</span>
<?php else: ?>
	<h6 class="card-title mb-0">Tasks</h6>
<?php endif; ?>
</div>
<?php
	if (!empty($tasks_info))
	{
?>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>#</th>
			<th>Type</th>
			<th>Item</th>
			<th>Action</th>
			<th>Details</th>
			<th>Assigned to</th>
			<th>Completion Date & Time</th>
			<th>Closing Comments</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
<?php
		$i = 1;
		foreach ($tasks_info as $cur_info)
		{
			$task_status = '';
			if ($cur_info['assigned_to'] == 0)
				$task_status = '<span class="badge badge-warning">Open</span>';
			else if ($cur_info['task_status'] == 4)
				$task_status = '<span class="badge badge-success">Closed</span>';
			else if ($cur_info['task_status'] == 3)
				$task_status = '<span class="badge badge-primary">Ready for review</span>';
			else if ($cur_info['task_status'] == 2)
				$task_status = '<span class="badge badge-warning">Open</span>';
				//$task_status = '<span class="badge badge-info">Accepted by Technician</span>';
			else if ($cur_info['task_status'] == 1)
				$task_status = '<span class="badge badge-warning">Open</span>';
			else if ($cur_info['task_status'] == 0)
				$task_status = '<span class="badge badge-danger">Canceled</span>';

			$start = ($cur_info['time_start'] != '00:00:00' ? format_date($cur_info['time_start'], 'H:i') : '');
			$end   = ($cur_info['time_end'] != '00:00:00' ? format_date($cur_info['time_end'], 'H:i') : '');
			$interval = (($start != '' && $end != '') ? $start.' - '.$end : '');

			$edit = ($access7) ? '<span class="badge bg-primary" role="button" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="manageTask('.$cur_info['id'].')"><i class="fas fa-edit"></i> edit</span>' : '';
?>
		<tr>
			<td class="ta-center">
				<p>#<?=$cur_info['id']?></p>
				<p><?php echo $edit ?></p>
			</td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['type_name']) ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['item_name']) ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['problem_name']) ?></td>
			<td class="min-100"><?php echo html_encode($cur_info['task_message']) ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['assigned_name']) ?></td>
			<td class="min-100 ta-center">
				<p><?php echo format_date($cur_info['dt_completed'], 'm/d/Y') ?></p>
				<p><?php echo $interval ?></p>
			</td>
			<td class="min-100"><?php echo html_encode($cur_info['tech_comment']) ?></td>
			<td class="min-100 ta-center"><?php echo $task_status ?></td>
		</tr>
<?php
		++$i;
	}
?>
	</tbody>
</table>

<?php
		$cur_project_files = $SwiftUploader->displayCurProjectImages('hca_wom_tasks', $task_ids);
		if (!empty($SwiftUploader->cur_project_files))
		{
?>
<div class="card-header d-flex justify-content-between">
	<h6 class="card-title mb-0">Uploaded Image</h6>
</div>
<div class="card">
	<div class="card-body">
		<div class="row">
			<?php echo $cur_project_files; ?>
		</div>
	</div>
</div>
<?php
		}
	}
	else
	{
?>
<div class="card">
	<div class="card-body">
		<div class="alert alert-warning py-2" role="alert">You have no tasks available for this Work Order.</div>
	</div>
</div>
<?php
	}
?>

	<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Edit information</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>`
			</div>
		</div>
	</div>

</form>

<script>
function manageTask(task_id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_manage_task')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_manage_task') ?>",
		type:	"POST",
		dataType: "json",
		data: ({task_id:task_id,csrf_token:csrf_token}),
		success: function(re){
			$(".modal-title").empty().html(re.modal_title);
			$(".modal-body").empty().html(re.modal_body);
			$(".modal-footer").empty().html(re.modal_footer);
		},
		error: function(re){
			document.getElementById("#brd-messages").innerHTML = re;
		}
	});
}
function getActions(){
	var item_id = $("#fld_item_id").val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,csrf_token:csrf_token}),
		success: function(re){
			$("#fld_task_action").empty().html(re.item_actions);

		},
		error: function(re){
			document.getElementById("#fld_task_action").innerHTML = re;
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';
