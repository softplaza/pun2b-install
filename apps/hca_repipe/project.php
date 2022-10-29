<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access1 = ($User->checkAccess('hca_repipe', 1)) ? true : false; // new
$access2 = ($User->checkAccess('hca_repipe', 2)) ? true : false; // edit
if (!$access1 && $access2)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

if (isset($_POST['create']))
{
	$form_data = [
		'property_id' 			=> intval($_POST['property_id']),
		'unit_id' 				=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
		//'building_ids'			=> '',
		//'building_numbers'		=> '',
		//'unit_ids'				=> '',
		//'unit_numbers'			=> '',
		'created_by'			=> $User->get('id'),
		'created_time'			=> time(),
		'project_description'	=> swift_trim($_POST['project_description']),
		'project_manager_id'	=> intval($_POST['project_manager_id']),
		//'status'				=> intval($_POST['status']),
		'status'				=> 1
	];
/*
	$building_ids = $building_numbers = [];
	if (isset($_POST['building_number']) && !empty($_POST['building_number']))
	{
		foreach($_POST['building_number'] as $key => $val)
		{
			$building_ids[] = $key;
			$building_numbers[] = $val;
		}

		$form_data['building_ids'] = implode(',', $building_ids);
		$form_data['building_numbers'] = implode(', ', $building_numbers);
	}

	$unit_ids = $unit_numbers = [];
	if (isset($_POST['unit_number']) && !empty($_POST['unit_number']))
	{
		foreach($_POST['unit_number'] as $key => $val)
		{
			$unit_ids[] = $key;
			$unit_numbers[] = $val;
		}

		$form_data['unit_ids'] = implode(',', $unit_ids);
		$form_data['unit_numbers'] = implode(', ', $unit_numbers);
	}
*/
	if ($form_data['property_id'] == 0)
		$Core->add_error('Select property from dropdown list.');

	if ($form_data['unit_id'] == 0)
		$Core->add_error('Select an unit from dropdown.');

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert('hca_repipe_projects', $form_data);

		// Add flash message
		$flash_message = 'Re-Pipe project #'.$new_id.' has been created.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_repipe_project', $new_id), $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'project_description'	=> swift_trim($_POST['project_description']),
		'project_manager_id'	=> intval($_POST['project_manager_id']),
		'date_completed'		=> swift_trim($_POST['date_completed']),
		'status'				=> intval($_POST['status']),

		'vendor_id'				=> intval($_POST['vendor_id']),
		'date_start'			=> swift_trim($_POST['date_start']),
		'date_end'				=> swift_trim($_POST['date_end']),
		'po_number'				=> swift_trim($_POST['po_number']),
		'vendor_comment'		=> swift_trim($_POST['vendor_comment']),
	];

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_repipe_projects', $form_data, $id);

		// Add flash message
		$flash_message = 'Project #'.$id.' was updated.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_repipe_project', $id), $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	if ($id > 0)
	{
		$DBLayer->delete('hca_repipe_projects', $id);
		
		// Add flash message
		$flash_message = 'Project #'.$id.' was deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_repipe_projects', ''), $flash_message);
	}
}

$Core->set_page_id('hca_repipe_project', 'hca_repipe');
require SITE_ROOT.'header.php';

if ($access2 && $id > 0)
{
	$query = array(
		'SELECT'	=> 'pj.*, p.pro_name, un.unit_number',
		'FROM'		=> 'hca_repipe_projects AS pj',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=pj.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=pj.unit_id'
			],
		],
		'WHERE'		=> 'pj.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$main_info = $DBLayer->fetch_assoc($result);

	$query = array(
		'SELECT'	=> '*',
		'FROM'		=> 'sm_vendors',
		'WHERE'		=> 'hca_repipe=1',
		'ORDER BY'	=> 'vendor_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$vendors_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}

?>
<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Project information</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3">
					<label class="form-label" for="property_id">Property name</label>
					<h5><?php echo html_encode($main_info['pro_name']) ?></h5>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="unit_id">Unit number</label>
					<h5><?php echo html_encode($main_info['unit_number']) ?></h5>
				</div>	
<?php
/*
	$building_numbers = explode(',', $main_info['building_numbers']);
	if (!empty($main_info['building_numbers']) && $main_info['building_numbers'] != '')
	{
		$output = [];
		$output[] = '<label class="form-label" for="building">Buildings</label>';
		$output[] = '<div class="mb-0">';
		foreach ($building_numbers as $key => $value)
		{
			$output[] = '<a class="btn btn-outline-secondary fw-bold" href="#" role="button">'.$value.'</a>';
		}
		$output[] = '</div>';

		echo '<div class="col-md-auto mb-3">'.implode("\n", $output).'</div>';
	}

	$unit_numbers = explode(',', $main_info['unit_numbers']);
	if (!empty($unit_numbers) && $main_info['unit_numbers'] != '')
	{
		$output = [];
		$output[] = '<label class="form-label" for="units">Units</label>';
		$output[] = '<div class="mb-0">';
		foreach ($unit_numbers as $key => $value)
		{
			$output[] = '<a class="btn btn-outline-secondary fw-bold" href="#" role="button">'.$value.'</a>';
		}
		$output[] = '</div>';

		echo '<div class="col-md-auto mb-3">'.implode("\n", $output).'</div>';
	}	
*/
?>
			</div>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_project_manager_id">Project Manager</label>
					<select name="project_manager_id" class="form-select" id="fld_project_manager_id">
<?php
	$query = array(
		'SELECT'	=> 'u.id, u.realname',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.group_id=4 OR u.group_id=11',
		'ORDER BY'	=> 'u.realname'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$users[] = $row;
	}

	echo '<option value="0" selected>Select one</option>'."\n";
	foreach ($users as $user)
	{
		if ($main_info['project_manager_id'] == $user['id']) {
			echo "\t\t\t\t\t\t\t".'<option value="'.$user['id'].'" selected="selected">'.html_encode($user['realname']).'</option>'."\n";
		} else
			echo "\t\t\t\t\t\t\t".'<option value="'.$user['id'].'">'.html_encode($user['realname']).'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_date_completed">Date Completed</label>
					<input type="date" name="date_completed" class="form-control" id="fld_date_completed" value="<?php echo format_date($main_info['date_completed'], 'Y-m-d') ?>">
				</div>
				<div class="col-md-3">
				<label class="form-label" for="fld_status">Project Status</label>
					<select name="status" class="form-select" id="fld_status">
<?php
	$statuses = [
		//0 => 'On Hold',
		1 => 'Pending',
		2 => 'Completed for Hot',
		3 => 'Completed for Cold',
		4 => 'Completed',
	];

	foreach ($statuses as $key => $value)
	{
		if ($main_info['status'] == $key) {
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$value.'</option>'."\n";
		} else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
	}
?>
					</select>
				</div>
			</div>

			<div class="">
				<label class="form-label" for="fld_project_description">Project description</label>
				<textarea class="form-control" name="project_description" id="fld_project_description" placeholder="Leave your comment"><?php echo html_encode($main_info['project_description']) ?></textarea>
			</div>

		</div>
	</div>

	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Vendor information</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_vendor_id">Vendor</label>
					<select name="vendor_id" class="form-select" id="fld_vendor_id">
<?php
	echo '<option value="0" selected>Select one</option>'."\n";
	foreach ($vendors_info as $vendor)
	{
		if ($main_info['vendor_id'] == $vendor['id']) {
			echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'" selected="selected">'.html_encode($vendor['vendor_name']).'</option>'."\n";
		} else
			echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'">'.html_encode($vendor['vendor_name']).'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_po_number">PO Number</label>
					<input type="text" name="po_number" class="form-control" id="fld_po_number" value="<?php echo html_encode($main_info['po_number']) ?>">
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_date_start">Start Date</label>
					<input type="date" name="date_start" id="fld_date_start" class="form-control" value="<?php echo format_date($main_info['date_start'], 'Y-m-d') ?>">
					<label class="text-danger" onclick="document.getElementById('fld_date_start').value=''">Click to clear date</label>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_date_end">End Date</label>
					<input type="date" name="date_end" id="fld_date_end" class="form-control" value="<?php echo format_date($main_info['date_end'], 'Y-m-d') ?>">
					<label class="text-danger" onclick="document.getElementById('fld_date_end').value=''">Click to clear date</label>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_vendor_comment">Comments</label>
				<textarea class="form-control" id="fld_vendor_comment" name="vendor_comment" placeholder="Leave your comment"><?php echo html_encode($main_info['vendor_comment']) ?></textarea>
			</div>

			<div class="">
					<button type="submit" name="update" class="btn btn-primary mb-1">Update</button>
<?php if ($User->checkAccess('hca_repipe', 3)): ?>
				<button type="submit" name="delete" class="btn btn-danger mb-1" onclick="return confirm('Are you sure you want to delete it?')">Delete</button>
<?php endif; ?>
			</div>
		</div>
	</div>
</form>
	
<?php
	require SITE_ROOT.'footer.php';
}

// Else create new
$access1 = ($User->checkAccess('hca_repipe', 1)) ? true : false; // new
if (!$access1)
	message($lang_common['No permission']);

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
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">New Re-Pipe Project</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label" for="fld_property_id">Properties</label>
					<select id="fld_property_id" class="form-select" name="property_id" required onchange="getPropertyInfo()">
<?php
echo '<option value="0" selected disabled>Select one</option>'."\n";
foreach ($property_info as $cur_info)
{
	if ($property_id == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="row" id="unitlist_dropdown"></div>

			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label" for="fld_project_manager_id">Project Manager</label>
					<select name="project_manager_id" class="form-select" id="fld_project_manager_id">
<?php
	$query = array(
		'SELECT'	=> 'u.id, u.realname',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.group_id=4 OR u.group_id=11',
		'ORDER BY'	=> 'u.realname'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$users[] = $row;
	}

	echo '<option value="0" selected>Select one</option>'."\n";
	foreach ($users as $user)
	{
		if (isset($_POST['project_manager_id']) && $_POST['project_manager_id'] == $user['id']) {
			echo "\t\t\t\t\t\t\t".'<option value="'.$user['id'].'" selected="selected">'.html_encode($user['realname']).'</option>'."\n";
		} else
			echo "\t\t\t\t\t\t\t".'<option value="'.$user['id'].'">'.html_encode($user['realname']).'</option>'."\n";
	}
?>
					</select>
				</div>

<!--//hidden fields
				<div class="col-md-4 mb-3 hidden">
					<label class="form-label" for="fld_status">Project Status</label>
					<select name="status" class="form-select" id="fld_status">
<?php
	$statuses = [
		//0 => 'On Hold',
		1 => 'Pending',
		2 => 'Completed for Hot',
		3 => 'Completed for Cold',
		4 => 'Completed',
	];

	foreach ($statuses as $key => $value)
	{
		if (isset($_POST['status']) && $_POST['status'] == $key) {
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$value.'</option>'."\n";
		} else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
	}
?>
					</select>
				</div>
-->

			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_project_description">Project description</label>
				<textarea class="form-control" name="project_description" id="fld_project_description"></textarea>
			</div>
			<div class="mb-3">
				<button type="submit" name="create" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</div>
</form>

<script>
function getPropertyInfo(){
	getUnits();
	getBuildings();

	$("div").remove(".building-number-box");
	$("div").remove(".unit-number-box");
}
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_repipe_ajax_get_units')) ?>";
	var property_id = $("#fld_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_repipe_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		data: ({property_id:property_id,csrf_token:csrf_token}),
		success: function(re){
			$("#unitlist_dropdown").empty().html(re.unitlist_dropdown);
		},
		error: function(re){
			document.getElementById("#unitlist_dropdown").innerHTML = re;
		}
	});
}
function addUnit()
{
	var count = $('.unit-number-box').length;
	var val = $("#fld_unit_number").val();
	var text = $("#fld_unit_number").find("option:selected").text();
	
	var html = '<div class="form-check-inline unit-number-box" id="unit_number_'+val+'">';
	html += '<div class="mb-1 d-flex btn border border-secondary">';
	html += '<input type="hidden" name="unit_number['+val+']" value="'+text+'">';
	html += '<div class="toast-body fw-bold py-0 pe-1">'+text+'</div>';
	html += '<button type="button" class="btn-close" aria-label="Close" onclick="return confirm(\'Are you sure you want to delete it?\')?removeUnit('+val+'):\'\';"></button>';
	html += '</div></div>';

	if (val > 0 && count < 10)
		$( "#unit_numbers" ).after(html);
}
function removeUnit(id){
	$("div").remove("#unit_number_"+id);
}
function getBuildings(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_repipe_ajax_get_buildings')) ?>";
	var property_id = $("#fld_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_repipe_ajax_get_buildings') ?>",
		type:	"POST",
		dataType: "json",
		data: ({property_id:property_id,csrf_token:csrf_token}),
		success: function(re){
			$("#buildinglist_dropdown").empty().html(re.buildinglist_dropdown);
		},
		error: function(re){
			document.getElementById("#buildinglist_dropdown").innerHTML = re;
		}
	});
}
function addBLDG()
{
	var count = $('.building-number-box').length;
	var val = $("#fld_building_number").val();
	var text = $("#fld_building_number").find("option:selected").text();
	
	var html = '<div class="form-check-inline building-number-box" id="building_number_'+val+'">';
	html += '<div class="mb-1 d-flex btn border border-secondary">';
	html += '<input type="hidden" name="building_number['+val+']" value="'+text+'">';
	html += '<div class="toast-body fw-bold py-0 pe-1">'+text+'</div>';
	html += '<button type="button" class="btn-close" aria-label="Close" onclick="return confirm(\'Are you sure you want to delete it?\')?removeBLDG('+val+'):\'\';"></button>';
	html += '</div></div>';

	if (val > 0 && count < 10)
		$( "#building_numbers" ).after(html);
}
function removeBLDG(id){
	$("div").remove("#building_number_"+id);
}
</script>

<?php
require SITE_ROOT.'footer.php';
