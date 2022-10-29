<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sp', 12)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Special Project does not exist or has been removed.');

//Get cur project info
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u1.realname AS first_manager, u2.realname AS second_manager',
	'FROM'		=> 'sm_special_projects_records AS pj',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.project_manager_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.second_manager_id'
		),
	),
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

if (empty($project_info))
	message('Sorry, this Special Project does not exist or has been removed.');

$work_statuses = array(
	//0 => 'Removed',
	1 => 'Active',
	2 => 'Bid Phase',
	6 => 'Contract Phase',
	7 => 'Job Phase',
	3 => 'Pending',
	4 => 'On Hold',
	5 => 'Completed',
);

$admin_approved_array = array(0 => 'NOT APPROVED', 1 => 'APPROVED', 2 => 'NOT AVAILABLE');

if (isset($_POST['update']))
{
	$form_data = [];
	$form_data['property_id'] = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$form_data['unit_number'] = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';
	$form_data['project_manager_id'] = isset($_POST['project_manager_id']) ? intval($_POST['project_manager_id']) : 0;
	$form_data['second_manager_id'] = isset($_POST['second_manager_id']) ? intval($_POST['second_manager_id']) : 0;
	$form_data['project_scale'] = isset($_POST['project_scale']) ? intval($_POST['project_scale']) : 0;
	$form_data['project_desc'] = isset($_POST['project_desc']) ? swift_trim($_POST['project_desc']) : '';

	$form_data['date_action_start'] = isset($_POST['date_action_start']) ? swift_trim($_POST['date_action_start']) : '1000-01-01';
	$form_data['date_bid_start'] = isset($_POST['date_bid_start']) ? swift_trim($_POST['date_bid_start']) : '1000-01-01';
	$form_data['date_bid_end'] = isset($_POST['date_bid_end']) ? swift_trim($_POST['date_bid_end']) : '1000-01-01';
	$form_data['date_contract_start'] = isset($_POST['date_contract_start']) ? swift_trim($_POST['date_contract_start']) : '1000-01-01';
	$form_data['date_contract_end'] = isset($_POST['date_contract_end']) ? swift_trim($_POST['date_contract_end']) : '1000-01-01';
	$form_data['date_job_start'] = isset($_POST['date_job_start']) ? swift_trim($_POST['date_job_start']) : '1000-01-01';
	$form_data['date_job_end'] = isset($_POST['date_job_end']) ? swift_trim($_POST['date_job_end']) : '1000-01-01';

	$form_data['budget'] = isset($_POST['budget']) ? swift_trim($_POST['budget']) : 0;
	$form_data['cost'] = isset($_POST['cost']) ? swift_trim($_POST['cost']) : 0;
	$form_data['remarks'] = isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : '';
	$form_data['admin_approved'] = isset($_POST['admin_approved']) ? intval($_POST['admin_approved']) : 0;
	$form_data['work_status'] = isset($_POST['work_status']) ? intval($_POST['work_status']) : 0;

	if (compare_dates($form_data['date_bid_start'], $form_data['date_bid_end'], 1))
		$Core->add_error('The Start Bid Date cannot be greater than the End Bid Date, check that the dates are correct.');
	if (compare_dates($form_data['date_contract_start'], $form_data['date_contract_end'], 1))
		$Core->add_error('The Start Contract Date cannot be greater than the End Contract Date, check that the dates are correct.');
	if (compare_dates($form_data['date_job_start'], $form_data['date_job_end'], 1))
		$Core->add_error('The Start Job Date cannot be greater than the End Job Date, check that the dates are correct.');

	if ($form_data['project_manager_id'] == 0 && $form_data['second_manager_id'] == 0)
		$Core->add_error('At least one manager must be selected to manage the project.');
	if ($form_data['project_manager_id'] == $form_data['second_manager_id'])
		$Core->add_error('It doesn\'t make sense to assign the same manager twice.');

// temporary disable as requested Jon and Greg
//	if ($form_data['work_status'] == 5 && ($form_data['date_bid_start'] == '' || $form_data['date_bid_end'] == '' || $form_data['date_contract_start'] == '' || $form_data['date_contract_end'] == '' || $form_data['date_job_start'] == '' || $form_data['date_job_end'] == ''))
//		$Core->add_error('To complete this project setup all dates.');

	if (empty($Core->errors))
	{
		$DBLayer->update('sm_special_projects_records', $form_data, $id);

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
	redirect($URL->link('sm_special_projects_active', $id), $flash_message);
}

else if (isset($_POST['remove']))
{
	$query = array(
		'UPDATE'	=> 'sm_special_projects_records',
		'SET'		=> 'work_status=0',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Project #'.$id.' has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_special_projects_active', 0), $flash_message);
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

$project_managers = $User->getUserAccess('hca_sp', 14, 1);

$additional_managers = hca_sp_get_property_manager_id($project_info['property_id']);
if (!empty($additional_managers))
	$project_managers = array_merge($project_managers, $additional_managers);

$next_event = sm_special_projects_check_next_event($project_info['id'], $event_alert);

if ($event_alert)
	$Core->add_warning('Upcoming Work! '.$next_event);

if ($project_info['cost'] > $project_info['budget'])
	$Core->add_warning('Total price $'.gen_number_format($project_info['cost'], 2).' is more than an Budget $'.gen_number_format($project_info['budget'], 2));

$Core->set_page_id('sm_special_projects_manage', 'hca_sp');
require SITE_ROOT.'header.php';
?>

<style>
#field_date_action_start{background: #fedced;}
#field_date_bid_start, #field_date_bid_end{background: #ffffbb;}
#field_date_contract_start, #field_date_contract_end{background: #b9eeff;}
#field_date_job_start, #field_date_job_end{background: #b8ffb8;}
</style>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Project Information</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col border">
					<label class="form-label">Created:</label>
					<h6><?php echo format_time($project_info['created_date']) ?><h6>
				</div>
				<div class="col border">
					<label class="form-label">Project ID #:</label>
					<h6><?php echo html_encode($project_info['project_number']) ?><h6>
				</div>
			</div>
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
				<div class="col-md-4">
					<label class="form-label" for="field_unit_number">Unit number</label>
					<div id="unit_number">
						<input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : html_encode($project_info['unit_number']) ?>" class="form-control" id="field_unit_number">
					</div>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_project_scale">Scale</label>
					<select id="field_project_scale" name="project_scale" class="form-select">
<?php
$project_scale_arr = array(0 => 'Minor Scale', 1 => 'Major Scale');
foreach ($project_scale_arr as $key => $value)
{
	if ($project_info['project_scale'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_project_manager_id">First Project Manager</label>
					<select id="field_project_manager_id" name="project_manager_id" class="form-select" required>
<?php
foreach ($project_managers as $user_info)
{
	if ($project_info['project_manager_id'] == $user_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$user_info['id'].'" selected="selected">'.html_encode($user_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$user_info['id'].'">'.html_encode($user_info['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_second_manager_id">Second Project Manager</label>
					<select name="second_manager_id" id="field_second_manager_id" class="form-select">
<?php
echo '<option value="0" selected="selected">No Project Manager</option>'."\n";
foreach ($project_managers as $user_info)
{
	if ($project_info['second_manager_id'] == $user_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$user_info['id'].'" selected="selected">'.html_encode($user_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$user_info['id'].'">'.html_encode($user_info['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<label for="field_project_desc">Project description</label>
				<textarea id="field_project_desc" name="project_desc" class="form-control" placeholder="Leave project description"><?php echo isset($_POST['project_desc']) ? html_encode($_POST['project_desc']) : html_encode($project_info['project_desc']) ?></textarea>
			</div>

		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Project Dates</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_action_start">Action Date</label>
					<input id="field_date_action_start" class="form-control" type="date" name="date_action_start" value="<?php echo isset($_POST['date_action_start']) ? html_encode($_POST['date_action_start']) : format_date($project_info['date_action_start']) ?>">
				</div>
			</div>

			<hr>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_bid_start">Bid Start Date</label>
					<input id="field_date_bid_start" class="form-control" type="date" name="date_bid_start" value="<?php echo isset($_POST['date_bid_start']) ? html_encode($_POST['date_bid_start']) : format_date($project_info['date_bid_start']) ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_date_bid_end">Bid End Date</label>
					<input id="field_date_bid_end" class="form-control" type="date" name="date_bid_end" value="<?php echo isset($_POST['date_bid_end']) ? html_encode($_POST['date_bid_end']) : format_date($project_info['date_bid_end']) ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_contract_start">Contract Start Date</label>
					<input id="field_date_contract_start" class="form-control" type="date" name="date_contract_start" value="<?php echo isset($_POST['date_contract_start']) ? html_encode($_POST['date_contract_start']) : format_date($project_info['date_contract_start']) ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_date_contract_end">Contract End Date</label>
					<input id="field_date_contract_end" class="form-control" type="date" name="date_contract_end" value="<?php echo isset($_POST['date_contract_end']) ? html_encode($_POST['date_contract_end']) : format_date($project_info['date_contract_end']) ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_job_start">Job Start Date</label>
					<input id="field_date_job_start" class="form-control" type="date" name="date_job_start" value="<?php echo isset($_POST['date_job_start']) ? html_encode($_POST['date_job_start']) : format_date($project_info['date_job_start']) ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_date_job_end">Job End Date</label>
					<input id="field_date_job_end" class="form-control" type="date" name="date_job_end" value="<?php echo isset($_POST['date_job_end']) ? html_encode($_POST['date_job_end']) : format_date($project_info['date_job_end']) ?>">
				</div>
			</div>

			<hr>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_budget">Budget</label>
					<input id="field_budget" class="form-control" type="text" name="budget" value="<?php echo isset($_POST['budget']) ? html_encode($_POST['budget']) : html_encode($project_info['budget']) ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_cost">Total Cost</label>
					<input id="field_cost" class="form-control" type="text" name="cost" value="<?php echo isset($_POST['cost']) ? html_encode($_POST['cost']) : html_encode($project_info['cost']) ?>">
					<a href="<?php echo $URL->link('sm_special_projects_manage_invoice', $id) ?>">Edit Invoice</a>
				</div>
			</div>

			<div class="mb-3">
				<label for="field_remarksc">Remarks</label>
				<textarea id="field_remarks" name="remarks" class="form-control" placeholder="Leave your remarks"><?php echo isset($_POST['remarks']) ? html_encode($_POST['remarks']) : html_encode($project_info['remarks']) ?></textarea>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_work_status">Work Status</label>
					<select name="work_status" id="field_work_status" class="form-select">
<?php
	if ($project_info['work_status'] == 0)
		echo "\t\t\t\t\t\t\t".'<option value="0" selected="selected">REMOVED</option>'."\n";

	foreach ($work_statuses as $key => $value)
	{
		if ($project_info['work_status'] == $key)
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$value.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_admin_approved">Notice Approved?</label>
					<select name="admin_approved" id="field_admin_approved" class="form-select">
<?php
	foreach ($admin_approved_array as $key => $value) {
		if ($project_info['admin_approved'] == $key)
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
	}
?>
					</select>
				</div>
			</div>
		</div>

		<div class="card-body">
			<button type="submit" name="update" class="btn btn-primary">Update Project</button>
			<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
<?php if ($User->checkAccess('hca_sp', 13)) : ?>
			<button type="submit" name="remove" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">Delete Project</button>
<?php endif; ?>
		</div>

	</div>
</form>

<script>
function checkFormSubmit(form)
{
	$('form input[name="form_sent"]').css("pointer-events","none");
	$('form input[name="form_sent"]').val("Processing...");
}
function clearDate(id){
	$('.set'+id+' input').val('');
}
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_sp_ajax_get_units')) ?>";
	var id = $("#field_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_sp_ajax_get_units') ?>",
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