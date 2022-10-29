<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('punch_list_management', 4)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$check_statuses = [
	1 => 'OK',
	2 => 'YES',
	3 => 'NO',
	4 => 'Repaired',
	5 => 'Replaced'
];

if (isset($_POST['add_moisture']))
{
	$form_data = [
		'moisture_name'	=> isset($_POST['moisture_name']) ? swift_trim($_POST['moisture_name']) : '',
		'location_id'		=> isset($_POST['location_id']) ? swift_trim($_POST['location_id']) : 0,
	];
	
	if ($form_data['moisture_name'] == '')
		$Core->add_error('Moisture name cannot be empty.');
	if ($form_data['location_id'] == 0)
		$Core->add_error('Location not selected.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_management_maint_moisture', $form_data);
		
		// Add flash message
		$flash_message = 'Equipment has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_item']))
{
	$form_data = [
		'location_id'	=> isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
		'moisture_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
	];

	if ($form_data['moisture_name'] == '')
		$Core->add_error('Item name cannot be empty.');
	if ($form_data['location_id'] == 0)
		$Core->add_error('Location not selected.');

	if (empty($Core->errors))
	{
		$DBLayer->update('punch_list_management_maint_moisture', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_item']))
{
	$DBLayer->delete('punch_list_management_maint_moisture', $id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_management_moisture', 0), $flash_message);
}

$locations = $DBLayer->select_all('punch_list_management_maint_locations');

// Get from ajax
$query = array(
	'SELECT'	=> 'm.*, l.location_name',
	'FROM'		=> 'punch_list_management_maint_moisture AS m',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
			'ON'			=> 'l.id=m.location_id'
		),
	),
	'ORDER BY'	=> 'l.location_name, m.moisture_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$moisture_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$moisture_info[] = $row;
}

//print_dump($items_info);
$Core->set_page_title('Moisture items');
$Core->set_page_id('punch_list_management_moisture', 'punch_list_management');
require SITE_ROOT.'header.php';

if ($id > 0)
{
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'punch_list_management_maint_moisture AS i',
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);

	$query = array(
		'SELECT'	=> 'l.*',
		'FROM'		=> 'punch_list_management_maint_locations AS l',
		'ORDER BY'	=> 'l.location_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$locations = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$locations[] = $row;
	}

?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
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
							<label class="form-label" for="input_item_name">Item name</label>
							<input type="text" name="item_name" value="<?php echo html_encode($item_info['moisture_name']) ?>" class="form-control" id="input_item_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="update_item" class="btn btn-primary">Update</button>
				<a href="<?php echo $URL->link('punch_list_management_moisture', 0) ?>" class="btn btn-secondary text-white">Cancel</a>
				<button type="submit" name="delete_item" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
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
							<label class="form-label" for="select_locations">Add to Category</label>
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
							<label class="form-label" for="input_moisture_name">Moisture name</label>
							<input type="text" name="moisture_name" value="" class="form-control" id="input_moisture_name" required>
						</div>
					</div>
				</div>
				<button type="submit" name="add_moisture" class="btn btn-primary">Add item</button>
			</div>
		</div>
	</form>
	
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">List of Check List items</h6>
			</div>
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Location</th>
						<th></th>
<?php
foreach($check_statuses as $key => $value)
{
	echo '<th style="width:70px;max-width:70px;">'.$value.'</th>';
}
?>
						
					</tr>
				</thead>
				<tbody>
<?php
$location_id = 0;
foreach($moisture_info as $item)
{
			if ($location_id != $item['location_id'])
			{
				echo '<tr><td colspan="8" class="col-sm-2"><strong>'.$item['location_name'].'</strong></td></tr>';
				$location_id = $item['location_id'];
			}

			echo '<tr>';
			echo '<td>'.$item['moisture_name'].'</td>';
			echo '<td><a href="'.$URL->link('punch_list_management_moisture', $item['id']).'" class="badge bg-primary text-white">Edit</a></td>';

			$status_exceptions = !empty($item['status_exceptions']) ? explode(',', $item['status_exceptions']) : [];
			foreach($check_statuses as $key => $value)
			{
				$checked = in_array($key, $status_exceptions) ? 'checked' : '';
				$check_box = '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="exceptions'.$item['id'].'_'.$key.'" onchange="updateCheckListItem('.$item['id'].', '.$key.')" '.$checked.'></div>';
				echo '<td>'.$check_box.'</td>';
			}

			echo '</tr>';
} ?>
				</tbody>
			</table>
		</div>
	</form>

<script>
function updateCheckListItem(id, key){
	var val = 0;
	if($('#exceptions'+id+'_'+key).prop("checked") == true){
		val = 1;
	}
	else if($('#exceptions'+id+'_'+key).prop("checked") == false){
		val = 0;
	}

	var csrf_token = "<?php echo generate_form_token($URL->link('punch_list_management_ajax_update_moisture_item')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('punch_list_management_ajax_update_moisture_item') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,key:key,val:val,csrf_token:csrf_token}),
		success: function(re){
			//$(".msg-section").empty().html(re.message);
		},
		error: function(re){
			//$("#edit_property").empty().html('Error: Please refresh this page and try again.');
		}
	});	
}
</script>
<?php
require SITE_ROOT.'footer.php';