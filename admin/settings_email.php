<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

$Core->set_page_id('admin_settings_email', 'settings');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<input type="hidden" name="form_sent" value="1" />
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Site email addresses and mailing list</h6>
		</div>
		<div class="card-body">	
			<div class="mb-3">
				<label class="form-label" for="fld_admin_email">Administrator's email</label>
				<input type="text" name="form[admin_email]" value="<?php echo html_encode($Config->get('o_admin_email')) ?>" class="form-control" id="fld_admin_email">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_webmaster_email">Webmaster email</label>
				<input type="text" name="form[webmaster_email]" value="<?php echo html_encode($Config->get('o_webmaster_email')) ?>" class="form-control" id="fld_webmaster_email">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_mailing_list">Create mailing list</label>
				<textarea type="text" name="form[mailing_list]" class="form-control" id="fld_mailing_list"><?php echo html_encode($Config->get('o_mailing_list')) ?></textarea>
				<label class="text-muted">A comma separated list of recipients of reports and/or new registration notifications</label>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Mail server configuration for sending emails from the site</h6>
		</div>
		<div class="card-body">	
			<div class="alert alert-info" role="alert"><?php echo $lang_admin_settings['E-mail server info'] ?></div>

			<h6 class="card-title mb-0">Email mode</h6>
			<hr class="my-1">
			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="form[email_mode]" id="fld_email_mode_0" value="0" <?php if ($Config->get('o_email_mode') == '0') echo ' checked' ?>>
					<label class="form-check-label" for="fld_email_mode_0">Disabled</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="form[email_mode]" id="fld_email_mode_1" value="1" <?php if ($Config->get('o_email_mode') == '1') echo ' checked' ?>>
					<label class="form-check-label" for="fld_email_mode_1">Simple mode</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="form[email_mode]" id="fld_email_mode_2" value="2" <?php if ($Config->get('o_email_mode') == '2') echo ' checked' ?>>
					<label class="form-check-label" for="fld_email_mode_2">SMTP mode</label>
				</div>
				<p class="text-muted py-0">Set the email sending mode using the server.</p>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_smtp_host">SMTP server address</label>
				<input type="text" name="form[smtp_host]" value="<?php echo html_encode($Config->get('o_smtp_host')) ?>" class="form-control" id="fld_smtp_host">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_smtp_port">SMTP port</label>
				<input type="text" name="form[smtp_port]" value="<?php echo html_encode($Config->get('o_smtp_port')) ?>" class="form-control" id="fld_smtp_port">
				<label class="text-muted">Enter the address of the external server and, if required, specify a custom port number if the SMTP server doesn't run on the default port 25</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_smtp_user">SMTP server username</label>
				<input type="text" name="form[smtp_user]" value="<?php echo html_encode($Config->get('o_smtp_user')) ?>" class="form-control" id="fld_smtp_user">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_smtp_pass">SMTP server password</label>
				<input type="text" name="form[smtp_pass]" value="<?php echo html_encode($Config->get('o_smtp_pass')) ?>" class="form-control" id="fld_smtp_pass">
			</div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="form[smtp_ssl]" value="1" id="fld_smtp_ssl" <?php if ($Config->get('o_smtp_ssl') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_smtp_ssl">Encrypt SMTP using SSL, if your version of PHP supports SSL and your SMTP server requires it</label>
			</div>
			<hr>
			<button type="submit" name="save" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
