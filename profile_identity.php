<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

// Setup the form
$page_param['form_action'] = $URL->link('profile_identity', $id);

$page_param['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($page_param['form_action']).'" />'
);

if ($User->is_admmod() && ($User->is_admin() || $User->get('g_mod_rename_users') == '1'))
	$page_param['hidden_fields']['old_username'] = '<input type="hidden" name="old_username" value="'.html_encode($user['username']).'" />';

// Does the form have required fields
$page_param['has_required'] = ((($User->is_admmod() && ($User->is_admin() || $User->get('g_mod_rename_users') == '1')) || $User->is_admmod()) ? true : false);

// Create array for private information
$page_param['user_private'] = array();

if (!$User->is_admmod())
	$page_param['user_private']['change_email'] = '<li><span><a href="'.$URL->link('change_email', $id).'">'.(($page_param['own_profile']) ? $lang_profile['Change your e-mail'] : sprintf($lang_profile['Change user e-mail'], html_encode($user['realname']))).'</a></span></li>';

if ($page_param['own_profile'] || $User->is_admin() || ($User->get('g_moderator') == '1' && $User->get('g_mod_change_passwords') == '1'))
	$page_param['user_private']['change_password'] = '<li><span><a href="'.$URL->link('change_password', $id).'">'.(($page_param['own_profile']) ? $lang_profile['Change your password'] : sprintf($lang_profile['Change user password'], html_encode($user['realname']))).'</a></span></li>';

$page_title = ($page_param['own_profile']) ? $lang_profile['Identity welcome'] : sprintf($lang_profile['Identity welcome user'], html_encode($user['realname']));
$Core->set_page_title($page_title);

?>
<div class="col-md-8">

	<form method="post" accept-charset="utf-8" action="<?php echo $page_param['form_action'] ?>">
		<?php echo implode("\n\t\t\t\t", $page_param['hidden_fields'])."\n" ?>
		<div class="card">

<?php if ($page_param['has_required']): ?>

			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_profile['Private info'] ?></h6>
			</div>
			<div class="card-body">	

	<?php if ($User->is_admmod() && ($User->is_admin() || $User->get('g_mod_rename_users') == '1')): ?>

				<div class="mb-3">
					<label class="form-label" for="input_first_name">First Name</label>
					<input type="text" name="form[first_name]" value="<?php echo(isset($form['first_name']) ? html_encode($form['first_name']) : html_encode($user['first_name'])) ?>" class="form-control" id="input_first_name" required>
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_last_name">Last Name</label>
					<input type="text" name="form[last_name]" value="<?php echo(isset($form['last_name']) ? html_encode($form['last_name']) : html_encode($user['last_name'])) ?>" class="form-control" id="input_last_name" required>
				</div>
		 <?php if ($User->is_admin()): ?>
				<div class="mb-3">
					<label class="form-label" for="input_username">Login</label>
					<input type="text" name="req_username" value="<?php echo(isset($form['req_username']) ? html_encode($form['req_username']) : html_encode($user['username'])) ?>" class="form-control" id="input_username" required>
				</div>
		 <?php endif; ?>
	<?php endif; ?>

	<?php if ($User->is_admmod()): ?>

				<div class="mb-3">
					<label class="form-label" for="input_email">E-mail: <?php echo $lang_profile['E-mail help'] ?></label>
					<input type="email" name="req_email" value="<?php echo(isset($form['req_email']) ? html_encode($form['req_email']) : html_encode($user['email'])) ?>" class="form-control" id="input_email" required>
				</div>
		 <?php if ($User->is_admin()): ?>
				<div class="mb-3">
					<label class="form-label" for="input_realname">Print Name: Enter First and Last name</label>
					<input type="text" name="form[realname]" value="<?php echo(isset($form['realname']) ? html_encode($form['realname']) : html_encode($user['realname'])) ?>" class="form-control" id="input_realname" required>
				</div>
		 <?php endif; ?>
	<?php endif; ?>
			</div>
<?php endif; ?>

			<div class="card-header">
				<h6 class="card-title mb-0">Contact information</h6>
			</div>
			<div class="card-body">	
				<input type="hidden" name="form[location]" value="<?php echo((isset($form['location']) ? html_encode($form['location']) : html_encode($user['location']))) ?>" />

				<div class="mb-3">
					<label class="form-label" for="input_work_phone">Work Phone Number</label>
					<input type="text" name="form[work_phone]" value="<?php echo(isset($form['work_phone']) ? html_encode($form['work_phone']) : html_encode($user['work_phone'])) ?>" class="form-control" id="input_work_phone">
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_cell_phone">Cell Phone Number</label>
					<input type="text" name="form[cell_phone]" value="<?php echo(isset($form['cell_phone']) ? html_encode($form['cell_phone']) : html_encode($user['cell_phone'])) ?>" class="form-control" id="input_cell_phone">
				</div>
				<div class="mb-3">
					<label class="form-label" for="input_home_phone">Home Phone Number</label>
					<input type="text" name="form[home_phone]" value="<?php echo(isset($form['home_phone']) ? html_encode($form['home_phone']) : html_encode($user['home_phone'])) ?>" class="form-control" id="input_home_phone">
				</div>
				<button type="submit" name="update" class="btn btn-primary">Update profile</button>
			</div>

		</div>
	</form>
</div>
<?php

