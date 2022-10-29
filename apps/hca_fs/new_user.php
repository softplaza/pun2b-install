<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 13)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$gid = 0;
if (isset($_POST['new_user']))
{
	$email1 = strtolower(swift_trim($_POST['req_email1']));
	$first_name = isset($_POST['first_name']) ? swift_trim($_POST['first_name']) : '';
	$last_name = isset($_POST['last_name']) ? swift_trim($_POST['last_name']) : '';
	$realname = $first_name.' '.$last_name;
	$username = $first_name.'.'.$last_name;
	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$password1 = random_key(5, true);
	
	if ($group_id < 1)
		$Core->add_error('Please select group of employee.');
	if ($first_name == '')
		$Core->add_error('Please enter First Name.');
	if ($last_name == '')
		$Core->add_error('Please enter Last Name.');
	if (!is_valid_email($email1))
		$Core->add_error('Invalid e-mail. Please, enter valid email of new user.');

	if (empty($Core->errors))
	{
		$salt = random_key(12);
		$password_hash = spm_hash($password1, $salt);
		
		$user_info = array(
					'username'				=>	$username,
					'group_id'				=>	$group_id,
					'salt'					=>	$salt,
					'password'				=>	$password1,
					'password_hash'			=>	$password_hash,
					'email'					=>	$email1,
					'email_setting'			=>	$Config->get('o_default_email_setting'),
					'save_pass'				=>	0,
					'timezone'				=>	$Config->get('o_default_timezone'),
					'dst'					=>	0,
					'language'				=>	$Config->get('o_default_lang'),
					'style'					=>	$Config->get('o_default_style'),
					'registered'			=>	time(),
					'registration_ip'		=>	get_remote_address(),
					'activate_key'			=>	'NULL',
					'require_verification'	=>	false,
					'notify_admins'			=>	($Config->get('o_regs_report') == '1')
		);
		
		add_user($user_info, $new_uid);
		
		if ($new_uid)
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 
					'realname=\''.$DBLayer->escape($realname).'\', 
					first_name=\''.$DBLayer->escape($first_name).'\', 
					last_name=\''.$DBLayer->escape($last_name).'\', time_format=4, date_format=5',
				'WHERE'		=> 'id='.$new_uid
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
//			if (!$user_info['require_verification'])
//			{
				$mail_subject = 'Wellcome to '.$Config->get('o_board_title');
				$mail_message = [];
				$mail_message[] = 'Hello '.$realname.'!';
				$mail_message[] = 'You have a new account on '.$Config->get('o_board_title').'.'."\n";
				$mail_message[] = 'To enter your account, use your username and password.';
				$mail_message[] = 'Username: '.$username;
				$mail_message[] = 'Password: '.$password1;
				$mail_message[] = 'Go to login page: '.$URL->link('login')."\n";
				$mail_message[] = 'For the safety of your account, change your password immediately after logging into your account. Do not share your username and password to anyone.';
				
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($email1, $mail_subject, implode("\n", $mail_message));
//			}
			
			// Add flash message
			$flash_message = 'The member '.$realname.' has been added';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_fs_weekly_schedule', [$group_id, date('Y-m-d', time())]).'&uid='.$new_uid, $flash_message);
		}
	}
}

$page_param['item_count'] = $page_param['group_count'] = $page_param['fld_count'] = 0;

$Core->set_page_title('Add a new user');
$Core->set_page_id('hca_fs_new_user', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm">
	<div class="ct-box info-box">
		<ul class="info-list">
			<li>Fill in all the fields and click the "Create Account" button.</li>
		</ul>
	</div>
	<div class="ct-group">
		<form id="afocus" method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<div class="frm-form">
				<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
					<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
						<div class="sf-box text required">
							<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>First Name</span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo $page_param['fld_count'] ?>" name="first_name" value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : '' ?>" size="35" maxlength="25" required autocomplete="off" /></span>
						</div>
					</div>
					<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
						<div class="sf-box text required">
							<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Last Name</span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo $page_param['fld_count'] ?>" name="last_name" value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : '' ?>" size="35" maxlength="25" required autocomplete="off" /></span>
						</div>
					</div>
					<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
						<div class="sf-box text required">
							<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>E-mail</span></label><br />
							<span class="fld-input"><input type="email" id="fld<?php echo $page_param['fld_count'] ?>" name="req_email1" value="<?php echo(isset($_POST['req_email1']) ? html_encode($_POST['req_email1']) : '') ?>" size="35" maxlength="80" required autocomplete="off" /></span>
						</div>
					</div>
					<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
						<div class="sf-box select required">
							<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Group of Employee</span></label><br />
							<span class="fld-input">
								<select id="fld<?php echo $page_param['fld_count'] ?>" name="group_id" required>
									<option value="0">Select Group</option>
<?php
	
// Get the list of user groups (excluding the guest group)
$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g.g_id > 2',
	'ORDER BY'	=> 'g.g_id'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($cur_group = $DBLayer->fetch_assoc($result))
{
	if ($cur_group['g_id'] == $Config->get('o_hca_fs_maintenance') || $cur_group['g_id'] == $Config->get('o_hca_fs_painters'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
}
?>
							</select></span>
						</div>
					</div>
				</fieldset>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="new_user" value="Create Account" /></span>
				</div>
			</div>
		</form>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';