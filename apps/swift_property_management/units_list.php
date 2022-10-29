<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

// Get data from table
$sm_property_db = $DBLayer->select('sm_property_db', $id);

if (isset($_POST['update_units']))
{
	$unit_types = isset($_POST['unit_type']) ? $_POST['unit_type'] : [];
	if (!empty($unit_types))
	{
		$total_units = 0;
		foreach ($unit_types as $key => $value) 
		{
			$pos_x = isset($_POST['pos_x'][$key]) ? swift_trim($_POST['pos_x'][$key]) : '';
			$pos_y = isset($_POST['pos_y'][$key]) ? swift_trim($_POST['pos_y'][$key]) : '';
			$mbath = isset($_POST['mbath'][$key]) && ($_POST['mbath'][$key] == '1') ? '1' : '0';
			//$bldg_id = isset($_POST['bldg_id'][$key]) ? intval($_POST['bldg_id'][$key]) : 0;

			$DBLayer->update('sm_property_units', [
				'unit_type' => $value,
				'pos_x' => $pos_x,
				'pos_y' => $pos_y,
				'mbath' => $mbath,
				//'bldg_id' => $bldg_id // use ajax
			], $key);
			++$total_units;
		}
		
		$DBLayer->update('sm_property_db', ['total_units' => $total_units], $id);

		// Add flash message
		$flash_message = 'Units info has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_property_management_units_list', $id), $flash_message);
	}
}

else if (isset($_POST['new_unit']))
{
	$unit_number = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';
	$last_unit_number = isset($_POST['last_unit_number']) ? intval($_POST['last_unit_number']) : 0;
	$unit_type = isset($_POST['unit_type']) ? swift_trim($_POST['unit_type']) : '';
	$square_footage = isset($_POST['square_footage']) ? swift_trim($_POST['square_footage']) : '';
	
	$street_address = isset($_POST['street_address']) ? swift_trim($_POST['street_address']) : '';
	$city = isset($_POST['city']) ? swift_trim($_POST['city']) : '';
	$state = isset($_POST['state']) ? swift_trim($_POST['state']) : '';
	$zip_code = isset($_POST['zip_code']) ? intval($_POST['zip_code']) : '';
	
	if ($unit_number != '' && $id > 0)
	{
		if ($last_unit_number != '' && $last_unit_number > $unit_number)
		{
			for ($i = $unit_number; $i <= $last_unit_number; $i++)
			{
				$query = array(
					'INSERT'	=> 'property_id, unit_number, unit_type, square_footage, street_address, city, state, zip_code',
					'INTO'		=> 'sm_property_units',
					'VALUES'	=> ''.$id.', \''.$DBLayer->escape($i).'\', \''.$DBLayer->escape($unit_type).'\', \''.$DBLayer->escape($square_footage).'\', \''.$DBLayer->escape($street_address).'\', \''.$DBLayer->escape($city).'\', \''.$DBLayer->escape($state).'\', \''.$DBLayer->escape($zip_code).'\''
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		else
		{
			$query = array(
				'INSERT'	=> 'property_id, unit_number, unit_type, square_footage, street_address, city, state, zip_code',
				'INTO'		=> 'sm_property_units',
				'VALUES'	=> ''.$id.', \''.$DBLayer->escape($unit_number).'\', \''.$DBLayer->escape($unit_type).'\', \''.$DBLayer->escape($square_footage).'\', \''.$DBLayer->escape($street_address).'\', \''.$DBLayer->escape($city).'\', \''.$DBLayer->escape($state).'\', \''.$DBLayer->escape($zip_code).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'Unit has been added.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_property_management_units_list', $id), $flash_message);
	}
}

// Delete All units
else if (isset($_POST['delete_units']))
{
	$query = array(
		'DELETE'	=> 'sm_property_units',
		'WHERE'		=> 'property_id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Units has been deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_property_management_units_list', $id), $flash_message);
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
$units_info = [];
$total_units = 0;
while ($data = $DBLayer->fetch_assoc($result))
{
	$units_info[] = $data;
	++$total_units;
}

$sm_property_buildings = [];
$query = array(
	'SELECT'	=> 'b.id, b.bldg_number',
	'FROM'		=> 'sm_property_buildings AS b',
	'WHERE'		=> 'b.property_id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result))
	$sm_property_buildings[] = $row;

if ($sm_property_db['enabled'] == '0')
	$Core->add_warning('The property has been disabled and will not appear in the property list.');

$Core->set_page_title('List of '.html_encode($sm_property_db['pro_name']).' units');
$Core->set_page_id('sm_property_management_units_list', 'sm_property_management');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Add a new unit to <?php echo html_encode($sm_property_db['pro_name']) ?></h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_unit_number">Unit number</label>
					<input id="fld_unit_number" class="form-control" type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : '' ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_unit_type">Unit type</label>
					<input id="fld_unit_type" class="form-control" type="text" name="unit_type" value="<?php echo isset($_POST['unit_type']) ? html_encode($_POST['unit_type']) : '' ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_square_footage">Square footage</label>
					<input id="fld_square_footage" class="form-control" type="text" name="square_footage" value="<?php echo isset($_POST['square_footage']) ? html_encode($_POST['square_footage']) : '' ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="mb-3 col-md-9">
					<label class="form-label" for="fld_street_address">Street Address</label>
					<input id="fld_street_address" class="form-control" type="text" name="street_address" value="<?php echo isset($_POST['street_address']) ? html_encode($_POST['street_address']) : '' ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_city">City</label>
					<input id="fld_city" class="form-control" type="text" name="city" value="<?php echo isset($_POST['city']) ? html_encode($_POST['city']) : '' ?>">
				</div>
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_state">State</label>
					<input id="fld_state" class="form-control" type="text" name="state" value="<?php echo isset($_POST['state']) ? html_encode($_POST['state']) : '' ?>">
				</div>
				<div class="mb-3 col-md-3">
					<label class="form-label" for="fld_zip_code">ZIP Code</label>
					<input id="fld_zip_code" class="form-control" type="text" name="zip_code" value="<?php echo isset($_POST['zip_code']) ? html_encode($_POST['zip_code']) : '' ?>">
				</div>
			</div>

			<button type="submit" name="new_unit" class="btn btn-primary">Add unit</button>

		</div>
	</div>
</form>

<div class="card-header">
	<h6 class="card-title mb-0">List of units (total: <?php echo $sm_property_db['total_units'] ?>)</h6>
</div>
<?php
if (!empty($units_info))
{
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<table class="table table-sm table-striped table-bordered">
		<thead>
			<tr>
				<th>Unit Number</th>
				<th>Building #</th>
				<th>Unit Size</th>
				<th>Unit Key</th>
				<th>X-position</th>
				<th>Y-position</th>
				<th>MB</th>
				<th>HB</th>
			</tr>
		</thead>
		<tbody>

<?php
	foreach ($units_info as $cur_info)
	{
		$bldg_number = [];
		$bldg_number[] = '<select id="fld_bldg_id'.$cur_info['id'].'" onchange="updateUnit('.$cur_info['id'].')">';
		$bldg_number[] = '<option value="0" selected>BLDG #</option>';
		if (!empty($sm_property_buildings))
		{
			foreach($sm_property_buildings as $building_info)
			{
				if ($building_info['id'] == $cur_info['bldg_id'])
					$bldg_number[] = '<option value="'.$building_info['id'].'" selected>'.html_encode($building_info['bldg_number']).'</option>';
				else
					$bldg_number[] = '<option value="'.$building_info['id'].'">'.html_encode($building_info['bldg_number']).'</option>';
			}
		}
		$bldg_number[] = '</select>';

?>
			<tr>
				<td>
					<span class="fw-bold"><?php echo html_encode($cur_info['unit_number']) ?></span>
					<span class="float-end"><a href="<?php echo $URL->link('sm_property_management_edit_unit', $cur_info['id']) ?>" class="badge bg-primary text-white">Edit</a></span>
				</td>
				<td><?php echo implode("\n", $bldg_number) ?></td>
				<td><input type="text" name="unit_type[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['unit_type']) ?>"></td>
				<td><?php echo html_encode($cur_info['key_number']) ?></td>
				<td><input type="text" name="pos_x[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['pos_x']) ?>"></td>
				<td><input type="text" name="pos_y[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['pos_y']) ?>"></td>
				<td class="ta-center">
					<input type="checkbox" name="mbath[<?php echo $cur_info['id'] ?>]" value="1" <?php echo ($cur_info['mbath'] == '1' ? 'checked' : '') ?>></td>
				<td class="ta-center"><?php echo ($cur_info['hbath'] == '1' ? 'Yes' : '') ?></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
	<div class="fixed-bottom">
		<button type="submit" name="update_units" class="btn btn-primary float-end">Update</button>
	</div>
</form>
<?php
}
else
{
?>

<div class="alert alert-warning" role="alert">You have no items on this page or not found within your search criteria.</div>

<?php
}
?>


<div class="toast-messages fixed-bottom end-0"></div>

<script>
function updateUnit(unit_id){

	var bldg_id = $("#fld_bldg_id"+unit_id).val();

	var csrf_token = "<?php echo generate_form_token($URL->link('sm_property_management_ajax_update_unit')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('sm_property_management_ajax_update_unit') ?>",
		type:	"POST",
		dataType: "json",
		data: ({unit_id:unit_id,bldg_id:bldg_id,csrf_token:csrf_token}),
		success: function(re){
			$(".toast-messages").empty().html(re.toast_message);
		},
		error: function(re){
			$(".msg-section").empty().html('Error: Please refresh this page and try again.');
		}
	});	
}
/*
var toastElList = [].slice.call(document.querySelectorAll('.toast'));
var toastList = toastElList.map(function (toastEl) {
  return new bootstrap.Toast(toastEl, option);
});
*/
</script>

<?php
require SITE_ROOT.'footer.php';