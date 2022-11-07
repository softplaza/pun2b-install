<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access_admin = $User->is_admin() ? true : false;
$access9 = ($User->checkAccess('hca_ui', 9)) ? true : false;
$access20 = ($User->checkAccess('hca_ui', 20)) ? true : false;

if (!$access9)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaUnitInspection = new HcaUnitInspection;

if (isset($_POST['add']))
{
	$form_data = [
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		'display_position' => isset($_POST['display_position']) ? intval($_POST['display_position']) : 0,
		'part_number' => isset($_POST['part_number']) ? swift_trim($_POST['part_number']) : '',
	];

	if (isset($_POST['location_id'])) $form_data['location_id'] = intval($_POST['location_id']);
	if (isset($_POST['equipment_id'])) $form_data['equipment_id'] = intval($_POST['equipment_id']);
	if (isset($_POST['element_id'])) $form_data['element_id'] = intval($_POST['element_id']);

	//if ($form_data['item_name'] == '')
	//	$Core->add_error('item name cannot be empty.');

	//if ($form_data['location_id'] == 0)
	//	$Core->add_error('Select a location.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_ui_items', $form_data);
		
		// Add flash message
		$flash_message = 'item has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		//'location_id'	=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
		//'equipment_id'	=> isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0,
		//'element_id'	=> isset($_POST['element_id']) ? intval($_POST['element_id']) : 0,
		'display_position' => isset($_POST['display_position']) ? intval($_POST['display_position']) : 0,
		'display_in_checklist' => isset($_POST['display_in_checklist']) ? intval($_POST['display_in_checklist']) : 0,
		'req_appendixb' => isset($_POST['req_appendixb']) ? intval($_POST['req_appendixb']) : 0,
		'summary_report' => isset($_POST['summary_report']) ? intval($_POST['summary_report']) : 0,
		'part_number' => isset($_POST['part_number']) ? swift_trim($_POST['part_number']) : '',
	];

	if (isset($_POST['location_id'])) $form_data['location_id'] = intval($_POST['location_id']);
	if (isset($_POST['equipment_id'])) $form_data['equipment_id'] = intval($_POST['equipment_id']);
	if (isset($_POST['element_id'])) $form_data['element_id'] = intval($_POST['element_id']);

	//if ($form_data['item_name'] == '')
	//	$Core->add_error('item name cannot be empty.');

	//if ($form_data['location_id'] == 0)
	//	$Core->add_error('Select a location.');

	$problems = [];
	if (isset($_POST['problem']) && !empty($_POST['problem']))
	{
		foreach($_POST['problem'] as $key => $val)
		{
			if ($val == 1)
				$problems[] = $key;
		}
	}

	$form_data['problems'] = !empty($problems) ? implode(',', $problems) : '';

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_ui_items', $form_data, $id);

		// Add flash message
		$flash_message = 'item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$DBLayer->delete('hca_ui_items', $id);

	$query = array(
		'DELETE'	=> 'hca_ui_checklist_items',
		'WHERE'		=> 'item_id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_ui_items', 0), $flash_message);
}

if ($id > 0)
{
	$Core->set_page_id('hca_ui_items', 'hca_ui');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_ui_items AS i',
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit item</h6>
		</div>
		<div class="card-body">

			<?php if ($User->checkAccess('hca_ui', 9)): ?>
			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="select_locations">Locations</label>
						<select name="location_id" class="form-select form-select-sm" required>

<?php

	foreach($HcaUnitInspection->locations as $location_id => $location_name)
	{
		if ($location_id == $item_info['location_id'])
			echo '<option value="'.$location_id.'" selected>'.html_encode($location_name).'</option>';
		else
			echo '<option value="'.$location_id.'">'.html_encode($location_name).'</option>';
	}
?>
						</select>
					</div>
				</div>
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="select_locations">Equipments</label>
						<select name="equipment_id" class="form-select form-select-sm">
<?php
	foreach($HcaUnitInspection->equipments as $key => $value)
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
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="select_locations">Elements</label>
						<select name="element_id" class="form-select form-select-sm" required>
<?php
	foreach($HcaUnitInspection->elements as $key => $value)
	{
		if ($key == $item_info['element_id'])
			echo '<option value="'.$key.'" selected>'.html_encode($value).'</option>';
		else
			echo '<option value="'.$key.'">'.html_encode($value).'</option>';
	}
?>
						</select>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_item_name">item name</label>
						<input type="text" name="item_name" value="<?php echo html_encode($item_info['item_name']) ?>" class="form-control" id="fld_item_name" required>
					</div>
				</div>
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_display_position">Position</label>
						<input type="text" name="display_position" value="<?php echo html_encode($item_info['display_position']) ?>" class="form-control" id="fld_display_position" required>
					</div>
				</div>
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_part_number">Part number</label>
						<input type="text" name="part_number" value="<?php echo html_encode($item_info['part_number']) ?>" class="form-control" id="fld_part_number">
					</div>
				</div>
			</div>

			<h6 class="card-title mb-0">Problems</h6>
			<hr class="my-2">
			<div class="mb-3">
<?php

$problems = explode(',', $item_info['problems']);
foreach($HcaUnitInspection->getProblems() as $key => $value)
{
	$checked = in_array($key, $problems) ? 'checked' : '';
?>
				<div class="form-check form-check-inline">
					<input type="hidden" name="problem[<?=$key?>]" value="0">
					<input class="form-check-input" id="fld_problem<?=$key?>" type="checkbox" name="problem[<?=$key?>]" value="1" <?php echo $checked ?>>
					<label class="form-check-label" for="fld_problem<?=$key?>"><?php echo $value ?></label>
				</div>
<?php
}
?>
			</div>

			<h6 class="card-title mb-0">Displaying of this item</h6>
			<hr class="my-2">

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
			<a href="<?php echo $URL->link('hca_ui_items', 0) ?>" class="btn btn-secondary text-white">Back</a>
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
	'FROM'		=> 'hca_ui_items AS i',
	'ORDER BY'	=> 'i.display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_id('hca_ui_items', 'hca_ui');
require SITE_ROOT.'header.php';
?>

<div class="accordion-item mb-3" id="accordionExample">
	<h2 class="accordion-header" id="heading0">
		<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse0" aria-expanded="true" aria-controls="collapse0">+ Add an item</button>
	</h2>
	<div id="collapse0" class="accordion-collapse collapse" aria-labelledby="heading0" data-bs-parent="#accordionExample">
		<div class="accordion-body card-body">

			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

				<div class="row">
					<div class="col-md-4">
						<div class="mb-3">
							<label class="form-label" for="select_locations">Locations</label>
							<select name="location_id" class="form-select form-select-sm" required>
<?php
	foreach($HcaUnitInspection->locations as $location_id => $location_name)
	{
		echo '<option value="'.$location_id.'">'.html_encode($location_name).'</option>';
	}
?>
							</select>
						</div>
					</div>
					<div class="col-md-4">
						<div class="mb-3">
							<label class="form-label" for="select_locations">Equipments</label>
							<select name="equipment_id" class="form-select form-select-sm">
								<option value="0">No equipment</option>
<?php
	foreach($HcaUnitInspection->equipments as $key => $value)
	{
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
	}
?>
							</select>
						</div>
					</div>
					<div class="col-md-4">
						<div class="mb-3">
							<label class="form-label" for="select_locations">Elements</label>
							<select name="element_id" class="form-select form-select-sm" required>
<?php
	foreach($HcaUnitInspection->elements as $key => $value)
	{
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
	}
?>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="mb-3">
							<label class="form-label" for="fld_item_name">Item name</label>
							<input type="text" name="item_name" value="<?php echo isset($_POST['item_name']) ? html_encode($_POST['item_name']) : '' ?>" class="form-control" id="fld_item_name" required>
						</div>
					</div>
					<div class="col-md-6">
						<div class="mb-3">
							<label class="form-label" for="fld_display_position">Position</label>
							<input type="text" name="display_position" value="<?php echo isset($_POST['display_position']) ? html_encode($_POST['display_position']) : '' ?>" class="form-control" id="fld_display_position" required>
						</div>
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
			<th>Location</th>
			<th>Element</th>
			<th>Part #</th>
<?php if ($access_admin): ?>
			<th>Equipment</th>
			<th>Display Name</th>
<?php endif; ?>
			<th>Problems</th>
			<th>Displayed in Checklist</th>
			<th>Displayed in Summary report</th>
			<th>Position</th>
			<th></th>
		</tr>
	</thead>
	<tbody>

<?php
if (!empty($main_info))
{
	$i = 1;
	foreach($HcaUnitInspection->locations as $location_id => $location_name)
	{
		foreach($main_info as $cur_info)
		{
			if ($location_id == $cur_info['location_id'])
			{
				//$css_row = ($cur_info['display_in_checklist'] == 0) ? 'table-danger' : ($cur_info['summary_report'] == 0 ? 'table-warning' : '')
				$problem_names = $HcaUnitInspection->getItemProblems($cur_info['problems']);
				$actions = ($access9) ? '<a href="'.$URL->link('hca_ui_items', $cur_info['id']).'" class="badge bg-primary text-white">Edit</a>' : '';
?>
		<tr class="<?php echo (($cur_info['display_in_checklist'] == 0) ? 'table-danger' : ($cur_info['summary_report'] == 0 ? 'table-warning' : '')) ?>">
			<td class=""><?php echo $location_name ?></td>
			<td class="fw-bold"><?php echo html_encode($HcaUnitInspection->getElement($cur_info['element_id'])) ?></td>
			<td class="ta-center"><?php echo html_encode($cur_info['part_number']) ?></td>
<?php if ($access_admin): ?>
			<td class=""><?php echo html_encode($HcaUnitInspection->getEquipment($cur_info['equipment_id'])) ?></td>
			<td class=""><?php echo html_encode($cur_info['item_name']) ?></td>
<?php endif; ?>
			<td class="fw-bold text-danger"><?php echo $problem_names ?></td>
			<td class="ta-center"><?php echo ($cur_info['display_in_checklist'] == 1 ? 'Yes' : '') ?></td>
			<td class="ta-center"><?php echo ($cur_info['summary_report'] == 1 ? 'Yes' : '') ?></td>
			<td class="fw-bold ta-center"><?php echo $cur_info['display_position'] ?></td>
			<td class="fw-bold ta-center"><?php echo $actions ?></td>
		</tr>
<?php
				++$i;
			}
		}
		
	}
}
?>
	</tbody>
</table>

<?php
require SITE_ROOT.'footer.php';
