<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vcr', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$rules_to = 'hca_vcr';
$access_options = [
	1 => 'Project list',
	2 => 'Create projects',
	3 => 'Edit projects',
	4 => 'Delete projects',
	5 => 'Vendor schedule',
	6 => 'In-House schedule',
	7 => 'Property schedule',

	20 => 'Settings'
];

$permissions_options = [];
$notifications_options = [
	5 => 'New project was created',
	1 => 'Project status changed',
	2 => 'Move Out date changed',
	3 => 'Maintenance request sent',
	4 => 'Painter request sent',
];

$default_services = [
	1 => 'Urine Scan',
	2 => 'Painters',
	6 => 'Cleaning Service',
	3 => 'Vinyl Service',
	4 => 'Carpet Service',
	9 => 'Carpet Clean',
	7 => 'Refinish',
	5 => 'Pest Control'
];

$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g.g_id > 2',
	'ORDER BY'	=> 'g.g_title'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$groups_info = array();
while ($cur_group = $DBLayer->fetch_assoc($result))
	$groups_info[] = $cur_group;

if (isset($_POST['create']))
{
	$type = isset($_POST['type']) ? intval($_POST['type']) : 0;
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

	if ($user_id > 0 && $type == 1)
	{
		foreach($access_options as $key => $title)
		{
			$data = [
				'a_uid'		=> $user_id,
				'a_to'		=> $rules_to,
				'a_key'		=> $key,
				'a_value'	=> 0
			];
			$DBLayer->insert('user_access', $data);
		}
	}
	else if ($user_id > 0 && $type == 2)
	{
		foreach($permissions_options as $key => $title)
		{
			$data = [
				'p_uid'		=> $user_id,
				'p_to'		=> $rules_to,
				'p_key'		=> $key,
				'p_value'	=> 0
			];
			$DBLayer->insert('user_permissions', $data);
		}
	}
	else if ($user_id > 0 && $type == 3)
	{
		foreach($notifications_options as $key => $title)
		{
			$data = [
				'n_uid'		=> $user_id,
				'n_to'		=> $rules_to,
				'n_key'		=> $key,
				'n_value'	=> 0
			];
			$DBLayer->insert('user_notifications', $data);
		}
	}
	else if ($group_id > 0 && $type == 4)
	{
		foreach($access_options as $key => $title)
		{
			$data = [
				'a_gid'		=> $group_id,
				'a_to'		=> $rules_to,
				'a_key'		=> $key,
				'a_value'	=> 0
			];
			$DBLayer->insert('user_access', $data);
		}
	}
	else if ($group_id > 0 && $type == 5)
	{
		foreach($permissions_options as $key => $title)
		{
			$data = [
				'p_gid'		=> $group_id,
				'p_to'		=> $rules_to,
				'p_key'		=> $key,
				'p_value'	=> 0
			];
			$DBLayer->insert('user_permissions', $data);
		}
	}
	else if ($group_id > 0 && $type == 6)
	{
		foreach($notifications_options as $key => $title)
		{
			$data = [
				'n_gid'		=> $group_id,
				'n_to'		=> $rules_to,
				'n_key'		=> $key,
				'n_value'	=> 0
			];
			$DBLayer->insert('user_notifications', $data);
		}
	}
	else
		$Core->add_error('Select an user or group');

	if (empty($Core->errors))
	{
		// Add flash message
		$flash_message = 'Rules created';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_access']))
{
	$user_id = intval(key($_POST['delete_access']));

	$query = array(
		'DELETE'	=> 'user_access',
		'WHERE'		=> 'a_to=\''.$DBLayer->escape($rules_to).'\' AND a_uid='.$user_id,
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Access deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['delete_group_access']))
{
	$gid = intval(key($_POST['delete_group_access']));

	$query = array(
		'DELETE'	=> 'user_access',
		'WHERE'		=> 'a_to=\''.$DBLayer->escape($rules_to).'\' AND a_gid='.$gid,
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Access deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['delete_permissions']))
{
	$user_id = intval(key($_POST['delete_permissions']));

	$query = array(
		'DELETE'	=> 'user_permissions',
		'WHERE'		=> 'p_to=\''.$DBLayer->escape($rules_to).'\' AND p_uid='.$user_id,
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Permissions deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['delete_notifications']))
{
	$user_id = intval(key($_POST['delete_notifications']));

	$query = array(
		'DELETE'	=> 'user_notifications',
		'WHERE'		=> 'n_to=\''.$DBLayer->escape($rules_to).'\' AND n_uid='.$user_id,
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Notifications deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['fix_access']))
{
	$user_id = intval(key($_POST['fix_access']));
	$access_keys = $_POST['access_keys'][$user_id];

	foreach($access_options as $key => $title)
	{
		$data = [
			'a_uid'		=> $user_id,
			'a_to'		=> $rules_to,
			'a_key'		=> $key,
			'a_value'	=> 0
		];
		if (!in_array($key, $access_keys))
			$DBLayer->insert('user_access', $data);
	}
	
	// Add flash message
	$flash_message = 'User permissions fixed.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['fix_group_access']))
{
	$group_id = intval(key($_POST['fix_group_access']));
	$access_keys = isset($_POST['access_keys'][$group_id]) ? $_POST['access_keys'][$group_id] : [];

	foreach($access_options as $key => $title)
	{
		$data = [
			'a_gid'		=> $group_id,
			'a_to'		=> $rules_to,
			'a_key'		=> $key,
			'a_value'	=> 0
		];
		if (!empty($access_keys) && !in_array($key, $access_keys))
			$DBLayer->insert('user_access', $data);
	}
	
	// Add flash message
	$flash_message = 'Group permissions fixed.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['fix_permissions']))
{
	$user_id = intval(key($_POST['fix_permissions']));
	$permissions_keys = $_POST['permissions_keys'][$user_id];

	foreach($permissions_options as $key => $title)
	{
		$data = [
			'p_uid'		=> $user_id,
			'p_to'		=> $rules_to,
			'p_key'		=> $key,
			'p_value'	=> 0
		];
		if (!in_array($key, $permissions_keys))
			$DBLayer->insert('user_permissions', $data);
	}
	
	// Add flash message
	$flash_message = 'Permissions updated.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['fix_notifications']))
{
	$user_id = intval(key($_POST['fix_notifications']));
	$notifications_keys = $_POST['notifications_keys'][$user_id];

	foreach($notifications_options as $key => $title)
	{
		$data = [
			'n_uid'		=> $user_id,
			'n_to'		=> $rules_to,
			'n_key'		=> $key,
			'n_value'	=> 0
		];
		if (!in_array($key, $notifications_keys))
			$DBLayer->insert('user_notifications', $data);
	}
	
	// Add flash message
	$flash_message = 'Notifications updated.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['add_vendor']))
{
	$vendor_id = intval(key($_POST['add_vendor']));

	if ($vendor_id > 0)
	{
		foreach($default_services as $key => $title)
		{
			$data = [
				'vendor_id'		=> $vendor_id,
				'group_id'		=> $key,
				'enabled'		=> 0
			];
			$DBLayer->insert('hca_vcr_vendors', $data);
		}

		// Add flash message
		$flash_message = 'Vendor filter created';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_settings').'#vid'.$vendor_id, $flash_message);
	}
}
//
else if (isset($_POST['remove_vendor']))
{
	$vendor_id = intval(key($_POST['remove_vendor']));

	if ($vendor_id > 0)
	{
		$DBLayer->delete('hca_vcr_vendors', 'vendor_id='.$vendor_id);

		$DBLayer->update('sm_vendors', ['hca_vcr' => 0], $vendor_id);

		// Add flash message
		$flash_message = 'Vendor removed';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_settings').'#vid'.$vendor_id, $flash_message);
	}
}

else if (isset($_POST['save_settings']))
{
	$Config->update($_POST['form']);
	
	if (isset($_POST['hca_vcr_groups']) && !empty($_POST['hca_vcr_groups']))
	{
		foreach ($_POST['hca_vcr_groups'] as $user_id => $val)
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'hca_vcr_groups=\''.$DBLayer->escape($val).'\'',
				'WHERE'		=> 'id='.$user_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}
	
	// Add flash message
	$flash_message = 'Settings has been updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.hca_vcr_access, u.hca_vcr_perms, u.hca_vcr_notify, u.hca_vcr_groups, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id > 2',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = $assigned_users = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	if ($fetch_assoc['hca_vcr_access'] > 0)
		$assigned_users[] = $fetch_assoc;
	else
		$users_info[] = $fetch_assoc;
}

$page_param['item_count'] = $page_param['fld_count'] = $page_param['group_count'] = 0;

$Core->set_page_id('hca_vcr_settings', 'hca_vcr');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
{
	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.hca_fs_group, g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'group_id > 2',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $fetch_assoc;
	}
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Create user permissions</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-12">

					<?php if (!empty($access_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio1" value="1" checked onchange="switchSection(1)">
						<label class="form-check-label fw-bold" for="radio1">User permissions</label>
					</div>
					<?php endif; ?>

					<?php if (!empty($permissions_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio2" value="2" onchange="switchSection(1)">
						<label class="form-check-label fw-bold" for="radio2">User Permissions</label>
					</div>
					<?php endif; ?>

					<?php if (!empty($notifications_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio3" value="3" onchange="switchSection(1)">
						<label class="form-check-label fw-bold" for="radio3">User Notification</label>
					</div>
					<?php endif; ?>

					<?php if (!empty($access_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio4" value="4" onchange="switchSection(2)">
						<label class="form-check-label fw-bold" for="radio4">Group permissions</label>
					</div>
					<?php endif; ?>

					<?php if (!empty($permissions_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio5" value="5" onchange="switchSection(2)">
						<label class="form-check-label fw-bold" for="radio5">Group Permissions</label>
					</div>
					<?php endif; ?>

					<?php if (!empty($notifications_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio6" value="6" onchange="switchSection(2)">
						<label class="form-check-label fw-bold" for="radio6">Group Notification</label>
					</div>
					<?php endif; ?>

				</div>	
			</div>
			<div class="row" id="users_list">
				<div class="col-md-4 mb-3">
					<select name="user_id" class="form-select form-select-sm">
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="" selected="selected" disabled>Select an user</option>'."\n";
	foreach ($users_info as $cur_user)
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				echo '</optgroup>';
			}
			echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
			$optgroup = $cur_user['group_id'];
		}
		
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
?>
					</select>
				</div>
			</div>
			<div class="row" id="group_list" style="display:none">
				<div class="col-md-4 mb-3">
					<select name="group_id" class="form-select form-select-sm">
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="" selected="selected" disabled>Select a group</option>'."\n";
	foreach ($groups_info as $cur_group)
	{
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
	}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<button type="submit" name="create" class="btn btn-primary btn-sm">Create</button>
			</div>
		</div>
	</div>
</form>
<?php
}

$query = [
	'SELECT'	=> 'a.*, u.group_id, u.realname',
	'FROM'		=> 'user_access AS a',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=a.a_uid'
		],
	],
	'WHERE'		=> 'a.a_to=\''.$DBLayer->escape($rules_to).'\'',
	'ORDER BY'	=> 'u.realname',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$access_info = $access_for_users = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$access_info[] = $row;

	if (!isset($access_for_users[$row['a_uid']]))
		$access_for_users[$row['a_uid']] = $row['realname'];
}

if (!empty($access_for_users))
{
?>
<div class="card-header">
	<h6 class="card-title mb-0">Available user permissions</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-sm table-striped">
		<thead>
			<tr>
				<th>Username</th>
<?php
	foreach($access_options as $key => $title)
		echo '<th>'.$title.'</th>';
?>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
	$num_options = count($access_options);
	foreach($access_for_users as $user_id => $username)
	{
		echo '<tr>';
		echo '<td><h6><a href="'.$URL->link('user', $user_id).'">'.html_encode($username).'</a></h6></td>';
		
		$a = 0;
		foreach($access_options as $key => $title)
		{
			$cur_info = settings_get_access($access_info, $user_id, $key);

			if (!empty($cur_info) && $cur_info['a_key'] == $key)
			{
				echo '<input type="hidden" name="access_keys['.$user_id.'][]" value="'.$key.'">';

				$checked = ($cur_info['a_value'] == 1) ? 'checked' : '';
				echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(1, '.$cur_info['id'].')" id="access_'.$cur_info['id'].'" '.$checked.'></div></td>';

				++$a;
			}
		}

		if ($num_options > $a)
			echo '<td><button type="submit" name="fix_access['.$user_id.']" class="badge bg-success ms-1">Update</button></td>';

		echo '<td><button type="submit" name="delete_access['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';

		echo '</tr>';
	}
?>
		</tbody>
	</table>
</form>
<?php
}

$query = [
	'SELECT'	=> 'a.*, g.g_id, g.g_title',
	'FROM'		=> 'user_access AS a',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'groups AS g',
			'ON'			=> 'g.g_id=a.a_gid'
		],
	],
	'WHERE'		=> 'a.a_to=\''.$DBLayer->escape($rules_to).'\'',
	'ORDER BY'	=> 'g.g_title',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$access_info = $access_for_groups = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$access_info[] = $row;

	if (!isset($access_for_groups[$row['g_id']]))
		$access_for_groups[$row['g_id']] = $row['g_title'];
}

if (!empty($access_for_groups))
{
?>
<div class="card-header">
	<h6 class="card-title mb-0">Available group permissions</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-sm table-striped">
		<thead>
			<tr>
				<th>Group name</th>
<?php
	foreach($access_options as $key => $title)
		echo '<th>'.$title.'</th>';
?>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
	$num_options = count($access_options);
	foreach($access_for_groups as $gid => $username)
	{
		echo '<tr>';
		echo '<td><h6>'.html_encode($username).'</h6></td>';

		$a = 0;
		foreach($access_options as $key => $title)
		{
			$cur_info = settings_get_group_access($access_info, $gid, $key);

			if (!empty($cur_info) && $cur_info['a_key'] == $key)
			{
				echo '<input type="hidden" name="access_keys['.$gid.'][]" value="'.$key.'">';

				$checked = ($cur_info['a_value'] == 1) ? 'checked' : '';
				echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(1, '.$cur_info['id'].')" id="access_'.$cur_info['id'].'" '.$checked.'></div></td>';

				++$a;
			}
		}

		if ($num_options > $a)
			echo '<td><button type="submit" name="fix_group_access['.$gid.']" class="badge bg-success ms-1">Update</button></td>';

		echo '<td><button type="submit" name="delete_group_access['.$gid.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';

		echo '</tr>';
	}
?>
		</tbody>
	</table>
</form>
<?php
}

$query = [
	'SELECT'	=> 'n.*, u.group_id, u.realname',
	'FROM'		=> 'user_notifications AS n',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=n.n_uid'
		],
	],
	'WHERE'		=> 'n.n_to=\''.$DBLayer->escape($rules_to).'\'',
	'ORDER BY'	=> 'u.realname',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$notifications_info = $notifications_for_users = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$notifications_info[] = $row;

	if (!isset($notifications_for_users[$row['n_uid']]))
		$notifications_for_users[$row['n_uid']] = $row['realname'];
}

if (!empty($notifications_for_users))
{
?>
<div class="card-header">
	<h6 class="card-title mb-0">Available user notifications</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-sm table-striped">
		<thead>
			<tr>
				<th>Username</th>
<?php
	foreach($notifications_options as $key => $title)
		echo '<th>'.$title.'</th>';
?>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
	$num_options = count($notifications_options);
	foreach($notifications_for_users as $user_id => $username)
	{
		echo '<tr>';
		echo '<td><h6><a href="'.$URL->link('user', $user_id).'">'.html_encode($username).'</a></h6></td>';

		$n = 0;
		foreach($notifications_options as $key => $title)
		{
			$cur_info = get_cur_notification($notifications_info, $user_id, $key);

			if (!empty($cur_info) && $cur_info['n_key'] == $key)
			{
				echo '<input type="hidden" name="notifications_keys['.$user_id.'][]" value="'.$key.'">';

				$checked = ($cur_info['n_value'] == 1) ? 'checked' : '';
				echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(3, '.$cur_info['id'].')" id="notification_'.$cur_info['id'].'" '.$checked.'></div></td>';

				++$n;
			}
		}

		if ($num_options > $n)
			echo '<td><button type="submit" name="fix_notifications['.$user_id.']" class="badge bg-success ms-1">Update</button></td>';

		echo '<td><button type="submit" name="delete_notifications['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all notifications for this user?\')">Delete</button></td>';

		echo '</tr>';
	}
?>
		</tbody>
	</table>
</form>
<?php
}

$query = array(
	'SELECT'	=> 'v.*',
	'FROM'		=> 'sm_vendors AS v',
	'WHERE'		=> 'v.hca_vcr=1',
	'ORDER BY'	=> 'v.vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

$query = array(
	'SELECT'	=> 'v.*',
	'FROM'		=> 'hca_vcr_vendors AS v',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$default_vendors = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$default_vendors[] = $row;
}
?>	

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Default vendors</h6>
		</div>

		<table class="table table-sm table-bordered table-striped">
			<thead>
				<tr>
					<th rowspan="2" class="align-middle">Vendor name</th>
					<th colspan="9"><h6>Services</h6></th>
				</tr>
				<tr>
<?php
	foreach($default_services as $key => $title)
		echo '<th>'.$title.'</th>';
?>
					<th></th>
				</tr>
			</thead>
			<tbody>
<?php

function hca_vcr_check_vendor($default_vendors, $vendor_id, $key)
{
	if (!empty($default_vendors))
	{
		foreach($default_vendors as $cur_info)
		{
			if ($vendor_id == $cur_info['vendor_id'] && $key == $cur_info['group_id'])
				return $cur_info;
		}
		return [];
	}
	return [];
}

if (!empty($vendors_info))
{
	foreach($vendors_info as $cur_info)
	{
		echo '<tr id="vid'.$cur_info['id'].'">';
		echo '<td><span class="fw-bold">'.html_encode($cur_info['vendor_name']).'</span></td>';

		foreach($default_services as $key => $title)
		{
			$default_vendor = hca_vcr_check_vendor($default_vendors, $cur_info['id'], $key);
			if (!empty($default_vendor))
			{
				if ($default_vendor['group_id'] == $key)
				{
					$checked = ($default_vendor['enabled'] == 1) ? 'checked' : '';
					echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateVendor('.$default_vendor['id'].')" id="vendor_'.$default_vendor['id'].'" '.$checked.'></div></td>';
				}
			}
			else
			{
				echo '<td colspan="8"><button type="submit" name="add_vendor['.$cur_info['id'].']" class="badge bg-primary ms-1 float-end">Create filter</button></td>';
				break;
			}
		}

		echo '<td><button type="submit" name="remove_vendor['.$cur_info['id'].']" class="badge bg-danger ms-1" onclick="return confirm(\'Are you sure you want to remove this vendor from list?\')">Remove</button></td>';
		echo '</tr>';
	}
}
?>
			</tbody>
		</table>




<!--		
		<div class="card-body mb-3">
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_carpet_vendor">Carpet service</label>
				<select id="fld_carpet_vendor" class="form-select" name="form[hca_vcr_default_carpet_vendor]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['id'] == $Config->get('o_hca_vcr_default_carpet_vendor'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_vinyl_vendor">Vinyl service</label>
				<select id="fld_vinyl_vendor" class="form-select" name="form[hca_vcr_default_vinyl_vendor]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['id'] == $Config->get('o_hca_vcr_default_vinyl_vendor'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_urine_vendor">Urine scan service</label>
				<select id="fld_urine_vendor" class="form-select" name="form[hca_vcr_default_urine_vendor]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['id'] == $Config->get('o_hca_vcr_default_urine_vendor'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_pest_vendor">Pest control service</label>
				<select id="fld_pest_vendor" class="form-select" name="form[hca_vcr_default_pest_vendor]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['id'] == $Config->get('o_hca_vcr_default_pest_vendor'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_cleaning_vendor">Pest control service</label>
				<select id="fld_cleaning_vendor" class="form-select" name="form[hca_vcr_default_cleaning_vendor]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['id'] == $Config->get('o_hca_vcr_default_cleaning_vendor'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_painter_vendor">Painting service</label>
				<select id="fld_painter_vendor" class="form-select" name="form[hca_vcr_default_painter_vendor]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['id'] == $Config->get('o_hca_vcr_default_painter_vendor'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_refinish_vendor">Refinish service</label>
				<select id="fld_refinish_vendor" class="form-select" name="form[hca_vcr_default_refinish_vendor]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($cur_info['id'] == $Config->get('o_hca_vcr_default_refinish_vendor'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
		</div>
-->


		<div class="card-header">
			<h6 class="card-title mb-0">Other settings</h6>
		</div>
		<div class="card-body mb-3">
			<div class="mb-3">
				<label class="form-label" for="fld_home_office_emails">Home Office emails</label>
				<textarea id="fld_home_office_emails" class="form-control" name="form[hca_vcr_home_office_emails]"><?php echo html_encode($Config->get('o_hca_vcr_home_office_emails')) ?></textarea>
				<label class="text-muted" for="fld_home_office_emails">Enter emails separated by commas</label>
			</div>
			<div class="col-md-4 mb-3">
				<label for="fld_complete_expired_days" class="form-label">Expired Days after Movein Date</label>
				<input type="number" id="fld_complete_expired_days" class="form-control" name="form[hca_vcr_complete_expired_days]" value="<?php echo $Config->get('o_hca_vcr_complete_expired_days') ?>">
				<label class="text-muted" for="fld_complete_expired_days">Set how many days must pass before overdue projects are to be marked as completed automatically. 0 - disabled.</label>
			</div>
			<button type="submit" name="save_settings" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<script>
function switchSection(id){
	if (id == 1){
		$('#users_list').css('display', 'block');
		$('#group_list').css('display', 'none');
	}else{
		$('#users_list').css('display', 'none');
		$('#group_list').css('display', 'block');
	}
}
function updateVendor(id){
	var val = 0;

	if($('#vendor_'+id).prop("checked") == true){val = 1;}
	else {val = 0;}

	var csrf_token = "<?php echo generate_form_token($URL->link('hca_vcr_ajax_update_default_vendor')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_vcr_ajax_update_default_vendor') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,val:val,csrf_token:csrf_token}),
		success: function(re){
			$(".msg-section").empty().html(re.message);
		},
		error: function(re){
			$(".msg-section").empty().html('Error: Please refresh this page and try again.');
		}
	});	
}
function updateRules(type,id){
	var val = 0;

	if (type == 1){
		if($('#access_'+id).prop("checked") == true){val = 1;}
		else if($('#access_'+id).prop("checked") == false){val = 0;}
	}else if(type == 2){
		if($('#permission_'+id).prop("checked") == true){val = 1;}
		else if($('#permission_'+id).prop("checked") == false){val = 0;}
	}else if(type == 3){
		if($('#notification_'+id).prop("checked") == true){val = 1;}
		else if($('#notification_'+id).prop("checked") == false){val = 0;}
	}

	var csrf_token = "<?php echo generate_form_token($URL->link('inc_ajax_update_apn')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('inc_ajax_update_apn') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({type:type,id:id,val:val,csrf_token:csrf_token}),
		success: function(re){
			$(".msg-section").empty().html(re.message);
		},
		error: function(re){
			$(".msg-section").empty().html('Error: Please refresh this page and try again.');
		}
	});	
}
</script>

<?php
require SITE_ROOT.'footer.php';