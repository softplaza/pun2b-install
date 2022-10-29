<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_inventory', 10)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$SwiftUploader = new SwiftUploader;

if (isset($_POST['create']))
{
	$form_data = array(
		'cid'				=> isset($_POST['cid']) ? intval($_POST['cid']) : 0,
		'item_number'		=> isset($_POST['item_number']) ? swift_trim($_POST['item_number']) : '',
		'item_name'			=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		'pid'				=> isset($_POST['pid']) ? intval($_POST['pid']) : 0,
		'pick_up_location'	=> isset($_POST['pick_up_location']) ? swift_trim($_POST['pick_up_location']) : '',
		'total_quantity'	=> isset($_POST['total_quantity']) ? intval($_POST['total_quantity']) : 1,
	);
	
	if ($form_data['item_name'] == '')
		$Core->add_error('Item name cannot be empty.');
	
	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('hca_inventory_equipments', $form_data);
		
		if ($new_id)
		{
			$SwiftUploader->uploadFiles('hca_inventory_equipments', $new_id);

			// Add flash message
			$flash_message = 'Item has been added';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_inventory_warehouse', $new_id), $flash_message);
		}
	}
}

else if (isset($_POST['add_category']))
{
	$form_data = array(
		'cat_name'		=> isset($_POST['cat_name']) ? swift_trim($_POST['cat_name']) : '',
	);
	
	if ($form_data['cat_name'] == '')
		$Core->add_error('Category name cannot be empty.');
	
	if (empty($Core->errors))
	{
		// Create a New cat
		$new_id = $DBLayer->insert_values('hca_inventory_categories', $form_data);
		
		if ($new_id)
		{
			// Add flash message
			$flash_message = 'Item has been added';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
	}
}

$categories = $DBLayer->select_all('hca_inventory_categories');
$query = [
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'ORDER BY'	=> 'p.pro_name',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$properties = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$properties[] = $row;
}

$Core->set_page_title('Inventory Management');
$Core->set_page_id('hca_inventory_add', 'hca_inventory');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="alert alert-warning" role="alert">Add an equipment</div>
	<div class="card">
		<div class="card-body">
			<div class="row">
				<div class="col-md-8">
					<div class="mb-3 hidden">
						<label class="form-label" for="input_item_number">Category</label>
						<select name="cid" class="form-control">
							<option value="0">Select category</option>
<?php
foreach($categories as $category)
{
	echo '<option value="'.$category['id'].'">'.html_encode($category['cat_name']).'</option>';
}
?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label" for="input_item_number">Serial number</label>
						<input type="text" name="item_number" value="" class="form-control" id="input_item_number">
					</div>
					<div class="mb-3">
						<label for="input_item_name" class="form-label">Equipment name</label>
						<input type="text" name="item_name" value="" class="form-control" id="input_item_name" required>
					</div>
					<div class="mb-3">
						<label class="form-label" for="input_item_number">Properties</label>
						<select name="pid" class="form-control">
							<option value="0">Select property</option>
<?php
foreach($properties as $property)
{
	echo '<option value="'.$property['id'].'">'.html_encode($property['pro_name']).'</option>';
}
?>
						</select>
					</div>
					<div class="mb-3">
						<label for="input_pick_up_location" class="form-label">Pick Up Location</label>
						<input type="text" name="pick_up_location" value="" class="form-control" id="input_pick_up_location" required>
					</div>
					<div class="mb-3">
						<label class="form-label" for="input_total_quantity">Quantity</label>
						<input type="number" name="total_quantity" value="1" class="form-control" id="input_total_quantity" min="1">
					</div>
					
					<?php $SwiftUploader->setForm(['input' => '']) ?>
				</div>
			</div>
			<button type="submit" name="create" class="btn btn-primary">Submit</button>
		</div>
	</div>
</form>

<form method="post" class="frm-form  hidden" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-body">
			<div class="row">
				<div class="col-md-8">
					<div class="mb-3">
						<label class="form-label" for="input_cat_name">Category name</label>
						<input type="text" name="cat_name" value="" class="form-control" id="input_cat_name">
					</div>
				</div>
			</div>
			<button type="submit" name="add_category" class="btn btn-primary">Add category</button>
		</div>
	</div>

	<table class="table table-striped hidden">
		<thead>
			<tr class="table-primary">
				<th>Categories</th>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($categories as $cur_info)
	{
?>
			<tr id="row<?php echo $cur_info['id'] ?>">
				<td class="fw-bold"><?php echo html_encode($cur_info['cat_name']) ?></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</form>

<?php
require SITE_ROOT.'footer.php';