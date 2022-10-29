<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('punch_list_management', 3)) ? true : false;
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
		$new_id = $DBLayer->insert_values('punch_list_management_maint_locations', $form_data);
		
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
		'location_id'		=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
	];
	
	if ($form_data['equipment_name'] == '')
		$Core->add_error('Equipment name cannot be empty.');
	if ($form_data['location_id'] == 0)
		$Core->add_error('Location not selected.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_management_maint_equipments', $form_data);
		
		// Add flash message
		$flash_message = 'Equipment has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['add_item']))
{
	$form_data = [
		'location_id'	=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
		'equipment_id'	=> isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0,
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
	];
	
	if ($form_data['item_name'] == '')
		$Core->add_error('Item name cannot be empty.');
	if ($form_data['location_id'] == 0)
		$Core->add_error('Location not selected.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_management_maint_items', $form_data);
		
		// Add flash message
		$flash_message = 'Item has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_item']))
{
	$form_data = [
		'location_id'	=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
		'equipment_id'	=> isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0,
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
	];

	if ($form_data['item_name'] == '')
		$Core->add_error('Item name cannot be empty.');
	if ($form_data['location_id'] == 0)
		$Core->add_error('Location not selected.');

	if (empty($Core->errors))
	{
		$DBLayer->update('punch_list_management_maint_items', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_position']))
{
	if (isset($_POST['location_position']) && isset($_POST['equipment_position']))
	{
		foreach($_POST['location_position'] as $key => $value)
		{
			$form_data = [
				'loc_position'	=> intval($value),
			];
			$DBLayer->update('punch_list_management_maint_locations', $form_data, $key);
		}

		foreach($_POST['equipment_position'] as $key => $value)
		{
			$form_data = [
				'eq_position'	=> intval($value),
			];
			$DBLayer->update('punch_list_management_maint_equipments', $form_data, $key);
		}

		// Add flash message
		$flash_message = 'Positions has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_item']))
{
	$DBLayer->delete('punch_list_management_maint_items', $id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_management_locations', 0), $flash_message);
}

$query = array(
	'SELECT'	=> 'l.*',
	'FROM'		=> 'punch_list_management_maint_locations AS l',
	'ORDER BY'	=> 'l.loc_position',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$locations = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$locations[] = $row;
}

$query = array(
	'SELECT'	=> 'p.id, p.pro_name',
	'FROM'		=> 'sm_property_db AS p',
	'ORDER BY'	=> 'p.pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	if (!in_array($row['id'], [113, 115, 116]))
	$property_info[$row['id']] = $row['pro_name'];
}

// Get from ajax
$query = array(
	'SELECT'	=> 'e.*, l.location_name',
	'FROM'		=> 'punch_list_management_maint_equipments AS e',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'punch_list_management_maint_locations AS l',
			'ON'			=> 'l.id=e.location_id'
		),
	),
	'ORDER BY'	=> 'e.eq_position',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$equipments_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$equipments_info[] = $row;
}

$query = array(
	'SELECT'	=> 'i.*, e.equipment_name, e.eq_position, l.location_name, l.loc_position',
	'FROM'		=> 'punch_list_management_maint_items AS i',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'punch_list_management_maint_equipments AS e',
			'ON'			=> 'e.id=i.equipment_id'
		),
		array(
			'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
			'ON'			=> 'l.id=i.location_id'
		),
	),
	'ORDER BY'	=> 'l.loc_position, e.eq_position, i.item_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$items_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$items_info[] = $row;
}

if ($action == 'positions')
{
	$Core->set_page_title('Location positions');
	$Core->set_page_id('punch_list_management_positions', 'punch_list_management');
	require SITE_ROOT.'header.php';
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Location positions</h6>
			</div>
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Categories</th>
						<th>Position</th>
					</tr>
				</thead>
				<tbody>
<?php
	foreach($locations as $location)
	{
		$location_output = [];
		$location_output[] = '<tr>';
		$location_output[] = '<td><strong class="text-danger">'.$location['location_name'].'</strong></td>';
		$location_output[] = '<td><input type="number" name="location_position['.$location['id'].']" value="'.$location['loc_position'].'"></td>';
		$location_output[] = '</tr>';
		echo implode("\n", $location_output);

		$equipment_output = [];
		foreach($equipments_info as $equipment)
		{
			if ($location['id'] == $equipment['location_id'])
			{
				$equipment_output[] = '<tr>';
				$equipment_output[] = '<td><strong>- '.$equipment['equipment_name'].'</strong></td>';
				$equipment_output[] = '<td><input type="number" name="equipment_position['.$equipment['id'].']" value="'.$equipment['eq_position'].'"></td>';
				$equipment_output[] = '</tr>';
			}
		}
		echo implode("\n", $equipment_output);
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
}

else if ($action == 'edit_item' && $id > 0)
{
	$Core->set_page_title('Location management');
	$Core->set_page_id('punch_list_management_locations', 'punch_list_management');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'punch_list_management_maint_items AS i',
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);

	$query = array(
		'SELECT'	=> 'e.*, l.location_name',
		'FROM'		=> 'punch_list_management_maint_equipments AS e',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
				'ON'			=> 'l.id=e.location_id'
			),
		),
		'ORDER BY'	=> 'e.equipment_name',
		'WHERE'		=> 'e.location_id='.$item_info['location_id'],
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$equipments = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$equipments[] = $row;
	}

?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Edit category</h6>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="select_locations">Categories</label>
							<select name="location_id" class="form-select form-select-sm" required>
<?php 
	foreach($locations as $location)
	{
		if ($location['id'] == $item_info['location_id'])
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
							<label class="form-label" for="select_locations">Sub-categories</label>
							<select name="equipment_id" class="form-select form-select-sm" required>
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="0" selected>Without Sub Category</option>'."\n";
	foreach ($equipments as $equipment_info)
	{
		if ($equipment_info['location_id'] != $optgroup) {
			if ($optgroup) {
				echo '</optgroup>';
			}
			echo '<optgroup label="'.html_encode($equipment_info['location_name']).'">';
			$optgroup = $equipment_info['location_id'];
		}
		if ($item_info['equipment_id'] == $equipment_info['id'])
			echo "\t\t\t\t\t\t".'<option value="'.$equipment_info['id'].'" selected>'.html_encode($equipment_info['equipment_name']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$equipment_info['id'].'">'.html_encode($equipment_info['equipment_name']).'</option>'."\n";
	}
?>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="input_item_name">Item name</label>
							<input type="text" name="item_name" value="<?php echo html_encode($item_info['item_name']) ?>" class="form-control" id="input_item_name" required>
						</div>
					</div>
				</div>

				<button type="submit" name="update_item" class="btn btn-primary">Update</button>
				<a href="<?php echo $URL->link('punch_list_management_locations', 0) ?>" class="btn btn-secondary text-white">Cancel</a>
				<button type="submit" name="delete_item" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Display by Property</h6>
			</div>
			<div class="card-body">
<?php
if (!empty($property_info))
{
	$property_exceptions = !empty($item_info['property_exceptions']) ? explode(',', $item_info['property_exceptions']) : [];
	foreach($property_info as $pro_id => $pro_name)
	{
		$checked = ($item_info['property_exceptions'] == '' || !in_array($pro_id, $property_exceptions)) ? 'checked' : '';

		echo '<div class="form-check form-switch">';
		echo '<input class="form-check-input" type="checkbox" id="exceptions'.$item_info['id'].'_'.$pro_id.'" onchange="updateLocation('.$item_info['id'].', '.$pro_id.')" '.$checked.'>';
		echo '<label class="form-check-label" for="flexCheckDefault">'.$pro_name.'</label>';
		echo '</div>';
	}
}
?>


			</div>
		</div>
	</form>

<script>
function updateLocation(id, key){
	var val = 0;
	if($('#exceptions'+id+'_'+key).prop("checked") == true){
		val = 0;
	}
	else if($('#exceptions'+id+'_'+key).prop("checked") == false){
		val = 1;
	}

	var csrf_token = "<?php echo generate_form_token($URL->link('punch_list_management_ajax_update_maint_location')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('punch_list_management_ajax_update_maint_location') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,key:key,val:val,csrf_token:csrf_token}),
		success: function(re){
			$(".msg-section").empty().html(re.message);
		},
		error: function(re){
			$(".msg-section").empty().html('Error: Please refresh this page and try again.');
		}
	});	
}
</script>

<?php
	require SITE_ROOT.'footer.php';
}

$Core->set_page_title('List of location');
$Core->set_page_id('punch_list_management_locations', 'punch_list_management');
require SITE_ROOT.'header.php';
?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="input_location_name">Location name</label>
							<input type="text" name="location_name" value="" class="form-control" id="input_location_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="add_location" class="btn btn-primary">Add location</button>
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
							<label class="form-label" for="select_locations">Locations</label>
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
							<label class="form-label" for="input_equipment_name">Equipment name</label>
							<input type="text" name="equipment_name" value="" class="form-control" id="input_equipment_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="add_equipment" class="btn btn-primary">Add equipment</button>
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
							<label class="form-label" for="select_locations">Locations</label>
							<select name="location_id" class="form-select form-select-sm" required id="location_id" onchange="getLocationItems()">
<?php foreach($locations as $location) {
	echo '<option value="'.$location['id'].'">'.html_encode($location['location_name']).'</option>';
} ?>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<div class="mb-3" id="form_equipment_id"></div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8">
						<div class="mb-3">
							<label class="form-label" for="input_item_name">Item name</label>
							<input type="text" name="item_name" value="" class="form-control" id="input_item_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="add_item" class="btn btn-primary">Add item</button>
			</div>
		</div>
	</form>

	<div class="card">
		<div class="card-body">
			<div class="alert alert-warning" role="alert">Set up the check list individually for each property. Enable checkboxes where this item is required.</div>
		</div>
	</div>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">List of locations and display them by property.</h6>
			</div>
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Location</th>
						<th>Position</th>
						
<?php
if (!empty($property_info))
{
	foreach($property_info as $key => $pro_name)
	{
		echo '<th style="width:60px;max-width:60px;">'.substr($pro_name, 0, 10).'</th>';
	}
}
?>
					</tr>
				</thead>
				<tbody>
<?php
$location_id = 0;
$equipment_id = 0;
$num_columns = count($property_info);
foreach($locations as $location)
{
	$location_output = [];
	$location_items_output = [];

	$location_output[] = '<tr>';
	$location_output[] = '<td><strong>'.$location['location_name'].'</strong></td>';
	$location_output[] = '<td></td>';
	$location_output[] = '<td colspan="'.$num_columns.'"></td>';
	$location_output[] = '</tr>';

	foreach($items_info as $item)
	{
		if ($location['id'] == $item['location_id'] && $item['equipment_id'] == 0)
		{
			$location_items_output[] = '<tr>';
			$location_items_output[] = '<td>'.$item['item_name'].'</td>';
			$location_items_output[] = '<td><a href="'.$URL->link('punch_list_management_locations', ['edit_item', $item['id']]).'" class="badge bg-primary text-white">Edit</a></td>';
			if (!empty($property_info))
			{
				$property_exceptions = !empty($item['property_exceptions']) ? explode(',', $item['property_exceptions']) : [];
				foreach($property_info as $pro_id => $pro_name)
				{
					$check_box = ($item['property_exceptions'] == '' || !in_array($pro_id, $property_exceptions)) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>';
	
					$location_items_output[] = '<td>'.$check_box.'</td>';
				}
			}
			$location_items_output[] = '</tr>';
		}
	}

	echo implode("\n", $location_output);
	if (!empty($location_items_output))
	{
		echo implode("\n", $location_items_output);
	}

	foreach($equipments_info as $equipment)
	{
		$equipment_output = [];
		$equipment_items_output = [];

		$equipment_output[] = '<tr>';
		$equipment_output[] = '<td><strong>'.$equipment['equipment_name'].'</strong></td>';
		$equipment_output[] = '<td></td>';
		$equipment_output[] = '<td colspan="'.$num_columns.'"></td>';
		$equipment_output[] = '</tr>';

		foreach($items_info as $item)
		{
			if ($item['equipment_id'] == $equipment['id'] && $location['id'] == $item['location_id'])
			{
				$equipment_items_output[] = '<tr>';
				$equipment_items_output[] = '<td>'.$item['item_name'].'</td>';
				$equipment_items_output[] = '<td><a href="'.$URL->link('punch_list_management_locations', ['edit_item', $item['id']]).'" class="badge bg-primary text-white">Edit</a></td>';
				if (!empty($property_info))
				{
					$property_exceptions = !empty($item['property_exceptions']) ? explode(',', $item['property_exceptions']) : [];
					foreach($property_info as $pro_id => $pro_name)
					{
						$check_box = ($item['property_exceptions'] == '' || !in_array($pro_id, $property_exceptions)) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>';
		
						$equipment_items_output[] = '<td>'.$check_box.'</td>';
					}
				}
				$equipment_items_output[] = '</tr>';
			}
		}

		if (!empty($equipment_items_output))
		{
			echo implode("\n", $equipment_output);
			echo implode("\n", $equipment_items_output);
		}
	}
}
?>
				</tbody>
			</table>
		</div>
	</form>

<script>
function getLocationItems(){
	var csrf_token = "<?php echo generate_form_token($URL->link('punch_list_management_ajax_get_equipments')) ?>";
	var id = $("#location_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('punch_list_management_ajax_get_equipments') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#form_equipment_id").empty().html(re.form_equipment_id);
		},
		error: function(re){
			$("#form_equipment_id").empty().html('Error. Update the page and try again.');
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';