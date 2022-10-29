<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'WHERE'		=> 'id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = $DBLayer->fetch_assoc($result);

$SwiftUploader = new SwiftUploader;

if (isset($_POST['update_property']))
{
	$manager_id = isset($_POST['manager_id']) ? intval($_POST['manager_id']) : 0;
	$form_data = [
		'pro_name' => isset($_POST['pro_name']) ? swift_trim($_POST['pro_name']) : '',
		//'manager_id' => isset($_POST['manager_id']) ? intval($_POST['manager_id']) : 0,
		'manager_email' => isset($_POST['manager_email']) ? swift_trim($_POST['manager_email']) : '',
		'office_address' => isset($_POST['office_address']) ? swift_trim($_POST['office_address']) : '',
		'office_phone' => isset($_POST['office_phone']) ? swift_trim($_POST['office_phone']) : '',
		'office_fax' => isset($_POST['office_fax']) ? swift_trim($_POST['office_fax']) : '',
		'water_heater' => isset($_POST['water_heater']) ? intval($_POST['water_heater']) : 0,
		'hvac' => isset($_POST['hvac']) ? intval($_POST['hvac']) : 0,
		'washers' => isset($_POST['washers']) ? intval($_POST['washers']) : 0,
		'attics' => isset($_POST['attics']) ? intval($_POST['attics']) : 0,
		'furnace' => isset($_POST['furnace']) ? intval($_POST['furnace']) : 0,
		'zone' => isset($_POST['zone']) ? intval($_POST['zone']) : 0,
		'enabled' => isset($_POST['enabled']) ? intval($_POST['enabled']) : 0,
	];

	if ($form_data['pro_name'] == '')
		$Core->add_error('Property name cannot be empty.');
	
	if (empty($Core->errors))
	{
		$DBLayer->update('sm_property_db', $form_data, $id);

		if ($manager_id != $property_info['manager_id'])
		{
			// null old user
			if ($property_info['manager_id'] > 0)
			{
				$query = array(
					'UPDATE'	=> 'users',
					'SET'		=> 'sm_pm_property_id=0',
					'WHERE'		=> 'id='.$property_info['manager_id']
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}

			if ($manager_id > 0)
			{
				$query = array(
					'UPDATE'	=> 'users',
					'SET'		=> 'sm_pm_property_id='.$id,
					'WHERE'		=> 'id='.$manager_id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		
		// Add flash message
		$flash_message = 'Property info updated.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_property_management_edit_property', $id), $flash_message);
	}
}

else if (isset($_POST['upload_file']))
{
	if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name']))
	{
		$SwiftUploader->checkPath('sm_property_db');
		$file_path = 'uploads/sm_property_db/'.date('Y').'/'.date('m').'/';

		foreach($_FILES['file']['name'] as $key => $value)
		{
			$base_filename = basename($_FILES['file']['name'][$key]);
			$map_path = $file_path . $base_filename;

			if (move_uploaded_file($_FILES['file']['tmp_name'][$key], SITE_ROOT . $map_path))
			{
				$form_data = ['map_link' => $map_path];
				$DBLayer->update('sm_property_db', $form_data, $id);
			}
			else
				$Core->add_error('Could not upload file.');
			
			break;
		}

		if (empty($Core->errors))
		{
			$flash_message = 'Files has been uploaded to project #'.$id;
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
	}
}

else if (isset($_POST['delete_property']))
{
	$query = array(
		'DELETE'	=> 'sm_property_db',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'DELETE'	=> 'sm_property_units',
		'WHERE'		=> 'property_id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Property deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_property_management_properties_list'), $flash_message);
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
	'WHERE'		=> 'g_sm_property_mngr=1',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($user_data = $DBLayer->fetch_assoc($result))
{
	$users_info[$user_data['id']] = $user_data;
}

$query = array(
	'SELECT'	=> 'u.*, b.bldg_number',
	'FROM'		=> 'sm_property_units AS u',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'sm_property_buildings AS b',
			'ON'			=> 'b.id=u.bldg_id'
		],
	],
	'WHERE'		=> 'u.property_id='.$id,
	'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$units_info = array();
$units_counter = 0;
while ($data = $DBLayer->fetch_assoc($result))
{
	$units_info[] = $data;
	++$units_counter;
}

if ($property_info['enabled'] == '0')
	$Core->add_warning('The property has been disabled and will not appear in the property list.');

$Core->set_page_id('sm_property_management_properties_list', 'sm_property_management');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit property information</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_pro_name">Property name</label>
					<input id="fld_pro_name" class="form-control" type="text" name="pro_name" value="<?php echo isset($_POST['pro_name']) ? html_encode($_POST['pro_name']) : html_encode($property_info['pro_name']) ?>">
				</div>
				<div class="col-md-8">
					<label class="form-label" for="fld_pro_name">Enable/Disable</label>
					<div class="form-check">
						<input type="hidden" name="enabled" value="0">
						<input class="form-check-input" id="fld_enabled" type="checkbox" name="enabled" value="1" <?php echo ($property_info['enabled'] == '1' ? 'checked="checked"' : '') ?>>
						<label class="form-check-label" for="fld_enabled">Check this box to display the property in the list.</label>
					</div>

				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_manager_id">Manager</label>
					<select id="fld_manager_id" class="form-select" name="manager_id" required>
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
		
		if ($property_info['manager_id'] == $cur_user['id'])
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected="selected">'.html_encode($cur_user['realname']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_manager_email">Property email</label>
					<input type="text" name="manager_email" value="<?php echo isset($_POST['manager_email']) ? html_encode($_POST['manager_email']) : html_encode($property_info['manager_email']) ?>" id="fld_manager_email" class="form-control">
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-6">
					<label class="form-label" for="fld_office_address">Office adress</label>
					<input type="text" name="office_address" value="<?php echo isset($_POST['office_address']) ? html_encode($_POST['office_address']) : html_encode($property_info['office_address']) ?>" id="fld_office_address" class="form-control">
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_office_phone">Office phone #</label>
					<input type="text" name="office_phone" value="<?php echo isset($_POST['office_phone']) ? html_encode($_POST['office_phone']) : html_encode($property_info['office_phone']) ?>" id="fld_office_phone" class="form-control">
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_office_fax">Office FAX</label>
					<input type="text" name="office_fax" value="<?php echo isset($_POST['office_fax']) ? html_encode($_POST['office_fax']) : html_encode($property_info['office_fax']) ?>" id="fld_office_fax" class="form-control">
				</div>
			</div>

			<div class="mb-3 col-md-4">
				<label class="form-label" for="fld_zone">Emergency zone</label>
				<select name="zone" class="form-select" id="fld_zone">
<?php
	$zone_array = array(1 => 'Zone 1', 2 => 'Zone 2', 3 => 'Zone 3');
	echo "\t\t\t\t\t\t".'<option value="0" selected="selected">Zone did not set</option>'."\n";
	foreach ($zone_array as $key => $val)
	{
		if ($property_info['zone'] == $key)
			echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
	}
?>
				</select>
			</div>
			

			<h6 class="card-title mb-0">Equipments:</h6>
			<hr class="my-2">
			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input type="hidden" name="water_heater" value="0">
					<input class="form-check-input" id="fld_water_heater" type="checkbox" name="water_heater" value="1" <?php echo ($property_info['water_heater'] == '1' ? 'checked="checked"' : '') ?>>
					<label class="form-check-label" for="fld_water_heater">Water Heater</label>
				</div>
				<div class="form-check form-check-inline">
					<input type="hidden" name="hvac" value="0">
					<input class="form-check-input" id="fld_hvac" type="checkbox" name="hvac" value="1" <?php echo ($property_info['hvac'] == '1' ? 'checked="checked"' : '') ?>>
					<label class="form-check-label" for="fld_hvac">HVAC</label>
				</div>
				<div class="form-check form-check-inline">
					<input type="hidden" name="washers" value="0">
					<input class="form-check-input" id="fld_washers" type="checkbox" name="washers" value="1" <?php echo ($property_info['washers'] == '1' ? 'checked="checked"' : '') ?>>
					<label class="form-check-label" for="fld_washers">Washers</label>
				</div>
				<div class="form-check form-check-inline">
					<input type="hidden" name="attics" value="0">
					<input class="form-check-input" id="fld_attics" type="checkbox" name="attics" value="1" <?php echo ($property_info['attics'] == '1' ? 'checked="checked"' : '') ?>>
					<label class="form-check-label" for="fld_attics">Attics</label>
				</div>
				<div class="form-check form-check-inline">
					<input type="hidden" name="furnace" value="0">
					<input class="form-check-input" id="fld_furnace" type="checkbox" name="furnace" value="1" <?php echo ($property_info['furnace'] == '1' ? 'checked="checked"' : '') ?>>
					<label class="form-check-label" for="fld_furnace">Furnace</label>
				</div>
			</div>

			<button type="submit" name="update_property" class="btn btn-primary">Update</button>
			<a href="<?php echo $URL->link('sm_property_management_properties_list') ?>" class="btn btn-secondary text-white">Cancel</a>

<?php if ($User->checkAccess('swift_property_management', 13)): ?>
			<button type="submit" name="delete_property" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this property?')">Delete</button>
<?php endif; ?>

		</div>
	</div>
</form>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Property Map</h6>
		</div>
		<div class="card-body">

		<?php if ($property_info['map_link'] != ''): ?>
			<style>#pdf_preview{width: 100%;height: 400px;zoom: 2;}</style>
			<iframe id="pdf_preview" src="<?php echo BASE_URL.'/'.$property_info['map_link'].'?'.time() ?>"></iframe>
		<?php endif; ?>

			<?php $SwiftUploader->setForm();?>

			<button type="submit" name="upload_file" class="btn btn-primary" id="btn_upload_file">Upload file</button>

		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';