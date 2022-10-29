<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

if (isset($_POST['update']))
{
	$form_info = [
		'unit_number' => swift_trim($_POST['unit_number']),
		'bldg_id' => intval($_POST['bldg_id']),
		'map_id' => intval($_POST['map_id']),
		'unit_type' => swift_trim($_POST['unit_type']),
		'square_footage' => swift_trim($_POST['square_footage']),
		'key_number' => swift_trim($_POST['key_number']),
		'street_address' => swift_trim($_POST['street_address']),
		'city' => swift_trim($_POST['city']),
		'state' => swift_trim($_POST['state']),
		'zip_code' => swift_trim($_POST['zip_code']),
		'mbath' => intval($_POST['mbath']),
		'hbath' => intval($_POST['hbath']),
	];

	if ($form_info['unit_number'] == '')
		$Core->add_error('Unit number cannot be empty.');

	if (empty($Core->errors))
	{
		$DBLayer->update('sm_property_units', $form_info, $id);

		// Add flash message
		$flash_message = 'Units info updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

// Delete All units
else if (isset($_POST['delete']))
{
	$query = array(
		'DELETE'	=> 'sm_property_units',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Unit deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_property_management_edit_property', $id), $flash_message);
}

$query = array(
	'SELECT'	=> 'u.*',
	'FROM'		=> 'sm_property_units AS u',
	'WHERE'		=> 'u.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$unit_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'b.id, b.bldg_number',
	'FROM'		=> 'sm_property_buildings AS b',
	'WHERE'		=> 'b.property_id='.$unit_info['property_id'],
	'ORDER BY'	=> 'LENGTH(b.bldg_number), b.bldg_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$buildings_info = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$buildings_info[] = $row;
}

$Core->set_page_id('sm_property_management_edit_unit', 'sm_property_management');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Update unit information</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_unit_number">Unit number</label>
					<input id="fld_unit_number" class="form-control" type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : html_encode($unit_info['unit_number']) ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_bldg_id">BLDG #</label>
					<select id="fld_bldg_id" class="form-select" name="bldg_id">
						<option value="0">No BLDG</option>
<?php
if (!empty($buildings_info))
{
	foreach($buildings_info as $building)
	{
		if ($building['id'] == $unit_info['bldg_id'])
			echo "\t\t\t\t\t\t".'<option value="'.$building['id'].'" selected="selected">'.html_encode($building['bldg_number']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$building['id'].'">'.html_encode($building['bldg_number']).'</option>'."\n";
	}
}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_map_id">Map #</label>
					<select id="fld_map_id" class="form-select" name="map_id">
						<option value="0">No map</option>
<?php
$query = array(
	'SELECT'	=> 'm.id, m.map_name',
	'FROM'		=> 'sm_property_maps AS m',
	'WHERE'		=> 'm.property_id='.$unit_info['property_id'],
	'ORDER BY'	=> 'LENGTH(m.map_title), m.map_title',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_maps = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$sm_property_maps[] = $row;
}

if (!empty($sm_property_maps))
{
	foreach($sm_property_maps as $cur_info)
	{
		if ($cur_info['id'] == $unit_info['map_id'])
			echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['map_name']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['map_name']).'</option>'."\n";
	}
}
?>
					</select>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_unit_type">Unit type</label>
					<input id="fld_unit_type" class="form-control" type="text" name="unit_type" value="<?php echo isset($_POST['unit_type']) ? html_encode($_POST['unit_type']) : html_encode($unit_info['unit_type']) ?>">
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_square_footage">Square footage</label>
					<input id="fld_square_footage" class="form-control" type="text" name="square_footage" value="<?php echo isset($_POST['square_footage']) ? html_encode($_POST['square_footage']) : html_encode($unit_info['square_footage']) ?>">
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_key_number">Key Number</label>
					<input id="fld_key_number" class="form-control" type="text" name="key_number" value="<?php echo isset($_POST['key_number']) ? html_encode($_POST['key_number']) : html_encode($unit_info['key_number']) ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="mb-3 col-md-8">
					<label class="form-label" for="fld_street_address">Street Address</label>
					<input id="fld_street_address" class="form-control" type="text" name="street_address" value="<?php echo isset($_POST['street_address']) ? html_encode($_POST['street_address']) : html_encode($unit_info['street_address']) ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_city">City</label>
					<input id="fld_city" class="form-control" type="text" name="city" value="<?php echo isset($_POST['city']) ? html_encode($_POST['city']) : html_encode($unit_info['city']) ?>">
				</div>
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_state">State</label>
					<input id="fld_state" class="form-control" type="text" name="state" value="<?php echo isset($_POST['state']) ? html_encode($_POST['state']) : html_encode($unit_info['state']) ?>">
				</div>
				<div class="mb-3 col-md-2">
					<label class="form-label" for="fld_zip_code">ZIP Code</label>
					<input id="fld_zip_code" class="form-control" type="text" name="zip_code" value="<?php echo isset($_POST['zip_code']) ? html_encode($_POST['zip_code']) : html_encode($unit_info['zip_code']) ?>">
				</div>
			</div>

			<h6 class="card-title mb-0">Locations:</h6>
			<hr class="my-2">
			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input type="hidden" name="mbath" value="0">
					<input class="form-check-input" id="fld_mbath" type="checkbox" name="mbath" value="1" <?php echo ($unit_info['mbath'] == '1' ? 'checked="checked"' : '') ?>>
					<label class="form-check-label" for="fld_mbath">Master Bathroom</label>
				</div>
				<div class="form-check form-check-inline">
					<input type="hidden" name="hbath" value="0">
					<input class="form-check-input" id="fld_hbath" type="checkbox" name="hbath" value="1" <?php echo ($unit_info['hbath'] == '1' ? 'checked="checked"' : '') ?>>
					<label class="form-check-label" for="fld_hbath">Half Bathroom</label>
				</div>
			</div>

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<a href="<?php echo $URL->link('sm_property_management_edit_property', $unit_info['property_id']) ?>" class="btn btn-secondary text-white">Cancel</a>
			<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this unit?')">Delete</button>

		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';