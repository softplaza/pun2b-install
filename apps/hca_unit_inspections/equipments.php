<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_ui', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_POST['add']))
{
	$form_data = [
		'equipment_name'		=> isset($_POST['equipment_name']) ? swift_trim($_POST['equipment_name']) : ''
	];
	
	if ($form_data['equipment_name'] == '')
		$Core->add_error('equipment name cannot be empty.');
	
	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_ui_equipments', $form_data);
		
		// Add flash message
		$flash_message = 'equipment has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'equipment_name'		=> isset($_POST['equipment_name']) ? swift_trim($_POST['equipment_name']) : '',
		'display_position' => isset($_POST['display_position']) ? intval($_POST['display_position']) : 0
	];

	if ($form_data['equipment_name'] == '')
		$Core->add_error('equipment name cannot be empty.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_ui_equipments', $form_data, $id);

		// Add flash message
		$flash_message = 'equipment has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$DBLayer->delete('hca_ui_equipments', $id);

	// Add flash message
	$flash_message = 'equipment has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_ui_equipments', 0), $flash_message);
}

if ($id > 0)
{
	$Core->set_page_title('equipment management');
	$Core->set_page_id('hca_ui_equipments', 'hca_ui');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'l.*',
		'FROM'		=> 'hca_ui_equipments AS l',
		'WHERE'		=> 'l.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit equipment</h6>
		</div>
		<div class="card-body">

			<div class="mb-3">
				<label class="form-label" for="fld_equipment_name">equipment name</label>
				<input type="text" name="equipment_name" value="<?php echo html_encode($item_info['equipment_name']) ?>" class="form-control" id="fld_equipment_name" required>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_display_position">Position</label>
				<input type="text" name="display_position" value="<?php echo html_encode($item_info['display_position']) ?>" class="form-control" id="fld_display_position" required>
			</div>

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<a href="<?php echo $URL->link('hca_ui_equipments', 0) ?>" class="btn btn-secondary text-white">Back</a>
			<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
		</div>
	</div>
</form>

<?php
	require SITE_ROOT.'footer.php';
}

$query = array(
	'SELECT'	=> 'e.*',
	'FROM'		=> 'hca_ui_equipments AS e',
	'ORDER BY'	=> 'e.display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_title('List of equipments');
$Core->set_page_id('hca_ui_equipments', 'hca_ui');
require SITE_ROOT.'header.php';
?>

<div class="card-header">
	<h6 class="card-title mb-0">Add a equipment</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-body">
			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_equipment_name">equipment name</label>
						<input type="text" name="equipment_name" value="" class="form-control" id="fld_equipment_name" required>
					</div>
				</div>
			</div>
			<button type="submit" name="add" class="btn btn-primary">Add equipment</button>
		</div>
	</div>
</form>

<div class="card-header">
	<h6 class="card-title mb-0">List of equipments</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>equipment name</th>
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
	?>
			<tr>
				<td class="fw-bold"><?php echo html_encode($cur_info['equipment_name']) ?></td>
				<td class="fw-bold ta-center"><?php echo $cur_info['display_position'] ?></td>
				<td class="fw-bold ta-center"><a href="<?php echo $URL->link('hca_ui_equipments', $cur_info['id']) ?>" class="badge bg-primary text-white">Edit</a></td>
			</tr>
	<?php
	}
}
?>
	</tbody>
</table>

<?php
require SITE_ROOT.'footer.php';
