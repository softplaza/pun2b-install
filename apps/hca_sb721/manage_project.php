<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sb721', 12)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Special Project does not exist or has been removed.');

$work_statuses = array(
	//0 => 'Removed',
	1 => 'Bid Phase',
	2 => 'Active Phase',
	3 => 'Completion Phase',
);

if (isset($_POST['update']))
{
	$form_data = [];
	$form_data['property_id'] = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$form_data['unit_number'] = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';
	$form_data['locations'] = isset($_POST['locations']) ? swift_trim($_POST['locations']) : '';
	$form_data['project_description'] = isset($_POST['project_description']) ? swift_trim($_POST['project_description']) : '';
	$form_data['symptoms'] = isset($_POST['symptoms']) ? swift_trim($_POST['symptoms']) : '';
	$form_data['action'] = isset($_POST['action']) ? swift_trim($_POST['action']) : '';

	$form_data['date_preinspection_start'] = isset($_POST['date_preinspection_start']) ? swift_trim($_POST['date_preinspection_start']) : '1000-01-01';
	$form_data['date_preinspection_end'] = isset($_POST['date_preinspection_end']) ? swift_trim($_POST['date_preinspection_end']) : '1000-01-01';
	$form_data['performed_by'] = isset($_POST['performed_by']) ? intval($_POST['performed_by']) : 0;

	$form_data['date_city_inspection_start'] = isset($_POST['date_city_inspection_start']) ? swift_trim($_POST['date_city_inspection_start']) : '1000-01-01';
	$form_data['date_city_inspection_end'] = isset($_POST['date_city_inspection_end']) ? swift_trim($_POST['date_city_inspection_end']) : '1000-01-01';
	$form_data['city_engineer'] = isset($_POST['city_engineer']) ? swift_trim($_POST['city_engineer']) : '';
	
	if (compare_dates($form_data['date_preinspection_start'], $form_data['date_preinspection_end'], 1))
		$Core->add_error('Preinspection Start Date cannot be greater than Preinspection End Date, check that the dates are correct.');

	if (compare_dates($form_data['date_city_inspection_start'], $form_data['date_city_inspection_end'], 1))
		$Core->add_error('Engineer Start Date cannot be greater than Engineer End Date, check that the dates are correct.');

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select property from dropdown list.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_sb721_projects', $form_data, $id);

		// Add flash message
		$flash_message = 'Project #'.$id.' has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['cancel']))
{
	// Add flash message
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_sb721_projects', ['active', $id]), $flash_message);
}

else if (isset($_POST['remove']))
{
	$query = array(
		'UPDATE'	=> 'hca_sb721_projects',
		'SET'		=> 'project_status=0',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Remove vendors
	$query = array(
		'DELETE'	=> 'hca_sb721_vendors',
		'WHERE'		=> 'project_id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Project #'.$id.' has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_sb721_projects', ['active', 0]), $flash_message);
}

else if (isset($_POST['add_vendor']))
{
	$form_data = array(
		'project_id'			=> $id,
		'vendor_id'				=> isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0,
		'date_bid'				=> isset($_POST['date_bid']) ? swift_trim($_POST['date_bid']) : '',
		'date_start_job'		=> isset($_POST['date_start_job']) ? swift_trim($_POST['date_start_job']) : '',
		'date_end_job'			=> isset($_POST['date_end_job']) ? swift_trim($_POST['date_end_job']) : '',
		'cost'					=> isset($_POST['cost']) ? swift_trim($_POST['cost']) : '',
		'comment'				=> isset($_POST['comment']) ? swift_trim($_POST['comment']) : '',
	);

	$form_data['cost'] = str_replace(',', '', $form_data['cost']);

	if ($form_data['vendor_id'] == 0)
		$Core->add_error('Select a vendor from dropdown list.');

	if ($form_data['date_start_job'] == '' && $form_data['date_end_job'] != '')
		$Core->add_error('Missed Job Start Date! Setup Job Start Date and update again.');

	if (compare_dates($form_data['date_start_job'], $form_data['date_end_job'], 1))
		$Core->add_error('Job Start Date cannot be greater than Job End Date, check that the dates are correct.');

	//if ($form_data['date_bid'] == '' && ($form_data['date_start_job'] != '' || $form_data['date_end_job'] != ''))
	//	$Core->add_error('Missed Bid Date! Setup Bid Date and update again.');

	//if (compare_dates($form_data['date_bid'], $form_data['date_start_job'], 1))
	//	$Core->add_error('Bid Date cannot be greater than Job Start Date, check that the dates are correct.');

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert_values('hca_sb721_vendors', $form_data);

		// Return back to Active
		//$DBLayer->update('hca_sb721_projects', ['project_status' => 1], $id);
		
		// Add flash message
		$flash_message = 'Vendor has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['update_vendor']))
{
	$vid = isset($_POST['vid']) ? intval($_POST['vid']) : 0;
	$form_data = array(
		'vendor_id'				=> isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0,
		'date_bid'				=> isset($_POST['date_bid']) ? swift_trim($_POST['date_bid']) : '',
		'date_start_job'		=> isset($_POST['date_start_job']) ? swift_trim($_POST['date_start_job']) : '',
		'date_end_job'			=> isset($_POST['date_end_job']) ? swift_trim($_POST['date_end_job']) : '',
		'cost'					=> isset($_POST['cost']) ? swift_trim($_POST['cost']) : '',
		'comment'				=> isset($_POST['comment']) ? swift_trim($_POST['comment']) : '',
	);

	$form_data['cost'] = str_replace(',', '', $form_data['cost']);

	if ($form_data['vendor_id'] == 0)
		$Core->add_error('Select a vendor from dropdown list.');

	if ($form_data['date_start_job'] == '' && $form_data['date_end_job'] != '')
		$Core->add_error('Missed Job Start Date! Setup Job Start Date and update again.');

	if (compare_dates($form_data['date_start_job'], $form_data['date_end_job'], 1))
		$Core->add_error('Job Start Date cannot be greater than Job End Date, check that the dates are correct.');

	if (empty($Core->errors) && $vid > 0)
	{
		$DBLayer->update('hca_sb721_vendors', $form_data, $vid);
		
		// Return back to Active
		//$DBLayer->update('hca_sb721_projects', ['project_status' => 1], $id);

		// Add flash message
		$flash_message = 'Vendor has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_vendor']))
{
	$vid = isset($_POST['vid']) ? intval($_POST['vid']) : 0;
	$DBLayer->delete('hca_sb721_vendors', $vid);
	
	// Add flash message
	$flash_message = 'Vendor has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

//Get cur project info
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u.realname',
	'FROM'		=> 'hca_sb721_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=pj.performed_by'
		),
	),
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'l.*',
	'FROM'		=> 'sm_property_locations AS l',
	'ORDER BY'	=> 'l.location_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$location_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$location_info[] = $row;
}

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

$query = array(
	'SELECT'	=> 'u.id, u.realname',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'user_access AS a',
			'ON'			=> 'u.id=a.a_uid'
		),
	),
	'ORDER BY'	=> 'u.realname',
	'WHERE'		=> 'a.a_to=\'hca_sb721\' AND a.a_key=16 AND a.a_value=1'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_managers = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$project_managers[] = $row;
}

$Core->set_page_id('hca_sb721_projects_manage', 'hca_sb721');
require SITE_ROOT.'header.php';
?>

<style>
#field_date_bid_start{background: #ffffbb;}
#field_date_active_start{background: #b9eeff;}
#field_date_complete_start{background: #b8ffb8;}
</style>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Project # <?php echo html_encode($project_info['project_number']) ?></h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_property_id">Property name</label>
					<select id="field_property_id" class="form-select" name="property_id" required onchange="getUnits()">
<?php
echo '<option value="0" selected="selected" disabled>Select a property</option>'."\n";
$property_selected = false;
foreach ($property_info as $cur_info)
{
	if ($project_info['property_id'] == $cur_info['id'])
	{
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
		$property_selected = true;
	}
	else if (!$property_selected && $project_info['property_id'] == 0 && $project_info['property_name'] == $cur_info['pro_name'])
	{
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	}
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label" for="field_unit_number">Unit number</label>
					<div id="unit_number">
						<input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : html_encode($project_info['unit_number']) ?>" class="form-control" id="field_unit_number">
					</div>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="select_location">List of Locations</label>
					<select id="select_location" class="form-select">
<?php
echo '<option value="" selected="selected" disabled>Select a Location</option>'."\n";
foreach ($location_info as $location) {
echo "\t\t\t\t\t\t\t".'<option value="'.$location['location_name'].'">'.html_encode($location['location_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-1" style="display:flex;flex-direction: column;justify-content: center;">
					<button type="button" class="btn btn-sm btn-success" onclick="addLocationSelect()">+</button>
				</div>
				<div class="col-md-8" id="cur_locations">
					<label class="form-label" for="field_locations">Locations</label>
					<textarea name="locations" id="field_locations" class="form-control"><?php echo html_encode($project_info['locations']) ?></textarea>
				</div>
			</div>
			<div class="mb-3">
				<label for="field_project_description">Project description</label>
				<textarea id="field_project_description" name="project_description" class="form-control"><?php echo isset($_POST['project_description']) ? html_encode($_POST['project_description']) : html_encode($project_info['project_description']) ?></textarea>
			</div>
			<div class="mb-3">
				<label for="field_symptoms">Symptoms</label>
				<textarea id="field_symptoms" name="symptoms" class="form-control"><?php echo isset($_POST['symptoms']) ? html_encode($_POST['symptoms']) : html_encode($project_info['symptoms']) ?></textarea>
			</div>
			<div class="mb-3">
				<label for="field_action">Action</label>
				<textarea id="field_action" name="action" class="form-control"><?php echo isset($_POST['action']) ? html_encode($_POST['action']) : html_encode($project_info['action']) ?></textarea>
			</div>
		</div>
	</div>


	<div class="card-header">
		<h6 class="card-title mb-0">Project vendors</h6>
	</div>
<?php
$query = array(
	'SELECT'	=> 'v2.*, v1.vendor_name, v1.phone_number, v1.email',
	'FROM'		=> 'hca_sb721_vendors AS v2',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_vendors AS v1',
			'ON'			=> 'v1.id=v2.vendor_id'
		),
	),
	'ORDER BY'	=> 'v1.vendor_name',
	'WHERE'		=> 'v2.project_id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sb721_vendors = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$sb721_vendors[] = $row;
}

if (!empty($sb721_vendors))
{
?>
	<table class="table table-striped table-bordered">
		<thead class="sticky-under-menu">
			<tr>
				<th>Vendor</th>
				<th>Bid date</th>
				<th>Job start date</th>
				<th>Job end date</th>
				<th>Cost</th>
				<th>Work performed</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($sb721_vendors as $cur_info)
	{
		$btn_actions = [];
		$btn_actions[] = '<button type="button" class="badge bg-primary" onclick="getVendor('.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#exampleModal">Edit</button>';

		$vendor_info = [];
		if ($cur_info['phone_number'] != '' || $cur_info['email'] != '')
		{
			$vendor_info[] = '<p class="float-end">';
			$vendor_info[] = '<a tabindex="0" class="text-info" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="'.html_encode($cur_info['phone_number']).' '.html_encode($cur_info['email']).'"><i class="fas fa-info-circle"></i></a>';
			$vendor_info[] = '</p>';
		}
		$vendor_info[] = '<h6>'.html_encode($cur_info['vendor_name']).'</h6>';
?>
			<tr>
				<td><?php echo implode("\n", $vendor_info) ?></td>
				<td><h6><?php echo format_date($cur_info['date_bid'], 'n/j/y') ?></h6></td>
				<td><h6><?php echo format_date($cur_info['date_start_job'], 'n/j/y') ?></h6></td>
				<td><h6><?php echo format_date($cur_info['date_end_job'], 'n/j/y') ?></h6></td>
				<td><h6><?php echo gen_number_format($cur_info['cost']) ?></h6></td>
				<td><h6><?php echo html_encode($cur_info['comment']) ?></h6></td>
				<td><?php echo implode("\n", $btn_actions) ?></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
<?php
}
else
	echo '<div class="alert alert-warning" role="alert">No project vendors.</div>';
?>
	<div class="my-3">
		<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal" onclick="getVendor(0)">Add a vendor</button>
	</div>

	
	<div class="row">
		<div class="col-md-6">
			<div class="card">
				<div class="card-header">
					<h6 class="card-title mb-0">In-House Pre-Inspection Dates</h6>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							<label class="form-label" for="field_date_preinspection_start">Start Date</label>
							<input id="field_date_preinspection_start" class="form-control" type="date" name="date_preinspection_start" value="<?php echo isset($_POST['date_preinspection_start']) ? html_encode($_POST['date_preinspection_start']) : format_date($project_info['date_preinspection_start']) ?>">
							<label class="text-danger" onclick="document.getElementById('field_date_preinspection_start').value=''">Click to clear date</label>
						</div>
						<div class="col-md-6">
							<label class="form-label" for="field_date_preinspection_end">End Date</label>
							<input id="field_date_preinspection_end" class="form-control" type="date" name="date_preinspection_end" value="<?php echo isset($_POST['date_preinspection_end']) ? html_encode($_POST['date_preinspection_end']) : format_date($project_info['date_preinspection_end']) ?>">
							<label class="text-danger" onclick="document.getElementById('field_date_preinspection_end').value=''">Click to clear date</label>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label" for="field_performed_by">Performed by</label>
						<select id="field_performed_by" name="performed_by" class="form-select">
<?php
echo "\t\t\t\t\t\t\t".'<option value="0" selected="selected">Project Managers</option>'."\n";
foreach ($project_managers as $user_info)
{
	if ($project_info['performed_by'] == $user_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$user_info['id'].'" selected="selected">'.html_encode($user_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$user_info['id'].'">'.html_encode($user_info['realname']).'</option>'."\n";
}
?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="card">
				<div class="card-header">
					<h6 class="card-title mb-0">Engineer Inspection Dates</h6>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							<label class="form-label" for="field_date_city_inspection_start">Start Date</label>
							<input id="field_date_city_inspection_start" class="form-control" type="date" name="date_city_inspection_start" value="<?php echo isset($_POST['date_city_inspection_start']) ? html_encode($_POST['date_city_inspection_start']) : format_date($project_info['date_city_inspection_start']) ?>">
							<label class="text-danger" onclick="document.getElementById('field_date_city_inspection_start').value=''">Click to clear date</label>
						</div>
						<div class="col-md-6">
							<label class="form-label" for="field_date_city_inspection_end">End Date</label>
							<input id="field_date_city_inspection_end" class="form-control" type="date" name="date_city_inspection_end" value="<?php echo isset($_POST['date_city_inspection_end']) ? html_encode($_POST['date_city_inspection_end']) : format_date($project_info['date_city_inspection_end']) ?>">
							<label class="text-danger" onclick="document.getElementById('field_date_city_inspection_end').value=''">Click to clear date</label>
						</div>
					</div>	
					<div class="mb-3">
						<label class="form-label" for="field_city_engineer">Engineer Name</label>
						<input id="field_city_engineer" class="form-control" type="text" name="city_engineer" value="<?php echo isset($_POST['city_engineer']) ? html_encode($_POST['city_engineer']) : format_date($project_info['city_engineer']) ?>">
					</div>
				</div>
			</div>
		</div>

		<div class="card-body">
			<button type="submit" name="update" class="btn btn-primary">Update project</button>
			<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
<?php if ($User->checkAccess('hca_sb721', 13)) : ?>
			<button type="submit" name="remove" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">Remove project</button>
<?php endif; ?>
		</div>

	</div>
</form>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
				<div class="modal-hidden">
					<!--hidden_fields-->
				</div>
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Vendor information</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<!--body_fields-->
				</div>
				<div class="modal-footer">
					<!--buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function()
{
	var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
	var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl)
	})

}, false);
function getVendor(id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_sb721_ajax_get_vendor')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_sb721_ajax_get_vendor') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-hidden').empty().html(re.modal_hidden);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$(".modal .alert").css('display', 'block');
		}
	});
	
	$('.modal input[name="event_id"]').val(id);
}
function addLocationSelect()
{
	var et = $("#field_locations").val();
	var es = $("#select_location").val();
	var coma = (et !== '') ? ', ' : '';
	if(es != 0 && es !== '' && es !== null){
		var em = et + coma + es;
		$("#field_locations").val(em);
	}
}
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_sb721_ajax_get_units')) ?>";
	var id = $("#field_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_sb721_ajax_get_units') ?>",
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