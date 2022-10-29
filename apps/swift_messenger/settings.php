<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_messenger', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$rules_to = 'swift_messenger';
$access_options = [
	//1 => 'Settings',
	2 => 'Make request',
	3 => 'Property Requests',
	4 => 'Maintenance Weekly Schedule',
	5 => 'Painter Weekly Schedule',
	6 => 'Monthly Emergency Schedule',
	7 => 'Work Order Report',
	8 => 'Permanently Assignments',
	9 => 'Vacations',
	10 => 'Work Orders',
	11 => 'Properties Schedule',
	20 => 'Settings'
];

$permissions_options = [];
$notifications_options = [];

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
	print_dump($_POST);
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
	$flash_message = 'Permissions updated.';
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

else if (isset($_POST['save_settings']))
{
	$Config->update($_POST['form']);
	
	// Add flash message
	$flash_message = 'Settings updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['send_test']))
{
	$phone_number = isset($_POST['test_phone_number']) ? swift_trim($_POST['test_phone_number']) : '';

	if ($phone_number != '')
	{
		$text = 'Weekly schedule has been updated.
		To view the schedule follow this link:
		https://hcamanager.com/apps/hca_fs/weekly_schedule.php?gid=3&week_of=2022-02-10';
		$SwiftMessenger = new SwiftMessenger;
		$SwiftMessenger->send($text, $phone_number);

		// Add flash message
		$flash_message = 'Text message has been sent';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
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
	$users_info[] = $fetch_assoc;
}
$page_param['item_count'] = $page_param['fld_count'] = $page_param['group_count'] = 0;



$Core->set_page_id('hca_fs_settings', 'hca_fs');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
{
	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
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
			<h6 class="card-title mb-0">Create Access, Permissions or Notifications</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-12">

					<?php if (!empty($access_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio1" value="1" checked onchange="switchSection(1)">
						<label class="form-check-label fw-bold" for="radio1">User Access</label>
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

					<div style="display:none">
					<?php if (!empty($access_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio4" value="4" onchange="switchSection(2)">
						<label class="form-check-label fw-bold" for="radio4">Group Access</label>
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
			</div>
			<div class="row" id="users_list">
				<div class="col mb-3">
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
				<div class="col mb-3">
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
		<h6 class="card-title mb-0">Available User Access</h6>
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
	'SELECT'	=> 'p.*, u.group_id, u.realname',
	'FROM'		=> 'user_permissions AS p',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=p.p_uid'
		],
	],
	'WHERE'		=> 'p.p_to=\''.$DBLayer->escape($rules_to).'\'',
	'ORDER BY'	=> 'u.realname',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$permissions_info = $permissions_for_users = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$permissions_info[] = $row;

	if (!isset($permissions_for_users[$row['p_uid']]))
		$permissions_for_users[$row['p_uid']] = $row['realname'];
}

if (!empty($permissions_for_users))
{
?>
	<div class="card-header">
		<h6 class="card-title mb-0">Available User Permissions</h6>
	</div>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped">
			<thead>
				<tr>
					<th>Username</th>
					<th></th>
<?php
	foreach($permissions_options as $key => $title)
		echo '<th>'.$title.'</th>';
?>
				</tr>
			</thead>
			<tbody>
<?php

	
	$num_options = count($permissions_options);
	foreach($permissions_for_users as $user_id => $username)
	{
		echo '<tr>';
		echo '<td><h6>'.html_encode($username).'</h6></td>';
		echo '<td><button type="submit" name="delete_permissions['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';

		$p = 0;
		foreach($permissions_options as $key => $title)
		{
			$cur_info = settings_get_permission($permissions_info, $user_id, $key);

			if (!empty($cur_info) && $cur_info['p_key'] == $key)
			{
				echo '<input type="hidden" name="permissions_keys['.$user_id.'][]" value="'.$key.'">';

				$checked = ($cur_info['p_value'] == 1) ? 'checked' : '';
				echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(2, '.$cur_info['id'].')" id="permission_'.$cur_info['id'].'" '.$checked.'></div></td>';

				++$p;
			}
		}

		if ($num_options > $p)
			echo '<td><button type="submit" name="fix_permissions['.$user_id.']" class="badge bg-success ms-1">Update</button></td>';

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
					<th></th>
<?php
	foreach($notifications_options as $key => $title)
		echo '<th>'.$title.'</th>';
?>
				</tr>
			</thead>
			<tbody>
<?php
	$num_options = count($notifications_options);
	foreach($notifications_for_users as $user_id => $username)
	{
		echo '<tr>';
		echo '<td><h6>'.html_encode($username).'</h6></td>';
		echo '<td><button type="submit" name="delete_notifications['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all notifications for this user?\')">Delete</button></td>';

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
		<h6 class="card-title mb-0">Available Access of Groups</h6>
	</div>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped">
			<thead>
				<tr>
					<th>UGroup name</th>
					<th></th>
<?php
	foreach($access_options as $key => $title)
		echo '<th>'.$title.'</th>';
?>
				</tr>
			</thead>
			<tbody>
<?php
	$num_options = count($access_options);
	foreach($access_for_groups as $gid => $username)
	{
		echo '<tr>';
		echo '<td><h6>'.html_encode($username).'</h6></td>';
		echo '<td><button type="submit" name="delete_group_access['.$gid.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';

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

		echo '</tr>';
	}
?>
			</tbody>
		</table>
	</form>
<?php
}
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Provider information</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-8">
					<div class="mb-3">
						<label class="form-label" for="input_item_number">Providers</label>
						<select name="form[swift_messenger_app]" class="form-control">
							<option value="">Select App from list</option>
<?php
$packages = get_dir_list(SITE_ROOT.'apps/swift_messenger/vendor');
foreach($packages as $name)
{
	if ($Config->get('o_swift_messenger_app') == $name)
		echo '<option value="'.$name.'" selected>'.html_encode($name).'</option>';
	else
		echo '<option value="'.$name.'">'.html_encode($name).'</option>';
}
?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label" for="fld_swift_messenger_number">Phone Number</label>
						<input type="text" name="form[swift_messenger_number]" value="<?php echo html_encode($Config->get('o_swift_messenger_number')) ?>" class="form-control" id="fld_swift_messenger_number">
					</div>
					<div class="mb-3">
						<label class="form-label" for="fld_swift_messenger_sid">SID</label>
						<input type="text" name="form[swift_messenger_sid]" value="<?php echo html_encode($Config->get('o_swift_messenger_sid')) ?>" class="form-control" id="fld_swift_messenger_sid">
					</div>
					<div class="mb-3">
						<label class="form-label" for="fld_swift_messenger_token">Token</label>
						<input type="text" name="form[swift_messenger_token]" value="<?php echo html_encode($Config->get('o_swift_messenger_token')) ?>" class="form-control" id="fld_swift_messenger_token">
					</div>
				</div>
			</div>
			<button type="submit" name="save_settings" class="btn btn-primary">Update</button>
		</div>
	</div>
</form>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Test message</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-8">
					<div class="mb-3">
						<label class="form-label" for="fld_test_phone_number">Phone number</label>
						<input type="text" name="test_phone_number" value="" class="form-control" id="fld_test_phone_number">
					</div>
				</div>
			</div>
			<button type="submit" name="send_test" class="btn btn-primary">Test</button>
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