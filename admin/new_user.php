<?php

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('system', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

// Load the userlist.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/userlist.php';
require SITE_ROOT.'lang/'.$User->get('language').'/admin_common.php';
require SITE_ROOT.'lang/'.$User->get('language').'/profile.php';

if (isset($_POST['new_user']))
{
	$email1 = strtolower(swift_trim($_POST['email']));
	$first_name = isset($_POST['first_name']) ? swift_trim($_POST['first_name']) : '';
	$last_name = isset($_POST['last_name']) ? swift_trim($_POST['last_name']) : '';
	$realname = $first_name.' '.$last_name;
	$username = $first_name.'.'.$last_name;
	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : $Config->get('o_default_user_group');
	$send_email = isset($_POST['send_email']) ? intval($_POST['send_email']) : 0;

	if ($first_name == '')
		$Core->add_error('First Name field can not be empty. Please enter First Name.');
	if ($last_name == '')
		$Core->add_error('Last Name field can not be empty. Please enter Last Name.');

	if ($Config->get('o_regs_verify') == '1')
		$password1 = random_key(8, true);
	else
		$password1 = swift_trim($_POST['password']);

	// ... and the password
	if (utf8_strlen($password1) < 4)
		$Core->add_error($lang_profile['Pass too short']);
	
	if (!is_valid_email($email1))
		$Core->add_error('Invalid e-mail. Please, enter valid email of new user.');
	
	// Check if it's a banned e-mail address
	$banned_email = is_banned_email($email1);
	if ($banned_email && $Config->get('p_allow_banned_email') == '0')
		$Core->add_error($lang_profile['Banned e-mail']);

	$query = array(
		'SELECT'	=> 'u.*',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.email=\''.$DBLayer->escape($email1).'\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$dupe_list = array();
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$dupe_list[] = $row;
	}

	if (!empty($dupe_list) && empty($Core->errors))
	{
		if ($Config->get('p_allow_dupe_email') == '0')
		{
			$Core->add_error('Someone else is already registered with that email address. Please choose another email address or remove/change folowing duplicated accounts:');
			foreach($dupe_list as $cur_dupe)
			{
				$Core->add_error('Duplicate account of '.$cur_dupe['realname'].'. Follow this link to manage the account: <a href="'.$URL->link('user', $cur_dupe['id']).'" class="fw-bold">'.$cur_dupe['realname'].'</a>');
			}
		}
	}

	// Did everything go according to plan so far?
	if (empty($Core->errors))
	{
		$initial_group_id = ($Config->get('o_regs_verify') == '0') ? $group_id : USER_GROUP_UNVERIFIED;
		$salt = random_key(12);
		$password_hash = spm_hash($password1, $salt);
		
		// Add the user
		$user_info = [
			'username'				=>	$username,
			'first_name'			=>	$first_name,
			'last_name'				=>	$last_name,
			'realname'				=>	$realname,
			'group_id'				=>	$initial_group_id,
			'salt'					=>	$salt,
			'password'				=>	$password_hash,
			'email'					=>	$email1,
			'email_setting'			=>	$Config->get('o_default_email_setting'),
			'timezone'				=>	$user_info['timezone'],
			'dst'					=>	0,
			'language'				=>	$Config->get('o_default_lang'),
			'style'					=>	$Config->get('o_default_style'),
			'registered'			=>	time(),
			'registration_ip'		=>	get_remote_address(),
			'activate_key'			=>	($Config->get('o_regs_verify') == '1') ? '\''.random_key(8, true).'\'' : 'NULL',
		];
		$new_uid = $DBLayer->insert('users', $user_info);

		if ($new_uid)
		{
			if ($send_email == 1)
			{
				$mail_subject = 'Wellcome to '.$Config->get('o_board_title');
				$mail_message = 'Hello '.$realname.'!'."\n\n";
				$mail_message .= 'You have a new account on '.$Config->get('o_board_title').'.'."\n";
				$mail_message .= 'To enter your account, use your username and password.'."\n\n";
				$mail_message .= 'Username: '.$username."\n";
				$mail_message .= 'Password: '.$password1."\n\n";
				$mail_message .= 'Login page: '.$URL->link('login')."\n\n";
				$mail_message .= 'For the safety of your account, change your password immediately after logging into your account. Do not share your username and password to anyone.'."\n\n";
				
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($email1, $mail_subject, $mail_message);
			}
			
			// Should we alert people on the admin mailing list that a new user has registered?
			if ($Config->get('o_regs_report') == '1' && $Config->get('o_mailing_list') != '')
			{
				$mail_subject2 = 'New account on '.$Config->get('o_board_title');
				$mail_message2 = [];
				$mail_message2[] = 'New account was created on '.$Config->get('o_board_title')."\n";
				$mail_message2[] = 'User name: '.$user_info['realname'];
				$mail_message2[] = 'User email: '.$user_info['email'];
				$mail_message2[] = 'User profile: '.$URL->link('user', $new_uid);
				$mail_message2[] = 'Created by: '.$User->get('realname');

				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($Config->get('o_mailing_list'), $mail_subject2, implode("\n", $mail_message2));
			}

			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'realname=\''.$DBLayer->escape($realname).'\', time_format=4, date_format=5',
				'WHERE'		=> 'id='.$new_uid
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			// Add flash message
			$flash_message = 'A new account has been created by '.$User->get('realname');
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('user', $new_uid), $flash_message);
		}
	}
}

$Core->set_page_id('admin_new_user', 'users');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Create a new account</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<ul>
					<li>Login will be created automatically based on First and Last name.</li>
<?php if ($Config->get('o_regs_verify') != '0'): ?>
					<li>After sending the invitation, the user must activate his account within 72 hours, otherwise it will be deleted automatically after this time.</li>
<?php endif; ?>
				</ul>
			</div>
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_first_name">First name</label>
					<input type="text" name="first_name" value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : '' ?>" class="form-control" id="fld_first_name" required>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_last_name">Last name</label>
					<input type="text" name="last_name" value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : '' ?>" class="form-control" id="fld_last_name" required>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_email">Email</label>
					<input type="text" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : '' ?>" class="form-control" id="fld_email" required>
					<label class="text-muted">A current valid email address</label>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_password">Password</label>
					<input type="text" name="password" value="<?php echo isset($_POST['password']) ? $_POST['password'] : '' ?>" class="form-control" id="fld_password" required>
					<label class="text-muted">Minimum 4 characters. cAsE sEnsiTive</label>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_group_id">Assign to</label>
				<select id="fld_group_id" name="group_id" class="form-select" required>
					<option value="0" selected>Select one</option>
<?php
// Get the list of user groups (excluding the guest group)
$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g.g_id!='.USER_GROUP_GUEST.' AND g.g_id!='.USER_GROUP_ADMIN.' AND g.g_moderator!=1',
	'ORDER BY'	=> 'g.g_id'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($cur_group = $DBLayer->fetch_assoc($result))
{
	if (isset($_POST['group_id']) && $_POST['group_id'] == $cur_group['g_id'])
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected>'.html_encode($cur_group['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="send_email" id="fld_send_email" value="1" <?php echo isset($_POST['send_email']) && $_POST['send_email'] == 1 ? ' checked' : '' ?>>
				<label class="form-check-label" for="fld_send_email">Send wellcome email to new user</label>
			</div>
			<div class="mb-3">
				<button type="submit" name="new_user" class="btn btn-primary">Create account</button>
			</div>
		</div>
	</div>
</form>

<?php

// Grab the users
$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.email, u.title, u.realname, u.num_posts, u.registered, u.last_visit, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
//	'WHERE'		=> 'u.group_id='.USER_GROUP_UNVERIFIED.' OR u.email=\'\'',
	'ORDER BY'	=> 'u.realname',
	'LIMIT'		=> 100
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$founded_user_datas = $dupes = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	if ($row['group_id'] == USER_GROUP_UNVERIFIED || $row['email'] == '' || isset($dupes[$row['email']]))
	{
		$founded_user_datas[] = $row;
	}
	$dupes[$row['email']] = $row;
}

$page_param['item_count'] = 0;

if (!empty($founded_user_datas))
{
	$page_param['table_header'] = array();
	
	$page_param['table_header']['realname'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>User name</strong></th>';
	$page_param['table_header']['email'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>User email</strong></th>';
	$page_param['table_header']['group'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>User group</strong></th>';
	$page_param['table_header']['registered'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>Created</strong></th>';

?>
<div class="card-header">
	<h6 class="card-title mb-0">List of unverified users</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<?php echo implode("\n\t\t\t\t\t\t", $page_param['table_header'])."\n" ?>
		</tr>
	</thead>
	<tbody>
<?php

	foreach ($founded_user_datas as $user_data)
	{
		$page_param['table_row'] = [];
		$page_param['table_row']['realname'] = '<td class="tc'.count($page_param['table_row']).'"><a href="'.$URL->link('user', $user_data['id']).'">'.html_encode($user_data['realname']).'</a></td>';
		$page_param['table_row']['email'] = '<td class="tc'.count($page_param['table_row']).'">'.html_encode($user_data['email']).'</td>';
		$page_param['table_row']['group'] = '<td class="tc'.count($page_param['table_row']).'">'.get_title($user_data).'</td>';
		$page_param['table_row']['last_visit'] = '<td class="tc'.count($page_param['table_row']).'">'.format_time($user_data['registered'], 1).'</td>';

		++$page_param['item_count'];

?>
		<tr class="<?php echo ($page_param['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php if ($page_param['item_count'] == 1) echo ' row1'; ?>">
			<?php echo implode("\n\t\t\t\t\t\t", $page_param['table_row'])."\n" ?>
		</tr>
<?php
	}
?>

	</tbody>
</table>

<?php
}
else
{
?>

<div class="alert alert-warning" role="alert">No unverified users found</div>

<?php
}
require SITE_ROOT.'footer.php';
