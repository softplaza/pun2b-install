<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 53))
	message($lang_common['No permission']);


if (isset($_POST['update']))
{
	$form = array_map('trim', $_POST['form']);
	$Config->update($form);

	if (isset($_POST['default_maint']) && !empty($_POST['default_maint']))
	{
		foreach($_POST['default_maint'] as $key => $val)
		{
			$DBLayer->update('sm_property_db', ['default_maint' => $val], $key);
		}
	}

	$flash_message = 'Settings updated.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$Core->set_page_id('hca_wom_admin_settings', 'hca_fs');
require SITE_ROOT.'header.php';
?>	

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Settings</h6>
		</div>
		<div class="card-body">
			<div class="mb-3">
				<div class="form-check">
					<input type="hidden" name="form[hca_wom_notify_technician]" value="0">
					<input class="form-check-input" type="checkbox" name="form[hca_wom_notify_technician]" id="fld_hca_wom_notify_technician" value="1" <?php echo ($Config->get('o_hca_wom_notify_technician') == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_hca_wom_notify_technician">Notify Technician when a new task has been assigned.</label>
				</div>
			</div>
			<div class="mb-3">
				<div class="form-check">
					<input type="hidden" name="form[hca_wom_notify_managers]" value="0">
					<input class="form-check-input" type="checkbox" name="form[hca_wom_notify_managers]" id="fld_hca_wom_notify_managers" value="1" <?php echo ($Config->get('o_hca_wom_notify_managers') == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_hca_wom_notify_managers">Notify Property Manager when a new task has been completed.</label>
				</div>
			</div>
			<div class="mb-3">
				<div class="form-check">
					<input type="hidden" name="form[hca_wom_notify_inhouse_from_manager]" value="0">
					<input class="form-check-input" type="checkbox" name="form[hca_wom_notify_inhouse_from_manager]" id="fld_hca_wom_notify_inhouse_from_manager" value="1" <?php echo ($Config->get('o_hca_wom_notify_inhouse_from_manager') == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_hca_wom_notify_inhouse_from_manager">Notify In-House Department when a new request has been sent.</label>
				</div>
			</div>
			<div class="mb-3">
				<div class="form-check">
					<input type="hidden" name="form[hca_wom_notify_managers_from_inhouse]" value="0">
					<input class="form-check-input" type="checkbox" name="form[hca_wom_notify_managers_from_inhouse]" id="fld_hca_wom_notify_managers_from_inhouse" value="1" <?php echo ($Config->get('o_hca_wom_notify_managers_from_inhouse') == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_hca_wom_notify_managers_from_inhouse">Notify Property Manager when a request is added in the Facility Schedule.</label>
				</div>
			</div>
		</div>
	</div>

<?php
$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] .= ' AND p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
}
?>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Default Maintenance</h6>
		</div>
		<div class="card-body">
<?php
if (!empty($sm_property_db))
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
		'WHERE'		=> 'u.group_id=3',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users[] = $fetch_assoc;
	}

	foreach($sm_property_db as $cur_info)
	{
?>
			<div class="row">
				<div class="col-md-3 mb-2">
					<h6><?php echo html_encode($cur_info['pro_name']) ?></h6>
				</div>
				<div class="col-md-3 mb-2">
					<select name="default_maint[<?=$cur_info['id']?>]" class="form-select form-select-sm">
						<option value="0">Select one</option>
<?php
		foreach($users as $cur_user)
		{
			if ($cur_info['default_maint'] == $cur_user['id'])
				echo '<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>';
			else
				echo '<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>';
		}
?>
					</select>
				</div>
			</div>
<?php
	}
}
?>

			<button type="submit" name="update" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
