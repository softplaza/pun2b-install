<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_territories', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['create']))
{
	$form_data = array(
		'ter_number'		=> isset($_POST['ter_number']) ? swift_trim($_POST['ter_number']) : '',
		'ter_description'	=> isset($_POST['ter_description']) ? swift_trim($_POST['ter_description']) : '',
	);

	if ($form_data['ter_number'] == '')
		$Core->add_error('Set territory number.');

	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('swift_territories', $form_data);
		
		if ($new_id)
		{
			// Add flash message
			$flash_message = 'Task has been created';
			$FlashMessenger->add_info($flash_message);
			if (empty($Core->errors))
				redirect($URL->link('territory_territories'), $flash_message);
		}
	}
}

$Core->set_page_id('territory_new', 'territory');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Create a new territory</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
			<div class="col-md-6 mb-3">
				<label class="form-label" for="fld_ter_number">Territory number</label>
				<input id="fld_ter_number" class="form-control" type="ter_number" name="ter_number" value="<?php echo isset($_POST['ter_number']) ? html_encode($_POST['ter_number']) : '' ?>">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_ter_description">Territory description</label>
				<textarea type="text" name="ter_description" class="form-control" id="fld_ter_description"><?php echo (isset($_POST['pter_description']) ? html_encode($_POST['ter_description']) : '') ?></textarea>
			</div>
			<div class="mb-3">
				<button type="submit" name="create" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';