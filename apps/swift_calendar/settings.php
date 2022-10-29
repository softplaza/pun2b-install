<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('sm_calendar_access') == '5') ? true : false;
if (!$User->is_admmod())
	message($lang_common['No permission']);

if (isset($_POST['add_new']))
{
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$new_access = isset($_POST['access']) ? intval($_POST['access']) : 1;
	
	if ($user_id > 0 && $new_access > 0)
	{
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'sm_calendar_access='.$new_access,
			'WHERE'		=> 'id='.$user_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Access updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	
}
else if (isset($_POST['update']))
{
	$vendor_name = isset($_POST['vendor_name']) ? $_POST['vendor_name'] : '';
	$arr_new_vendors = array();
	if (!empty($vendor_name)) {
		foreach($vendor_name as $key => $name) {
			if (isset($_POST['vendor_color'][$key]) && ($name != ''))
				$arr_new_vendors[$_POST['vendor_color'][$key]] = $name;
		}
	}
	
	$Config->update($_POST['form']);
	
	if (isset($_POST['sm_calendar_access']))
	{
		$projects_access = $_POST['sm_calendar_access'];
		foreach ($projects_access as $id => $val) {
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'sm_calendar_access=\''.$DBLayer->escape($val).'\'',
				'WHERE'		=> 'id='.$id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Add flash message
	$flash_message = 'Settings updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$page_param['item_count'] = $page_param['fld_count'] = $page_param['group_count'] = 0;

$Core->set_page_title('Settings');
$Core->set_page_id('sm_calendar_settings', 'sm_calendar');
require SITE_ROOT.'header.php';
?>

<style>
input[type="color"]{margin-left: .5em;}
</style>

<div class="main-content main-frm">
	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		</div>
<?php

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.sm_calendar_access, g.g_id, g.g_title',
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
while ($row = $DBLayer->fetch_assoc($result)) {
	if ($row['sm_calendar_access'] > 0)
		$assigned_users[] = $row;
	else
		$users_info[] = $row;
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
			<h6 class="hn"><span>List of assigned Employees</span></h6>
		</div>
		<fieldset class="frm-group frm-hdgroup group<?php echo ++$page_param['group_count'] ?>">
			<fieldset class="mf-set set<?php echo ++$page_param['item_count'] ?><?php echo ($page_param['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
<?php
$access_users = array(
	0 => 'Access denied',
	1 => 'View Projects',
	3 => 'OWN Manager',
	5 => 'MAIN Manager',
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
								<select id="fld<?php echo $page_param['fld_count'] ?>" name="sm_calendar_access[<?php echo $user['id'] ?>]">
<?php
		foreach ($access_users as $key => $value)
		{
			if ($user['sm_calendar_access'] == $key)
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
			<h2 class="hn"><span>Mailing details settings</span></h2>
		</div>
		<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Mailing List</span><small>Insert emails separated by commas.</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="form[sm_calendar_mailing_list]" rows="3" cols="55"><?php echo html_encode($Config->get('o_sm_calendar_mailing_list')) ?></textarea></span></div>
				</div>
			</div>
		</fieldset>
		
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="update" value="Save changes"></span>
		</div>
	</form>
</div>

<?php
require SITE_ROOT.'footer.php';