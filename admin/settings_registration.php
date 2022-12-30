<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

$Core->set_page_id('admin_settings_registration', 'settings');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<input type="hidden" name="form_sent" value="1" />
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">New registrations</h6>
		</div>
		<div class="card-body">	
			<div class="alert alert-info" role="alert"><?php echo $lang_admin_settings['New reg info'] ?></div>
			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="form[regs_allow]" value="1" id="fld_regs_allow" <?php if ($Config->get('o_regs_allow') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_regs_allow">Allow new users to register. Disable only under special circumstances</label>
			</div>
			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="form[regs_verify]" value="1" id="fld_regs_verify" <?php if ($Config->get('o_regs_verify') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_regs_verify">Require verification of all new registrations by email</label>
			</div>
			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="form[allow_banned_email]" value="1" id="fld_allow_banned_email" <?php if ($Config->get('p_allow_banned_email') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_allow_banned_email">Allow registration with banned email addresses</label>
			</div>
			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="form[allow_dupe_email]" value="1" id="fld_allow_dupe_email" <?php if ($Config->get('p_allow_dupe_email') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_allow_dupe_email">Allow registration with duplicate email addresses</label>
			</div>
			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="form[regs_report]" value="1" id="fld_regs_report" <?php if ($Config->get('o_regs_report') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_regs_report">Notify users on the mailing list when new users register.</label>
			</div>

            <label class="form-label my-2">Default email setting</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[default_email_setting]" value="0" id="fld_default_email_setting1" <?php if ($Config->get('o_default_email_setting') == 0) echo ' checked' ?>>
                <label class="form-check-label" for="fld_default_email_setting1">Display email address to other users</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[default_email_setting]" value="1" id="fld_default_email_setting2" <?php if ($Config->get('o_default_email_setting') == 1) echo ' checked' ?>>
                <label class="form-check-label" for="fld_default_email_setting2">Hide email address but allow email via the site</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[default_email_setting]" value="2" id="fld_default_email_setting3" <?php if ($Config->get('o_default_email_setting') == 2) echo ' checked' ?>>
                <label class="form-check-label" for="fld_default_email_setting3">Hide email address and disallow email via the site</label>
            </div>

		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Site terms and conditions</h6>
		</div>
		<div class="card-body">	
			<div class="alert alert-info" role="alert"><?php echo $lang_admin_settings['Registration rules info'] ?></div>
			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="form[rules]" value="1" id="fld_rules" <?php if ($Config->get('o_rules') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_rules">Users must agree to site rules before registering</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_rules_message">Rules message</label>
				<textarea type="text" name="form[rules_message]" class="form-control" id="fld_rules_message"><?php echo html_encode($Config->get('o_rules_message')) ?></textarea>
			</div>
			<hr>
			<button type="submit" name="save" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
