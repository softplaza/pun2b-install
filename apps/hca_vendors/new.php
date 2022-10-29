<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vendors', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['form_sent']))
{
	$form_data = array();
	
	if (isset($_POST['payee_id'])) $form_data['payee_id'] = swift_trim($_POST['payee_id']);
	if (isset($_POST['vendor_name'])) $form_data['vendor_name'] = swift_trim($_POST['vendor_name']);
	if (isset($_POST['phone_number'])) $form_data['phone_number'] = swift_trim($_POST['phone_number']);
	if (isset($_POST['service'])) $form_data['service'] = swift_trim($_POST['service']);
	if (isset($_POST['email'])) $form_data['email'] = swift_trim($_POST['email']);
	if (isset($_POST['orders_limit'])) $form_data['orders_limit'] = swift_trim($_POST['orders_limit']);

	if (!isset($form_data['vendor_name']) || $form_data['vendor_name'] == '')
		$Core->add_error('Vendor Name can not be empty. Please fiil out Vendor Name and send form again.');

	if (empty($Core->errors))
	{
		$new_pid = $DBLayer->insert_values('sm_vendors', $form_data);
		
		$flash_message = 'Vendor '.$form_data['vendor_name'].' has been added.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_vendors_list'), $flash_message);
	}
}

$Core->set_page_id('sm_vendors_new', 'sm_vendors');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-body">
			<div class="mb-3">
				<label for="input_vendor_name" class="form-label text-danger">Vendor name</label>
				<input type="text" name="vendor_name" value="<?php echo isset($_POST['vendor_name']) ? html_encode($_POST['vendor_name']) : '' ?>" class="form-control" id="input_vendor_name" required>
			</div>
			<div class="mb-3">
				<label class="form-label" for="input_payee_id">Payee ID</label>
				<input type="text" name="payee_id" value="<?php echo isset($_POST['payee_id']) ? html_encode($_POST['payee_id']) : '' ?>" class="form-control" id="input_payee_id">
			</div>
			<div class="mb-3">
				<label for="input_service" class="form-label">Service provided</label>
				<input type="text" name="service" value="<?php echo isset($_POST['service']) ? html_encode($_POST['service']) : '' ?>" class="form-control" id="input_service">
			</div>
			<div class="mb-3">
				<label class="form-label" for="phone_number">Phone numbers</label>
				<textarea type="text" name="phone_number" class="form-control" id="phone_number"><?php echo isset($_POST['phone_number']) ? html_encode($_POST['phone_number']) : '' ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label" for="email">Emails</label>
				<textarea type="text" name="email" class="form-control" id="email"><?php echo isset($_POST['email']) ? html_encode($_POST['email']) : '' ?></textarea>
			</div>
			<div class="mb-3">
				<label for="input_orders_limit" class="form-label">Limit orders per day, 0 - disabled</label>
				<input type="text" name="orders_limit" value="<?php echo isset($_POST['orders_limit']) ? html_encode($_POST['orders_limit']) : '0' ?>" class="form-control" id="input_orders_limit">
			</div>
			<button type="submit" name="form_sent" class="btn btn-primary">Create</button>
		</div>
	</div>
</form>
<?php
require SITE_ROOT.'footer.php';