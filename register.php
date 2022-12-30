<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

// If we are logged in, we shouldn't be here
if (!$User->is_guest())
{
	header('Location: '.$URL->link('index'));
	exit;
}

// Load the profile.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/profile.php';

if ($Config->get('o_regs_allow') == '0')
	message($lang_profile['No new regs']);

$errors = array();


// User pressed the cancel button
if (isset($_GET['cancel']))
	redirect($URL->link('index'), $lang_profile['Reg cancel redirect']);

// User pressed agree but failed to tick checkbox
else if (isset($_GET['agree']) && !isset($_GET['req_agreement']))
	redirect($URL->link('index'), $lang_profile['Reg cancel redirect']);

// Show the rules
else if ($Config->get('o_rules') == '1' && !isset($_GET['agree']) && !isset($_POST['form_sent']))
{
	// Setup form
	$page_param['group_count'] = $page_param['item_count'] = $page_param['fld_count'] = 0;

	// Setup breadcrumbs
	$page_param['crumbs'] = array(
		array($Config->get('o_board_title'), $URL->link('index')),
		array($lang_common['Register'], $URL->link('register')),
		$lang_common['Rules']
	);

	$Core->set_page_id('rules-register');
	require SITE_ROOT.'header.php';

	$page_param['set_count'] = $page_param['fld_count'] = 0;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo sprintf($lang_profile['Register at'], $Config->get('o_board_title')) ?></span></h2>
	</div>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_profile['Reg rules head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div id="rules-content" class="ct-box user-box">
			<?php echo $Config->get('o_rules_message') ?>
		</div>
		<form method="get" accept-charset="utf-8" action="<?php echo $URL->link('register') ?>">
			<div class="frm-group group<?php echo ++$page_param['group_count'] ?>">
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$page_param['fld_count'] ?>" name="req_agreement" value="1" required /></span>
						<label for="fld<?php echo $page_param['fld_count'] ?>"><span><?php echo $lang_profile['Agreement'] ?></span> <?php echo $lang_profile['Agreement label'] ?></label>
					</div>
				</div>
			</div>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="agree" value="<?php echo $lang_profile['Agree'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php

	require SITE_ROOT.'footer.php';
}

else if (isset($_POST['form_sent']))
{
	// Check that someone from this IP didn't register a user within the last hour (DoS prevention)
	$query = array(
		'SELECT'	=> 'COUNT(u.id)',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.registration_ip=\''.$DBLayer->escape(get_remote_address()).'\' AND u.registered>'.(time() - 3600)
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	if ($DBLayer->result($result) > 0)
	{
		$errors[] = $lang_profile['Registration flood'];
	}

	// Did everything go according to plan so far?
	if (empty($errors))
	{
		$username = swift_trim($_POST['req_username']);
		$email1 = strtolower(swift_trim($_POST['req_email1']));

		if ($Config->get('o_regs_verify') == '1')
		{
			$password1 = random_key(8, true);
			$password2 = $password1;
		}
		else
		{
			$password1 = swift_trim($_POST['req_password1']);
			$password2 = ($Config->get('o_mask_passwords') == '1') ? swift_trim($_POST['req_password2']) : $password1;
		}

		// Validate the username
		$errors = array_merge($errors, validate_username($username));

		// ... and the password
		if (utf8_strlen($password1) < 4)
			$errors[] = $lang_profile['Pass too short'];
		else if ($password1 != $password2)
			$errors[] = $lang_profile['Pass not match'];

		if (!is_valid_email($email1))
			$errors[] = $lang_profile['Invalid e-mail'];

		// Check if it's a banned e-mail address
		$banned_email = is_banned_email($email1);
		if ($banned_email && $Config->get('p_allow_banned_email') == '0')
			$errors[] = $lang_profile['Banned e-mail'];

		// Clean old unverified registrators - delete older than 72 hours
		$query = array(
			'DELETE'	=> 'users',
			'WHERE'		=> 'group_id='.USER_GROUP_UNVERIFIED.' AND activate_key IS NOT NULL AND registered < '.(time() - 259200)
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Check if someone else already has registered with that e-mail address
		$dupe_list = array();

		$query = array(
			'SELECT'	=> 'u.username',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.email=\''.$DBLayer->escape($email1).'\''
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		while ($cur_dupe = $DBLayer->fetch_assoc($result))
		{
			$dupe_list[] = $cur_dupe['username'];
		}

		if (!empty($dupe_list) && empty($errors))
		{
			if ($Config->get('p_allow_dupe_email') == '0')
				$errors[] = $lang_profile['Dupe e-mail'];
		}

		// Did everything go according to plan so far?
		if (empty($errors))
		{
			// Make sure we got a valid language string
			if (isset($_POST['language']))
			{
				$language = preg_replace('#[\.\\\/]#', '', $_POST['language']);
				if (!file_exists(SITE_ROOT.'lang/'.$language.'/common.php'))
					message($lang_common['Bad request']);
			}
			else
				$language = $Config->get('o_default_lang');

			$initial_group_id = ($Config->get('o_regs_verify') == '0') ? $Config->get('o_default_user_group') : USER_GROUP_UNVERIFIED;
			$salt = random_key(12);
			$password_hash = spm_hash($password1, $salt);

			// Validate timezone and DST
			$timezone = (isset($_POST['timezone'])) ? floatval($_POST['timezone']) : $Config->get('o_default_timezone');

			// Validate timezone — on error use default value
			if (($timezone > 14.0) || ($timezone < -12.0)) {
				$timezone = $Config->get('o_default_timezone');
			}

			// DST
			$dst = (isset($_POST['dst']) && intval($_POST['dst']) === 1) ? 1 : $Config->get('o_default_dst');


			// Insert the new user into the database. We do this now to get the last inserted id for later use.
			$user_info = array(
				'username'				=>	$username,
				'group_id'				=>	$initial_group_id,
				'salt'					=>	$salt,
				'password'				=>	$password1,
				'password_hash'			=>	$password_hash,
				'email'					=>	$email1,
				'email_setting'			=>	$Config->get('o_default_email_setting'),
				'timezone'				=>	$timezone,
				'dst'					=>	$dst,
				'language'				=>	$language,
				'style'					=>	$Config->get('o_default_style'),
				'registered'			=>	time(),
				'registration_ip'		=>	get_remote_address(),
				'activate_key'			=>	($Config->get('o_regs_verify') == '1') ? '\''.random_key(8, true).'\'' : 'NULL',
				'require_verification'	=>	($Config->get('o_regs_verify') == '1'),
				'notify_admins'			=>	($Config->get('o_regs_report') == '1')
			);
			add_user($user_info, $new_uid);

			// If we previously found out that the e-mail was banned
			if ($banned_email && $Config->get('o_mailing_list') != '')
			{
				$mail_subject = 'Alert - Banned e-mail detected';
				$mail_message = 'User \''.$username.'\' registered with banned e-mail address: '.$email1."\n\n".'User profile: '.$URL->link('user', $new_uid)."\n\n".'-- '."\n".'Site Mailer'."\n".'(Do not reply to this message)';

				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($Config->get('o_mailing_list'), $mail_subject, $mail_message);
			}

			// If we previously found out that the e-mail was a dupe
			if (!empty($dupe_list) && $Config->get('o_mailing_list') != '')
			{
				$mail_subject = 'Alert - Duplicate e-mail detected';
				$mail_message = 'User \''.$username.'\' registered with an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\n\n".'User profile: '.$URL->link('user', $new_uid)."\n\n".'-- '."\n".'Site Mailer'."\n".'(Do not reply to this message)';

				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($Config->get('o_mailing_list'), $mail_subject, $mail_message);
			}

			// Must the user verify the registration or do we log him/her in right now?
			if ($Config->get('o_regs_verify') == '1')
			{
				message(sprintf($lang_profile['Reg e-mail'], '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));
			}
			else
			{

			}

			$expire = time() + $Config->get('o_timeout_visit');

			$User->set_cookie($cookie_name, base64_encode($new_uid.'|'.$password_hash.'|'.$expire.'|'.sha1($salt.$password_hash.spm_hash($expire, $salt))), $expire);

			redirect($URL->link('index'), $lang_profile['Reg complete']);
		}
	}
}

// Setup form
$page_param['group_count'] = $page_param['item_count'] = $page_param['fld_count'] = 0;
$page_param['form_action'] = $URL->link('register').'?action=register';

// Setup form information
$page_param['frm_info'] = array();
if ($Config->get('o_regs_verify') != '0')
	$page_param['frm_info']['email'] = '<p class="warn">'.$lang_profile['Reg e-mail info'].'</p>';

// Setup breadcrumbs
$page_param['crumbs'] = array(
	array($Config->get('o_board_title'), $URL->link('index')),
	sprintf($lang_profile['Register at'], $Config->get('o_board_title'))
);

// Load JS for timezone detection
$Loader->add_js(BASE_URL.'/include/js/min/punbb.timezone.min.js');
$Loader->add_js('PUNBB.timezone.detect_on_register_form();', array('type' => 'inline'));

$Core->set_page_id('register');
require SITE_ROOT.'header.php';
?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo sprintf($lang_profile['Register at'], $Config->get('o_board_title')) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php
	if (!empty($page_param['frm_info'])):
?>
		<div class="ct-box info-box">
			<?php echo implode("\n\t\t\t", $page_param['frm_info'])."\n" ?>
		</div>
<?php
	endif;

	// If there were any errors, show them
	if (!empty($errors))
	{
		$page_param['errors'] = array();
		foreach ($errors as $cur_error)
			$page_param['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><span><?php echo $lang_profile['Register errors'] ?></span></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $page_param['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo $lang_common['Required warn'] ?></p>
		</div>
		<form class="frm-form frm-suggest-username" id="afocus" method="post" accept-charset="utf-8" action="<?php echo $page_param['form_action'] ?>" autocomplete="off">
			<div class="hidden">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($page_param['form_action']) ?>" />
				<input type="hidden" name="timezone" id="register_timezone" value="<?php echo html_encode($Config->get('o_default_timezone')) ?>" />
				<input type="hidden" name="dst" id="register_dst" value="<?php echo html_encode($Config->get('o_default_dst')) ?>" />
			</div>
			<div class="frm-group group<?php echo ++$page_param['group_count'] ?>">
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><?php echo $lang_profile['E-mail'] ?></span> <small><?php echo $lang_profile['E-mail help'] ?></small></label><br />
						<span class="fld-input"><input type="email" data-suggest-role="email" id="fld<?php echo $page_param['fld_count'] ?>" name="req_email1" value="<?php echo(isset($_POST['req_email1']) ? html_encode($_POST['req_email1']) : '') ?>" size="35" maxlength="80" required spellcheck="false" /></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$page_param['item_count']; if ($Config->get('o_regs_verify') == '0') echo ' prepend-top'; ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><?php echo $lang_profile['Username'] ?></span> <small><?php echo $lang_profile['Username help'] ?></small></label><br />
						<span class="fld-input"><input type="text" data-suggest-role="username" id="fld<?php echo $page_param['fld_count'] ?>" name="req_username" value="<?php echo(isset($_POST['req_username']) ? html_encode($_POST['req_username']) : '') ?>" size="35" maxlength="25" required spellcheck="false" /></span>
					</div>
				</div>
<?php if ($Config->get('o_regs_verify') == '0'): ?>
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><?php echo $lang_profile['Password'] ?></span> <small><?php echo $lang_profile['Password help'] ?></small></label><br />
						<span class="fld-input"><input type="<?php echo($Config->get('o_mask_passwords') == '1' ? 'password' : 'text') ?>" id="fld<?php echo $page_param['fld_count'] ?>" name="req_password1" size="35" value="<?php if (isset($_POST['req_password1'])) echo html_encode($_POST['req_password1']); ?>" required autocomplete="off" /></span>
					</div>
				</div>
	<?php if ($Config->get('o_mask_passwords') == '1'): ?>
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><?php echo $lang_profile['Confirm password'] ?></span> <small><?php echo $lang_profile['Confirm password help'] ?></small></label><br />
						<span class="fld-input"><input type="password" id="fld<?php echo $page_param['fld_count'] ?>" name="req_password2" size="35" value="<?php if (isset($_POST['req_password2'])) echo html_encode($_POST['req_password2']); ?>" required autocomplete="off" /></span>
					</div>
				</div>
	<?php endif; ?>
<?php endif;

		$languages = array();
		$d = dir(SITE_ROOT.'lang');
		while (($entry = $d->read()) !== false)
		{
			if ($entry != '.' && $entry != '..' && is_dir(SITE_ROOT.'lang/'.$entry) && file_exists(SITE_ROOT.'lang/'.$entry.'/common.php'))
				$languages[] = $entry;
		}
		$d->close();

		// Only display the language selection box if there's more than one language available
		if (count($languages) > 1)
		{
			natcasesort($languages);

?>
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><?php echo $lang_profile['Language'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $page_param['fld_count'] ?>" name="language">
<?php

			$select_lang = isset($_POST['language']) ? $_POST['language'] : $Config->get('o_default_lang');
			foreach ($languages as $lang)
			{
				if ($select_lang == $lang)
					echo "\t\t\t\t\t\t".'<option value="'.$lang.'" selected="selected">'.$lang.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$lang.'">'.$lang.'</option>'."\n";
			}

?>
						</select></span>
					</div>
				</div>
<?php

		}
?>
			</div>

			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="register" value="<?php echo $lang_profile['Register'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

require SITE_ROOT.'footer.php';
