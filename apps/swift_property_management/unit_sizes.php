<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_property_management', 4)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_POST['add']))
{
	$form_data = [
		'size_title'			=> isset($_POST['size_title']) ? swift_trim($_POST['size_title']) : '',
		'size_description'	=> isset($_POST['size_description']) ? swift_trim($_POST['size_description']) : '',
	];
	
	if ($form_data['size_title'] == '')
		$Core->add_error('Part name cannot be empty.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('sm_property_unit_sizes', $form_data);
		
		// Add flash message
		$flash_message = 'Item has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_item']))
{
	$item_id = intval(key($_POST['delete_item']));
	$DBLayer->delete('sm_property_unit_sizes', $item_id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['update']))
{
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$form_data = [
		'size_title'			=> isset($_POST['size_title']) ? swift_trim($_POST['size_title']) : '',
		'size_description'	=> isset($_POST['size_description']) ? swift_trim($_POST['size_description']) : '',
	];

	if ($form_data['size_title'] == '')
		$Core->add_error('Name of location cannot be empty.');

	if (empty($Core->errors) && $id > 0)
	{
		$DBLayer->update('sm_property_unit_sizes', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_property_management_unit_sizes', ['', 0]), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'l.*',
	'FROM'		=> 'sm_property_unit_sizes AS l',
	'ORDER BY'	=> 'l.size_title',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_id('sm_property_management_unit_sizes', 'sm_property_management');
require SITE_ROOT.'header.php';

if ($action == 'edit' && $id > 0)
{
	$query = array(
		'SELECT'	=> 'l.*',
		'FROM'		=> 'sm_property_unit_sizes AS l',
		'WHERE'		=> 'l.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$material_info = $DBLayer->fetch_assoc($result);

?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit unit</h6>
		</div>
		<div class="card-body">
			<div class="mb-3">
				<label class="form-label" for="fld_size_title">Title</label>
				<input type="text" name="size_title" value="<?php echo html_encode($material_info['size_title']) ?>" class="form-control" id="fld_size_title">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_size_description">Description</label>
				<textarea name="size_description" class="form-control" id="fld_size_description"><?php echo html_encode($material_info['size_description']) ?></textarea>
			</div>
			<button type="submit" name="update" class="btn btn-primary">Update</button>
		</div>
	</div>
</form>
<?php
	require SITE_ROOT.'footer.php';
}
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Add a new unit size</h6>
		</div>
		<div class="card-body">
			<div class="mb-3">
				<label class="form-label" for="fld_size_title">Title</label>
				<input type="text" name="size_title" value="" class="form-control" id="fld_size_title">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_size_description">Description</label>
				<textarea name="size_description" class="form-control" id="fld_size_description"></textarea>
			</div>
			<button type="submit" name="add" class="btn btn-primary">Add</button>
		</div>
	</div>
</form>
<?php
if (!empty($main_info))
{
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card-header">
		<h6 class="card-title mb-0">List of unit sizes</h6>
	</div>
	<table class="table table-striped my-0">
		<thead>
			<tr>
				<th>Title</th>
				<th>Description</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
	foreach($main_info as $cur_info)
	{
?>
			<tr>
				<td class="fw-bold"><?php echo html_encode($cur_info['size_title']) ?></td>
				<td><?php echo html_encode($cur_info['size_description']) ?></td>
				<td>

<?php if ($User->checkAccess('swift_property_management', 18)) : ?>
						<a href="<?=$URL->link('sm_property_management_unit_sizes', ['edit', $cur_info['id']])?>" class="btn badge bg-primary text-white">Edit</a>
<?php endif; ?>

<?php if ($User->checkAccess('swift_property_management', 19)) : ?>
						<button type="submit" name="delete_item[<?php echo $cur_info['id'] ?>]" class="badge bg-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
<?php endif; ?>

				</td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</form>
<?php
}
else
{
	echo '<div class="alert alert-warning mt-3" role="alert">You have no items on this page.</div>';
}
require SITE_ROOT.'footer.php';