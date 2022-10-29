<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_property_management', 3)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_POST['add']))
{
	$form_data = [
		'gl_code'			=> isset($_POST['gl_code']) ? swift_trim($_POST['gl_code']) : '',
		'dept_name'			=> isset($_POST['dept_name']) ? swift_trim($_POST['dept_name']) : '',
		'dept_decription'	=> isset($_POST['dept_decription']) ? swift_trim($_POST['dept_decription']) : '',
	];
	
	if ($form_data['gl_code'] == '')
		$Core->add_error('GL-code cannot be empty.');
	if ($form_data['dept_name'] == '')
		$Core->add_error('Department name cannot be empty.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('sm_property_departments', $form_data);
		
		// Add flash message
		$flash_message = 'Part has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_item']))
{
	$item_id = intval(key($_POST['delete_item']));
	$DBLayer->delete('sm_property_departments', $item_id);

	// Add flash message
	$flash_message = 'Location has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_property_management_departments', ['', 0]), $flash_message);
}

else if (isset($_POST['update']))
{
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$form_data = [
		'gl_code'			=> isset($_POST['gl_code']) ? swift_trim($_POST['gl_code']) : '',
		'dept_name'			=> isset($_POST['dept_name']) ? swift_trim($_POST['dept_name']) : '',
		'dept_decription'	=> isset($_POST['dept_decription']) ? swift_trim($_POST['dept_decription']) : '',
	];

	if ($form_data['gl_code'] == '')
		$Core->add_error('GL-code cannot be empty.');
	if ($form_data['dept_name'] == '')
		$Core->add_error('Department name cannot be empty.');

	if (empty($Core->errors) && $id > 0)
	{
		$DBLayer->update('sm_property_departments', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_property_management_departments', ['', 0]), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'd.*',
	'FROM'		=> 'sm_property_departments AS d',
	'ORDER BY'	=> 'd.dept_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_id('sm_property_management_departments', 'sm_property_management');
require SITE_ROOT.'header.php';

if ($action == 'edit')
{
	$query = array(
		'SELECT'	=> 'd.*',
		'FROM'		=> 'sm_property_departments AS d',
		'WHERE'		=> 'd.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$material_info = $DBLayer->fetch_assoc($result);

?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Edit department information</h6>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label class="form-label" for="fld_gl_code">GL Code</label>
					<input type="text" name="gl_code" value="<?php echo html_encode($material_info['gl_code']) ?>" class="form-control" id="fld_gl_code">
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_dept_name">Department name</label>
					<input type="text" name="dept_name" value="<?php echo html_encode($material_info['dept_name']) ?>" class="form-control" id="fld_dept_name">
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_dept_decription">Description</label>
					<textarea name="dept_decription" class="form-control" id="fld_dept_decription"><?php echo html_encode($material_info['dept_decription']) ?></textarea>
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
				<h6 class="card-title mb-0">Add a new department</h6>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label class="form-label" for="fld_gl_code">GL Code</label>
					<input type="text" name="gl_code" value="" class="form-control" id="fld_gl_code">
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_dept_name">Department name</label>
					<input type="text" name="dept_name" value="" class="form-control" id="fld_dept_name">
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_dept_decription">Description</label>
					<textarea name="dept_decription" class="form-control" id="fld_dept_decription"></textarea>
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
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">List of departments</h6>
			</div>
			<table class="table table-striped my-0">
				<thead>
					<tr>
						<th>GL Code</th>
						<th>Department Name</th>
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
						<td><?php echo html_encode($cur_info['gl_code']) ?></td>
						<td><?php echo html_encode($cur_info['dept_name']) ?></td>
						<td><?php echo html_encode($cur_info['dept_decription']) ?></td>
						<td>
							<a href="<?=$URL->link('sm_property_management_departments', ['edit', $cur_info['id']])?>" class="badge bg-primary text-white">Edit</a>
<?php if ($User->is_admin()) : ?>
							<button type="submit" name="delete_item[<?php echo $cur_info['id'] ?>]" class="badge bg-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
<?php endif; ?>
						</td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</div>
	</form>
<?php
}
else
{
	echo '<div class="alert alert-warning mt-3" role="alert">You have no items on this page.</div>';
}
require SITE_ROOT.'footer.php';