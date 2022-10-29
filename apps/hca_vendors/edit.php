<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('hca_vendors', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$access6 = ($User->checkAccess('hca_vendors', 6)) ? true : false;
if (isset($_POST['update']))
{
	$form_data = [
		'payee_id'			=> isset($_POST['payee_id']) ? intval($_POST['payee_id']) : 0,
		'vendor_name'		=> isset($_POST['vendor_name']) ? swift_trim($_POST['vendor_name']) : '',
		'service'			=> isset($_POST['service']) ? swift_trim($_POST['service']) : '',
		'phone_number'		=> isset($_POST['phone_number']) ? swift_trim($_POST['phone_number']) : '',
		'email'				=> isset($_POST['email']) ? swift_trim($_POST['email']) : 1,
		'orders_limit'		=> isset($_POST['orders_limit']) ? intval($_POST['orders_limit']) : 0,
	];
	
	Hook::doAction('HcaVendorsEditUpdateValidation');

	if ($form_data['vendor_name'] == '')
		$Core->add_error('Vendor name cannot be empty.');
	
	if (empty($Core->errors))
	{
		$DBLayer->update('sm_vendors', $form_data, $id);
		
		// Add flash message
		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['cancel']))
{
	$flash_message = 'Action canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_vendors_list'), $flash_message);
}

else if (isset($_POST['delete']))
{
	if ($id > 0)
	{
		$DBLayer->delete('sm_vendors', $id);

		$flash_message = 'Item deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_vendors_list'), $flash_message);
	}
}

$edit_info = $DBLayer->select('sm_vendors', 'id='.$id);

$Core->set_page_id('hca_vendors_edit', 'hca_vendors');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Vendor editor</h6>
		</div>
		<div class="card-body">
			<div class="mb-3">
				<label for="input_vendor_name" class="form-label text-danger">Vendor name</label>
				<input type="text" name="vendor_name" value="<?php echo html_encode($edit_info['vendor_name']) ?>" class="form-control" id="input_vendor_name" required>
			</div>
			<div class="mb-3">
				<label class="form-label" for="input_payee_id">Payee ID</label>
				<input type="text" name="payee_id" value="<?php echo html_encode($edit_info['payee_id']) ?>" class="form-control" id="input_payee_id">
			</div>
			<div class="mb-3">
				<label for="input_service" class="form-label">Service</label>
				<input type="text" name="service" value="<?php echo html_encode($edit_info['service']) ?>" class="form-control" id="input_service">
			</div>
			<div class="mb-3">
				<label class="form-label" for="phone_number">Phone numbers</label>
				<textarea type="text" name="phone_number" class="form-control" id="phone_number"><?php echo (isset($_POST['phone_number']) ? html_encode($_POST['phone_number']) : html_encode($edit_info['phone_number'])) ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label" for="email">Emails</label>
				<textarea type="text" name="email" class="form-control" id="email"><?php echo (isset($_POST['email']) ? html_encode($_POST['email']) : html_encode($edit_info['email'])) ?></textarea>
			</div>
			<div class="mb-3">
				<label for="input_orders_limit" class="form-label">Limit orders per day, 0 - disabled</label>
				<input type="text" name="orders_limit" value="<?php echo html_encode($edit_info['orders_limit']) ?>" class="form-control" id="input_orders_limit">
			</div>

			<hr class="my-4">

			<div class="mb-3" id="section_projects">
				<h6>Displaying this vendor in projects</h6>
			</div>

			<?php Hook::doAction('HcaVendorsEditPreSumbit'); ?>

			<hr class="my-4">

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
<?php if ($access6): ?>
			<button type="submit" name="delete" class="btn btn-danger" formnovalidate>Delete</button>
<?php endif; ?>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';