<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_pc', 11))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$reported_by_arr = array('MANAGER', 'RESIDENT', 'MAINTENANCE TECH', 'VENDOR');
$pest_problem = array('ROACHES','BED BUGS','RATS','MICE','FLEAS', 'TERMITES','GOPHERS','OTHER');
$vendors = array('TERMINIX','MVP','THRASHER');
$apt_locations = array('L/ROOM','D/ROOM','KITCHEN','HALLWAY','G/BATHROOM','M/BATHROOM','G/BEDROOM','M/BEDROOM','LAUNDRY','BALCONY','WHATER HEATER CLOSET','ATTICK','ENTIRE UNIT','LANDSCAPE');
$unit_clearance = array(0 => 'NO', 1 => 'YES', 2 => 'IN PROGRESS', 3 => 'ON HOLD');

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

if (isset($_POST['update']))
{
	$form_data = array(
		'property_id'	 	=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit'				=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',
		'location'			=> isset($_POST['location']) ? swift_trim($_POST['location']) : '',
		'created'			=> time(),
		'created_by'		=> $User->get('realname'),
		'reported'			=> strtotime($_POST['reported']),
		'reported_by'		=> isset($_POST['reported_by']) ? swift_trim($_POST['reported_by']) : '',
		'pest_problem'		=> isset($_POST['pest_problem']) ? swift_trim($_POST['pest_problem']) : '',
		'pest_action'		=> isset($_POST['pest_action']) ? swift_trim($_POST['pest_action']) : '',
		'inspection_date'	=> strtotime($_POST['inspection_date']),
		'vendor'			=> isset($_POST['vendor']) ? swift_trim($_POST['vendor']) : '',
		'start_date'		=> strtotime($_POST['start_date']),
		'manager_action'	=> isset($_POST['manager_action']) ? swift_trim($_POST['manager_action']) : '',
		'completion_date'	=> strtotime($_POST['completion_date']),
		'unit_clearance'	=> intval($_POST['unit_clearance']),
		'remarks'			=> isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : '',
		'link_hash'			=> random_key(5, true, true)
	);
	
	$DBLayer->update('sm_pest_control_records', $form_data, $id);
	
	// Add flash message
	$flash_message = 'Project has been updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['cancel']))
{
	// Add flash message
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_pest_control_active', 0), $flash_message);
}
else if (isset($_POST['remove']))
{
	$query = array(
		'UPDATE'	=> 'sm_pest_control_records',
		'SET'		=> 'unit_clearance=5',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Project #'.$id.' has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_pest_control_active', 0), $flash_message);
}

//Get cur project info
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name',
	'FROM'		=> 'sm_pest_control_records AS pj',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

$Core->set_page_id('sm_pest_control_new', 'hca_pc');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit project</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="property_id">Property name</label>
					<select id="property_id" class="form-select" name="property_id" onchange="getUnits()" required>
<?php
echo '<option value="" selected disabled>Select Property</option>'."\n";
foreach ($property_info as $cur_info)
{
	if ($project_info['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>

				<div class="col-md-4">
					<label class="form-label" for="fld_unit_number">Unit number</label>
					<div id="unit_number">
						<input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : html_encode($project_info['unit']) ?>" class="form-control" id="fld_unit_number">
					</div>
				</div>

				<div class="col-md-4">
					<label class="form-label" for="fld_location">Location</label>
					<input type="text" name="location" value="<?php echo (isset($_POST['location']) ? html_encode($_POST['location']) : html_encode($project_info['location'])) ?>" class="form-select" list="location" required>
					<datalist id="location">
<?php
echo '<option value="" selected disabled>Select Location</option>'."\n";
foreach ($apt_locations as $locations)
{
	echo "\t\t\t\t\t\t\t".'<option value="'.$locations.'">'."\n";
}
?>
					</datalist>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_reported">Reported date</label>
					<input class="form-control" id="fld_reported" type="date" name="reported" value="<?php echo format_time($project_info['reported'], 1, 'Y-m-d'); ?>" required>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_reported_by">Reported by</label>
					<select id="fld_reported_by" class="form-select" name="reported_by" required>
						<option value="" selected disabled>Select reported by</option>
<?php
foreach ($reported_by_arr as $val)
{
	if ($project_info['reported_by'] == $val)
		echo "\t\t\t\t\t\t\t".'<option value="'.$val.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$val.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-3 col-md-4">
				<label class="form-label" for="fld_pest_problem">Pest control problem</label>
				<input type="text" name="pest_problem" value="<?php echo (isset($_POST['pest_problem']) ? html_encode($_POST['pest_problem']) : html_encode($project_info['pest_problem'])) ?>" class="form-select" list="pest_problem" required>
				<datalist id="pest_problem">
<?php
foreach ($pest_problem as $val) {
	echo "\t\t\t\t\t\t\t".'<option value="'.$val.'">'."\n";
}
?>
				</datalist>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_pest_action">Pest control action</label>
				<textarea id="fld_pest_action" name="pest_action" class="form-control"><?php echo isset($_POST['pest_action']) ? html_encode($_POST['pest_action']) : html_encode($project_info['pest_action']) ?></textarea>
			</div>

			<div class="mb-3 col-md-4">
				<label class="form-label" for="fld_inspection_date">Surrounding inspection date</label>
				<input type="date" name="inspection_date" value="<?php echo isset($_POST['inspection_date']) ? html_encode($_POST['inspection_date']) : format_time($project_info['inspection_date'], 1, 'Y-m-d') ?>" id="fld_inspection_date" class="form-control">
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_vendor">Vendor</label>
					<select id="fld_vendor" class="form-select" name="vendor">
						<option value="" selected disabled>Select Vendor</option>
<?php
foreach ($vendors as $val)
{
	if ($project_info['vendor'] == $val)
		echo "\t\t\t\t\t\t\t".'<option value="'.$val.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$val.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_start_date">Start date</label>
					<input type="date" name="start_date" value="<?php echo isset($_POST['start_date']) ? html_encode($_POST['start_date']) : format_time($project_info['start_date'], 1, 'Y-m-d') ?>" id="fld_start_date" class="form-control">
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_manager_action">Notice for Manager</label>
				<textarea name="manager_action" id="fld_manager_action" class="form-control" placeholder="Leave comments if need manager attention"><?php echo isset($_POST['manager_action']) ? html_encode($_POST['manager_action']) : html_encode($project_info['manager_action']) ?></textarea>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_completion_date">Completion date</label>
					<input type="date" name="completion_date" value="<?php echo isset($_POST['completion_date']) ? html_encode($_POST['completion_date']) : format_time($project_info['completion_date'], 1, 'Y-m-d') ?>" id="fld_completion_date" class="form-control">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_unit_clearance">Unit clearence</label>
					<select id="fld_unit_clearance" class="form-select" name="unit_clearance">
<?php
$statuses = [0 => 'NO', 1 => 'YES', 2 => 'IN PROGRESS', 3 => 'ON HOLD'];
foreach ($statuses as $key => $value)
{
	if ($project_info['unit_clearance'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_remarks">Remarks</label>
				<textarea name="remarks" placeholder="Leave your remarks" id="fld_remarks" class="form-control"><?php echo isset($_POST['remarks']) ? html_encode($_POST['remarks']) : html_encode($project_info['remarks']) ?></textarea>
			</div>

			<button type="submit" name="update" class="btn btn-primary">Update project</button>
			<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
			<button type="submit" name="remove" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">Remove</button>
		</div>	
	</div>
</form>
	
<script>
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_pc_ajax_get_units')) ?>";
	var id = $("#property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_pc_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#unit_number").empty().html(re.unit_number);
		},
		error: function(re){
			document.getElementById("unit_number").innerHTML = re;
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';