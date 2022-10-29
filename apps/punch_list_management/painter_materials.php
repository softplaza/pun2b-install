<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('punch_list_management', 7)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_POST['add_part']))
{
	$form_data = [
		'part_number'	=> isset($_POST['part_number']) ? swift_trim($_POST['part_number']) : '',
		'part_name'		=> isset($_POST['part_name']) ? swift_trim($_POST['part_name']) : '',
	];
	
	if ($form_data['part_name'] == '')
		$Core->add_error('Part name cannot be empty.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_painter_parts', $form_data);
		
		// Add flash message
		$flash_message = 'Part has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_item']))
{
	$item_id = intval(key($_POST['delete_item']));
	$DBLayer->delete('punch_list_painter_parts', $item_id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['update_material']))
{
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$form_data = [
		'part_number'		=> isset($_POST['part_number']) ? swift_trim($_POST['part_number']) : '',
		'part_name'			=> isset($_POST['part_name']) ? swift_trim($_POST['part_name']) : '',
		'part_cost'			=> isset($_POST['part_cost']) ? swift_trim($_POST['part_cost']) : '',
	];

	if ($form_data['part_name'] == '')
		$Core->add_error('Name of material cannot be empty.');

	if (empty($Core->errors) && $id > 0)
	{
		$DBLayer->update('punch_list_painter_parts', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punch_list_painter_materials', ['list', $id]), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'punch_list_painter_parts AS p',
	'ORDER BY'	=> 'p.part_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$items_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$items_info[] = $row;
}

$Core->set_page_id('punch_list_painter_materials', 'punch_list_management');
require SITE_ROOT.'header.php';

if ($action == 'edit_material')
{
	$query = array(
		'SELECT'	=> 'p.*',
		'FROM'		=> 'punch_list_painter_parts AS p',
		'WHERE'		=> 'p.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$material_info = $DBLayer->fetch_assoc($result);

?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-body">
				<div class="mb-3">
					<label class="form-label" for="input_part_number">Part number</label>
					<input type="text" name="part_number" value="<?php echo html_encode($material_info['part_number']) ?>" class="form-control" id="input_part_number">
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_part_name">Part name/description</label>
					<input type="text" name="part_name" value="<?php echo html_encode($material_info['part_name']) ?>" class="form-control" id="input_part_name" required>
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_part_cost">Part cost</label>
					<input type="text" name="part_cost" value="<?php echo html_encode($material_info['part_cost']) ?>" class="form-control" id="input_part_cost">
				</div>
				<button type="submit" name="update_material" class="btn btn-primary">Update part info</button>
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
			<div class="card-body">
				<div class="mb-3">
					<label class="form-label" for="input_part_number">Part number</label>
					<input type="text" name="part_number" value="" class="form-control" id="input_part_number">
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_part_name">Part name/description</label>
					<input type="text" name="part_name" value="" class="form-control" id="input_part_name" required>
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_part_cost">Part cost</label>
					<input type="text" name="part_cost" value="" class="form-control" id="input_part_cost">
				</div>
				<button type="submit" name="add_part" class="btn btn-primary">Add part</button>
			</div>
		</div>
	</form>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">List of Materials Used</h6>
			</div>
			<table class="table table-striped my-0">
				<thead>
					<tr>
						<th>Part number</th>
						<th>Part name/description</th>
						<th>Cost</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
<?php
$i = 0;
foreach($items_info as $cur_info)
{
	$active = ($id == $cur_info['id']) ? 'anchor' : '';
?>
				<tr id="row<?=$cur_info['id']?>" class="<?=$active?>">
					<td><?php echo html_encode($cur_info['part_number']) ?></td>
					<td><?php echo html_encode($cur_info['part_name']) ?></td>
					<td style="min-width: 100px;"><?php echo html_encode($cur_info['part_cost']) ?></td>
					<td>
						<a href="<?=$URL->link('punch_list_painter_materials', ['edit_material', $cur_info['id']])?>" class="badge bg-primary text-white">Edit</a>
						<button type="submit" name="delete_item[<?php echo $cur_info['id'] ?>]" class="badge bg-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
					</td>
				</tr>
<?php
	++$i;
}
?>
				</tbody>
			</table>
		</div>
	</form>

<?php
require SITE_ROOT.'footer.php';