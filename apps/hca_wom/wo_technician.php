<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access2 = ($User->checkAccess('hca_wom', 2)) ? true : false;
if (!$access2)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaWOM = new HcaWOM;

if (isset($_POST['accepted']))
{
	if ($id > 0)
	{
		$form_data = [
			'wo_status'		=> 2,
			'dt_accepted'	=> date('Y-m-d\TH:i:s'),
		];
		$DBLayer->update('hca_wom_work_orders', $form_data, $id);

/*
		$WO_info = $HcaWOM->getWorkOrderInfo($id);
		$mail_subject = 'Property Work Order #'.$id;

		$mail_message = [];
		$mail_message[] = 'Property Work Order #'.$id.' has been accepted.'."\n";
		$mail_message[] = 'Property name: '.$WO_info['pro_name'];
		$mail_message[] = 'Unit number: '.$WO_info['unit_number'];
		$mail_message[] = 'Date requested: '.format_date($WO_info['date_requested'], 'Y-m-d');
		$mail_message[] = 'Permission to enter: '.($WO_info['enter_permission'] == 1 ? 'YES' : 'NO');
		$mail_message[] = 'Animal in Unit: '.($WO_info['has_animal'] == 1 ? 'YES' : 'NO');
		$mail_message[] = 'Comments: '.$WO_info['wo_message']."\n";
		$mail_message[] = 'Accepted by: '.$User->get('realname');
		$mail_message[] = 'Date accepted: '.date('Y-m-d H:i');

		$mail_message[] = 'To view the Work Order follow the link:';
		$mail_message[] = $URL->link('hca_wom_wo_manager', $id);

		if (!empty($WO_info['requested_email']))
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($WO_info['requested_email'], $mail_subject, implode("\n", $mail_message));
		}
*/

		// Add flash message
		$flash_message = 'Work Order #'.$id.' has been accepted.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
/*
else if (isset($_POST['declined']))
{
	$decline_reason = isset($_POST['decline_reason']) ? swift_trim($_POST['decline_reason']) : '';

	if ($decline_reason == '')
		$Core->add_error('The reason for decline cannot be empty. Please provide the reason.');

	if (empty($Core->errors))
	{
		$form_data = [
			'wo_status'		=> 0
		];
		$DBLayer->update('hca_wom_work_orders', $form_data, $id);

		$WO_info = $HcaWOM->getWorkOrderInfo($id);
		$mail_subject = 'Property Work Order #'.$id;

		$mail_message = [];
		$mail_message[] = 'Property Work Order #'.$id.' has been declined.'."\n";
		$mail_message[] = 'Property name: '.$WO_info['pro_name'];
		$mail_message[] = 'Unit number: '.$WO_info['unit_number'];
		$mail_message[] = 'Date requested: '.format_date($WO_info['date_requested'], 'Y-m-d');
		$mail_message[] = 'Permission to enter: '.($WO_info['enter_permission'] == 1 ? 'YES' : 'NO');
		$mail_message[] = 'Animal in Unit: '.($WO_info['has_animal'] == 1 ? 'YES' : 'NO')."\n";
		$mail_message[] = 'The reason for decline: '.$decline_reason;
		$mail_message[] = 'Declined by: '.$User->get('realname');
		$mail_message[] = 'Date declined: '.date('Y-m-d H:i');

		$mail_message[] = 'To view the Work Order follow the link:';
		$mail_message[] = $URL->link('hca_wom_wo_manager', $id);

		if (!empty($WO_info['requested_email']))
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($WO_info['requested_email'], $mail_subject, implode("\n", $mail_message));
		}

		// Add flash message
		$flash_message = 'Work Order #'.$id.' has been declined.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
*/
else if (isset($_POST['completed']))
{
	if ($id > 0)
	{
		$form_data = [
			'wo_status'		=> 3,
			'dt_completed'	=> date('Y-m-d\TH:i:s'),
		];
		$DBLayer->update('hca_wom_work_orders', $form_data, $id);

/*
		$WO_info = $HcaWOM->getWorkOrderInfo($id);
		$mail_subject = 'Property Work Order #'.$id;

		$mail_message = [];
		$mail_message[] = 'Property Work Order #'.$id.' has been completed.'."\n";
		$mail_message[] = 'Property name: '.$WO_info['pro_name'];
		$mail_message[] = 'Unit number: '.$WO_info['unit_number'];
		$mail_message[] = 'Date requested: '.format_date($WO_info['date_requested'], 'Y-m-d');
		$mail_message[] = 'Permission to enter: '.($WO_info['enter_permission'] == 1 ? 'YES' : 'NO');
		$mail_message[] = 'Animal in Unit: '.($WO_info['has_animal'] == 1 ? 'YES' : 'NO');
		$mail_message[] = 'Comments: '.$WO_info['wo_message']."\n";
		$mail_message[] = 'Completed by: '.$User->get('realname');
		$mail_message[] = 'Date completed: '.date('Y-m-d H:i');

		$mail_message[] = 'To view the Work Order follow the link:';
		$mail_message[] = $URL->link('hca_wom_wo_manager', $id);

		if (!empty($WO_info['requested_email']))
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($WO_info['requested_email'], $mail_subject, implode("\n", $mail_message));
		}
*/

		// Add flash message
		$flash_message = 'Work Order #'.$id.' has been completed.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'ORDER BY'	=> 'p.display_position',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
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
	'WHERE'		=> 'u.group_id = 3 OR u.group_id = 9',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users[] = $fetch_assoc;
}

$Core->set_page_id('hca_wom_work_order', 'hca_wom');
require SITE_ROOT.'header.php';

if ($id > 0)
{

	$wo_info = $HcaWOM->getWorkOrderInfo($id);

	$query = array(
		'SELECT'	=> 't.*',
		'FROM'		=> 'hca_wom_tasks AS t',
		'WHERE'		=> 't.work_order_id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Work Order #<?php echo $wo_info['id'] ?></h6>
		</div>
		<div class="card-body">

			<?php echo $HcaWOM->getWorkOrderStatus($wo_info['wo_status']) ?>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label">Property</label>
					<h5 class="mb-0"><?php echo html_encode($wo_info['pro_name']) ?></h5>
				</div>
				<div class="col-md-3">
					<label class="form-label">Unit #</label>
					<h5 class="mb-0"><?php echo html_encode($wo_info['unit_number']) ?></h5>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label">Date Requested</label>
					<h5 class="mb-0"><?php echo format_date($wo_info['date_requested'], 'Y-m-d') ?></h5>
				</div>
				<div class="col-md-3">
					<label class="form-label">Priority</label>
					<h5 class="mb-0"><?php echo $HcaWOM->priority[$wo_info['priority']] ?></h5>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label">Technician</label>
				<h5><?php echo html_encode($wo_info['assigned_name']) ?></h5>
			</div>

			<div class="mb-3">
				<label class="form-label">Permission to Enter</label>
				<h5><?php echo $wo_info['enter_permission'] == 1 ? ' YES' : 'NO' ?></h5>
			</div>

			<div class="mb-3">
				<label class="form-label">Animal in Unit</label>
				<h5><?php echo $wo_info['has_animal'] == 1 ? ' YES' : 'NO' ?></h5>
			</div>

			<div class="mb-3">
				<label class="form-label">Comments</label>
				<div class="callout callout-info">
					<?php echo html_encode($wo_info['wo_message']) ?>
				</div>
			</div>

			<div class="mb-3">
				<button type="submit" name="completed" class="btn btn-sm btn-success">Complete Work Order</button>
			</div>

<?php
/*
$i = 2;
if (!empty($tasks_info))
{
	foreach($tasks_info as $cur_task)
	{
?>
			<div class="mb-3">
				<label class="form-label" for="fld_task_text_<?php echo $cur_task['id'] ?>">Task</label>
				<textarea name="task_text[<?php echo $cur_task['id'] ?>]" class="form-control" placeholder="Your comment" id="fld_task_text_<?php echo $cur_task['id'] ?>"><?php echo html_encode($cur_task['request_text']) ?></textarea>
				<label class="form-label float-end"><button type="submit" name="delete_task[<?php echo $cur_task['id'] ?>]" class="badge bg-danger">Delete task</button></label>
			</div>
<?php
		++$i;
	}
}
*/
?>

		</div>
	</div>
</form>

<?php
}

require SITE_ROOT.'footer.php';
