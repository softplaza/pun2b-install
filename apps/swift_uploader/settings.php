<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_uploader', 20)) ? true : false;
if (!$User->is_admin())
	message($lang_common['No permission']);

$rules_to = 'swift_uploader';
$access_options = [
	1 => 'FileList',
	2 => 'Images',
	3 => 'Media Files',
	4 => 'Documents',

	11 => 'Manage Files',

	20 => 'Settings'
];

if (isset($_POST['create']))
{
	$type = isset($_POST['type']) ? intval($_POST['type']) : 0;
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

	if ($user_id > 0)
	{
		// Create Access
		if ($type == 1)
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

		if ($type == 2)
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

		if ($type == 3)
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
	}
	else
		$Core->add_error('Select an user to create rules.');

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

else if (isset($_POST['save_config']))
{
	$Config->update($_POST['form']);
	
	$flash_message = 'Settings updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$Core->set_page_id('swift_uploader_settings', 'swift_uploader');
require SITE_ROOT.'header.php';

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
			<h6 class="card-title mb-0">Create Permissions</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-12">
					<?php if (!empty($access_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio1" value="1" checked>
						<label class="form-check-label fw-bold" for="radio1">User's Access</label>
					</div>
					<?php endif; ?>
					<?php if (!empty($permissions_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio2" value="2">
						<label class="form-check-label fw-bold" for="radio2">User's Permissions</label>
					</div>
					<?php endif; ?>
					<?php if (!empty($notifications_options)) : ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="type" id="radio3" value="3">
						<label class="form-check-label fw-bold" for="radio3">User's Notification</label>
					</div>
					<?php endif; ?>
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
			<div class="mb-3">
				<button type="submit" name="create" class="btn btn-primary btn-sm">Create</button>
			</div>
		</div>
	</div>
</form>

<?php
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
	foreach($access_for_users as $user_id => $username)
	{
		echo '<tr>';
		echo '<td><h6><a href="'.$URL->link('user', $user_id).'">'.html_encode($username).'</a></h6></td>';
		echo '<td><button type="submit" name="delete_access['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';

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
?>


	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Uploader settings</h6>
			</div>
			<div class="card-body">

				<div class="mb-3">
					<label class="form-label" for="field_sm_uploader_structure">File structure</label>
					<select name="form[sm_uploader_structure]" class="form-select" id="field_sm_uploader_structure">
<?php
$file_sructure = [
	0 => 'uploads/Project_Name/YYYY/MM/',
	1 => 'uploads/YYYY/MM/Project_Name/'
];
foreach ($file_sructure as $key => $val)
{
	if ($Config->get('o_sm_uploader_structure') == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.html_encode($val).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.html_encode($val).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="mb-3">
					<label class="form-label" for="field_sm_uploader_image_types">Allowed image types</label>
					<input id="field_sm_uploader_image_types" class="form-control" type="text" name="form[sm_uploader_image_types]" value="<?php echo html_encode($Config->get('o_sm_uploader_image_types')) ?>">
				</div>
				<div class="mb-3">
					<label class="form-label" for="field_sm_uploader_file_types">Allowed file types</label>
					<input id="field_sm_uploader_file_types" class="form-control" type="text" name="form[sm_uploader_file_types]" value="<?php echo html_encode($Config->get('o_sm_uploader_file_types')) ?>">
				</div>
				<div class="mb-3">
					<label class="form-label" for="field_sm_uploader_media_types">Allowed media types</label>
					<input id="field_sm_uploader_media_types" class="form-control" type="text" name="form[sm_uploader_media_types]" value="<?php echo html_encode($Config->get('o_sm_uploader_media_types')) ?>">
				</div>

				<div class="mb-3">
					<button type="submit" name="save_config" class="btn btn-primary">Update settings</button>
				</div>

			</div>
		</div>
	</form>

<script>
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