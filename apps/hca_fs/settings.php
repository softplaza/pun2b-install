<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// Settings START
// Set project ID
$SwiftSettings->setId('hca_fs');

// Set User access, permissions and notifications.
$SwiftSettings->addAccessOption(1, 'Manage Technician');
$SwiftSettings->addAccessOption(2, 'Make request');
$SwiftSettings->addAccessOption(3, 'Property Requests');
$SwiftSettings->addAccessOption(4, 'Manage Maintenance');
$SwiftSettings->addAccessOption(5, 'Manage Painter');
$SwiftSettings->addAccessOption(6, 'Monthly Emergency Schedule');
$SwiftSettings->addAccessOption(7, 'Report');
$SwiftSettings->addAccessOption(8, 'Permanently Assignments');
$SwiftSettings->addAccessOption(9, 'Vacations');
$SwiftSettings->addAccessOption(10, 'Technician W.O.');
$SwiftSettings->addAccessOption(11, 'Properties Schedule');
$SwiftSettings->addAccessOption(14, 'View Technician Schedule');
$SwiftSettings->addAccessOption(12, 'Approve requests');
$SwiftSettings->addAccessOption(13, 'Add technician');
$SwiftSettings->addAccessOption(20, 'Settings');

//$SwiftSettings->addPermissionOption(10, 'PermissionOption');
//$SwiftSettings->addNotifyOption(1, 'NotifyOption');
// Settings END

$SwiftSettings->POST();


if (isset($_POST['save_settings']))
{
	$Config->update($_POST['form']);

	if (isset($_POST['maint_user_id']) || isset($_POST['paint_user_id']))
	{
		$maint_user_id = isset($_POST['maint_user_id']) ? intval($_POST['maint_user_id']) : 0;
		$paint_user_id = isset($_POST['paint_user_id']) ? intval($_POST['paint_user_id']) : 0;

		// Reset group
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'hca_fs_group=0',
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		if ($maint_user_id > 0 && $Config->get('o_hca_fs_maintenance') > 0)
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'hca_fs_group=\''.$Config->get('o_hca_fs_maintenance').'\'',
				'WHERE'		=> 'id='.$maint_user_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}

		if ($paint_user_id > 0 && $Config->get('o_hca_fs_painters') > 0)
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'hca_fs_group=\''.$Config->get('o_hca_fs_painters').'\'',
				'WHERE'		=> 'id='.$paint_user_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Add flash message
	$flash_message = 'Settings updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}


$Core->set_page_id('hca_fs_settings', 'hca_fs');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
{
	$SwiftSettings->createRule();
}

$SwiftSettings->getUserAccess();

$SwiftSettings->getGroupAccess();

$SwiftSettings->getUserNotifications();

$SwiftSettings->getJS();
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Group association</h6>
		</div>
		<div class="card-body mb-3">
<?php
$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g.g_id!='.USER_GROUP_GUEST.' AND g.g_id!='.USER_GROUP_ADMIN,
	'ORDER BY'	=> 'g.g_id'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$groups_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$groups_info[] = $row;
}
?>
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_hca_fs_maintenance">Maintenance group</label>
					<select class="form-select" id="fld_hca_fs_maintenance" name="form[hca_fs_maintenance]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select group</option>'."\n";
foreach ($groups_info as $cur_group)
{
	if ($cur_group['g_id'] == $Config->get('o_hca_fs_maintenance'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.html_encode($cur_group['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_maint_user_id">Manager of Maintenance</label>
					<select class="form-select" id="fld_maint_user_id" name="maint_user_id">
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" selected>Select an user</option>'."\n";
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	if ($cur_user['hca_fs_group'] == $Config->get('o_hca_fs_maintenance'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
					</select>
					<label>Set user who will build maintenance schedule</label>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_hca_fs_maintenance">Painter group</label>
					<select class="form-select" id="fld_hca_fs_painters" name="form[hca_fs_painters]">
<?php
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select group</option>'."\n";
foreach ($groups_info as $cur_group)
{
	if ($cur_group['g_id'] == $Config->get('o_hca_fs_painters'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.html_encode($cur_group['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_paint_user_id">Manager of Painters</label>
					<select class="form-select" id="fld_paint_user_id" name="paint_user_id">
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" selected>Select an user</option>'."\n";
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	if ($cur_user['hca_fs_group'] == $Config->get('o_hca_fs_painters'))
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
					</select>
					<label>Set user who will build painter schedule</label>
				</div>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Covering Weekends Settings</h6>
		</div>
		<div class="card-body mb-3">
			<div class="mb-3 col-md-4">
				<label class="form-label" for="fld_hca_fs_number_of_week">How many weeks ti display on Covering Weekends</label>
				<input type="number" id="fld_hca_fs_number_of_week" name="form[hca_fs_number_of_week]" value="<?php echo $Config->get('o_hca_fs_number_of_week') ?>" class="form-control">
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Other</h6>
		</div>
		<div class="card-body mb-3">
			<div class="mb-3">
				<label class="form-label" for="fld_hca_fs_geo_codes">GL Codes</label>
				<textarea id="fld_hca_fs_geo_codes" name="form[hca_fs_geo_codes]" class="form-control"><?php echo html_encode($Config->get('o_hca_fs_geo_codes')) ?></textarea>
			</div>

			<button type="submit" name="save_settings" class="btn btn-primary">Save changes</button>
		</div>
		
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';