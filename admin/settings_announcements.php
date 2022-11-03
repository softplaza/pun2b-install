<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

$Core->set_page_id('admin_settings_announcements', 'settings');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<input type="hidden" name="form_sent" value="1" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0"><?php echo $lang_admin_settings['Announcements legend'] ?></h6>
		</div>
		<div class="card-body">	
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="form[announcement]" value="1" id="fld_announcement" <?php if ($Config->get('o_announcement') == '1') echo ' checked' ?>>
				<label class="form-check-label" for="fld_announcement"><?php echo $lang_admin_settings['Enable announcement label'] ?></label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_announcement_heading"><?php echo $lang_admin_settings['Announcement heading label'] ?></label>
				<input type="text" name="form[announcement_heading]" value="<?php echo html_encode($Config->get('o_announcement_heading')) ?>" class="form-control" id="fld_announcement_heading">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_announcement_message"><?php echo $lang_admin_settings['Announcement message label'] ?></label>
				<textarea type="text" name="form[announcement_message]" class="form-control" id="fld_announcement_message"><?php echo html_encode($Config->get('o_announcement_message')) ?></textarea>
			</div>
			<hr>
			<button type="submit" name="save" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
