<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi', 20))
	message($lang_common['No permission']);

$Moisture = new Moisture;
$HcaMi = new HcaMi;

if (isset($_POST['update']))
{
	$form = array_map('trim', $_POST['form']);
	$Config->update($form);

	$flash_message = 'Settings have been updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$Core->set_page_id('hca_5840_settings', 'hca_5840');
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
				<label class="form-label" for="fld_hca_5840_mailing_list">Mailing List</label>
				<textarea id="fld_hca_5840_mailing_list" name="form[hca_5840_mailing_list]" class="form-control" id="maintenance_comment" placeholder="Insert emails separated by commas"><?php echo html_encode($Config->get('o_hca_5840_mailing_list')) ?></textarea>
				<label class="text-muted">Insert emails separated by commas</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_hca_5840_locations">Locations</label>
				<textarea id="fld_hca_5840_locations" name="form[hca_5840_locations]" class="form-control" id="maintenance_comment" placeholder="Insert emails separated by commas"><?php echo html_encode($Config->get('o_hca_5840_locations')) ?></textarea>
				<label class="text-muted">Insert locations separated by commas</label>
			</div>
			<button type="submit" name="update" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
