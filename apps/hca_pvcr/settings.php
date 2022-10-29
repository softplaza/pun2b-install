<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('hca_pvcr_access') == 5) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['add_new']))
{
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	
	if ($user_id > 0)
	{
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'hca_pvcr_access=1',
			'WHERE'		=> 'id='.$user_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'User #'.$user_id.' has been added to the VCR Projects';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['save_settings']))
{
	$schedule_access = isset($_POST['hca_pvcr_access']) ? $_POST['hca_pvcr_access'] : '';
	if (!empty($schedule_access))
	{
		foreach ($schedule_access as $id => $val)
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'hca_pvcr_access=\''.$DBLayer->escape($val).'\'',
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}
	
	if (isset($_POST['hca_pvcr_perms']) && !empty($_POST['hca_pvcr_perms']))
	{
		foreach ($_POST['hca_pvcr_perms'] as $id => $val)
		{
			$values = implode(',', $val);
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'hca_pvcr_perms=\''.$DBLayer->escape($values).'\'',
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}
	
	if (isset($_POST['hca_vcr_notify']) && !empty($_POST['hca_pvcr_notify']))
	{
		foreach ($_POST['hca_pvcr_notify'] as $id => $val)
		{
			$values = implode(',', $val);
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'hca_pvcr_notify=\''.$DBLayer->escape($values).'\'',
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

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.hca_pvcr_access, u.hca_pvcr_perms, u.hca_pvcr_notify, g.g_id, g.g_title',
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
	if ($fetch_assoc['hca_pvcr_access'] > 0)
		$assigned_users[] = $fetch_assoc;
	else
		$users_info[] = $fetch_assoc;
}

$page_param['item_count'] = $page_param['fld_count'] = $page_param['group_count'] = 0;

//$Core->set_page_title('Settings');
$Core->set_page_id('hca_pvcr_settings', 'hca_pvcr');
require SITE_ROOT.'header.php';
?>

<style>
.user-perms{margin-bottom: 1.5em;border-style: dashed;border-width: 1px;border-color: #d4e5f2;background: aliceblue;}
</style>

<div class="main-content main-frm">
<?php if ($User->is_admmod()) : ?>
	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		</div>
		<div class="content-head">
			<h6 class="hn"><span>Add Employees to Project</span></h6>
		</div>
		<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Members</span><small>Select a VCR Manager from dropdown list</small></label><br>
					<span class="fld-input"><select id="fld<?php echo ++$page_param['fld_count'] ?>" name="user_id" required>
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="" selected="selected" disabled>Select Empoyee</option>'."\n";
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
<?php endif; ?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
<?php
if (!empty($assigned_users) && $User->is_admmod())
{
?>
		<div class="content-head">
			<h6 class="hn"><span>Project Permissions</span></h6>
		</div>
		<fieldset class="frm-group frm-hdgroup group<?php echo ++$page_param['group_count'] ?>">
			<fieldset class="mf-set set<?php echo ++$page_param['item_count'] ?><?php echo ($page_param['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
<?php
$access_users = array(
	0 => 'Access denied',
	1 => 'Viewer of VCR',
	3 => 'Own Manager of VCR',
	4 => 'Manager of Inspectors',
	5 => 'Main Manager of VCR',
);

	foreach($assigned_users as $user)
	{
		$username = ($user['realname'] != 'NULL' ? $user['realname'] : $user['username']);
?>
				<div class="user-perms">
					<legend><span><a href="<?php echo $URL->link('user', $user['id']) ?>"><?php echo $username; ?></a></span></legend>
					<div class="mf-box">
						<div class="mf-field mf-field1 text">
							<span class="fld-input">
									<select id="fld<?php echo $page_param['fld_count'] ?>" name="hca_pvcr_access[<?php echo $user['id'] ?>]">
<?php
		foreach ($access_users as $key => $value)
		{
			if ($user['hca_pvcr_access'] == $key)
				echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$value.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
		}
?>
								</select>
							</span>
						</div>
					</div>
					<legend><span>Manager of: </span></legend>
					<div class="mf-box" style="display:inline-flex;">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" name="hca_vcr_groups[<?php echo $user['id'] ?>]" value="0" <?php echo ($user['hca_vcr_groups'] == 0 ? 'checked="checked"' : '') ?>></span>
							<label for="fld<?php echo ++$page_param['fld_count'] ?>">All Groups</label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" name="hca_vcr_groups[<?php echo $user['id'] ?>]" value="<?php echo $Config->get('o_hca_fs_maintenance') ?>" <?php echo ($user['hca_vcr_groups'] == $Config->get('o_hca_fs_maintenance') ? 'checked="checked"' : '') ?>></span>
							<label for="fld<?php echo ++$page_param['fld_count'] ?>">Maintenance</label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" name="hca_vcr_groups[<?php echo $user['id'] ?>]" value="<?php echo $Config->get('o_hca_fs_painters') ?>" <?php echo ($user['hca_vcr_groups'] == $Config->get('o_hca_fs_painters') ? 'checked="checked"' : '') ?>></span>
							<label for="fld<?php echo ++$page_param['fld_count'] ?>">Painters</label>
						</div>
					</div>
					
<?php
//Use function hca_vcr_check_perms($val)
$user_perms_array = array(
	1 => 'View Projects',
	2 => 'Create Projects',
	7 => 'Manage Projects',
	11 => 'Delete Projects',
	3 => 'View Vendor Schedule',
	4 => 'Manage Vendor Schedule',
	5 => 'View In-House Schedule',
	6 => 'Manage In-House Schedule',
	8 => 'View Property Schedule',
	9 => 'Manage Property Schedule',
	10 => 'Delete Vendors in Schedule',
);

$hca_vcr_perms_array = explode(',', $user['hca_vcr_perms']);
$hca_vcr_perms_fields = array();
foreach($user_perms_array as $key => $value) {
	if (in_array($key, $hca_vcr_perms_array))
		$hca_vcr_perms_fields[] = '<input type="checkbox" value="'.$key.'" checked="checked" name="hca_vcr_perms['.$user['id'].']['.$key.']">&nbsp'.$value.'&nbsp';
	else
		$hca_vcr_perms_fields[] = '<input type="checkbox" value="'.$key.'" name="hca_vcr_perms['.$user['id'].']['.$key.']">&nbsp'.$value.'&nbsp';
}
?>
					<legend><span>Permissions: </span></legend>
					<div class="mf-box">
						<div class="mf-field mf-field1 text">
							<span class="fld-input">
								<input type="hidden" value="0" checked="checked" name="hca_vcr_perms[<?php echo $user['id'] ?>][0]">
								<?php echo implode('&nbsp', $hca_vcr_perms_fields) ?>
							</span>
						</div>
					</div>
				
<?php
$user_notify_array = array(
	1 => 'Any Changes',
	2 => 'New Project',
	3 => 'MoveOut Date Changed',
	4 => 'Project Status Changed',
);

$hca_vcr_notify_array = explode(',', $user['hca_vcr_notify']);
$hca_vcr_notify_fields = array();
foreach($user_notify_array as $key => $value) {
	if (in_array($key, $hca_vcr_notify_array))
		$hca_vcr_notify_fields[] = '<input type="checkbox" value="'.$key.'" checked="checked" name="hca_vcr_notify['.$user['id'].']['.$key.']">&nbsp'.$value.'&nbsp';
	else
		$hca_vcr_notify_fields[] = '<input type="checkbox" value="'.$key.'" name="hca_vcr_notify['.$user['id'].']['.$key.']">&nbsp'.$value.'&nbsp';
}
?>
					<legend><span>Notifications: </span></legend>
					<div class="mf-box">
						<div class="mf-field mf-field1 text">
							<span class="fld-input">
								<input type="hidden" value="0" checked="checked" name="hca_vcr_notify[<?php echo $user['id'] ?>][0]">
								<?php echo implode('&nbsp', $hca_vcr_notify_fields) ?>
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
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="save_settings" value="Save changes"></span>
		</div>
	</form>
</div>

<?php
require SITE_ROOT.'footer.php';