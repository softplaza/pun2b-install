<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access_admin = $User->is_admin() ? true : false;
$access90 = ($User->checkAccess('hca_wom', 20)) ? true : false;

if (!$access90)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaWOM = new HcaWOM;

if (isset($_POST['add']))
{
	$form_data = [
		'item_type' => isset($_POST['item_type']) ? intval($_POST['item_type']) : '',
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
	];

	$item_actions = [];
	if (isset($_POST['item_actions']) && !empty($_POST['item_actions']))
	{
		foreach($_POST['item_actions'] as $key => $val)
		{
			if ($val == 1)
				$item_actions[] = $key;
		}
	}
	$form_data['item_actions'] = !empty($item_actions) ? implode(',', $item_actions) : '';

	if ($form_data['item_name'] == '')
		$Core->add_error('Iitem name cannot be empty.');

	if ($form_data['item_type'] == 0)
		$Core->add_error('Select type of item.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_wom_items', $form_data);
		
		// Add flash message
		$flash_message = 'Item has been added.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'item_type' => isset($_POST['item_type']) ? intval($_POST['item_type']) : '',
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
	];

	$item_actions = [];
	if (isset($_POST['item_actions']) && !empty($_POST['item_actions']))
	{
		foreach($_POST['item_actions'] as $key => $val)
		{
			if ($val == 1)
				$item_actions[] = $key;
		}
	}
	$form_data['item_actions'] = !empty($item_actions) ? implode(',', $item_actions) : '';

	if ($form_data['item_name'] == '')
		$Core->add_error('Iitem name cannot be empty.');

	if ($form_data['item_type'] == 0)
		$Core->add_error('Select type of item.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_wom_items', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$DBLayer->delete('hca_wom_items', $id);
	
	// Add flash message
	$flash_message = 'Item has been deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_wom_items', 0), $flash_message);
}

if ($id > 0)
{
	$Core->set_page_id('hca_wom_items', 'hca_wom');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'WHERE'		=> 'i.id='.$id,
		'ORDER BY'	=> 'i.item_name'
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

			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_item_type">Type of item</label>
						<select name="item_type" class="form-select form-select-sm" required>

<?php
	foreach($HcaWOM->item_types as $key => $value)
	{
		if ($key == $item_info['item_type'])
			echo '<option value="'.$key.'" selected>'.html_encode($value).'</option>';
		else
			echo '<option value="'.$key.'">'.html_encode($value).'</option>';
	}
?>
						</select>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_item_name">Item name</label>
						<input type="text" name="item_name" value="<?php echo html_encode($item_info['item_name']) ?>" class="form-control" id="fld_item_name" required>
					</div>
				</div>
			</div>

			<h6 class="card-title mb-0">Item actions</h6>
			<hr class="my-2">
			<div class="mb-3">
<?php

$item_actions = explode(',', $item_info['item_actions']);
foreach($HcaWOM->task_actions as $key => $value)
{
	$checked = in_array($key, $item_actions) ? 'checked' : '';
?>
				<div class="form-check form-check-inline">
					<input type="hidden" name="item_actions[<?=$key?>]" value="0">
					<input class="form-check-input" id="fld_item_actions<?=$key?>" type="checkbox" name="item_actions[<?=$key?>]" value="1" <?php echo $checked ?>>
					<label class="form-check-label" for="fld_item_actions<?=$key?>"><?php echo $value ?></label>
				</div>
<?php
}
?>
			</div>

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<a href="<?php echo $URL->link('hca_wom_items', 0) ?>" class="btn btn-secondary text-white">Back</a>
<?php if ($User->is_admin()): ?>
			<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
<?php endif; ?>
		</div>
	</div>
</form>

<?php
	require SITE_ROOT.'footer.php';
}

$Core->set_page_id('hca_wom_items', 'hca_wom');
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

				<div class="col-md-4 mb-3">
					<label class="form-label" for="fld_item_type">Type of item</label>
					<select name="item_type" class="form-select form-select-sm" required>
<?php
foreach($HcaWOM->item_types as $key => $value)
{
	echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
				<div class="mb-3 col-md-4">
					<label class="form-label" for="fld_item_name">Item name</label>
					<input type="text" name="item_name" value="<?php echo isset($_POST['item_name']) ? html_encode($_POST['item_name']) : '' ?>" class="form-control" id="fld_item_name" required>
				</div>

				<h6 class="card-title mb-0">Actions</h6>
				<hr class="my-2">
				<div class="mb-3">
<?php

foreach($HcaWOM->task_actions as $key => $value)
{
?>
					<div class="form-check form-check-inline">
						<input type="hidden" name="item_actions[<?=$key?>]" value="0">
						<input class="form-check-input" id="fld_item_actions<?=$key?>" type="checkbox" name="item_actions[<?=$key?>]" value="1">
						<label class="form-check-label" for="fld_item_actions<?=$key?>"><?php echo $value ?></label>
					</div>
<?php
}
?>
				</div>

				<button type="submit" name="add" class="btn btn-primary">Submit</button>
			</form>
		</div>
	</div>
</div>

<div class="card-header">
	<h6 class="card-title mb-0">List of items</h6>
</div>

<?php
$query = array(
	'SELECT'	=> 'i.*',
	'FROM'		=> 'hca_wom_items AS i',
	//'ORDER BY'	=> 'i.display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_items = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_items[] = $row;
}

if (!empty($hca_wom_items))
{
?>
<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>Type of item</th>
			<th>Item name</th>
			<th>Actions</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
	$i = 1;
	foreach($hca_wom_items as $cur_info)
	{
?>
		<tr>
			<td class=""><?php echo html_encode($HcaWOM->item_types[$cur_info['item_type']]) ?></td>
			<td class="fw-bold"><?php echo html_encode($cur_info['item_name']) ?></td>
			<td class="text-danger"><?php echo $HcaWOM->getActions($cur_info['item_actions']) ?></td>
			<td class="text-danger"><a href="<?php echo $URL->link('hca_wom_items', $cur_info['id']) ?>" class="badge bg-primary text-white">Edit</a></td>
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
	echo '<div class="alert alert-warning" role="alert">You have no items on this page.</div>';


require SITE_ROOT.'footer.php';
