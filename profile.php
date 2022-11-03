<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

// Load the profile.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/profile.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : 'about';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id < 2)
	redirect($URL->link('login'));

if ($action != 'change_pass' || !isset($_GET['key']))
{
	if ($User->get('g_read_board') == '0')
		message($lang_common['No view']);
	else if ($User->get('g_view_users') == '0' && ($User->is_guest() || $User->get('id') != $id))
		message($lang_common['No permission']);
}

// Fetch info about the user whose profile we're viewing
$query = array(
	'SELECT'	=> 'u.*, g.g_id, g.g_user_title, g.g_moderator, o.prev_url',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'groups AS g',
			'ON'		=> 'g.g_id=u.group_id'
		),
		array(
			'LEFT JOIN'	=> 'online AS o',
			'ON'		=> 'o.user_id=u.id'
		)
	),
	'WHERE'		=> 'u.id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$user = $DBLayer->fetch_assoc($result);

if (!$user)
	message($lang_common['Bad request']);

if (isset($_POST['cancel']))
{
	// Add flash message
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('profile_about', $id), $flash_message);
}

else if (isset($_POST['remove_access']))
{
	$confirm_action = isset($_POST['confirm_action']) ? intval($_POST['confirm_action']) : 0;

	if ($confirm_action == 0)
		$Core->add_error('Please confirm this action');

	if (!($User->checkAccess('system', 12)))
		message($lang_common['No permission']);

	if ($user['g_id'] == USER_GROUP_ADMIN)
		message($lang_profile['Cannot delete admin']);

	if (empty($Core->errors))
	{
		$form_data = [
			'group_id'		=> 2,
			'email'			=> '',
			'password'		=> '',
			'work_phone'	=> '',
			'cell_phone'	=> '',
			'home_phone'	=> '',
		];
		$DBLayer->update('users', $form_data, 'id='.$id);

		$DBLayer->delete('user_access', 'a_uid='.$id);
		$DBLayer->delete('user_permissions', 'p_uid='.$id);
		$DBLayer->delete('user_notifications', 'n_uid='.$id);

		// Add flash message
		$flash_message = 'Access has been denied';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('profile_about', $id), $flash_message);
	}
}

else if (isset($_POST['delete_user']))
{
	$confirm_action = isset($_POST['confirm_action']) ? intval($_POST['confirm_action']) : 0;

	if ($confirm_action == 0)
		$Core->add_error('Please confirm this action');

	if (!($User->checkAccess('system', 12)))
		message($lang_common['No permission']);

	if ($user['g_id'] == USER_GROUP_ADMIN)
		message($lang_profile['Cannot delete admin']);

	if (empty($Core->errors))
	{
		$DBLayer->delete('user_access', 'a_uid='.$id);
		$DBLayer->delete('user_permissions', 'p_uid='.$id);
		$DBLayer->delete('user_notifications', 'n_uid='.$id);

		$DBLayer->delete('users', 'id='.$id);

		// Add flash message
		$flash_message = 'User has been deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('index'), $flash_message);
	}
}

if ($action == 'change_pass')
{
	// User pressed the cancel button
	if (isset($_POST['cancel']))
		redirect($URL->link('profile_about', $id), $lang_common['Cancel redirect']);

	if (isset($_GET['key']))
	{
		$key = $_GET['key'];

		// If the user is already logged in we shouldn't be here :)
		if (!$User->is_guest())
			message($lang_profile['Pass logout']);

		if ($key == '' || $key != $user['activate_key'])
			message(sprintf($lang_profile['Pass key bad'], '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));
		else
		{
			if (isset($_POST['form_sent']))
			{
				$new_password1 = swift_trim($_POST['req_new_password1']);
				$new_password2 = ($Config->get('o_mask_passwords') == '1') ? swift_trim($_POST['req_new_password2']) : $new_password1;

				if (utf8_strlen($new_password1) < 4)
					$Core->add_error($lang_profile['Pass too short']);
				else if ($new_password1 != $new_password2)
					$Core->add_error($lang_profile['Pass not match']);

				// Did everything go according to plan?
				if (empty($Core->errors))
				{
					$new_password_hash = spm_hash($new_password1, $user['salt']);

					$query = array(
						'UPDATE'	=> 'users',
						'SET'		=> 'password=\''.$new_password_hash.'\', activate_key=NULL',
						'WHERE'		=> 'id='.$id
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);

					// Add flash message
					$FlashMessenger->add_info($lang_profile['Pass updated']);
					redirect($URL->link('index'), $lang_profile['Pass updated']);
				}
			}

			// Is this users own profile
			$page_param['own_profile'] = ($User->get('id') == $id) ? true : false;
			$page_param['form_action'] = $URL->link('change_password_key', array($id, $key));

			$Core->set_page_id('profile_changepass', 'profile');
			require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<input type="hidden" name="form_sent" value="1" />
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0"><?php echo $page_param['own_profile'] ? $lang_profile['Change your password'] : sprintf($lang_profile['Change user password'], html_encode($user['username'])) ?></h6>
		</div>
		<div class="card-body">
			<div class="mb-3">
				<label class="form-label" for="fld_req_new_password1"><?php echo $lang_profile['New password'] ?></label>
				<input type="<?php echo($Config->get('o_mask_passwords') == '1' ? 'password' : 'text') ?>" id="fld_req_new_password1" name="req_new_password1" value="<?php if (isset($_POST['req_new_password1'])) echo html_encode($_POST['req_new_password1']); ?>" required autocomplete="off" class="form-control"/>
				<?php echo $lang_profile['Password help'] ?>
			</div>
<?php if ($Config->get('o_mask_passwords') == '1'): ?>
			<div class="mb-3">
				<label class="form-label" for="fld_req_new_password2"><?php echo $lang_profile['Confirm new password'] ?></label>
				<input type="<?php echo($Config->get('o_mask_passwords') == '1' ? 'password' : 'text') ?>" id="fld_req_new_password2" name="req_new_password2" value="<?php if (isset($_POST['req_new_password2'])) echo html_encode($_POST['req_new_password2']); ?>" required autocomplete="off" class="form-control"/>
				<?php echo $lang_profile['Confirm password help'] ?>
			</div>
<?php endif; ?>
			<div class="mb-3">
				<button type="submit" name="update" class="btn btn-primary">Submit</button>
				<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
			</div>
		</div>
	</div>
</form>

<?php
			require SITE_ROOT.'footer.php';
		}
	}

	// Make sure we are allowed to change this user's password
	if ($User->get('id') != $id &&
		!$User->is_admin() &&
		($User->get('g_moderator') != '1' || $User->get('g_mod_edit_users') == '0' || $User->get('g_mod_change_passwords') == '0' || $user['g_id'] == USER_GROUP_ADMIN || $user['g_moderator'] == '1'))
		message($lang_common['No permission']);

	if (isset($_POST['form_sent']))
	{
		$old_password = isset($_POST['req_old_password']) ? swift_trim($_POST['req_old_password']) : '';
		$new_password1 = swift_trim($_POST['req_new_password1']);
		$new_password2 = ($Config->get('o_mask_passwords') == '1') ? swift_trim($_POST['req_new_password2']) : $new_password1;

		if (utf8_strlen($new_password1) < 4)
			$Core->add_error($lang_profile['Pass too short']);
		else if ($new_password1 != $new_password2)
			$Core->add_error($lang_profile['Pass not match']);

		$authorized = false;
		if (!empty($user['password']))
		{
			$old_password_hash = spm_hash($old_password, $user['salt']);

			if (($user['password'] == $old_password_hash) || $User->is_admmod())
				$authorized = true;
		}

		if (!$authorized)
			$Core->add_error($lang_profile['Wrong old password']);

		// Did everything go according to plan?
		if (empty($Core->errors))
		{
			$new_password_hash = spm_hash($new_password1, $user['salt']);

			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'password=\''.$new_password_hash.'\'',
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			if ($User->get('id') == $id)
			{
				$cookie_data = @explode('|', base64_decode($_COOKIE[$cookie_name]));

				$expire = ($cookie_data[2] > time() + $Config->get('o_timeout_visit')) ? time() + 1209600 : time() + $Config->get('o_timeout_visit');
				$User->set_cookie($cookie_name, base64_encode($User->get('id').'|'.$new_password_hash.'|'.$expire.'|'.sha1($user['salt'].$new_password_hash.spm_hash($expire, $user['salt']))), $expire);
			}

			// Add flash message
			$FlashMessenger->add_info($lang_profile['Pass updated redirect']);
			redirect($URL->link('profile_about', $id), $lang_profile['Pass updated redirect']);
		}
	}

	// Is this users own profile
	$page_param['own_profile'] = ($User->get('id') == $id) ? true : false;
	$page_param['form_action'] = $URL->link('change_password', $id);
	$page_param['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($page_param['form_action']).'" />'
	);

	$Core->set_page_id('profile_changepass', 'profile');
	require SITE_ROOT.'header.php';
?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<input type="hidden" name="form_sent" value="1" />
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $page_param['own_profile'] ? $lang_profile['Change your password'] : sprintf($lang_profile['Change user password'], html_encode($user['username'])) ?></h6>
			</div>
			<div class="card-body">
<?php if (!$User->is_admmod() || $User->get('id') == $id): ?>
				<div class="mb-3">
					<label for="req_old_password"><?php echo $lang_profile['Old password'] ?></label>
					<input type="<?php echo($Config->get('o_mask_passwords') == '1' ? 'password' : 'text') ?>" id="req_old_password" name="req_old_password" value="<?php if (isset($_POST['req_old_password'])) echo html_encode($_POST['req_old_password']); ?>" required class="form-control"/>
					<?php echo $lang_profile['Old password help'] ?>
				</div>
<?php endif; ?>
				<div class="mb-3">
					<label for="req_new_password1"><?php echo $lang_profile['New password'] ?></label>
					<input type="<?php echo($Config->get('o_mask_passwords') == '1' ? 'password' : 'text') ?>" id="req_new_password1" name="req_new_password1" value="<?php if (isset($_POST['req_new_password1'])) echo html_encode($_POST['req_new_password1']); ?>" required autocomplete="off" class="form-control"/>
					<?php echo $lang_profile['Password help'] ?>
				</div>
<?php if ($Config->get('o_mask_passwords') == '1'): ?>
				<div class="mb-3">
					<label for="field_req_new_password2"><?php echo $lang_profile['Confirm new password'] ?></label>
					<input type="text" id="field_req_new_password2" name="req_new_password2" value="<?php if (isset($_POST['req_new_password2'])) echo html_encode($_POST['req_new_password2']); ?>" required autocomplete="off" class="form-control"/>
					<?php echo $lang_profile['Confirm password help'] ?>
				</div>
<?php endif; ?>
				<div class="mb-3">
					<button type="submit" name="update" class="btn btn-sm btn-primary">Submit</button>
					<button type="submit" name="cancel" class="btn btn-sm btn-secondary" formnovalidate>Cancel</button>
				</div>
			</div>
		</div>
	</form>

<?php
	require SITE_ROOT.'footer.php';
}

else if ($action == 'change_email')
{
	// Make sure we are allowed to change this user's e-mail
	if ($User->get('id') != $id &&
		!$User->is_admin() &&
		($User->get('g_moderator') != '1' || $User->get('g_mod_edit_users') == '0' || $user['g_id'] == USER_GROUP_ADMIN || $user['g_moderator'] == '1'))
		message($lang_common['No permission']);

	// User pressed the cancel button
	if (isset($_POST['cancel']))
		redirect($URL->link('profile_about', $id), $lang_common['Cancel redirect']);

	if (isset($_GET['key']))
	{
		$key = $_GET['key'];

		if ($key == '' || $key != $user['activate_key'])
			message(sprintf($lang_profile['E-mail key bad'], '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));
		else
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'email=activate_string, activate_string=NULL, activate_key=NULL',
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			message($lang_profile['E-mail updated']);
		}
	}
	else if (isset($_POST['form_sent']))
	{
		if (spm_hash($_POST['req_password'], $User->get('salt')) !== $User->get('password'))
			$Core->add_error($lang_profile['Wrong password']);

		// Validate the email-address
		$new_email = strtolower(swift_trim($_POST['req_new_email']));
		if (!is_valid_email($new_email))
			$Core->add_error($lang_common['Invalid e-mail']);

		// Check if someone else already has registered with that e-mail address
		$query = array(
			'SELECT'	=> 'u.id, u.username',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.email=\''.$DBLayer->escape($new_email).'\''
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$dupe_list = array();
		while ($cur_dupe = $DBLayer->fetch_assoc($result))
		{
			$dupe_list[] = $cur_dupe['username'];
		}

		if (!empty($dupe_list))
		{
			if ($Config->get('p_allow_dupe_email') == '0')
				$Core->add_error($lang_profile['Dupe e-mail']);
			else if (($Config->get('o_mailing_list') != '') && empty($Core->errors))
			{
				$mail_subject = 'Alert - Duplicate e-mail detected';
				$mail_message = 'User \''.$User->get('username').'\' changed to an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\n\n".'User profile: '.$URL->link('user', $id)."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($Config->get('o_mailing_list'), $mail_subject, $mail_message);
			}
		}

		// Did everything go according to plan?
		if (empty($Core->errors))
		{
			if ($Config->get('o_regs_verify') != '1')
			{
				// We have no confirmed e-mail so we change e-mail right now
				$query = array(
					'UPDATE'	=> 'users',
					'SET'		=> 'email=\''.$DBLayer->escape($new_email).'\'',
					'WHERE'		=> 'id='.$id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);

				redirect($URL->link('profile_about', $id), $lang_profile['E-mail updated redirect']);
			}

			// We have a confirmed e-mail so we going to send an activation link

			$new_email_key = random_key(8, true);

			// Save new e-mail and activation key
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'activate_string=\''.$DBLayer->escape($new_email).'\', activate_key=\''.$new_email_key.'\'',
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			// Load the "activate e-mail" template
			$mail_tpl = swift_trim(file_get_contents(SITE_ROOT.'lang/'.$User->get('language').'/mail_templates/activate_email.tpl'));

			// The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = swift_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = swift_trim(substr($mail_tpl, $first_crlf));

			$mail_message = str_replace('<username>', $User->get('username'), $mail_message);
			$mail_message = str_replace('<base_url>', BASE_URL.'/', $mail_message);
			$mail_message = str_replace('<activation_url>', str_replace('&amp;', '&', $URL->link('change_email_key', array($id, $new_email_key))), $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $Config->get('o_board_title')), $mail_message);

			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($new_email, $mail_subject, $mail_message);

			message(sprintf($lang_profile['Activate e-mail sent'], '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));
		}
	}

	// Is this users own profile
	$page_param['own_profile'] = ($User->get('id') == $id) ? true : false;
	$page_param['form_action'] = $URL->link('change_email', $id);
	// Setup form information
	$page_param['form_info'] = '<p class="important"><span>'.$lang_profile['E-mail info'].'</span></p>';

	$Core->set_page_id('profile_changemail', 'profile');
	require SITE_ROOT.'header.php';
?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<input type="hidden" name="form_sent" value="1" />
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php printf(($User->get('id') == $id) ? $lang_profile['Profile welcome'] : $lang_profile['Profile welcome user'], html_encode($user['username'])) ?></h6>
			</div>
			<div class="card-body">
				<div class="alert alert-info mb-3" role="alert">
					<?php echo $page_param['form_info']."\n" ?>
				</div>
				<div class="mb-3">
					<label for="req_new_email"><?php echo $lang_profile['New e-mail'] ?></label>
					<input type="email" id="req_new_email" name="req_new_email" maxlength="80" value="<?php if (isset($_POST['req_new_email'])) echo html_encode($_POST['req_new_email']); ?>" required class="form-control"/>
				</div>

				<div class="mb-3">
					<label for="req_password"><?php echo $lang_profile['Password'] ?></label>
					<input type="<?php echo($Config->get('o_mask_passwords') == '1' ? 'password' : 'text') ?>" id="req_password" name="req_password" value="<?php if (isset($_POST['req_password'])) echo html_encode($_POST['req_password']); ?>" required autocomplete="off" class="form-control"/>
					<?php echo $lang_profile['Old password help'] ?>
				</div>

				<div class="mb-3">
					<button type="submit" name="update" class="btn btn-sm btn-primary">Submit</button>
					<button type="submit" name="cancel" class="btn btn-sm btn-secondary" formnovalidate>Cancel</button>
				</div>
			</div>
		</div>
	</form>

<?php
	require SITE_ROOT.'footer.php';
}

else if ($action == 'delete_user')
{
	if (!($User->checkAccess('system', 12)))
		message($lang_common['No permission']);

	if ($user['g_id'] == USER_GROUP_ADMIN)
		message($lang_profile['Cannot delete admin']);

	$Core->set_page_id('dialogue', 'profile');
	require SITE_ROOT.'header.php';
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Delete profile of <?php echo html_encode($user['username']) ?></h6>
			</div>
			<div class="card-body">
				<div class="alert alert-warning bm-3" role="alert">
					<p><strong>Deny access</strong> means that the user will become inactive and will no longer be able to log into his account, will no longer be able to participate in projects and receive notifications. All projects created by this user will not be deleted.</p>
<?php if ($User->is_admin()): ?>
					<p><strong>Delete permanently</strong> means that the user will be deleted from the database. His account cannot be recovered. Perhaps this will affect the display of projects created by this user.</p>
<?php endif; ?>
				</div>
				<div class="form-check mb-3">
					<input type="checkbox" id="field_delete_confirm" name="confirm_action" value="1" class="form-check-input" required />
					<label for="field_delete_confirm" class="form-check-label">Mark this checkbox to confirm the action.</label>
				</div>
				<div class="mb-3">
					<button type="submit" name="remove_access" class="btn btn-warning">Deny access</button>
<?php if ($User->is_admin()): ?>
					<button type="submit" name="delete_user" class="btn btn-danger">Delete permanently</button>
<?php endif; ?>
					<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
				</div>
			</div>
		</div>
	</form>
<?php
	require SITE_ROOT.'footer.php';
}

else if (isset($_POST['update_group_membership']))
{
	if (!$User->is_admin())
		message($lang_common['No permission']);

	$new_group_id = intval($_POST['group_id']);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'group_id='.$new_group_id,
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'SELECT'	=> 'g.g_moderator',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id='.$new_group_id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$new_group_mod = $DBLayer->result($result);

	// Add flash message
	$flash_message = $lang_profile['Group membership redirect'];
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('profile_admin', $id), $flash_message);
}

else if (isset($_POST['form_sent']))
{
	// Make sure we are allowed to edit this user's profile
	if ($User->get('id') != $id &&
		!$User->is_admin() &&
		($User->get('g_moderator') != '1' || $User->get('g_mod_edit_users') == '0' || $user['g_id'] == USER_GROUP_ADMIN || $user['g_moderator'] == '1'))
		message($lang_common['No permission']);

	$username_updated = false;

	// Validate input depending on section
	switch ($section)
	{
		case 'identity':
		{
			$form = extract_elements(array('first_name', 'last_name', 'realname', 'url', 'location', 'work_phone', 'cell_phone', 'home_phone'));

			if (isset($form['first_name']) && isset($form['last_name']))
				$form['realname'] = $form['first_name'].' '.$form['last_name'];

			if ($User->is_admin())
			{
				$form['username'] = swift_trim($_POST['req_username']);
				$old_username = swift_trim($_POST['old_username']);

				// Validate the new username
				$Core->add_errors(validate_username($form['username'], $id));

				if ($form['username'] != $old_username)
					$username_updated = true;
			}

			if ($User->is_admmod())
			{
				// Validate the email-address
				$form['email'] = strtolower(swift_trim($_POST['req_email']));
				if (!is_valid_email($form['email']))
					$Core->add_error($lang_common['Invalid e-mail']);
			}

			break;
		}

		case 'settings':
		{
			$form = extract_elements(array('dst', 'timezone', 'language', 'email_setting', 'notify_with_post', 'auto_notify', 'time_format', 'date_format', 'num_items_on_page', 'show_img', 'show_img_sig', 'show_avatars', 'style', 'users_sort_by'));

			Hook::doAction('ProfileChangeDetailsSettingsValidation');

			$form['dst'] = (isset($form['dst'])) ? 1 : 0;
			$form['time_format'] = (isset($form['time_format'])) ? intval($form['time_format']) : 0;
			$form['date_format'] = (isset($form['date_format'])) ? intval($form['date_format']) : 0;
			$form['timezone'] = (isset($form['timezone'])) ? floatval($form['timezone']) : $Config->get('o_default_timezone');

			// Validate timezone
			if (($form['timezone'] > 14.0) || ($form['timezone'] < -12.0)) {
				message($lang_common['Bad request']);
			}

			$form['email_setting'] = intval($form['email_setting']);
			if ($form['email_setting'] < 0 || $form['email_setting'] > 2) $form['email_setting'] = 1;

			if ($Config->get('o_subscriptions') == '1')
			{
				if (!isset($form['notify_with_post']) || $form['notify_with_post'] != '1') $form['notify_with_post'] = '0';
				if (!isset($form['auto_notify']) || $form['auto_notify'] != '1') $form['auto_notify'] = '0';
			}

			// Make sure we got a valid language string
			if (isset($form['language']))
			{
				$form['language'] = preg_replace('#[\.\\\/]#', '', $form['language']);
				if (!file_exists(SITE_ROOT.'lang/'.$form['language'].'/common.php'))
					message($lang_common['Bad request']);
			}

			// Make sure we got a valid style string
			if (isset($form['style']))
			{
				$form['style'] = preg_replace('#[\.\\\/]#', '', $form['style']);
				if (!file_exists(SITE_ROOT.'style/'.$form['style'].'/index.php'))
					message($lang_common['Bad request']);
			}
			break;
		}
		default:
		{
			break;
		}
	}

	$skip_db_update_sections = array('avatar');

	// All sections apart from avatar potentially affect the database
	if (!in_array($section, $skip_db_update_sections) && empty($Core->errors))
	{
		// Singlequotes around non-empty values and NULL for empty values
		$new_values = array();
		foreach ($form as $key => $input)
		{
			$value = ($input !== '') ? '\''.$DBLayer->escape($input).'\'' : 'NULL';

			$new_values[] = $key.'='.$value;
		}

		// Make sure we have something to update
		if (empty($new_values))
			message($lang_common['Bad request']);

		// Run the update
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> implode(',', $new_values),
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = $lang_profile['Profile redirect'];
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('profile_'.$section, $id), $flash_message);
	}
}

else if (isset($_POST['invite']))
{
	
	$password = swift_trim($_POST['password']);
	$mail_message = swift_trim($_POST['message'])."\n";
	$mail_message .= 'Password: '.$password;

	if (utf8_strlen($password) < 4)
		$Core->add_error($lang_profile['Pass too short']);

	// Did everything go according to plan?
	if (empty($Core->errors))
	{
		$new_password_hash = spm_hash($password, $user['salt']);
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'password=\''.$new_password_hash.'\'',
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$SwiftMailer = new SwiftMailer;
		$SwiftMailer->send($user['email'], 'Invitation', $mail_message);

		// Add flash message
		$flash_message = 'User '.$user['username'].' has been invited.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('profile_about', $id), $flash_message);
	}
}

// Is this users own profile
$page_param['own_profile'] = ($User->get('id') == $id) ? true : false;
$page_param['mod_profile'] = ($User->get('id') == $id || $User->is_admin() || $User->get('g_mod_edit_users') == '1' || $User->get('g_moderator') == '1') ? true : false;

$page_param['nav_links'] = $page_param['contacts'] = $page_param['actions'] = [];

// PROFILE NAVIGATION
if ($User->is_admmod() || $User->get('g_view_users') == '1')
	$page_param['nav_links'][] = '<a class="btn btn-outline-primary btn-sm mb-1" href="'.$URL->link('profile_about', $id).'">About</a>';
if ($User->is_admin() || $page_param['own_profile'] || $User->get('g_mod_edit_users') == '1')
	$page_param['nav_links'][] = '<a class="btn btn-outline-primary btn-sm mb-1" href="'.$URL->link('profile_identity', $id).'">Identity</a>';
if ($User->is_admin() || $page_param['mod_profile'])
	$page_param['nav_links'][] = '<a class="btn btn-outline-primary btn-sm mb-1" href="'.$URL->link('profile_settings', $id).'">Settings</a>';
if (!$page_param['own_profile'] && $page_param['mod_profile'])
	$page_param['nav_links'][] = '<a class="btn btn-outline-primary btn-sm mb-1" href="'.$URL->link('profile_invite', $id).'">Invite</a>';

// User's contacts
if ($user['email_setting'] == '0' && $User->get('g_send_email') == '1' && $user['email'] != '')
	$page_param['contacts'][] = '<p>Email: <strong><a href="mailto:'.html_encode($user['email']).'" class="email">'.html_encode($user['email']).'</a></strong></p>';
if ($user['work_phone'] != '')
	$page_param['contacts'][] = '<p>Work Phone: <strong><a href="tel:'.html_encode($user['work_phone']).'" class="email">'.html_encode($user['work_phone']).'</a></strong></p>';
if ($user['cell_phone'] != '')
	$page_param['contacts'][] = '<p>Cell Phone: <strong><a href="tel:'.html_encode($user['cell_phone']).'" class="email">'.html_encode($user['cell_phone']).'</a></strong></p>';
if ($user['home_phone'] != '')
	$page_param['contacts'][] = '<p>Home Phone: <strong><a href="tel:'.html_encode($user['home_phone']).'" class="email">'.html_encode($user['home_phone']).'</a></strong></p>';

// Security actions
if ($page_param['own_profile'])
	$page_param['actions'][] = '<a class="btn btn-warning btn-sm text-white mb-1" href="'.$URL->link('change_email', $id).'">Change Email</a>';

if ($page_param['own_profile'] || $User->is_admin() || ($User->get('g_moderator') == '1' && $User->get('g_mod_change_passwords') == '1'))
	$page_param['actions'][] = '<a class="btn btn-warning btn-sm text-white mb-1" href="'.$URL->link('change_password', $id).'">Change password</a>';

if ($User->is_admin() && !$page_param['own_profile'])
	$page_param['actions'][] = '<a class="btn btn-warning btn-sm text-white mb-1" href="'.$URL->link('profile_admin', $id).'">Privileges</a>';

if (!$page_param['own_profile'] && ($User->checkAccess('system', 12)))
	$page_param['actions'][] = '<a class="btn btn-danger btn-sm text-white mb-1" href="'.$URL->link('delete_user', $id).'">Delete user</a>';

$page_title = ($User->get('id') == $id) ? $lang_profile['Profile welcome'] : sprintf($lang_profile['Profile welcome user'], html_encode($user['username']));

if ($section == 'identity' && $page_param['mod_profile'])
	$Core->set_page_id('profile_identity', 'profile');
else if ($section == 'settings' && $page_param['mod_profile'])
	$Core->set_page_id('profile_settings', 'profile');
else if ($section == 'admin' && $User->is_admin())
	$Core->set_page_id('profile_admin', 'profile');
else if ($section == 'invite' && $User->is_admmod())
	$Core->set_page_id('profile_invite', 'profile');
else
	$Core->set_page_id('profile_about', 'profile');

require SITE_ROOT.'header.php';

?>
<div class="row">
	
	<div class="col-md-4">
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $page_title ?></h6>
			</div>
			<div class="card-body text-center">
				<img src="<?=BASE_URL?>/img/avatars/default.jfif" alt="<?=$user['username']?>" class="img-fluid rounded-circle mb-2" width="128" height="128" />
				<h5 class="card-title mb-0"><?php echo ($user['realname'] != '') ? html_encode($user['realname']) : html_encode($user['username']) ?></h5>
				<div class="text-muted mb-2"><?php echo get_title($user) ?></div>
			</div>

<?php if (!empty($page_param['contacts'])): ?>
			<div class="card-body text-center">
				<h6 class="h6 card-title">Contact information</h6>
					<?php echo implode("\n\t\t\t\t\t\t", $page_param['contacts'])."\n" ?>
			</div>
<?php endif; ?>

<?php if (!empty($page_param['nav_links'])): ?>
			<div class="card-body text-center">
				<h6 class="h6 card-title">Management</h6>
					<?php echo implode("\n\t\t\t\t\t\t", $page_param['nav_links'])."\n" ?>
			</div>
<?php endif; ?>

<?php if (!empty($page_param['actions'])): ?>
			<div class="card-body text-center">
				<h6 class="h6 card-title">Administration</h6>
					<?php echo implode("\n\t\t\t\t\t\t", $page_param['actions'])."\n" ?>
			</div>
<?php endif; ?>

		</div>
	</div>

<?php
if ($section == 'identity' && $page_param['mod_profile'])
	require SITE_ROOT.'profile_identity.php';
else if ($section == 'settings' && $page_param['mod_profile'])
	require SITE_ROOT.'profile_settings.php';
else if ($section == 'admin' && $User->is_admin())
	require SITE_ROOT.'profile_admin.php';
else if ($section == 'invite' && $User->is_admmod())
	require SITE_ROOT.'profile_invite.php';
else
	require SITE_ROOT.'profile_about.php';
?>

</div>

<?php
require SITE_ROOT.'footer.php';
