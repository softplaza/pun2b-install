<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_property_management', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['add']))
{
	$property_name = isset($_POST['property_name']) ? swift_trim($_POST['property_name']) : '';
	$manager_id = isset($_POST['manager_id']) ? intval($_POST['manager_id']) : 0;
	$manager_email = isset($_POST['manager_email']) ? swift_trim($_POST['manager_email']) : '';
	
	if ($property_name == '')
		$Core->add_error('Property name cannot be empty.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'INSERT'	=> 'pro_name, manager_id, manager_email',
			'INTO'		=> 'sm_property_db',
			'VALUES'	=> '\''.$DBLayer->escape($property_name).'\', \''.$DBLayer->escape($manager_id).'\', \''.$DBLayer->escape($manager_email).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$new_id = $DBLayer->insert_id();
		
		$flash_message = 'Property added.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_property_management_edit_property', $new_id), $flash_message);
	}
	
}

// Grab the users
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
$users_info = array();
while ($user_data = $DBLayer->fetch_assoc($result))
{
	$users_info[$user_data['id']] = $user_data;
}

$page_param['item_count'] = $page_param['fld_count'] = $page_param['group_count'] = 0;

$Core->set_page_title('Create a new property');
$Core->set_page_id('sm_property_management_new_property', 'sm_property_management');
require SITE_ROOT.'header.php';
?>
	
<div class="main-content main-frm">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($URL->link('sm_property_management_new_property')) ?>" />
		<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo $page_param['fld_count'] ?>"><span>Property name</span><small></small></label><br>
					<span class="fld-input"><input type="text" id="fld<?php echo $page_param['fld_count'] ?>" name="property_name" value="<?php echo isset($_POST['property_name']) ? swift_trim($_POST['property_name']) : '' ?>" size="35" maxlength="255" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Manager of property</span><small>Select manager of property or write email of manager bellow</small></label><br>
					<span class="fld-input"><select id="fld<?php echo ++$page_param['fld_count'] ?>" name="manager_id" required>
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" selected="selected">Manager not assigned</option>'."\n";
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
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo $page_param['fld_count'] ?>"><span>Email of Manager</span><small></small></label><br>
					<span class="fld-input"><input type="text" id="fld<?php echo $page_param['fld_count'] ?>" name="manager_email" value="" size="35" maxlength="255" /></span>
				</div>
			</div>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="add" value="Create" /></span>
		</div>
	</form>
</div>
	
<?php
require SITE_ROOT.'footer.php';