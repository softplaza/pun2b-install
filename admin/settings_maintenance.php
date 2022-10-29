<?php
	$Core->set_page_id('admin_settings_maintenance', 'settings');
	require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<input type="hidden" name="form_sent" value="1" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Maintenance mode</h6>
		</div>
		<div class="card-body">	
			<div class="alert alert-info" role="alert">
				<p><?php echo $lang_admin_settings['Maintenance mode info'] ?></p>
				<p><?php echo $lang_admin_settings['Maintenance mode warn'] ?></p>
			</div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="form[maintenance]" value="1" id="fld_maintenance" <?php if ($Config->get('o_maintenance') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_maintenance"><?php echo $lang_admin_settings['Maintenance mode label'] ?></label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_maintenance_message"><?php echo $lang_admin_settings['Maintenance message label'] ?></label>
				<textarea type="text" name="form[maintenance_message]" class="form-control" id="fld_maintenance_message"><?php echo html_encode($Config->get('o_maintenance_message')) ?></textarea>
			</div>
			<hr>
			<button type="submit" name="save" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
