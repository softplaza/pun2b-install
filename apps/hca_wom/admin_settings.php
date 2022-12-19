<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 53))
	message($lang_common['No permission']);


if (isset($_POST['update']))
{
	$form = array_map('trim', $_POST['form']);
	$Config->update($form);

	$flash_message = 'Settings updated.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$Core->set_page_id('hca_wom_admin_settings', 'hca_5840');
require SITE_ROOT.'header.php';
?>	

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Settings</h6>
		</div>
		<div class="card-body">
			<div class="mb-3">
				<div class="form-check">
					<input type="hidden" name="form[hca_wom_notify_technician]" value="0">
					<input class="form-check-input" type="checkbox" name="form[hca_wom_notify_technician]" id="fld_hca_wom_notify_technician" value="1" <?php echo ($Config->get('o_hca_wom_notify_technician') == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_hca_wom_notify_technician">Notify technician when a new task has been assigned.</label>
				</div>
			</div>
			<div class="mb-3">
				<div class="form-check">
					<input type="hidden" name="form[hca_wom_notify_managers]" value="0">
					<input class="form-check-input" type="checkbox" name="form[hca_wom_notify_managers]" id="fld_hca_wom_notify_managers" value="1" <?php echo ($Config->get('o_hca_wom_notify_managers') == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_hca_wom_notify_managers">Notify property managers when a new task has been completed.</label>
				</div>
			</div>
			<button type="submit" name="update" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
