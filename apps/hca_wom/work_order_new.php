<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 2))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaWOM = new HcaWOM;

if (isset($_POST['add']))
{
	$time_now = time();
	$request_type = isset($_POST['request_type']) ? intval($_POST['request_type']) : 1;
	$form_data = array(
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_id'			=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
		
		//'request_type'		=> isset($_POST['request_type']) ? intval($_POST['request_type']) : 1,
		'template_type'		=> isset($_POST['template_type']) ? intval($_POST['template_type']) : 1,
		'template_id'		=> isset($_POST['template_id']) ? intval($_POST['template_id']) : 0,

		'wo_requested_date'	=> isset($_POST['wo_requested_date']) ? $_POST['wo_requested_date'] : '',
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',

		'dt_created'		=> date('Y-m-d\TH:i:s'),
		'requested_by'		=> $User->get('id'),
		'wo_status'			=> 1,
	);

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select a property from dropdown list.');

	if (empty($Core->errors))
	{
		// Send request to Facility
		if ($request_type == 2)
		{
			$hca_fs_tasks = [
				'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
				'unit_id'			=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
				'unit_number'		=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',

				'template_type'		=> isset($_POST['template_type']) ? intval($_POST['template_type']) : 1,
				'time_slot'			=> isset($_POST['time_slot']) ? intval($_POST['time_slot']) : 0,
				'gl_code'			=> isset($_POST['gl_code']) ? swift_trim($_POST['gl_code']) : '',
				'requested_date'	=> isset($_POST['requested_date']) ? $_POST['requested_date'] : '',
				'task_details'		=> isset($_POST['task_details']) ? swift_trim($_POST['task_details']) : '',

				'created_on'		=> $time_now,
				'created_by'		=> $User->get('id'),
				'group_id'			=> 3, // Maintenance Group
			];
			$new_fsid = $DBLayer->insert_values('hca_fs_tasks', $hca_fs_tasks);

			// notify when task assigned
			if ($Config->get('o_hca_wom_notify_inhouse_from_manager') == 1)
			{
				$task_info = $HcaWOM->getInHouseTaskInfo($new_fsid);
				if (isset($task_info['created_email']))
				{
					$SwiftMailer = new SwiftMailer;
					$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
					//$SwiftMailer->isHTML();

					$mail_subject = 'Property Request #'.$new_id;
					$mail_message = [];
					//$mail_message[] = 'Hello '.$task_info['created_name'];
					$mail_message[] = 'You have a new property request.';
					$mail_message[] = 'Property: '.$task_info['pro_name'];
					$mail_message[] = 'Unit: '.$task_info['unit_number'];

					if (strtotime($task_info['requested_date']) > 0)
						$mail_message[] = 'Requested date: '.format_date($task_info['requested_date'], 'm/d/Y')."\n";

					if ($task_info['task_details'] != '')
						$mail_message[] = 'Details: '.$task_info['task_details']."\n";

					$mail_message[] = 'To assign the request open Facility Schedule by link:';
					$mail_message[] = $URL->link('hca_fs_weekly_schedule', [$Config->get('o_hca_fs_maintenance'), date('Y-m-d')]);

					$SwiftMailer->send('talavera@hcares.com', $mail_subject, implode("\n", $mail_message));
				}
			}

			// Add flash message
			$flash_message = 'Request #'.$new_fsid.' sent to In-House Schedule.';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_wom_work_order_new', $new_fsid), $flash_message);
		}
		// Create WO
		else
		{
			// Create a new Work Order
			$new_id = $DBLayer->insert_values('hca_wom_work_orders', $form_data);

			if (isset($_POST['item_id']) && !empty($_POST['item_id']))
			{
				$num_tasks = 0;
				foreach($_POST['item_id'] as $key => $value)
				{
					$task_data = [
						'work_order_id' 	=> $new_id,
						'item_id'			=> isset($_POST['item_id'][$key]) ? intval($_POST['item_id'][$key]) : 0,
						'task_action'		=> isset($_POST['task_action'][$key]) ? intval($_POST['task_action'][$key]) : 0,
						'assigned_to'		=> isset($_POST['assigned_to'][$key]) ? intval($_POST['assigned_to'][$key]) : 0,
						'task_message'		=> isset($_POST['task_message'][$key]) ? swift_trim($_POST['task_message'][$key]) : '',
						'time_created'		=> $time_now,
						'task_status'		=> 2 // set as already accepted
					];
					$new_tid = $DBLayer->insert_values('hca_wom_tasks', $task_data);

					++$num_tasks;
				}
				
				if ($num_tasks > 0 && $new_tid > 0)
				{
					$query = array(
						'UPDATE'	=> 'hca_wom_work_orders',
						'SET'		=> 'num_tasks=num_tasks+'.$num_tasks.', last_task_id='.$new_tid,
						'WHERE'		=> 'id='.$new_id
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}

				// notify when task assigned
				if ($task_data['assigned_to'] > 0 && $Config->get('o_hca_wom_notify_technician') == 1)
				{
					$task_info = $HcaWOM->getTaskInfo($new_tid);

					if (isset($task_info['assigned_email']))
					{
						$SwiftMailer = new SwiftMailer;
						$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
						//$SwiftMailer->isHTML();

						$mail_subject = 'Property Work Order #'.$new_id;
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

			}

			// Add flash message
			$flash_message = 'Property Work Order created.';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_wom_work_order', $new_id), $flash_message);
		}
	}
}

$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

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
$property_manager = ['id' => 0, 'pro_name' => ''];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
	$property_manager = $row;
}

$Core->set_page_id('hca_wom_work_order_new', 'hca_fs');
require SITE_ROOT.'header.php';

if ($id > 0)
{
?>
<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Work Order #<?=$id?></h6>
	</div>
	<div class="card-body">
		<div class="alert alert-success" role="alert">
			<p>Your request has been sent to Facility Schedule.</p>
			<a href="<?php echo $URL->link('hca_wom_work_order_new', 0) ?>" class="btn btn-sm btn-outline-secondary">Create new one</a>
		</div>
	</div>
</div>
<?php
	require SITE_ROOT.'footer.php';
}
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">New Work Order</h6>
		</div>
		<div class="card-body">
			<div class="row">
<?php if ($is_manager): ?>
				<div class="col-md-3 mb-3">
					<label class="form-label">Property</label>
					<input type="hidden" id="fld_property_id" name="property_id" value="<?=$property_manager['id']?>">
					<input type="text" value="<?=html_encode($property_manager['pro_name'])?>" class="form-control form-control-sm" readonly>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_unit_id">Unit #</label>
					<select id="fld_unit_id" name="unit_id" class="form-select form-select-sm">
						<option value="0" selected>Common area</option>
<?php
$query = array(
	'SELECT'	=> 'un.*',
	'FROM'		=> 'sm_property_units AS un',
	'WHERE'		=> 'un.property_id='.$property_manager['id'],
	'ORDER BY'	=> 'LENGTH(un.unit_number), un.unit_number',
);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_units = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_units[] = $row;
}
foreach ($sm_property_units as $cur_info)
{
	if(isset($_POST['unit_id']) && $_POST['unit_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['unit_number']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['unit_number']).'</option>'."\n";
}
?>
					</select>
				</div>
<?php else: ?>
				<div class="col-md-3 mb-3 was-validated">
					<label class="form-label" for="fld_property_id">Properties</label>
					<select id="fld_property_id" name="property_id" class="form-select form-select-sm" required onchange="getUnits()">
<?php
echo '<option value="" selected disabled>Select one</option>'."\n";
foreach ($sm_property_db as $cur_info)
{
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
					<div class="invalid-feedback">Example invalid select feedback</div>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_unit_number">Unit #</label>
					<div id="unit_list">
						<input type="text" name="unit_id" value="" class="form-control form-control-sm" id="fld_unit_number" disabled>
					</div>
				</div>
<?php endif; ?>
			</div>

			<div class="row">
<?php
$query = array(
	'SELECT'	=> 'tpl.*',
	'FROM'		=> 'hca_wom_tpl_wo AS tpl',
	'ORDER BY'	=> 'tpl.tpl_name',
	//'WHERE'		=> 'tpl.property_id='.$property_manager['id'],
);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_tpl_wo = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_tpl_wo[] = $row;
}
?>
				<div class="col-md-3 mb-3 hidden">
					<label class="form-label" for="fld_template_id">Property Templates</label>
					<select id="fld_template_id" name="template_id" class="form-select form-select-sm" onchange="getWorkOrderTemplate()">
						<option value="0" selected>Select one</option>
<?php
foreach ($hca_wom_tpl_wo as $cur_info)
{
	if(isset($_POST['unit_id']) && $_POST['unit_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['tpl_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['tpl_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_template_type">Type of Work</label>
					<select name="template_type" id="fld_template_type" class="form-select form-select-sm">
<?php
foreach ($HcaWOM->template_type as $key => $val)
{
	if (isset($_POST['template_iihouse']) && intval($_POST['template_iihouse']) == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label">Request type</label>
					<div class="form-check">
						<input class="form-check-input" type="radio" name="request_type" value="1" id="fld_request_type1" checked required onclick="switchTemplateType(1)">
						<label class="form-check-label" for="fld_request_type1">Property Work Order</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="request_type" value="2" id="fld_request_type2" onclick="switchTemplateType(2)">
						<label class="form-check-label" for="fld_request_type2">In-House Request</label>
					</div>
				</div>
			</div>

		</div>
	</div>

	<div class="card" id="work_order_template">

		<div class="card-body property-fields">
			<div class="row">
				<div class="col-md-3 mb-2">
					<label class="form-label" for="fld_wo_requested_date">Requested Date</label>
					<input class="form-control form-control-sm" type="date" name="wo_requested_date" id="fld_wo_requested_date" value="<?php echo (isset($_POST['wo_requested_date']) ? $_POST['wo_requested_date'] : '') ?>" onclick="this.showPicker()">
					<label class="text-muted">Leave blank if any date</label>
				</div>

				<div class="col-md-2 mb-2">
					<label class="form-label" for="fld_priority">Priority</label>
					<select name="priority" id="fld_priority" class="form-select form-select-sm">
<?php
foreach ($HcaWOM->priority as $key => $val)
{
	if (isset($_POST['priority']) && intval($_POST['priority']) == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-2">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" <?php echo (isset($_POST['has_animal']) && $_POST['has_animal'] == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_has_animal">Pets in Unit</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" <?php echo (isset($_POST['enter_permission']) && $_POST['enter_permission'] == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>
				</div>
			</div>
			<div class="mb-2">
				<textarea type="text" name="wo_message" class="form-control" placeholder="Enter any special instructions for entry (example: After 2 pm only please)" rows="2"><?php echo (isset($_POST['wo_message']) ? html_encode($_POST['wo_message']) : '') ?></textarea>
			</div>
		</div>

		<div class="card-body badge-secondary property-fields">
			<h5 class="card-title mb-0">Task #1</h5>
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_item_id1">Items</label>
					<select id="fld_item_id1" name="item_id[1]" class="form-select form-select-sm">
<?php
$query = [
	'SELECT'	=> 'i.*, tp.type_name',
	'FROM'		=> 'hca_wom_items AS i',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_wom_types AS tp',
			'ON'			=> 'tp.id=i.item_type'
		],
	],
	'ORDER BY'	=> 'i.item_type, i.item_name',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_items = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_items[] = $row;
}

$optgroup = 0;
foreach ($hca_wom_items as $cur_info)
{
	if ($cur_info['item_type'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_info['type_name']).'">';
		$optgroup = $cur_info['item_type'];
	}
	if (isset($_POST['item_id'][1]) && $_POST['item_id'][1] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['item_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-2 mb-3">
					<label class="form-label" for="fld_task_action1">Action/Problem</label>
					<select id="fld_task_action1" name="task_action[1]" class="form-select form-select-sm">
<?php
$query = array(
	'SELECT'	=> 'pr.*',
	'FROM'		=> 'hca_wom_problems AS pr',
	'ORDER BY'	=> 'pr.problem_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_problems = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_problems[] = $row;
}

foreach ($hca_wom_problems as $cur_info)
{
	if (isset($_POST['task_action'][1]) && $_POST['task_action'][1] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['problem_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['problem_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_tassigned_to1">Assigned to</label>
					<select id="fld_assigned_to1" name="assigned_to[1]" class="form-select form-select-sm">
						<option value="0">Select one</option>
<?php
$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id=3',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$optgroup = 0;
foreach ($users_info as $cur_info)
{
	if ($cur_info['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_info['g_title']).'">';
		$optgroup = $cur_info['group_id'];
	}
	if (isset($_POST['assigned_to'][1]) && $_POST['assigned_to'][1] == $cur_info['id'] || $property_manager['default_maint'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected class="badge-warning">'.html_encode($cur_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-2">
				<textarea type="text" name="task_message[1]" class="form-control" placeholder="Enter details here"><?php echo (isset($_POST['task_message'][1]) ? html_encode($_POST['task_message'][1]) : '') ?></textarea>
			</div>
		</div>
	</div>


	<div class="card" id="inhouse_template">
		<div class="card-body inhouse-fields hidden">
			<div class="callout callout-info py-2 mb-3">This request will be sent to the In-House Department. As soon as this request is added to the Facility Schedule, you will receive a notification.</div>
			<div class="row">
				<div class="col-md-3 mb-2">
					<label class="form-label" for="fld_requested_date">Requested Date</label>
					<input class="form-control form-control-sm" type="date" name="requested_date" id="fld_requested_date" value="<?php echo (isset($_POST['requested_date']) ? $_POST['requested_date'] : '') ?>" onclick="this.showPicker()">
					<label class="text-muted">Leave blank if any date</label>
				</div>
				<div class="col-md-2 mb-3">
					<label class="form-label" for="fld_time_slot">Time shift</label>
					<select name="time_slot" id="fld_time_slot" class="form-select form-select-sm">
<?php
$time_slot = [1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.'];
foreach ($time_slot as $key => $val)
{
	if (isset($_POST['time_slot']) && intval($_POST['time_slot']) == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>

				<div class="col-md-3 mb-3 hidden">
					<label class="form-label" for="fld_gl_code">GL Code</label>
					<input class="form-control form-control-sm" type="text" name="gl_code" id="fld_gl_code" value="">
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_task_details">Comments</label>
				<textarea type="text" name="task_details" class="form-control" id="fld_task_details" placeholder="Enter details here" rows="2"><?php echo (isset($_POST['task_details']) ? html_encode($_POST['task_details']) : '') ?></textarea>
			</div>
		</div>
	</div>

	<div class="card mb-3">
		<div class="card-body mb-0">
			<button type="submit" name="add" class="btn btn-primary">Submit</button>
		</div>
	</div>

</form>

<script>
function getUnits()
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_units')) ?>";
	var id = $("#fld_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#unit_list").empty().html(re.unit_list);
		},
		error: function(re){
			document.getElementById("#unit_list").innerHTML = re;
		}
	});
}
function getWorkOrderTemplate()
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_wo_template')) ?>";
	var id = $("#fld_template_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_wo_template') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#work_order_template").empty().html(re.template_body);
		},
		error: function(re){
			document.getElementById("#work_order_template").innerHTML = re;
		}
	});
}
function switchTemplateType(v)
{
	if(v==2){
		$(".inhouse-fields").removeClass('hidden');
		$(".property-fields").addClass('hidden');
	}else{
		$(".property-fields").removeClass('hidden');
		$(".inhouse-fields").addClass('hidden');
	} 
}
</script>

<?php
require SITE_ROOT.'footer.php';
