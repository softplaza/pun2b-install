<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['create']))
{
	$form_data = array();
	$form_data['group_name'] = isset($_POST['group_name']) ? swift_trim($_POST['group_name']) : '';

	if ($form_data['group_name'] == '')
		$Core->add_error('Name of Group can not be empty.');

	if (empty($Core->errors))
	{
		$new_pid = $DBLayer->insert_values('sm_vendors_groups', $form_data);
		
		$FlashMessenger->add_info('Information updated');
		redirect();
	}
}

if (isset($_POST['update']))
{
	$group_names = isset($_POST['group_name']) ? swift_trim($_POST['group_name']) : array();
	
	if (!empty($group_names))
	{
		foreach($group_names as $id => $group_name)
		{
			$form_data = array('group_name' => $group_name);
			$DBLayer->update_values('sm_vendors_groups', $id, $form_data);	
		}

		$FlashMessenger->add_info('Information updated');
		redirect();
	}
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors_groups',
	'ORDER BY'	=> 'group_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Vendor service group');
$Core->set_page_id('sm_vendors_groups', 'sm_vendors');
require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm">
	<div class="ct-group">
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Add a New Vendor Group</strong></span></label><br>
					<span class="fld-input"><input type="text" name="group_name" value="<?php echo isset($_POST['group_name']) ? html_encode($_POST['group_name']) : '' ?>" size="35" required /></span>
				</div>
			</div>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="create" value="Create" /></span>
			</div>
		</form>
	
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table>
				<thead>
					<tr>
						<th>Service Name</th>
					</tr>
				</thead>
				<tbody>
<?php
if (!empty($main_info))
{
	foreach ($main_info as $cur_info)
	{
?>
		<tr id="row<?php echo $cur_info['id'] ?>">
			<td class="payee-id"><span class="input"><input type="text" name="group_name[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['group_name']) ?>"/></span></td>
		</tr>
<?php
	}
}
?>
				</tbody>
			</table>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="Update" /></span>
			</div>
		</form>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';