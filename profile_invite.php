<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

$page_param['message'] = [];
$page_param['message'][] = 'Hello, '.html_encode($user['realname'])."\n\n";
$page_param['message'][] = 'You are invited to '.$Config->get('o_board_title')."\n\n";
$page_param['message'][] = 'Use the following link and login information to enter the site.'."\n";
$page_param['message'][] = 'When you are logged in, please change your temporary password.'."\n\n";
$page_param['message'][] = 'Site: '.BASE_URL."\n";
$page_param['message'][] = 'Login: '.html_encode($user['username'])."\n";
?>

<div class="col-md-8">
	<form method="post" accept-charset="utf-8" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Invite <?php echo html_encode($user['realname']) ?></h6>
			</div>
			<div class="card-body">
                <div class="alert alert-info" role="alert">To invite a user, set a password with which this user will enter the site. The entered password will be added automatically below the message text.</div>
                <div class="mb-3">
					<label class="form-label text-danger" for="fld_password">Password</label>
					<input type="text" name="password" value="" class="form-control" id="fld_password" required>
                    <label class="text-muted" for="fld_password">Minimum 4 characters. This password will be used to log into his account.</label>
				</div>
				<div class="mb-3">
					<label class="form-label text-danger" for="fld_message">Invite message</label>
					<textarea name="message" class="form-control" id="fld_message" required><?php echo (isset($_POST['message']) ? html_encode($_POST['message']) : implode('', $page_param['message'])) ?></textarea>
                    <label class="text-muted" for="fld_message">This text will be sent to this user. The entered password will be added below the message text.</label>
				</div>
				<button type="submit" name="invite" class="btn btn-primary">Invite user</button>
			</div>
		</div>
	</form>
</div>
<?php
