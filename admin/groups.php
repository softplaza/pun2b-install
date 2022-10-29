<?php

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

// Load the admin.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/admin_common.php';
require SITE_ROOT.'lang/'.$User->get('language').'/admin_groups.php';

// Add a group
if (isset($_POST['add_new_group']))
{
	// Is this the admin group? (special rules apply)
	$is_admin_group = (isset($_POST['group_id']) && $_POST['group_id'] == USER_GROUP_ADMIN) ? true : false;
	$g_moderator = (isset($_POST['g_moderator']) && $_POST['g_moderator'] == '1') ? '1' : '0';

	$form_data = [];
	$form_data['g_title'] = isset($_POST['g_title']) ? swift_trim($_POST['g_title']) : '';
	$form_data['g_user_title'] = isset($_POST['g_user_title']) ? swift_trim($_POST['g_user_title']) : '';
	$form_data['g_moderator'] = $g_moderator;

	if (isset($_POST['g_mod_edit_users'])) $form_data['g_mod_edit_users'] = intval($_POST['g_mod_edit_users']);
	if (isset($_POST['g_mod_rename_users'])) $form_data['g_mod_rename_users'] = intval($_POST['g_mod_rename_users']);
	if (isset($_POST['g_mod_change_passwords'])) $form_data['g_mod_change_passwords'] = intval($_POST['g_mod_change_passwords']);
	if (isset($_POST['g_mod_ban_users'])) $form_data['g_mod_ban_users'] = intval($_POST['g_mod_ban_users']);

	if (isset($_POST['g_read_board'])) $form_data['g_read_board'] = intval($_POST['g_read_board']);
	if (isset($_POST['g_view_users'])) $form_data['g_view_users'] = intval($_POST['g_view_users']);
	if (isset($_POST['g_set_title'])) $form_data['g_set_title'] = intval($_POST['g_set_title']);
	if (isset($_POST['g_search'])) $form_data['g_search'] = intval($_POST['g_search']);
	if (isset($_POST['g_send_email'])) $form_data['g_send_email'] = intval($_POST['g_send_email']);

	if ($g_moderator == '1')
		$form_data['g_mod_edit_users'] = $form_data['g_mod_rename_users'] = $form_data['g_mod_change_passwords'] = $form_data['g_mod_ban_users'] = '1';

	if ($is_admin_group)
		$form_data['g_read_board'] = $form_data['g_view_users'] = $form_data['g_set_title'] = $form_data['g_search'] = $form_data['g_send_email'] = '1';

	if ($form_data['g_title'] == '')
		message($lang_admin_groups['Must enter group message']);

	$query = array(
		'SELECT'	=> 'COUNT(g.g_id)',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g_title=\''.$DBLayer->escape($form_data['g_title']).'\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	if ($DBLayer->result($result) != 0)
		message(sprintf($lang_admin_groups['Already a group message'], html_encode($form_data['g_title'])));

	// Insert the new group
	$new_group_id = $DBLayer->insert_values('groups', $form_data);

	// Add flash message
	$flash_message = 'Group added';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('admin_groups'), $flash_message);
}

if (isset($_POST['update_group']))
{
	// Is this the admin group? (special rules apply)
	$is_admin_group = (isset($_POST['group_id']) && $_POST['group_id'] == USER_GROUP_ADMIN) ? true : false;
	$g_moderator = (isset($_POST['g_moderator']) && $_POST['g_moderator'] == '1') ? '1' : '0';

	$form_data = [];
	$form_data['g_title'] = isset($_POST['g_title']) ? swift_trim($_POST['g_title']) : '';
	$form_data['g_user_title'] = isset($_POST['g_user_title']) ? swift_trim($_POST['g_user_title']) : '';
	$form_data['g_moderator'] = $g_moderator;

	if (isset($_POST['g_mod_edit_users'])) $form_data['g_mod_edit_users'] = intval($_POST['g_mod_edit_users']);
	if (isset($_POST['g_mod_rename_users'])) $form_data['g_mod_rename_users'] = intval($_POST['g_mod_rename_users']);
	if (isset($_POST['g_mod_change_passwords'])) $form_data['g_mod_change_passwords'] = intval($_POST['g_mod_change_passwords']);
	if (isset($_POST['g_mod_ban_users'])) $form_data['g_mod_ban_users'] = intval($_POST['g_mod_ban_users']);

	if (isset($_POST['g_read_board'])) $form_data['g_read_board'] = intval($_POST['g_read_board']);
	if (isset($_POST['g_view_users'])) $form_data['g_view_users'] = intval($_POST['g_view_users']);
	if (isset($_POST['g_set_title'])) $form_data['g_set_title'] = intval($_POST['g_set_title']);
	if (isset($_POST['g_search'])) $form_data['g_search'] = intval($_POST['g_search']);
	if (isset($_POST['g_send_email'])) $form_data['g_send_email'] = intval($_POST['g_send_email']);

	if ($g_moderator == '1')
		$form_data['g_mod_edit_users'] = $form_data['g_mod_rename_users'] = $form_data['g_mod_change_passwords'] = $form_data['g_mod_ban_users'] = '1';

	if ($is_admin_group)
		$form_data['g_read_board'] = $form_data['g_view_users'] = $form_data['g_set_title'] = $form_data['g_search'] = $form_data['g_send_email'] = '1';

	if ($form_data['g_title'] == '')
		message($lang_admin_groups['Must enter group message']);

	$group_id = intval($_POST['group_id']);

	// Make sure admins and guests don't get moderator privileges
	if ($group_id == USER_GROUP_ADMIN || $group_id == USER_GROUP_GUEST)
		$form_data['g_moderator'] = '0';

	// Make sure the default group isn't assigned moderator privileges
	if ($g_moderator == '1' && $Config->get('o_default_user_group') == $group_id)
		message($lang_admin_groups['Moderator default group']);

	$query = array(
		'SELECT'	=> 'COUNT(g.g_id)',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g_title=\''.$DBLayer->escape($form_data['g_title']).'\' AND g_id!='.$group_id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	if ($DBLayer->result($result) != 0)
		message(sprintf($lang_admin_groups['Already a group message'], html_encode($form_data['g_title'])));

	// Save changes
	$DBLayer->update('groups', $form_data, 'g_id='.$group_id);

	// Add flash message
	$flash_message = 'Group edited';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('admin_groups'), $flash_message);
}

// Set default group
else if (isset($_POST['set_default_group']))
{
	$group_id = intval($_POST['default_group']);

	// Make sure it's not the admin or guest groups
	if ($group_id == USER_GROUP_ADMIN || $group_id == USER_GROUP_GUEST)
		message($lang_common['Bad request']);

	// Make sure it's not a moderator group
	$query = array(
		'SELECT'	=> 'COUNT(g.g_id)',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id='.$group_id.' AND g.g_moderator=0',
		'LIMIT'		=> '1'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	if ($DBLayer->result($result) != 1)
		message($lang_common['Bad request']);

	$query = array(
		'UPDATE'	=> 'config',
		'SET'		=> 'conf_value='.$group_id,
		'WHERE'		=> 'conf_name=\'o_default_user_group\''
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Regenerate the config cache
	$Cachinger->gen_config();

	// Add flash message
	$flash_message = 'Default group set';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('admin_groups'), $flash_message);
}

else if (isset($_POST['cancel']))
{
	// Add flash message
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('admin_groups'), $flash_message);
}

if (isset($_POST['add_group']) || isset($_GET['edit_group']))
{
	if (isset($_POST['add_group']))
	{
		$base_group = intval($_POST['base_group']);

		$query = array(
			'SELECT'	=> 'g.*',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$base_group
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$group = $DBLayer->fetch_assoc($result);

		$mode = 'add';
	}
	else	// We are editing a group
	{
		$group_id = intval($_GET['edit_group']);
		if ($group_id < 1)
			message($lang_common['Bad request']);

		$query = array(
			'SELECT'	=> 'g.*',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$group_id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$group = $DBLayer->fetch_assoc($result);

		if (!$group)
			message($lang_common['Bad request']);

		$mode = 'edit';
	}

	$Core->set_page_id('admin_groups', 'users');
	require SITE_ROOT.'header.php';
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

		<input type="hidden" name="mode" value="<?php echo $mode ?>" />
<?php if ($mode == 'edit'): ?>
		<input type="hidden" name="group_id" value="<?php echo $group_id ?>" />
<?php endif;
if ($mode == 'add'): ?>
		<input type="hidden" name="base_group" value="<?php echo $base_group ?>" />
<?php endif; ?>

		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_admin_groups['Group title head'] ?></h6>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="field_req_title"><?php echo $lang_admin_groups['Group title label'] ?></label>
					<input type="text" id="field_req_title" name="g_title" maxlength="50" value="<?php if ($mode == 'edit') echo html_encode($group['g_title']); ?>" required class="form-control"/>
				</div>
				<div class="mb-3">
					<label for="field_user_title"><?php echo $lang_admin_groups['User title label'] ?></label>
					<input type="text" id="field_user_title" name="g_user_title" maxlength="50" value="<?php echo html_encode($group['g_user_title']) ?>" class="form-control"/>
				</div>
			</div>
		</div>
<?php
	// The rest of the form is for non-admin groups only
	if ($group['g_id'] != USER_GROUP_ADMIN)
	{
?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Sub-Admin permissions</h6>
			</div>
			<div class="card-body">
				<div class="form-check">
					<input type="hidden" name="g_moderator" value="0"/>
					<input type="checkbox" id="field_moderator" name="g_moderator" value="1" <?php if ($group['g_moderator'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_moderator" class="form-check-label"><?php echo $lang_admin_groups['Allow moderate label'] ?></label>
				</div>
				<div class="form-check">
					<input type="hidden" name="g_mod_edit_users" value="0"/>
					<input type="checkbox" id="field_mod_edit_users" name="g_mod_edit_users" value="1"<?php if ($group['g_mod_edit_users'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_mod_edit_users" class="form-check-label"><?php echo $lang_admin_groups['Allow mod edit profiles label'] ?></label>
				</div>
				<div class="form-check">
					<input type="hidden" name="g_mod_rename_users" value="0"/>
					<input type="checkbox" id="field_mod_rename_users" name="g_mod_rename_users" value="1"<?php if ($group['g_mod_rename_users'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_mod_rename_users" class="form-check-label"><?php echo $lang_admin_groups['Allow mod edit username label'] ?></label>
				</div>
				<div class="form-check">
					<input type="hidden" name="g_mod_change_passwords" value="0"/>
					<input type="checkbox" id="field_mod_change_passwords" name="g_mod_change_passwords" value="1"<?php if ($group['g_mod_change_passwords'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_mod_change_passwords" class="form-check-label"><?php echo $lang_admin_groups['Allow mod change pass label'] ?></label>
				</div>
				<div class="form-check">
					<input type="hidden" name="g_mod_ban_users" value="0"/>
					<input type="checkbox" id="field_mod_ban_users" name="g_mod_ban_users" value="1"<?php if ($group['g_mod_ban_users'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_mod_ban_users" class="form-check-label"><?php echo $lang_admin_groups['Allow mod bans label'] ?></label>
				</div>
			</div>
		</div>
<?php
		if ($group['g_moderator'] != '1')
		{
?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">User permissions</h6>
			</div>
			<div class="card-body">
				<div class="form-check">
					<input type="hidden" name="g_read_board" value="0"/>
					<input type="checkbox" id="field_read_board" name="g_read_board" value="1" <?php if ($group['g_read_board'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_read_board" class="form-check-label"><?php echo $lang_admin_groups['Allow read board label'] ?></label>
				</div>
				<div class="form-check">
					<input type="hidden" name="g_view_users" value="0"/>
					<input type="checkbox" id="field_view_users" name="g_view_users" value="1"<?php if ($group['g_view_users'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_view_users" class="form-check-label"><?php echo $lang_admin_groups['Allow view users label'] ?></label>
				</div>
				<div class="form-check">
					<input type="hidden" name="g_set_title" value="0"/>
					<input type="checkbox" id="field_set_title" name="g_set_title" value="1"<?php if ($group['g_set_title'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_set_title" class="form-check-label"><?php echo $lang_admin_groups['Allow set user title label'] ?></label>
				</div>
				<div class="form-check">
					<input type="hidden" name="g_search" value="0"/>
					<input type="checkbox" id="field_search" name="g_search" value="1"<?php if ($group['g_search'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_search" class="form-check-label"><?php echo $lang_admin_groups['Allow use search label'] ?></label>
				</div>
<?php if ($group['g_id'] != USER_GROUP_GUEST): ?>
				<div class="form-check">
					<input type="hidden" name="g_send_email" value="0"/>
					<input type="checkbox" id="field_send_email" name="g_send_email" value="1"<?php if ($group['g_send_email'] == '1') echo ' checked="checked"' ?> class="form-check-input" />
					<label for="field_send_email" class="form-check-label"><?php echo $lang_admin_groups['Allow send email label'] ?></label>
				</div>
<?php endif; ?>
			</div>
		</div>
<?php
		}
	}
?>
		<div class="card mb-3">
			<div class="card-body">
				<div class="mb-3">
<?php if ($mode == 'add'): ?>
					<button type="submit" name="add_new_group" class="btn btn-primary">Add group</button>
<?php else: ?>
					<button type="submit" name="update_group" class="btn btn-primary">Update group</button>
<?php endif; ?>
					<button type="submit" name="cancel" class="btn btn-secondary">Back</button>
				</div>
			</div>
		</div>
	</form>
<?php
	require SITE_ROOT.'footer.php';
}

// Remove a group
else if (isset($_GET['del_group']))
{
	$group_id = intval($_GET['del_group']);
	if ($group_id <= USER_GROUP_GUEST)
		message($lang_common['Bad request']);

	// User pressed the cancel button
	if (isset($_POST['del_group_cancel']))
		redirect($URL->link('admin_groups'), $lang_admin_common['Cancel redirect']);

	// Make sure we don't remove the default group
	if ($group_id == $Config->get('o_default_user_group'))
		message($lang_admin_groups['Cannot remove default group']);

	// Check if this group has any members
	$query = array(
		'SELECT'	=> 'g.g_title AS title, COUNT(u.id) AS num_members',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'g.g_id='.$group_id,
		'GROUP BY'	=> 'g.g_id, g.g_title'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$group_info = $DBLayer->fetch_assoc($result);

	// If the group doesn't have any members or if we've already selected a group to move the members to
	if (!$group_info || isset($_POST['del_group']))
	{
		if (isset($_POST['del_group']))	// Move users
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'group_id='.intval($_POST['move_to_group']),
				'WHERE'		=> 'group_id='.$group_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}

		// Delete the group and any site specific permissions
		$query = array(
			'DELETE'	=> 'groups',
			'WHERE'		=> 'g_id='.$group_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$FlashMessenger->add_info($lang_admin_groups['Group removed']);
		redirect($URL->link('admin_groups'), $lang_admin_groups['Group removed']);
	}

	$Core->set_page_id('admin_groups', 'users');
	require SITE_ROOT.'header.php';
?>
	<form method="post" accept-charset="utf-8" action="<?php echo $URL->link('admin_groups') ?>?del_group=<?php echo $group_id ?>">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($URL->link('admin_groups').'?del_group='.$group_id) ?>" />
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_admin_groups['Remove group legend'] ?></h6>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="field_move_to_group"><?php echo $lang_admin_groups['Move users to'] ?></label>
					<select id="field_move_to_group" name="move_to_group" class="form-select">
<?php

	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id!='.USER_GROUP_GUEST.' AND g.g_id!='.$group_id,
		'ORDER BY'	=> 'g.g_title'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_group = $DBLayer->fetch_assoc($result))
	{
		if ($cur_group['g_id'] == $Config->get('o_default_user_group'))	// Pre-select the default Members group
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.html_encode($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
	}

?>
					</select>
				</div>
				<div class="mb-3">
					<button type="submit" name="del_group" class="btn btn-sm btn-danger">Remove group</button>
					<button type="submit" name="del_group_cancel" class="btn btn-sm btn-secondary">Cancel</button>
				</div>
			</div>
		</div>
	</form>
<?php
	require SITE_ROOT.'footer.php';
}

$Core->set_page_id('admin_groups', 'users');
require SITE_ROOT.'header.php';
?>
	<div class="alert alert-primary bm-3" role="alert">
		<?php echo $lang_admin_groups['Existing groups intro'] ?>
	</div>
<?php

$query = array(
	'SELECT'	=> 'g.*',
	'FROM'		=> 'groups AS g',
	'ORDER BY'	=> 'g.g_title'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($cur_group = $DBLayer->fetch_assoc($result))
{
	$group_info[] = $cur_group;
}

?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Available groups</h6>
			</div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>Group title</th>
						<th>User title</th>
						<th>Privileges</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php

foreach ($group_info as $cur_info)
{
	echo '<tr>';
	echo '<td><span class="fw-bold">'.html_encode($cur_info['g_title']).'</span></td>';
	echo '<td>'.html_encode($cur_info['g_user_title']).'</td>';

	if ($cur_info['g_id'] == USER_GROUP_GUEST)
		echo '<td>Guest</td>';
	else if ($cur_info['g_id'] == USER_GROUP_ADMIN)
		echo '<td>SysAdmin</td>';
	else if ($cur_info['g_moderator'] == '1')
		echo '<td>SubAdmin</td>';
	else if ($Config->get('o_default_user_group') == $cur_info['g_id'])
		echo '<td>Default group</td>';
	else
		echo '<td></td>';

	echo '<td>';
	if ($User->is_admin())
	{
		echo '<a href="'.$URL->link('admin_groups').'?edit_group='.$cur_info['g_id'].'" class="badge bg-primary text-white me-2">Edit group</a>';
		if ($cur_info['g_id'] != $Config->get('o_default_user_group') && $cur_info['g_id'] != USER_GROUP_GUEST && $cur_info['g_id'] != USER_GROUP_ADMIN)
			echo '<a href="'.$URL->link('admin_groups').'?del_group='.$cur_info['g_id'].'" class="badge bg-danger text-white">Delete group</a>';
	}
	echo '</td>';

	echo '</tr>';
}
?>
				</tbody>
			</table>
		</div>
	</form>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_admin_groups['Add group heading'] ?></h6>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="field_base_group"><?php echo $lang_admin_groups['Base new group label'] ?></label>
					<select id="field_base_group" name="base_group" class="form-select">
<?php

$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g_id>'.USER_GROUP_GUEST,
	'ORDER BY'	=> 'g.g_title'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($cur_group = $DBLayer->fetch_assoc($result))
	echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].($cur_group['g_id'] == $Config->get('o_default_user_group') ? '" selected="selected">' : '">').html_encode($cur_group['g_title']).'</option>'."\n";

?>
					</select>
				</div>
				<div class="mb-3">
					<button type="submit" name="add_group" class="btn btn-sm btn-primary"><?php echo $lang_admin_groups['Add group'] ?></button>
				</div>
			</div>
		</div>
	</form>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_admin_groups['Default group label'] ?></h6>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="field_default_group"><?php echo $lang_admin_groups['Default group label'] ?></label>
					<select id="field_default_group" name="default_group" class="form-select">
<?php
$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g_id>'.USER_GROUP_GUEST.' AND g_moderator=0',
	'ORDER BY'	=> 'g.g_title'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($cur_group = $DBLayer->fetch_assoc($result))
{
	if ($cur_group['g_id'] == $Config->get('o_default_user_group'))
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.html_encode($cur_group['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="mb-3">
					<button type="submit" name="set_default_group" class="btn btn-sm btn-primary"><?php echo $lang_admin_groups['Set default'] ?></button>
				</div>
			</div>
		</div>
	</form>
<?php
require SITE_ROOT.'footer.php';
