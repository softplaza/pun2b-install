<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 54))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$SwiftUploader = new SwiftUploader;
$HcaWOM = new HcaWOM;

if (isset($_POST['create_tpl']))
{
	$form_data = array(
		//'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'tpl_name'			=> isset($_POST['tpl_name']) ? swift_trim($_POST['tpl_name']) : '',
		'template_type'		=> isset($_POST['template_type']) ? intval($_POST['template_type']) : 0,
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
		'created'			=> time(),
		'created_by'		=> $User->get('id'),
	);

	if ($form_data['tpl_name'] == '')
		$Core->add_error('Enter template name.');

	if (empty($Core->errors))
	{
		// Create a new Work Order
		$new_id = $DBLayer->insert_values('hca_wom_tpl_wo', $form_data);

		// Add flash message
		$flash_message = 'Work Order template has been created.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_wom_admin_templates', $new_id), $flash_message);
	}
}
if (isset($_POST['update_tpl']))
{
	$form_data = array(
		//'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'tpl_name'			=> isset($_POST['tpl_name']) ? swift_trim($_POST['tpl_name']) : '',
		'template_type'		=> isset($_POST['template_type']) ? intval($_POST['template_type']) : 0,
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',
		'created'			=> time(),
		'created_by'		=> $User->get('id'),
	);

	if ($form_data['tpl_name'] == '')
		$Core->add_error('Enter template name.');

	if (empty($Core->errors))
	{
		// Update Work Order
		$DBLayer->update('hca_wom_tpl_wo', $form_data, $id);

		// Add flash message
		$flash_message = 'Work Order Template #'.$id.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_tpl']))
{
	if ($id > 0)
	{
		$DBLayer->delete('hca_wom_tpl_wo', $id);

		// Add flash message
		$flash_message = 'Template #'.$id.' has been deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_wom_admin_templates', 0), $flash_message);
	}
}


// TASK ACTIONS
else if (isset($_POST['add_task']))
{
	$form_data = array(
		'tpl_id' 		=> $id,
		'item_id'		=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'task_action'	=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
	);

	if ($form_data['item_id'] == 0)
		$Core->add_error('Select item from dropdown list.');

	if (empty($Core->errors))
	{
		// Update task of Work Order
		$new_tid = $DBLayer->insert_values('hca_wom_tpl_tasks', $form_data);

		// Add flash message
		$flash_message = 'Task #'.$new_tid.' has been created.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_task']))
{
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	$form_data = [
		'item_id'		=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'task_action'	=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
	];

	if ($form_data['item_id'] == 0)
		$Core->add_error('Select item from dropdown list.');

	if (empty($Core->errors) && $task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tpl_tasks', $form_data, $task_id);
		
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
		$DBLayer->delete('hca_wom_tpl_tasks', $task_id);

		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$Core->set_page_id('hca_wom_admin_templates', 'hca_fs');
require SITE_ROOT.'header.php';

if ($id > 0)
{
	$query = [
		'SELECT'	=> 'tw.*',
		'FROM'		=> 'hca_wom_tpl_wo AS tw',
		'WHERE'		=> 'tw.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$main_info = $DBLayer->fetch_assoc($result);

	if (empty($main_info))
		message('The Work Order does not exist.');

	$query = [
		'SELECT'	=> 'tt.*, i.item_name, i.item_actions, i.item_type, tp.type_name, pb.problem_name',
		'FROM'		=> 'hca_wom_tpl_tasks AS tt',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=tt.item_id'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_types AS tp',
				'ON'			=> 'tp.id=i.item_type'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_problems AS pb',
				'ON'			=> 'pb.id=tt.task_action'
			],
		],
		'WHERE'		=> 'tt.tpl_id='.$id,
		'ORDER BY'	=> 'tt.item_id',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data" class="was-validated">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Work Order Template #<?php echo $main_info['id'] ?></h6>
		</div>
		<div class="card-body">

			<div class="col-md-6 mb-3">
				<label class="form-label" for="fld_tpl_name">Teplate Name</label>
				<input type="text" name="tpl_name" value="<?php echo html_encode($main_info['tpl_name']) ?>" class="form-control form-control-sm" id="fld_tpl_name" required>
			</div>

			<div class="col-md-3 mb-3">
				<label class="form-label" for="fld_template_type">Template Type</label>
				<select name="template_type" id="fld_template_type" class="form-select form-select-sm">
<?php
	foreach ($HcaWOM->template_type as $key => $val)
	{
		if ($main_info['template_type'] == $key)
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
	}
?>
				</select>
			</div>

			<div class="col-md-3 mb-3">
				<label class="form-label" for="fld_priority">Priority</label>
				<select name="priority" id="fld_priority" class="form-select form-select-sm" value="" required>
<?php
	foreach ($HcaWOM->priority as $key => $val)
	{
		if ($main_info['priority'] == $key)
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
	}
?>
				</select>
			</div>

			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" <?php echo ($main_info['has_animal'] == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_has_animal">Pets in Unit</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" <?php echo ($main_info['enter_permission'] == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_wo_message">Comments</label>
				<textarea type="text" name="wo_message" class="form-control" id="fld_wo_message" placeholder="Enter any special instructions for entry (example: After 2 pm only please)"><?php echo html_encode($main_info['wo_message']) ?></textarea>
			</div>

			<div class="mb-3">
				<button type="submit" name="update_tpl" class="btn btn-primary">Save changes</button>
				<button type="submit" name="delete_tpl" class="btn btn-danger">Delete</button>
			</div>
		</div>
	</div>
</form>

<div class="card-header d-flex justify-content-between">
	<h6 class="card-title mb-0">Tasks</h6>
	<span class="badge bg-primary" role="button" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="manageTask(0)"><i class="fas fa-plus"></i> new task</span>
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
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
		$i = 1;
		foreach ($tasks_info as $cur_info)
		{
			$edit = '<span class="badge bg-primary" role="button" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="manageTask('.$cur_info['id'].')"><i class="fas fa-edit"></i> edit</span>';
?>
		<tr>
			<td class="ta-center">#<?=$i?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['type_name']) ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['item_name']) ?></td>
			<td class="min-100 ta-center"><?php echo html_encode($cur_info['problem_name']) ?></td>
			<td class="min-100"><?php echo html_encode($cur_info['task_message']) ?></td>
			<td class="min-100 ta-center"><?php echo $edit ?></td>
		</tr>
<?php
		++$i;
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
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"></button>
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
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_manage_tpl_task')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_manage_tpl_task') ?>",
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
}
// If a new Work Order
else
{
?>

<div class="accordion-item mb-3" id="accordionExample">
	<h2 class="accordion-header" id="heading0">
		<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse0" aria-expanded="true" aria-controls="collapse0">+ New Template</button>
	</h2>
	<div id="collapse0" class="accordion-collapse collapse" aria-labelledby="heading0" data-bs-parent="#accordionExample">
		<div class="accordion-body card-body">

			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="col-md-3 mb-3 was-validated">
					<label class="form-label" for="fld_tpl_name">Teplate Name</label>
					<input type="text" name="tpl_name" value="" class="form-control form-control-sm" id="fld_tpl_name" required>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_template_type">Template Type</label>
					<select name="template_type" id="fld_template_type" class="form-select form-select-sm">
<?php
	foreach ($HcaWOM->template_type as $key => $val)
	{
		if (isset($_POST['template_type']) && intval($_POST['template_type']) == $key)
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
	}
?>
					</select>
				</div>

				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_priority">Priority</label>
					<select name="priority" id="fld_priority" class="form-select form-select-sm" value="" required>
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
				<div class="mb-3">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" <?php echo (isset($_POST['has_animal']) && $_POST['has_animal'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_has_animal">Pets in Unit</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" <?php echo (isset($_POST['enter_permission']) && $_POST['enter_permission'] == 1 ? ' checked' : '') ?>>
						<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>
					</div>
				</div>
				<div class="mb-3">
					<textarea type="text" name="wo_message" class="form-control" id="fld_wo_message" placeholder="Enter any special instructions for entry (example: After 2 pm only please)"><?php echo (isset($_POST['wo_message']) ? html_encode($_POST['wo_message']) : '') ?></textarea>
				</div>





				<div class="mb-3">
					<button type="submit" name="create_tpl" class="btn btn-primary">Submit</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="card-header">
	<h6 class="card-title mb-0">List of Templates</h6>
</div>
<?php

	$hca_wom_tpl_wo = [];
	$query = [
		'SELECT'	=> 'tw.*',
		'FROM'		=> 'hca_wom_tpl_wo AS tw',

		//'WHERE'		=> 't.work_order_id IN ('.implode(',', $hca_wom_wo_ids).')',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$hca_wom_tpl_wo[] = $row;
	}

	if (!empty($hca_wom_tpl_wo))
	{
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Template Name</th>
			<th>Template Type</th>
			<th>Priority</th>
			<th>Pets in Unit?</th>
			<th>Permission to Enter?</th>
			<th>Comments</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
		foreach ($hca_wom_tpl_wo as $cur_info)
		{
			$template_type = isset($HcaWOM->template_type[$cur_info['template_type']]) ? html_encode($HcaWOM->template_type[$cur_info['template_type']]) : '';

			$priority = isset($HcaWOM->priority[$cur_info['priority']]) ? html_encode($HcaWOM->priority[$cur_info['priority']]) : '';

			$enter_permission = $cur_info['enter_permission'] == 1 ? 'YES' : 'NO';
			$has_animal = $cur_info['has_animal'] == 1 ? 'YES' : 'NO';
?>
		<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id'] ? ' anchor' : '') ?>">
			<td class="fw-bold"><?php echo html_encode($cur_info['tpl_name']) ?></td>
			<td class="ta-center"><?php echo $template_type ?></td>
			<td class="ta-center"><?php echo $priority ?></td>
			<td class="ta-center"><?php echo $enter_permission ?></td>
			<td class="ta-center"><?php echo $has_animal ?></td>
			<td class=""><?php echo html_encode($cur_info['wo_message']) ?></td>
			<td class="ta-center"><a href="<?php echo $URL->link('hca_wom_admin_templates', $cur_info['id']) ?>" class="badge bg-primary text-white"><i class="fas fa-edit"></i> edit</a></td>
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
		<div class="alert alert-warning" role="alert">You have no items on this page.</div>
	</div>
</div>
<?php
	}
}
require SITE_ROOT.'footer.php';

