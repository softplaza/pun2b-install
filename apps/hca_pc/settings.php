<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_pc', 20))
	message($lang_common['No permission']);

$rules_to = 'hca_pc';
$access_options = [
	// Pages
	1 => 'Active Projects',
	2 => 'Report',
	3 => 'Recycle',
	4 => 'Messages',

	// Actions
	11 => 'Create Projects',
	12 => 'Edit Projects',
	13 => 'Remove Projects',
	14 => 'Send emails to managers',
	
	// Admin Settings
	20 => 'Settings'
];

$notifications_options = [
	1 => 'Form submitted by manager',
	2 => 'Project completed',
	3 => 'Project removed',
];

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

else if (isset($_POST['add_new']))
{
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$new_access = isset($_POST['access']) ? intval($_POST['access']) : 1;
	
	if ($user_id > 0 && $new_access > 0)
	{
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'sm_pc_access='.$new_access,
			'WHERE'		=> 'id='.$user_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Access level '.$new_access.' has been provided for User #'.$user_id;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	
}
else if (isset($_POST['update']))
{
	$Config->update($_POST['form']);

	if (isset($_POST['sm_pc_access']))
	{
		$projects_access = $_POST['sm_pc_access'];
		foreach ($projects_access as $id => $val) {
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'sm_pc_access=\''.$DBLayer->escape($val).'\'',
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Add flash message
	$flash_message = 'Settings has been updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$Core->set_page_id('sm_pest_control_settings', 'hca_pc');
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
		<h6 class="card-title mb-0">Create rules</h6>
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
					<label class="form-check-label fw-bold" for="radio3">User notification</label>
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
		<h6 class="card-title mb-0">Available User Notifications</h6>
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







<div class="main-content main-frm">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
<?php
$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.sm_pc_access, g.g_id, g.g_title',
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
	if ($fetch_assoc['sm_pc_access'] > 0)
		$assigned_users[] = $fetch_assoc;
	else
		$users_info[] = $fetch_assoc;
}
?>
		<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>List of Employees</span><small>Select an employee from the dropdown list</small></label><br>
					<span class="fld-input"><select id="fld<?php echo ++$page_param['fld_count'] ?>" name="user_id" required>
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select Empoyee</option>'."\n";
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
					</select></span>
					<div class="mf-field text">
						<span class="submit primary"><input type="submit" name="add_new" value="+" /></span>
					</div>
				</div>
			</div>
		</fieldset>
	</form>

	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		</div>
		
<?php
if (!empty($assigned_users))
{
?>
		<div class="content-head">
			<h6 class="hn"><span>List of Managers</span></h6>
		</div>
		<fieldset class="frm-group frm-hdgroup group<?php echo ++$page_param['group_count'] ?>">
			<fieldset class="mf-set set<?php echo ++$page_param['item_count'] ?><?php echo ($page_param['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
<?php
$access_users = array(
	0 => 'Access denied',
	1 => 'Viewer of Projects',
//	3 => 'OWN Manager',
	5 => 'Manager of Projects',
);

	foreach($assigned_users as $user)
	{
		$username = ($user['realname'] != 'NULL' ? $user['realname'] : $user['username']);
?>
				<div class="user-access">
					<legend><span><a href="<?php echo $URL->link('user', $user['id']) ?>"><?php echo $username; ?></a></span></legend>
					<div class="mf-box">
						<div class="mf-field mf-field1 text">
							<span class="fld-input">
								<select id="fld<?php echo $page_param['fld_count'] ?>" name="sm_pc_access[<?php echo $user['id'] ?>]">
<?php
		foreach ($access_users as $key => $value)
		{
			if ($user['sm_pc_access'] == $key)
				echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$value.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
		}
?>
								</select>
							</span>
						</div>
					</div>
				</div>
<?php
	}
?>
			</fieldset>
		</fieldset>
<?php
}
?>
		<div class="content-head">
			<h2 class="hn"><span>Mailing settings and notifications</span></h2>
		</div>
		<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
			    <div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Manager's period notification</span><small>Set the time in hours after which the form reminder should be sent to the manager.</small></label><br>
					<span class="fld-input"><input type="number" id="fld<?php echo ++$page_param['fld_count'] ?>" name="form[sm_pest_control_manager_period_notify]" size="3" value="<?php echo $Config->get('o_sm_pest_control_manager_period_notify') ?>"></span>
				</div>
			</div>
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>New project mailing</span><small>Write a message to be sent to the manager when Pest Control Project  was completed.</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="form[sm_pest_control_manager_email_msg]" rows="3" cols="55"><?php echo html_encode($Config->get('o_sm_pest_control_manager_email_msg')) ?></textarea></span></div>
				</div>
			</div>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="update" value="Save changes"></span>
		</div>
	</form>
</div>

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