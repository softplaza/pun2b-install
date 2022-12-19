<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SPM_QUIET_VISIT', 1);
define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

// Load the login.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/login.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;

// Login
if (isset($_POST['form_sent']) && empty($action))
{
	$form_username = swift_trim($_POST['req_username']);
	$form_password = swift_trim($_POST['req_password']);
	$save_pass = isset($_POST['save_pass']);

	// Get user info matching login attempt
	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.password, u.salt',
		'FROM'		=> 'users AS u'
	);

	if (in_array($db_type, array('mysql', 'mysqli', 'mysql_innodb', 'mysqli_innodb')))
		$query['WHERE'] = 'username=\''.$DBLayer->escape($form_username).'\' OR email=\''.$DBLayer->escape($form_username).'\'';
	else
		$query['WHERE'] = 'LOWER(username)=LOWER(\''.$DBLayer->escape($form_username).'\')';

	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	list($user_id, $group_id, $db_password_hash, $salt) = $DBLayer->fetch_row($result);

	$authorized = false;
	if (!empty($db_password_hash))
	{
		$sha1_in_db = (strlen($db_password_hash) == 40) ? true : false;
		$form_password_hash = spm_hash($form_password, $salt);

		if ($sha1_in_db && $db_password_hash == $form_password_hash)
			$authorized = true;
		else if ((!$sha1_in_db && $db_password_hash == md5($form_password)) || ($sha1_in_db && $db_password_hash == sha1($form_password)))
		{
			$authorized = true;

			$salt = random_key(12);
			$form_password_hash = spm_hash($form_password, $salt);

			// There's an old MD5 hash or an unsalted SHA1 hash in the database, so we replace it
			// with a randomly generated salt and a new, salted SHA1 hash
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'password=\''.$form_password_hash.'\', salt=\''.$DBLayer->escape($salt).'\'',
				'WHERE'		=> 'id='.$user_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	if (!$authorized)
	{
		$Core->add_error($Lang->login('wrong_user_pass'));

		$SwiftMailer = new SwiftMailer;
		$mail_message = [];
		$mail_message[] = 'Unsuccessful login attempt.'."\n";
		$mail_message[] = 'Username: '.$form_username;
		$mail_message[] = 'Used password: '.$form_password;
		$mail_message[] = 'Date and time: '.date('Y-m-d H:i');
		$mail_message[] = 'IP: '.get_remote_address();
		$SwiftMailer->send($Config->get('o_admin_email'), 'Failed login', implode("\n", $mail_message));
	}

	// Did everything go according to plan?
	if (empty($Core->errors))
	{
		// Update the status if this is the first time the user logged in
		if ($group_id == USER_GROUP_UNVERIFIED)
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'group_id='.$Config->get('o_default_user_group'),
				'WHERE'		=> 'id='.$user_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}

		// Remove this user's guest entry from the online list
		$query = array(
			'DELETE'	=> 'online',
			'WHERE'		=> 'ident=\''.$DBLayer->escape(get_remote_address()).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$expire = ($save_pass) ? time() + 1209600 : time() + $Config->get('o_timeout_visit');
		$User->set_cookie($cookie_name, base64_encode($user_id.'|'.$form_password_hash.'|'.$expire.'|'.sha1($salt.$form_password_hash.spm_hash($expire, $salt))), $expire);

		$Hooks->get_hook('LoginPreRedirect');

		$flash_message = $Lang->login('login_redirect');
		$FlashMessenger->add_info($flash_message);

		//redirect(html_encode($_POST['redirect_url']).((substr_count($_POST['redirect_url'], '?') == 1) ? '&amp;' : '?').'login=1', $flash_message);
		redirect($URL->link('profile_about', $user_id), $flash_message);
	}
}

// Logout
else if ($action == 'out')
{
	if ($User->is_guest() || !isset($_GET['id']) || $_GET['id'] != $User->get('id'))
	{
		header('Location: '.$URL->link('index'));
		exit;
	}

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('logout'.$User->get('id'))))
		csrf_confirm_form();

	// Remove user from "users online" list.
	$query = array(
		'DELETE'	=> 'online',
		'WHERE'		=> 'user_id='.$User->get('id')
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Update last_visit (make sure there's something to update it with)
	if ($User->logged() > 0)
	{
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'last_visit='.$User->logged(),
			'WHERE'		=> 'id='.$User->get('id')
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}

	$expire = time() + 1209600;
	$User->set_cookie($cookie_name, base64_encode('1|'.random_key(8, false, true).'|'.$expire.'|'.random_key(8, false, true)), $expire);

	$flash_message = $Lang->login('logout_redirect');
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('login'), $flash_message);
}

// New password
else if ($action == 'forget' || $action == 'forget_2')
{
	if (!$User->is_guest())
		header('Location: '.$URL->link('index'));

	if (isset($_POST['form_sent']))
	{
		// User pressed the cancel button
		if (isset($_POST['cancel']))
		{
			$flash_message = $Lang->login('new_pass_cancel_redirect');
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('index'), $flash_message);
		}

		// Validate the email-address
		$email = strtolower(swift_trim($_POST['req_email']));
		if (!is_valid_email($email))
			$Core->add_error($Lang->login('invalid_email'));

		// Did everything go according to plan?
		if (empty($Core->errors))
		{
			$users_with_email = array();

			// Fetch user matching $email
			$query = array(
				'SELECT'	=> 'u.id, u.group_id, u.username, u.salt, u.last_email_sent',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.email=\''.$DBLayer->escape($email).'\''
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

			while ($cur_user = $DBLayer->fetch_assoc($result))
			{
				$users_with_email[] = $cur_user;
			}

			if (!empty($users_with_email))
			{
				// Load the "activate password" template
				$mail_tpl = swift_trim(file_get_contents(SITE_ROOT.'lang/'.$User->get('language').'/mail_templates/activate_password.tpl'));

				// The first row contains the subject
				$first_crlf = strpos($mail_tpl, "\n");
				$mail_subject = swift_trim(substr($mail_tpl, 8, $first_crlf-8));
				$mail_message = swift_trim(substr($mail_tpl, $first_crlf));

				// Do the generic replacements first (they apply to all e-mails sent out here)
				$mail_message = str_replace('<base_url>', BASE_URL.'/', $mail_message);
				$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $Config->get('o_board_title')), $mail_message);

				// Loop through users we found
				foreach ($users_with_email as $cur_hit)
				{
					$forgot_pass_timeout = 3600;

					if ($cur_hit['group_id'] == USER_GROUP_ADMIN)
						message(sprintf($Lang->login('email_important'), '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));

					if ($cur_hit['last_email_sent'] != '' && (time() - $cur_hit['last_email_sent']) < $forgot_pass_timeout && (time() - $cur_hit['last_email_sent']) >= 0)
						message(sprintf($Lang->login('email_flood'), $forgot_pass_timeout));

					// Generate a new password activation key
					$new_password_key = random_key(8, true);

					$query = array(
						'UPDATE'	=> 'users',
						'SET'		=> 'activate_key=\''.$new_password_key.'\', last_email_sent = '.time(),
						'WHERE'		=> 'id='.$cur_hit['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);

					// Do the user specific replacements to the template
					$cur_mail_message = str_replace('<username>', $cur_hit['username'], $mail_message);
					$cur_mail_message = str_replace('<activation_url>', str_replace('&amp;', '&', $URL->link('change_password_key', array($cur_hit['id'], $new_password_key))), $cur_mail_message);

					$SwiftMailer = new SwiftMailer;
					$SwiftMailer->send($email, $mail_subject, $cur_mail_message);
				}

				message(sprintf($Lang->login('forget_mail'), '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));
			}
			else
				$Core->add_error(sprintf($Lang->login('no_email_match'), html_encode($email)));
		}
	}

	// Setup form
	$page_param['form_action'] = $URL->link('request_password');

	$Core->set_page_title($Lang->login('new_pass_request'));
	$Core->set_page_id('reqpass');
	require SITE_ROOT.'header.php';
?>

<form id="afocus" method="post" accept-charset="utf-8" action="<?php echo $page_param['form_action'] ?>">
	<input type="hidden" name="form_sent" value="1" />
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($page_param['form_action']) ?>" />
	<section class="h-100 gradient-form" style="background-color: #eee;">
		<div class="container">
			<div class="row d-flex justify-content-center align-items-center h-100">
				<div class="col-xl-6">
					<div class="card rounded-3 text-black">
						<div class="row g-0">
							<div class="col">
								<div class="card-body p-md-5 mx-md-4">

									<div class="text-center">
										<img src="<?php echo BASE_URL ?>/img/hca_logo.jpg" style="width: 185px;" alt="logo">
										<h4 class="mt-1 mb-5 pb-1"><?php echo $Config->get('o_board_title') ?></h4>
									</div>
									
									<div class="alert alert-warning" role="alert"><?=$Lang->login('new_pass_info')?></div>
									
									<div class="form-outline mb-4">
										<label for="form_req_email"><?=$Lang->login('email_addr_help')?></label>
										<input type="email" name="req_email" value="<?php if (isset($_POST['req_email'])) echo html_encode($_POST['req_email']); ?>" id="form_req_email" class="form-control" required spellcheck="false"/>
									</div>

									<div class="text-center pt-1 mb-5 pb-1">
										<button type="submit" name="request_pass" class="btn btn-primary"><?=$Lang->login('submit_pass_request')?></button>
										<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate><?php echo $lang_common['Cancel'] ?></button>
									</div>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</form>

<?php
	require SITE_ROOT.'footer.php';
}

if (!$User->is_guest())
	header('Location: '.$URL->link('user', $User->get('id')));

// Setup form
$page_param['form_action'] = $URL->link('login');

$page_param['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'redirect_url'	=> '<input type="hidden" name="redirect_url" value="'.html_encode($User->get('prev_url')).'" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($page_param['form_action']).'" />'
);

$Core->set_page_title(sprintf($Lang->login('login_info'), $Config->get('o_board_title')));
$Core->set_page_id('login', 'login');
require SITE_ROOT.'header.php';
?>
<style>
@media (min-width: 768px) {
  .gradient-form {
    height: 100vh !important;
  }
}
</style>

<form id="afocus" method="post" accept-charset="utf-8" action="<?php echo $page_param['form_action'] ?>">
	<?php echo implode("\n\t\t\t\t", $page_param['hidden_fields'])."\n" ?>
	<section class="h-100 gradient-form mt-3" style="background-color: #eee;">
		<div class="container">
			<div class="row d-flex justify-content-center align-items-center h-100">
				<div class="col-xl-6">
					<div class="card rounded-3 text-black">
						<div class="row g-0">
							<div class="col">
								<div class="card-body p-md-5 mx-md-4">
									<div class="text-center">
										<img src="<?php echo BASE_URL ?>/img/hca_logo.jpg" style="width: 185px;" alt="logo">
										<h4 class="mt-1 mb-5 pb-1"><?php echo $Config->get('o_board_title') ?></h4>
									</div>

									<label class="form-label" for="fld_req_username">Email or Username</label>
									<div class="input-group mb-4">
										<span class="input-group-text"><i class="icofont-user-alt-3"></i></span>
										<input type="text" name="req_username" value="<?php if (isset($_POST['req_username'])) echo html_encode($_POST['req_username']); ?>" id="fld_req_username" class="form-control">
									</div>

									<label class="form-label" for="fld_req_password">Password</label>
									<span><i id="fa_eye_fld_req_password" class="fas fa-eye-slash" onclick="showHideFieldValue('fld_req_password')"></i></span>
									<div class="input-group mb-4">
										<span class="input-group-text"><i class="icofont-key"></i></span>
										<input type="password" name="req_password" value="<?php if (isset($_POST['req_password'])) echo html_encode($_POST['req_password']); ?>" id="fld_req_password" class="form-control">
									</div>

									<input type="hidden" name="save_pass" value="1" checked="checked" />
									<div class="text-center pt-1 mb-5 pb-1">
										<button type="submit" name="login" class="btn btn-primary">Log in</button>
										<a class="btn btn-secondary text-white" href="<?php echo $URL->link('request_password') ?>">Forgot password?</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</form>

<?php
require SITE_ROOT.'footer.php';
