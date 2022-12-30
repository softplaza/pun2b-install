<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

$Core->set_page_id('admin_settings_features', 'settings');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<input type="hidden" name="form_sent" value="1" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">General system features which are optional</h6>
		</div>
		<div class="card-body">	

			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="form[users_online]" value="1" id="fld_users_online" <?php if ($Config->get('o_users_online') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_users_online">Display list of guests and registered users online</label>
			</div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="form[avatars]" value="1" id="fld_avatars" <?php if ($Config->get('o_avatars') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_avatars">Allow users to upload avatars</label>
			</div>
			<div class="row mb-3">
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_avatars_width">Avatar max width</label>
					<input type="text" name="form[avatars_width]" value="<?php echo html_encode($Config->get('o_avatars_width')) ?>" class="form-control" id="fld_avatars_width">
					<label>Pixels (60 is recommended)</label>
				</div>
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_avatars_height">Avatar max height</label>
					<input type="text" name="form[avatars_height]" value="<?php echo html_encode($Config->get('o_avatars_height')) ?>" class="form-control" id="fld_avatars_height">
					<label>Pixels (60 is recommended)</label>
				</div>
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_avatars_size">Avatar max size</label>
					<input type="text" name="form[avatars_size]" value="<?php echo html_encode($Config->get('o_avatars_size')) ?>" class="form-control" id="fld_avatars_size">
					<label>Bytes (15,360 is recommended)</label>
				</div>
			</div>

			<h6 class="card-title mb-0">Mask password</h6>
			<hr class="my-1">
			<div class="alert alert-info" role="alert"><?php echo $lang_admin_settings['Features mask passwords info'] ?></div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="form[mask_passwords]" value="1" id="fld_mask_passwords" <?php if ($Config->get('o_mask_passwords') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_mask_passwords"><?php echo $lang_admin_settings['Enable mask passwords label'] ?></label>
			</div>

			<h6 class="card-title mb-0"><?php echo $lang_admin_settings['Features gzip'] ?></h6>
			<hr class="my-1">
			<div class="alert alert-info" role="alert"><?php echo $lang_admin_settings['Features gzip info'] ?></div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="form[gzip]" value="1" id="fld_gzip" <?php if ($Config->get('o_gzip') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_gzip"><?php echo $lang_admin_settings['Enable gzip label'] ?></label>
			</div>

			<?php $Hooks->get_hook('aop_features_gzip_fieldset_end'); ?>

			<hr>
			<button type="submit" name="save" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
