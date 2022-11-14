<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access_admin = $User->is_admin() ? true : false;
$access9 = ($User->checkAccess('hca_hvac_inspections', 9)) ? true : false;
$access20 = ($User->checkAccess('hca_hvac_inspections', 20)) ? true : false;

if (!$access20)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaHVACInspections = new HcaHVACInspections;

if (isset($_POST['add']))
{
	$form_data = [
		'item_name'	=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		//'location_id'	=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
		'equipment_id'	=> isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0,
		//'element_id'	=> isset($_POST['element_id']) ? intval($_POST['element_id']) : 0,
		'display_position' => isset($_POST['display_position']) ? intval($_POST['display_position']) : 0,
		'display_in_checklist' => isset($_POST['display_in_checklist']) ? intval($_POST['display_in_checklist']) : 0,
		'req_appendixb' => isset($_POST['req_appendixb']) ? intval($_POST['req_appendixb']) : 0,
		'summary_report' => isset($_POST['summary_report']) ? intval($_POST['summary_report']) : 0,
		'item_type' => isset($_POST['item_type']) ? intval($_POST['item_type']) : 0,
		'item_inspection_type' => isset($_POST['item_inspection_type']) ? intval($_POST['item_inspection_type']) : 0,
		'comment_required' => isset($_POST['comment_required']) ? intval($_POST['comment_required']) : 0,
	];

	$job_actions = [];
	if (isset($_POST['job_action']) && !empty($_POST['job_action']))
	{
		foreach($_POST['job_action'] as $key => $val)
		{
			if ($val == 1)
				$job_actions[] = $key;
		}
	}
	$form_data['job_actions'] = !empty($job_actions) ? implode(',', $job_actions) : '';

	if ($form_data['item_name'] == '')
		$Core->add_error('item name cannot be empty.');

	//if ($form_data['location_id'] == 0)
	//	$Core->add_error('Select a location.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_hvac_inspections_items', $form_data);
		
		// Add flash message
		$flash_message = 'item has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'item_name'			=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		//'location_id'		=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
		'equipment_id'		=> isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0,
		//'element_id'		=> isset($_POST['element_id']) ? intval($_POST['element_id']) : 0,
		'display_position' 	=> isset($_POST['display_position']) ? intval($_POST['display_position']) : 0,
		'display_in_checklist' => isset($_POST['display_in_checklist']) ? intval($_POST['display_in_checklist']) : 0,
		'req_appendixb' 	=> isset($_POST['req_appendixb']) ? intval($_POST['req_appendixb']) : 0,
		'summary_report' 	=> isset($_POST['summary_report']) ? intval($_POST['summary_report']) : 0,
		'item_type'			=> isset($_POST['item_type']) ? intval($_POST['item_type']) : 0,
		'item_inspection_type' => isset($_POST['item_inspection_type']) ? intval($_POST['item_inspection_type']) : 0,
		'comment_required' => isset($_POST['comment_required']) ? intval($_POST['comment_required']) : 0,
	];

	$job_actions = [];
	if (isset($_POST['job_action']) && !empty($_POST['job_action']))
	{
		foreach($_POST['job_action'] as $key => $val)
		{
			if ($val == 1)
				$job_actions[] = $key;
		}
	}
	$form_data['job_actions'] = !empty($job_actions) ? implode(',', $job_actions) : '';

	if ($form_data['item_name'] == '')
		$Core->add_error('item name cannot be empty.');

	//if ($form_data['location_id'] == 0)
	//	$Core->add_error('Select a location.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_hvac_inspections_items', $form_data, $id);

		// Add flash message
		$flash_message = 'item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$DBLayer->delete('hca_hvac_inspections_items', $id);

	$query = array(
		'DELETE'	=> 'hca_hvac_inspections_checklist_items',
		'WHERE'		=> 'item_id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_hvac_inspections_items', 0), $flash_message);
}

if ($id > 0)
{
	$Core->set_page_title('item management');
	$Core->set_page_id('hca_hvac_inspections_items', 'hca_hvac_inspections');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_hvac_inspections_items AS i',
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);
?>

<form method="post" accept-charset="utf-8" action="" class="was-validated">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit item</h6>
		</div>
		<div class="card-body">

			<div class="row">

				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label" for="select_locations">Equipments</label>
						<select name="equipment_id" class="form-select form-select-sm">
<?php
	foreach($HcaHVACInspections->equipments as $key => $value)
	{
		if ($key == $item_info['equipment_id'])
			echo '<option value="'.$key.'" selected>'.html_encode($value).'</option>';
		else
			echo '<option value="'.$key.'">'.html_encode($value).'</option>';
	}
?>
						</select>
					</div>
				</div>

				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label" for="fld_item_name">Item name</label>
						<input type="text" name="item_name" value="<?php echo html_encode($item_info['item_name']) ?>" class="form-control" id="fld_item_name" required>
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label" for="fld_display_position">Position</label>
						<input type="number" name="display_position" value="<?php echo html_encode($item_info['display_position']) ?>" class="form-control" id="fld_display_position" required>
					</div>
				</div>
			</div>

			<label class="form-label mb-1">Type of inspection</label>
			<div class="mb-3">
				<div class="">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="item_inspection_type" id="fld_item_inspection_type1" value="1" <?php echo ($item_info['item_inspection_type'] == 1) ? 'checked' : '' ?> required>
						<label class="form-check-label" for="fld_item_inspection_type1">Full inspection</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="item_inspection_type" id="fld_item_inspection_type2" value="2" <?php echo ($item_info['item_inspection_type'] == 2) ? 'checked' : '' ?>>
						<label class="form-check-label" for="fld_item_inspection_type2">Filter replacement</label>
					</div>
				</div>
				<span class="text-muted mb-3">Set how would you like to see this item</span>
			</div>

			<label class="form-label mb-1">Create Work Order if checkbox was marked as:</label>
			<div class="mb-3">
				<div class="">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="item_type" id="fld_item_type0" value="0" <?php echo ($item_info['item_type'] == 0) ? 'checked' : '' ?>>
						<label class="form-check-label" for="fld_item_type0">Disabled</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="item_type" id="fld_item_type1" value="1" <?php echo ($item_info['item_type'] == 1) ? 'checked' : '' ?>>
						<label class="form-check-label" for="fld_item_type1">YES</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="item_type" id="fld_item_type2" value="2" <?php echo ($item_info['item_type'] == 2) ? 'checked' : '' ?>>
						<label class="form-check-label" for="fld_item_type2">NO</label>
					</div>
				</div>
				<span class="text-muted mb-3">The Work Order will be created depending on the option selected</span>
			</div>

			<h6 class="card-title mb-0">Work Order dropdown options</h6>
			<hr class="my-2">
			<div class="mb-3">
<?php
	$job_actions = explode(',', $item_info['job_actions']);
	foreach($HcaHVACInspections->actions as $key => $value)
	{
		$checked = in_array($key, $job_actions) ? 'checked' : '';
?>
				<div class="form-check form-check-inline">
					<input type="hidden" name="job_action[<?=$key?>]" value="0">
					<input class="form-check-input" id="fld_job_action<?=$key?>" type="checkbox" name="job_action[<?=$key?>]" value="1" <?php echo $checked ?>>
					<label class="form-check-label" for="fld_job_action<?=$key?>"><?php echo $value ?></label>
				</div>
<?php
	}
?>
			</div>

			<h6 class="card-title mb-0">Item settings</h6>
			<hr class="my-2">
			<div class="mb-2">
				<div class="form-check form-check-inline">
					<input type="hidden" name="comment_required" value="0">
					<input class="form-check-input" id="fld_comment_required" type="checkbox" name="comment_required" value="1" <?php echo ($item_info['comment_required'] == '1' ? 'checked' : '') ?>>
					<label class="form-check-label" for="fld_comment_required">Comment required</label>
				</div>
			</div>
			<div class="mb-2">
				<div class="form-check form-check-inline">
					<input type="hidden" name="display_in_checklist" value="0">
					<input class="form-check-input" id="fld_display_in_checklist" type="checkbox" name="display_in_checklist" value="1" <?php echo ($item_info['display_in_checklist'] == '1' ? 'checked' : '') ?>>
					<label class="form-check-label" for="fld_display_in_checklist">Display this item in Checklists/Work Orders</label>
				</div>
			</div>
			<div class="mb-2">
				<div class="form-check form-check-inline">
					<input type="hidden" name="req_appendixb" value="0">
					<input class="form-check-input" id="fld_req_appendixb" type="checkbox" name="req_appendixb" value="1" <?php echo ($item_info['req_appendixb'] == '1' ? 'checked' : '') ?>>
					<label class="form-check-label" for="fld_req_appendixb">Request Appendix-B form</label>
				</div>
			</div>
			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input type="hidden" name="summary_report" value="0">
					<input class="form-check-input" id="fld_summary_report" type="checkbox" name="summary_report" value="1" <?php echo ($item_info['summary_report'] == '1' ? 'checked' : '') ?>>
					<label class="form-check-label" for="fld_summary_report">Display this item in Summary Report</label>
				</div>
			</div>

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<a href="<?php echo $URL->link('hca_hvac_inspections_items', 0) ?>" class="btn btn-secondary text-white">Back</a>
<?php if ($User->is_admin()): ?>
			<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
<?php endif; ?>
		</div>
	</div>
</form>

<?php
	require SITE_ROOT.'footer.php';
}

$query = array(
	'SELECT'	=> 'i.*',
	'FROM'		=> 'hca_hvac_inspections_items AS i',
	'ORDER BY'	=> 'i.equipment_id, i.display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_title('List of items');
$Core->set_page_id('hca_hvac_inspections_items', 'hca_hvac_inspections');
require SITE_ROOT.'header.php';
?>

<div class="accordion-item mb-3" id="accordionExample">
	<h2 class="accordion-header" id="heading0">
		<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse0" aria-expanded="true" aria-controls="collapse0">+ Add an item</button>
	</h2>
	<div id="collapse0" class="accordion-collapse collapse" aria-labelledby="heading0" data-bs-parent="#accordionExample">
		<div class="accordion-body card-body">

			<form method="post" accept-charset="utf-8" action="" class="was-validated">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
				<div class="row">
					<div class="col-md-3">
						<div class="mb-3">
							<label class="form-label" for="select_locations">Equipments</label>
							<select name="equipment_id" class="form-select form-select-sm">
<?php
foreach($HcaHVACInspections->equipments as $key => $value)
{
	if (isset($_POST['equipment_id']) && $key == $_POST['equipment_id'])
		echo '<option value="'.$key.'" selected>'.html_encode($value).'</option>';
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
							</select>
						</div>
					</div>
					<div class="col-md-3">
						<div class="mb-3">
							<label class="form-label" for="fld_item_name">Item name</label>
							<input type="text" name="item_name" value="<?php echo isset($_POST['item_name']) ? html_encode($_POST['item_name']) : '' ?>" class="form-control" id="fld_item_name" required>
						</div>
					</div>
					<div class="col-md-3">
						<div class="mb-3">
							<label class="form-label" for="fld_display_position">Position</label>
							<input type="number" name="display_position" value="<?php echo isset($_POST['display_position']) ? html_encode($_POST['display_position']) : '0' ?>" class="form-control" id="fld_display_position" required>
						</div>
					</div>
				</div>

				<label class="form-label mb-1">Type of inspection</label>
				<div class="mb-3">
					<div class="">
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="item_inspection_type" id="fld_item_inspection_type1" value="1" required>
							<label class="form-check-label" for="fld_item_inspection_type1">Full inspection</label>
						</div>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="item_inspection_type" id="fld_item_inspection_type2" value="2">
							<label class="form-check-label" for="fld_item_inspection_type2">Filter replacement</label>
						</div>
					</div>
					<span class="text-muted mb-3">Set how would you like to see this item</span>
				</div>

				<label class="form-label mb-1">Item visibility</label>
				<div class="mb-3">
					<div class="">
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="item_type" id="fld_item_type0" value="0" checked>
							<label class="form-check-label" for="fld_item_type0">Hidden</label>
						</div>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="item_type" id="fld_item_type1" value="1">
							<label class="form-check-label" for="fld_item_type1">As Checklist item</label>
						</div>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="item_type" id="fld_item_type2" value="2">
							<label class="form-check-label" for="fld_item_type2">As Work Order item</label>
						</div>
					</div>
					<span class="text-muted mb-3">Set how would you like to see this item</span>
				</div>

				<h6 class="card-title mb-0">Job actions visibility</h6>
				<hr class="my-2">
				<div class="mb-3">
<?php
foreach($HcaHVACInspections->actions as $key => $value)
{
?>
					<div class="form-check form-check-inline">
						<input type="hidden" name="job_action[<?=$key?>]" value="0">
						<input class="form-check-input" id="fld_job_action<?=$key?>" type="checkbox" name="job_action[<?=$key?>]" value="1">
						<label class="form-check-label" for="fld_job_action<?=$key?>"><?php echo $value ?></label>
					</div>
<?php
}
?>
				</div>

				<h6 class="card-title mb-0">Item settings</h6>
				<hr class="my-2">
				<div class="mb-2">
					<div class="form-check form-check-inline">
						<input type="hidden" name="comment_required" value="0">
						<input class="form-check-input" id="fld_comment_required" type="checkbox" name="comment_required" value="1">
						<label class="form-check-label" for="fld_comment_required">Comment required</label>
					</div>
				</div>
				<div class="mb-2">
					<div class="form-check form-check-inline">
						<input type="hidden" name="display_in_checklist" value="0">
						<input class="form-check-input" id="fld_display_in_checklist" type="checkbox" name="display_in_checklist" value="1" checked>
						<label class="form-check-label" for="fld_display_in_checklist">Display this item in Checklists and Work Orders</label>
					</div>
				</div>
				<div class="mb-2">
					<div class="form-check form-check-inline">
						<input type="hidden" name="req_appendixb" value="0">
						<input class="form-check-input" id="fld_req_appendixb" type="checkbox" name="req_appendixb" value="1">
						<label class="form-check-label" for="fld_req_appendixb">Request Appendix-B form</label>
					</div>
				</div>
				<div class="mb-3">
					<div class="form-check form-check-inline">
						<input type="hidden" name="summary_report" value="0">
						<input class="form-check-input" id="fld_summary_report" type="checkbox" name="summary_report" value="1" checked>
						<label class="form-check-label" for="fld_summary_report">Display this item in Summary Report</label>
					</div>
				</div>

				<button type="submit" name="add" class="btn btn-primary">Add item</button>

			</form>
		</div>
	</div>
</div>

<div class="card-header">
	<h6 class="card-title mb-0">List of items</h6>
</div>
<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>Equipment</th>
			<th>Element</th>
			<th>Dropdown items</th>
			<th>Type of inspection</th>
			<th>Problem/WO</th>
			<th>Appendix-B</th>
			<th>Summary report</th>
			<th>Position</th>
			<th></th>
		</tr>
	</thead>
	<tbody>

<?php
if (!empty($main_info))
{
	foreach($main_info as $cur_info)
	{
		$equipment_name = $HcaHVACInspections->getEquipment($cur_info['equipment_id']);
		$actions = ($access9) ? '<a href="'.$URL->link('hca_hvac_inspections_items', $cur_info['id']).'" class="badge bg-primary text-white">Edit</a>' : '';

		$job_actions = [];
		$item_job_actions = explode(',', $cur_info['job_actions']);
		foreach($HcaHVACInspections->actions as $key => $value)
		{
			if (in_array($key, $item_job_actions))
				$job_actions[] = $value;
		}
?>
		<tr class="<?php echo (($cur_info['item_type'] == 0) ? 'table-danger' : ($cur_info['summary_report'] == 0 ? 'table-warning' : '')) ?>">
			<td class="fw-bold"><?php echo html_encode($equipment_name) ?></td>
			<td class=""><?php echo html_encode($cur_info['item_name']) ?></td>
			<td class=""><?php echo implode(', ', $job_actions) ?></td>
			<td class="ta-center"><?php echo ($cur_info['item_inspection_type'] == 2 ? 'Filter repl.' : 'Full insp.') ?></td>
			<td class="ta-center"><?php echo ($cur_info['item_type'] == 1 ? 'Problem' : ($cur_info['item_type'] == 2 ? 'WO' : 'Off')) ?></td>
			<td class="ta-center"><?php echo ($cur_info['req_appendixb'] == 1 ? 'Required' : '') ?></td>
			<td class="ta-center"><?php echo ($cur_info['summary_report'] == 1 ? 'Visible' : '') ?></td>
			<td class="fw-bold ta-center"><?php echo $cur_info['display_position'] ?></td>
			<td class="fw-bold ta-center"><?php echo $actions ?></td>
		</tr>
<?php
	}
}
?>
	</tbody>
</table>

<?php
require SITE_ROOT.'footer.php';
