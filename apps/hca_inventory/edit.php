<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('hca_inventory', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;

if (isset($_POST['update']))
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
		$DBLayer->update('hca_inventory_equipments', $form_data, $id);
		
		$SwiftUploader->uploadFiles('hca_inventory_equipments', $id);

		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_inventory_warehouse', $id), $flash_message);
	}
}

else if (isset($_POST['delete_image']))
{
	$image_id = intval(key($_POST['delete_image']));

	if ($image_id > 0)
	{
		$query = [
			'DELETE'	=> 'sm_uploader',
			'WHERE'		=> 'id='.$image_id
		];
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$flash_message = 'Image deleted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['cancel']))
{
	$flash_message = 'Action canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_inventory_warehouse', $id), $flash_message);
}

else if (isset($_POST['delete']))
{
	if ($id > 0)
	{
		$DBLayer->delete('hca_inventory_equipments', $id);

		$query = [
			'DELETE'	=> 'sm_uploader',
			'WHERE'		=> 'table_name=\'hca_inventory_equipments\' AND table_id='.$id
		];
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$flash_message = 'Item deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_inventory_warehouse', $id), $flash_message);
	}
}

$edit_info = $DBLayer->select('hca_inventory_equipments', 'id='.$id);
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

$query = array(
	'SELECT'	=> 'id, file_path, file_name',
	'FROM'		=> 'sm_uploader',
	'WHERE'		=> 'table_name=\'hca_inventory_equipments\' AND table_id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$files_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$files_info[] = $row;
}

$Core->set_page_title('Inventory Management');
$Core->set_page_id('hca_inventory_add', 'hca_inventory');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
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
	if ($edit_info['cid'] == $category['id'])
		echo '<option value="'.$category['id'].'" selected>'.html_encode($category['cat_name']).'</option>';
	else
		echo '<option value="'.$category['id'].'">'.html_encode($category['cat_name']).'</option>';
}
?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label" for="input_item_number">Serial number</label>
						<input type="text" name="item_number" value="<?php echo html_encode($edit_info['item_number']) ?>" class="form-control" id="input_item_number">
					</div>
					<div class="mb-3">
						<label for="input_item_name" class="form-label">Equipment name</label>
						<input type="text" name="item_name" value="<?php echo html_encode($edit_info['item_name']) ?>" class="form-control" id="input_item_name" required>
					</div>
					<div class="mb-3">
						<label class="form-label" for="input_item_number">Property</label>
						<select name="pid" class="form-control">
							<option value="0">Select property</option>
<?php
foreach($properties as $property)
{
	if ($edit_info['pid'] == $property['id'])
		echo '<option value="'.$property['id'].'" selected>'.html_encode($property['pro_name']).'</option>';
	else
		echo '<option value="'.$property['id'].'">'.html_encode($property['pro_name']).'</option>';
}
?>
						</select>
					</div>
					<div class="mb-3">
						<label for="input_pick_up_location" class="form-label">Pick Up Location</label>
						<input type="text" name="pick_up_location" value="<?php echo html_encode($edit_info['pick_up_location']) ?>" class="form-control" id="input_pick_up_location">
					</div>
					<div class="mb-3">
						<label class="form-label" for="input_total_quantity">Quantity</label>
						<input type="number" name="total_quantity" value="<?php echo html_encode($edit_info['total_quantity']) ?>" class="form-control" id="input_total_quantity" min="0">
					</div>


<?php
if (!empty($files_info))
{
	echo '<picture>';
	foreach($files_info as $cur_file)
	{
		echo '<img src="'.BASE_URL.'/'.$cur_file['file_path'].$cur_file['file_name'].'" class="img-fluid img-thumbnail" style="height:100px">';
		echo '<p><button type="submit" name="delete_image['.$cur_file['id'].']" class="badge bg-danger text-white">Delete image</button></p>';
	}
	echo '</picture>';
}
?>


					<?php $SwiftUploader->setForm(['input' => '']) ?>

				</div>
			</div>
			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
<?php if ($User->checkAccess('hca_inventory', 14)) : ?>
			<button type="submit" name="delete" class="btn btn-danger" formnovalidate>Delete</button>
<?php endif; ?>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';