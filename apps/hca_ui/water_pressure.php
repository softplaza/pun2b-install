<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_ui', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

if (isset($_POST['submit']))
{
	$form_data = [
		'property_id' 			=> intval($_POST['property_id']),
		'building_number'		=> swift_trim($_POST['building_number']),
		'pressure_current'		=> swift_trim($_POST['pressure_current']),
		'pressure_adjusted'		=> swift_trim($_POST['pressure_adjusted']),
		'date_completed'		=> date('Y-m-d'),
		'completed_by'			=> $User->get('id'),
		'status'				=> 1,
		'comment'				=> swift_trim($_POST['comment'])
	];

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select property from dropdown list.');

	if ($form_data['building_number'] == '')
		$Core->add_error('Enter building number.');

	if ($form_data['pressure_current'] == '')
		$Core->add_error('Enter current pressure.');

	//if ($form_data['pressure_adjusted'] == '')
	//	$Core->add_error('Enter adjusted pressure.');

	if (empty($Core->errors))
	{
		$DBLayer->insert('hca_ui_water_pressure', $form_data);

		// Add flash message
		$flash_message = 'Water pressure added';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_ui_water_pressure_report'), $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'property_id' 			=> intval($_POST['property_id']),
		'building_number'		=> swift_trim($_POST['building_number']),
		'pressure_current'		=> swift_trim($_POST['pressure_current']),
		'pressure_adjusted'		=> swift_trim($_POST['pressure_adjusted']),
		'comment'				=> swift_trim($_POST['comment'])
	];

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select property from dropdown list.');

	if ($form_data['building_number'] == '')
		$Core->add_error('Enter building number.');

	if ($form_data['pressure_current'] == '')
		$Core->add_error('Enter current pressure.');

	//if ($form_data['pressure_adjusted'] == '')
	//	$Core->add_error('Enter adjusted pressure.');

	$SwiftUploader->checkAllowed();
	$Core->add_errors($SwiftUploader->getErrors());
    $SwiftUploader->uploadFiles('hca_ui_water_pressure', $id);

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_ui_water_pressure', $form_data, $id);

		// Add flash message
		$flash_message = 'Water pressure updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_ui_water_pressure_report', $id), $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	if ($id > 0)
	{
		$DBLayer->delete('hca_ui_water_pressure', $id);
		
		// Add flash message
		$flash_message = 'Water Pressure was deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_ui_water_pressure_report', 0), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$Core->set_page_id('hca_ui_water_pressure', 'hca_ui');
require SITE_ROOT.'header.php';

if ($id > 0)
{
	$main_info = $DBLayer->select('hca_ui_water_pressure', $id);
?>

	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Building Water Pressure</h6>
			</div>
			<div class="card-body">
	
				<div class="row">
					<div class="col-md-4 mb-3">
						<label class="form-label" for="property_id">Property name</label>
						<select id="property_id" class="form-select" name="property_id" required>
	<?php
	echo '<option value="" selected disabled>Select Property</option>'."\n";
	foreach ($property_info as $cur_info)
	{
		if ($main_info['property_id'] == $cur_info['id'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	}
	?>
						</select>
					</div>
					<div class="col-md-4 mb-3">
						<label class="form-label" for="fld_building_number">Building number</label>
						<input type="text" name="building_number" value="<?php echo html_encode($main_info['building_number']) ?>" class="form-control" id="fld_building_number" maxlength="6">
					</div>
				</div>
	
				<div class="col-md-4 mb-3">
					<label class="form-label" for="fld_pressure_current">Current Pressure (psi)</label>
					<input type="number" name="pressure_current" value="<?php echo html_encode($main_info['pressure_current']) ?>" class="form-control" id="fld_pressure_current" min="0" max="999">
					<label class="text-muted">Enter an integer without letters.</label>
				</div>

				<div class="col-md-4 mb-3">
					<label class="form-label" for="fld_pressure_adjusted">Adjusted Pressure (psi)</label>
					<input type="number" name="pressure_adjusted" value="<?php echo html_encode($main_info['pressure_adjusted']) ?>" class="form-control" id="fld_pressure_adjusted" min="0" max="999">
					<label class="text-muted">Enter an integer without letters.</label>
				</div>
	
				<div class="mb-3">
					<label class="form-label" for="fld_comment">Comments</label>
					<textarea class="form-control" name="comment" id="fld_comment" placeholder="Leave your comment"><?php echo html_encode($main_info['comment']) ?></textarea>
				</div>

				<div class="mb-3">
					<button type="submit" name="update" class="btn btn-primary mb-1">Update</button>
<?php if ($User->checkAccess('hca_ui', 16)): ?>
					<button type="submit" name="delete" class="btn btn-danger mb-1" onclick="return confirm('Are you sure you want to delete it?')">Delete</button>
<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
	
<?php
$SwiftUploader = new SwiftUploader;

// Set up access for files uploading
$SwiftUploader->access_view_files = true;
if ($User->checkAccess('hca_ui', 18))
	$SwiftUploader->access_upload_files = true;
if ($User->checkAccess('hca_ui', 19))
	$SwiftUploader->access_delete_files = true;

$SwiftUploader->ajaxImages('hca_ui_water_pressure', $id);
$SwiftUploader->addJS();
?>

<script>
function closeModalWindow(){
	$('.modal .modal-title').empty().html('');
	$('.modal .modal-body').empty().html('');
	$('.modal .modal-footer').empty().html('');
}
</script>

<?php
	require SITE_ROOT.'footer.php';
}
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Building Water Pressure</h6>
		</div>
		<div class="card-body">

			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label" for="property_id">Property name</label>
					<select id="property_id" class="form-select" name="property_id" required>
<?php
echo '<option value="" selected disabled>Select Property</option>'."\n";
foreach ($property_info as $cur_info)
{
	if ($pid == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label" for="fld_building_number">Building number</label>
					<input type="text" name="building_number" value="" class="form-control" id="fld_building_number">
				</div>
			</div>

			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_pressure_current">Current Pressure (psi)</label>
				<input type="number" name="pressure_current" value="" class="form-control" id="fld_pressure_current" min="0" max="999" onchange="checkMaxNumber()" oninput="checkMaxNumber()">
				<label class="text-muted">Enter an integer without letters.</label>
			</div>

			<div id="box_adjusted_info" class="hidden">

				<div class="alert alert-danger my-3" role="alert">The entered value exceeds the water pressure limit. Please fill in the fields below.</div>

				<div class="col-md-4 mb-3">
					<label class="form-label text-danger" for="fld_pressure_adjusted">Adjusted Pressure (psi)</label>
					<input type="number" name="pressure_adjusted" value="" class="form-control" id="fld_pressure_adjusted" min="0" max="999">
					<label class="text-muted">Enter an integer without letters.</label>
				</div>
				<div class="mb-3">
					<label class="form-label text-danger" for="fld_comment">Comments</label>
					<textarea class="form-control" name="comment" id="fld_comment" placeholder="Leave your comment"></textarea>
				</div>
			</div>

			<div class="mb-3">
				<button type="submit" name="submit" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</div>
</form>

<script>
function checkMaxNumber(){
	var v = $('#fld_pressure_current').val();
	if (v > 58)
		$('#box_adjusted_info').css('display', 'block');
	else
		$('#box_adjusted_info').css('display', 'none');
}
</script>

<?php
require SITE_ROOT.'footer.php';
