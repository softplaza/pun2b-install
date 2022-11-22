<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access9 = ($User->checkAccess('hca_hvac_inspections', 9)) ? true : false;

if (!$access9)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_POST['add']))
{
	$form_data = [
		'filter_size'		=> isset($_POST['filter_size']) ? swift_trim($_POST['filter_size']) : '',
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
	];

	if ($form_data['filter_size'] == '' || $form_data['property_id'] == 0)
		$Core->add_error('Filter size field cannot be empty. Select a property from dropdown.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_hvac_inspections_filters', $form_data);
		
		// Add flash message
		$flash_message = 'Filter size has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'filter_size'		=> isset($_POST['filter_size']) ? swift_trim($_POST['filter_size']) : '',
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
	];

	if ($form_data['filter_size'] == '' || $form_data['property_id'] == 0)
		$Core->add_error('Filter size field cannot be empty. Select a property from dropdown.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_hvac_inspections_filters', $form_data, $id);

		// Add flash message
		$flash_message = 'Filter size has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$DBLayer->delete('hca_hvac_inspections_filters', $id);
	
	// Add flash message
	$flash_message = 'Filter size has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_hvac_inspections_items', 0), $flash_message);
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.enabled=1',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
}

if ($id > 0)
{
	$Core->set_page_title('Filter size management');
	$Core->set_page_id('hca_hvac_inspections_filters', 'hca_hvac_inspections');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'f.*',
		'FROM'		=> 'hca_hvac_inspections_filters AS f',
		'WHERE'		=> 'f.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$filter_info = $DBLayer->fetch_assoc($result);
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit item</h6>
		</div>
		<div class="card-body">

			<div class="col-md-4 mb-3">
				<label class="form-label" for="select_locations">Property</label>
				<select name="property_id" class="form-select form-select-sm">
					<option value="0">Select one</option>
<?php
	foreach($sm_property_db as $property)
	{
		if ($filter_info['property_id'] == $property['id'])
			echo '<option value="'.$property['id'].'" selected>'.html_encode($property['pro_name']).'</option>';
		else
			echo '<option value="'.$property['id'].'">'.html_encode($property['pro_name']).'</option>';
	}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_filter_size">Filter size</label>
				<input type="text" name="filter_size" value="<?php echo html_encode($filter_info['filter_size']) ?>" class="form-control" id="fld_filter_size">
			</div>

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<a href="<?php echo $URL->link('hca_hvac_inspections_filters', 0) ?>" class="btn btn-secondary text-white">Back</a>
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
	'SELECT'	=> 'f.*, p.pro_name',
	'FROM'		=> 'hca_hvac_inspections_filters AS f',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=f.property_id'
		],
	],
	'ORDER BY'	=> 'p.pro_name',
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
		<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse0" aria-expanded="true" aria-controls="collapse0">+ Add a new filter size</button>
	</h2>
	<div id="collapse0" class="accordion-collapse collapse" aria-labelledby="heading0" data-bs-parent="#accordionExample">
		<div class="accordion-body card-body">

			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

				<div class="col-md-4 mb-3">
					<label class="form-label" for="select_locations">Property</label>
					<select name="property_id" class="form-select form-select-sm">
						<option value="0">Select one</option>
<?php
	foreach($sm_property_db as $property)
	{
		if (isset($_POST['property_id']) && $_POST['property_id'] == $property['id'])
			echo '<option value="'.$property['id'].'" selected>'.html_encode($property['pro_name']).'</option>';
		else
			echo '<option value="'.$property['id'].'">'.html_encode($property['pro_name']).'</option>';
	}
?>
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label" for="fld_filter_size">Filter size</label>
					<input type="text" name="filter_size" value="<?php echo isset($_POST['filter_size']) ? html_encode($_POST['filter_size']) : '' ?>" class="form-control" id="fld_filter_size">
				</div>

				<button type="submit" name="add" class="btn btn-primary">Submit</button>

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
			<th>Property name</th>
			<th>Filter size</th>
			<th></th>
		</tr>
	</thead>
	<tbody>

<?php
if (!empty($main_info))
{
	foreach($main_info as $cur_info)
	{
		$actions = ($access9) ? '<a href="'.$URL->link('hca_hvac_inspections_filters', $cur_info['id']).'" class="badge bg-primary text-white">Edit</a>' : '';
?>
		<tr class="">
			<td class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></td>
			<td class="ta-center"><?php echo html_encode($cur_info['filter_size']) ?></td>
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
