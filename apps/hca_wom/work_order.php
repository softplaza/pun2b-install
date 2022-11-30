<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access1 = ($User->checkAccess('hca_wom', 1)) ? true : false;
if (!$access1)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaWOM = new HcaWOM;

if (isset($_POST['add']))
{
	$form_data = array(
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_id'			=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,

		'dt_created'		=> date('Y-m-d\TH:i:s'),
		'requested_by'		=> $User->get('id'),
		'wo_status'			=> 1
	);

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select a property from dropdown list.');

	if (empty($Core->errors))
	{
		// Create a new Work Order
		$new_id = $DBLayer->insert_values('hca_wom_work_orders', $form_data);

		//$DBLayer->insert_values('hca_wom_tasks', ['work_order_id' => $new_id]);

		// Add flash message
		$flash_message = 'Work Order has been created';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_wom_work_order', $new_id), $flash_message);
	}
}

else if (isset($_POST['update']) || isset($_POST['complete']))
{
	$form_data = array(
		//'date_requested'	=> isset($_POST['date_requested']) ? swift_trim($_POST['date_requested']) : '',
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 0,
		//'assigned_to'		=> isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,

		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
	);

	$wo_info = $HcaWOM->getWorkOrderInfo($id);

	if (isset($_POST['complete']))
		$form_data['wo_status'] = 4;

	if ($form_data['assigned_to'] > 0 && $wo_info['assigned_to'] == 0)
		$form_data['wo_status'] = 1;

	if (strtotime($form_data['date_requested']) < 0)
		$Core->add_error('Set the "Requested Date".');

	if (empty($Core->errors))
	{
		// Update Work Order
		$DBLayer->update('hca_wom_work_orders', $form_data, $id);

		if (isset($_POST['task']) & !empty($_POST['task']))
		{
			foreach($_POST['task'] as $task_id => $value)
			{
				$task_form = [
					'task_type'		=> isset($_POST['task_type'][$task_id]) ? intval($_POST['task_type'][$task_id]) : 0,
					'task_item'		=> isset($_POST['task_item'][$task_id]) ? intval($_POST['task_item'][$task_id]) : 0,
					'task_action'	=> isset($_POST['task_action'][$task_id]) ? intval($_POST['task_action'][$task_id]) : 0,
					'task_message'	=> isset($_POST['task_message'][$task_id]) ? swift_trim($_POST['task_message'][$task_id]) : '',
				];
				$DBLayer->update('hca_wom_tasks', $task_form, $task_id);
			}
		}

		// Add flash message
		$flash_message = 'Work Order #'.$id.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['cancel_wo']))
{
	$DBLayer->update('hca_wom_work_orders', ['wo_status' => 0, 'assigned_to' => 0], $id);

	// Add flash message
	$flash_message = 'Work Order #'.$id.' has been canceled.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['add_task']))
{
	$form_data = array(
		'task_type'		=> isset($_POST['task_type']) ? intval($_POST['task_type']) : 0,
		'task_item'		=> isset($_POST['task_item']) ? intval($_POST['task_item']) : 0,
		'task_action'	=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'assigned_to'	=> isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'work_order_id' => $id,
	);

	//if ($form_data['assigned_to'] == 0)
	//	$Core->add_error('Select technician.');

	if (empty($Core->errors))
	{
		// Update task of Work Order
		$DBLayer->insert_values('hca_wom_tasks', $form_data);

		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['update_task']))
{
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
	$form_data = array(
		'task_type'		=> isset($_POST['task_type']) ? intval($_POST['task_type']) : 0,
		'task_item'		=> isset($_POST['task_item']) ? intval($_POST['task_item']) : 0,
		'task_action'	=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'assigned_to'	=> isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
	);

	//if ($form_data['assigned_to'] == 0)
	//	$Core->add_error('Select technician.');

	if (empty($Core->errors) && $task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);


		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_task']))
{
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	if ($task_id > 0)
	{
		$DBLayer->delete('hca_wom_tasks', $task_id);

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

	if (empty($wo_info))
		message('The Work Order does not exist.');

	$query = [
		'SELECT'	=> 't.*, i.item_name, i.item_actions, u1.realname AS assigned_name',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.task_item'
			],
			[
				'LEFT JOIN'		=> 'users AS u1',
				'ON'			=> 'u1.id=t.assigned_to'
			],
		],
		'WHERE'		=> 't.work_order_id='.$id,
		'ORDER BY'	=> 't.id',
	];
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
					<label class="form-label">Submit Date & Time</label>
					<h5 class="mb-0"><?php echo format_date($wo_info['dt_created'], 'm/d/Y H:i') ?></h5>
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
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" <?php echo ($wo_info['has_animal'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_has_animal">Animal in Unit</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" <?php echo ($wo_info['enter_permission'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>
					</div>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_wo_message">Comments</label>
				<textarea type="text" name="wo_message" class="form-control" id="fld_wo_message" placeholder="Enter any special instructions for entry (example: After 2 pm only please)"><?php echo html_encode($wo_info['wo_message']) ?></textarea>
			</div>

			<div class="mb-3">
<?php if ($wo_info['wo_status'] == 3): ?>
				<button type="submit" name="complete" class="btn btn-success">Complete</button>
<?php else: ?>
				<button type="submit" name="update" class="btn btn-primary">Update</button>
<?php endif; ?>

<?php if ($wo_info['wo_status'] < 3): ?>
				<button type="submit" name="cancel_wo" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel it?')">Cancel</button>
<?php endif; ?>
			</div>
		</div>
	</div>
</form>

<div class="card-header d-flex justify-content-between">
	<h5 class="card-title mb-0">Tasks</h5>
	<span class="badge bg-primary" role="button" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="manageTask(0)"><i class="fas fa-plus"></i> new task</span>
</div>
<?php
	if (!empty($tasks_info))
	{
?>
<table class="table table-sm table-striped table-bordered">
	<thead>
		<tr>
			<th>Type</th>
			<th>Item</th>
			<th>Action</th>
			<th>Details</th>
			<th>Assigned to</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
		foreach ($tasks_info as $cur_info)
		{
			$edit = ($User->is_admmod()) ? '<span class="badge bg-primary" role="button" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="manageTask('.$cur_info['id'].')"><i class="fas fa-edit"></i> edit</span>' : '';
?>
		<tr>
			<td class="min-100 ta-center"><?php echo $HcaWOM->item_types[$cur_info['task_type']] ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['item_name']) ?></td>
			<td class="min-100 ta-center"><?php echo $HcaWOM->task_actions[$cur_info['task_action']] ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['task_message']) ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['assigned_name']) ?></td>
			<td class="min-100 ta-center"><?php echo $edit ?></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<?php
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
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Edit information</h5>
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
function getItems(){
	var type_id = $("#fld_task_type").val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({type_id:type_id,csrf_token:csrf_token}),
		success: function(re){
			$("#fld_task_item").empty().html(re.task_items);
			$("#fld_task_action").empty().html('<option value="0" selected disabled>Select one</option>');
		},
		error: function(re){
			document.getElementById("#fld_task_item").innerHTML = re;
		}
	});
}
function getActions(){
	var item_id = $("#fld_task_item").val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,csrf_token:csrf_token}),
		success: function(re){
			$("#fld_task_action").empty().html(re.task_items);

		},
		error: function(re){
			document.getElementById("#fld_task_action").innerHTML = re;
		}
	});
}
</script>

<?php
}
// If a new Work Order
else
{
?>
<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">A new Work Order</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_property_id">Available Properties</label>
					<select id="fld_property_id" name="property_id" class="form-select form-select-sm" required onchange="getUnits()">
<?php
echo '<option value="0" selected disabled>Select one</option>'."\n";
foreach ($sm_property_db as $cur_info)
{
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label" for="fld_unit_number">Unit #</label>
					<div id="unit_list">
						<input type="text" name="unit_id" value="" class="form-control form-control-sm" id="fld_unit_number" disabled>
					</div>
				</div>
			</div>

			<div class="row mb-3 hidden">
				<div class="col-md-3">
					<label class="form-label" for="fld_date_requested">Date Requested</label>
					<input type="date" name="date_requested" value="<?php echo isset($_POST['date_requested']) ? format_date($_POST['date_requested'], 'Y-m-d') : '' ?>" class="form-control form-control-sm" id="fld_date_requested">
				</div>

				<div class="col-md-2">
					<label class="form-label" for="fld_priority">Priority</label>
					<select name="priority" id="fld_priority" class="form-select form-select-sm">
<?php
foreach ($HcaWOM->priority as $key => $val)
{
	if (isset($_POST['priority']) && $_POST['priority'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="row hidden">
				<div class="col-md-3">
					<label class="form-label" for="fld_assigned_to">Technician</label>
					<select name="assigned_to" id="fld_assigned_to" class="form-select form-select-sm">
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="0" selected disabled>Select one</option>'."\n";
	foreach ($users as $cur_user)
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				echo '</optgroup>';
			}
			echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
			$optgroup = $cur_user['group_id'];
		}
		
		if (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $cur_user['id'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
?>
					</select>
				</div>
			</div>

		</div>
	</div>

	<div class="card hidden">
		<div class="card-body">	
			<div class="row mb-3">
				<div class="col-md-4">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" <?php echo (isset($_POST['has_animal']) && $_POST['has_animal'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_has_animal">Animal in Unit</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" <?php echo (isset($_POST['enter_permission']) && $_POST['enter_permission'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>
					</div>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_wo_message">Comments</label>
				<textarea type="text" name="wo_message" class="form-control" id="fld_wo_message" placeholder="Enter any special instructions for entry (example: After 2 pm only please)"><?php echo isset($_POST['wo_message']) ? html_encode($_POST['wo_message']) : '' ?></textarea>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<div class="mb-3">
				<button type="submit" name="add" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</div>
</form>

<script>
function getUnits(){
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
</script>

<?php
}
require SITE_ROOT.'footer.php';