<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$SwiftUploader = new SwiftUploader;

if (isset($_POST['create']))
{
	$form_data = array(
		'item_number'		=> isset($_POST['item_number']) ? swift_trim($_POST['item_number']) : '',
		'item_name'			=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		'item_description'	=> isset($_POST['item_description']) ? swift_trim($_POST['item_description']) : '',
		'limit_min'			=> isset($_POST['limit_min']) ? intval($_POST['limit_min']) : 0,
		'limit_max'			=> isset($_POST['limit_max']) ? intval($_POST['limit_max']) : 0,
		'quantity_total'	=> isset($_POST['quantity_total']) ? intval($_POST['quantity_total']) : 1,
		'updated_date'		=> date('Y-m-d'),
		'updated_by'		=> $User->get('id')
	);
	
	if ($form_data['item_number'] == '')
		$Core->add_error('Item number cannot be empty.');
	if ($form_data['item_name'] == '')
		$Core->add_error('Item name cannot be empty.');
	
	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('swift_inventory_management_items', $form_data);
		
		if ($new_id)
		{
			$SwiftUploader->uploadFiles('swift_inventory_management', $new_id);

			// Add flash message
			$flash_message = 'Item has been added';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('swift_inventory_management_records', $new_id), $flash_message);
		}
	}
}

$Core->set_page_title('Invertory Management');
$Core->set_page_id('swift_inventory_management_add', 'swift_inventory_management');
require SITE_ROOT.'header.php';
?>
	<div class="container-fluid">

		<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

			<div class="alert alert-warning" role="alert">Add a new item</div>

			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-8">

							<div class="mb-3">
								<label class="form-label" for="input_item_number">Part number</label>
								<input type="text" name="item_number" value="" class="form-control" id="input_item_number" required>
							</div>
							
							<div class="mb-3">
								<label for="input_item_name" class="form-label">Part name</label>
								<input type="text" name="item_name" value="" class="form-control" id="input_item_name" required>
							</div>

							<div class="mb-3">
								<label for="input_item_description" class="form-label">Part description</label>
								<textarea name="item_description" class="form-control" id="input_item_description" rows="3"></textarea>
							</div>
							
							<div class="mb-3">
								<label for="input_quantity_total" class="form-label">Quantity</label>
								<input type="number" name="quantity_total" value="0" class="form-control" id="input_quantity_total" min="0">
							</div>

							<div class="mb-3">
								<label for="input_limit_min" class="form-label">Minimal amount in stock</label>
								<input type="number" name="limit_min" value="1" class="form-control" id="input_limit_min" min="0">
							</div>

							<div class="mb-3">
								<label for="input_limit_max" class="form-label">Maximum amount in stock</label>
								<input type="number" name="limit_max" value="10" class="form-control" id="input_limit_max">
							</div>

							<?php $SwiftUploader->setForm(['input' => '']) ?>

						</div>
					</div>
					<button type="submit" name="create" class="btn btn-primary">Submit</button>
				</div>
			</div>

		</form>
	</div>

<?php
require SITE_ROOT.'footer.php';