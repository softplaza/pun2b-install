<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('punch_list_management', 6)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? swift_trim($_GET['action']) : null;	
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_POST['add_location']))
{
	$form_data = [
		'location_name'		=> isset($_POST['location_name']) ? swift_trim($_POST['location_name']) : ''
	];
	
	if ($form_data['location_name'] == '')
		$Core->add_error('Location name cannot be empty.');
	
	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_painter_locations', $form_data);
		
		// Add flash message
		$flash_message = 'Location has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['add_equipment']))
{
	$form_data = [
		'equipment_name'	=> isset($_POST['equipment_name']) ? swift_trim($_POST['equipment_name']) : '',
		'location_id'		=> isset($_POST['location_id']) ? swift_trim($_POST['location_id']) : 0,
	];
	
	if ($form_data['equipment_name'] == '')
		$Core->add_error('Equipment name cannot be empty.');
	if ($form_data['location_id'] == 0)
		$Core->add_error('Location not selected.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_painter_equipments', $form_data);
		
		// Add flash message
		$flash_message = 'Equipment has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_location']))
{
	$form_data = [
		'location_name'	=> isset($_POST['location_name']) ? swift_trim($_POST['location_name']) : '',
	];

	if ($form_data['location_name'] == '')
		$Core->add_error('Item name cannot be empty.');

	if (empty($Core->errors))
	{
		$DBLayer->update('punch_list_painter_locations', $form_data, $id);

		// Add flash message
		$flash_message = 'Location has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_item']))
{
	$job_actions = [];
	if (isset($_POST['job_actions']) && !empty($_POST['job_actions']))
	{
		foreach($_POST['job_actions'] as $key => $value)
			$job_actions[] = $key;
	}

	$form_data = [
		'location_id'		=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
		'equipment_name'	=> isset($_POST['equipment_name']) ? swift_trim($_POST['equipment_name']) : '',
		'job_actions'		=> implode(',', $job_actions),
		'replaced_action'	=> isset($_POST['replaced_action']) ? intval($_POST['replaced_action']) : 0,
	];

	if ($form_data['equipment_name'] == '')
		$Core->add_error('Item name cannot be empty.');
	if ($form_data['location_id'] == 0)
		$Core->add_error('Location not selected.');

	if (empty($Core->errors))
	{
		$DBLayer->update('punch_list_painter_equipments', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_position']))
{
	if (isset($_POST['position']))
	{
		foreach($_POST['position'] as $key => $value)
		{
			$form_data = [
				'position'	=> intval($value),
			];
			$DBLayer->update('punch_list_painter_locations', $form_data, $key);
		}

		// Add flash message
		$flash_message = 'Positions has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_location']))
{
	$DBLayer->delete('punch_list_painter_locations', $id);

	// Add flash message
	$flash_message = 'Location has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_painter_locations', ['view', 0]), $flash_message);
}

else if (isset($_POST['delete_item']))
{
	$DBLayer->delete('punch_list_painter_equipments', $id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_painter_locations', ['view', 0]), $flash_message);
}

else if (isset($_POST['cancel']))
{
	// Add flash message
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_painter_locations', ['view', 0]), $flash_message);
}

$query = array(
	'SELECT'	=> 'l.*',
	'FROM'		=> 'punch_list_painter_locations AS l',
	'ORDER BY'	=> 'l.position',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$locations = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$locations[] = $row;
}

$query = array(
	'SELECT'	=> 'e.*, l.location_name',
	'FROM'		=> 'punch_list_painter_equipments AS e',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'punch_list_painter_locations AS l',
			'ON'			=> 'l.id=e.location_id'
		),
	),
	'ORDER BY'	=> 'l.location_name, e.equipment_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$equipments_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$equipments_info[] = $row;
}

//print_dump($items_info);
$Core->set_page_title('Locations');
$Core->set_page_id('punch_list_painter_locations', 'punch_list_management');
require SITE_ROOT.'header.php';

if ($action == 'edit_location' && $id > 0)
{
	$query = array(
		'SELECT'	=> 'l.*',
		'FROM'		=> 'punch_list_painter_locations AS l',
		'WHERE'		=> 'l.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$location = $DBLayer->fetch_assoc($result);

?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="input_location_name">Location name</label>
							<input type="text" name="location_name" value="<?php echo html_encode($location['location_name']) ?>" class="form-control" id="input_location_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="update_location" class="btn btn-primary">Update</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
				<button type="submit" name="delete_location" class="btn btn-danger">Delete</button>
			</div>
		</div>
	</form>
<?php
	require SITE_ROOT.'footer.php';
}
else if ($action == 'edit_item' && $id > 0)
{
	$job_actions = [
		1 => 'Partial',
		2 => 'Completed',
		3 => 'Not Painted',
		4 => 'YES',
		5 => 'NO'
	];

	$query = array(
		'SELECT'	=> 'e.*',
		'FROM'		=> 'punch_list_painter_equipments AS e',
		'WHERE'		=> 'e.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$equipment = $DBLayer->fetch_assoc($result);
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Edit location</h6>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="select_locations">Add to Location</label>
							<select name="location_id" class="form-select form-select-sm" required>
<?php
	foreach($locations as $location)
	{
		if ($equipment['location_id'] == $location['id'])
			echo '<option value="'.$location['id'].'" selected>'.html_encode($location['location_name']).'</option>';
		else
			echo '<option value="'.$location['id'].'">'.html_encode($location['location_name']).'</option>';
	}
?>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="input_equipment_name">Equipment name</label>
							<input type="text" name="equipment_name" value="<?php echo html_encode($equipment['equipment_name']) ?>" class="form-control" id="input_equipment_name" required>
						</div>
					</div>
				</div>

				<label class="form-label" for="select_locations">Display Job Actions</label>
<?php
	$job_actions2 = !empty($equipment['job_actions']) ? explode(',', $equipment['job_actions']) : [];
	foreach($job_actions as $key => $title)
	{
		$checked = (in_array($key, $job_actions2)) ? 'checked' : '';

		echo '<div class="form-check form-switch">';
		echo '<input class="form-check-input" type="checkbox" name="job_actions['.$key.']" value="'.$key.'" id="exceptions'.$key.'" '.$checked.'>';
		echo '<label class="form-check-label" for="exceptions'.$key.'">'.$title.'</label>';
		echo '</div>';
	}
?>

				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" name="replaced_action" value="1" id="replaced_action" <?php echo ($equipment['replaced_action'] == 1 ? 'checked' : '') ?>>
					<label class="form-check-label" for="replaced_action">Replaced</label>
				</div>

				<button type="submit" name="update_item" class="btn btn-primary">Update</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Back</button>
				<button type="submit" name="delete_item" class="btn btn-danger">Delete</button>
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
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="input_location_name">Category</label>
							<input type="text" name="location_name" value="" class="form-control" id="input_location_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="add_location" class="btn btn-primary">Add category</button>
			</div>
		</div>
	</form>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="select_locations">Add to category</label>
							<select name="location_id" class="form-select form-select-sm" required>
<?php foreach($locations as $location) {
	echo '<option value="'.$location['id'].'">'.html_encode($location['location_name']).'</option>';
} ?>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="input_equipment_name">Name of Sub-category</label>
							<input type="text" name="equipment_name" value="" class="form-control" id="input_equipment_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="add_equipment" class="btn btn-primary">Add Sub-category</button>
			</div>
		</div>
	</form>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">List of Locations</h6>
			</div>
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Location</th>
						<th></th>
						<th>Position</th>
						<th>Partial</th>
						<th>Completed</th>
						<th>Not Painted</th>
						<th>YES</th>
						<th>NO</th>
						<th>Replaced</th>
					</tr>
				</thead>
				<tbody>
<?php
foreach($locations as $location)
{
	echo '<tr>';
	echo '<td class="col-sm-2"><strong>'.$location['location_name'].'</strong></td>';
	echo '<td><a href="'.$URL->link('punch_list_painter_locations', ['edit_location', $location['id']]).'" class="badge bg-primary text-white">Edit</a></td>';
	echo '<td class="ta-center"><input type="number" name="position['.$location['id'].']" value="'.$location['position'].'"></td>';
	echo '<td colspan="6"></td>';
	echo '</tr>';

	foreach($equipments_info as $item)
	{		
		if ($location['id'] == $item['location_id'])
		{
			$job_actions = ($item['job_actions'] != '' ? explode(',', $item['job_actions']) : []);
			
			echo '<tr>';
			echo '<td>'.$item['equipment_name'].'</td>';
			echo '<td><a href="'.$URL->link('punch_list_painter_locations', ['edit_item', $item['id']]).'" class="badge bg-primary text-white">Edit</a></td>';
			echo '<td></td>';
			echo '<td class="ta-center">'.(in_array(1, $job_actions) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>').'</td>';
			echo '<td class="ta-center">'.(in_array(2, $job_actions) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>').'</td>';
			echo '<td class="ta-center">'.(in_array(3, $job_actions) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>').'</td>';
			echo '<td class="ta-center">'.(in_array(4, $job_actions) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>').'</td>';
			echo '<td class="ta-center">'.(in_array(5, $job_actions) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>').'</td>';
			echo '<td class="ta-center">'.($item['replaced_action'] == 1 ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>').'</td>';
			echo '</tr>';
		}
	}
}
?>
				</tbody>
			</table>
			<div class="mb-3">
				<button type="submit" name="update_position" class="btn btn-primary">Update positions</button>
			</div>
		</div>
	</form>

<?php
require SITE_ROOT.'footer.php';