<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$user_access = ($User->is_admmod() || $User->checkAccess('hca_sp', 11)) ? true : false;
if (!$user_access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;

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

if (isset($_POST['create']))
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
	$form_data['created_date'] = time();

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select property name from dropdown list.');
	if ($form_data['project_manager_id'] == 0)
		$Core->add_error('At least one manager must be selected to manage the project.');
	if ($form_data['project_manager_id'] > 0 && $form_data['project_manager_id'] == $form_data['second_manager_id'])
		$Core->add_error('It doesn\'t make sense to assign the same manager twice.');

	if (compare_dates($form_data['date_bid_start'], $form_data['date_bid_end'], 1))
		$Core->add_error('The Start Bid Date cannot be greater than the End Bid Date, check that the dates are correct.');
	if (compare_dates($form_data['date_contract_start'], $form_data['date_contract_end'], 1))
		$Core->add_error('The Start Contract Date cannot be greater than the End Contract Date, check that the dates are correct.');
	if (compare_dates($form_data['date_job_start'], $form_data['date_job_end'], 1))
		$Core->add_error('The Start Job Date cannot be greater than the End Job Date, check that the dates are correct.');
	
	if ($form_data['work_status'] == 5 && ($form_data['date_bid_start'] == '' || $form_data['date_bid_end'] == '' || $form_data['date_contract_start'] == '' || $form_data['date_contract_end'] == '' || $form_data['date_job_start'] == '' || $form_data['date_job_end'] == ''))
		$Core->add_error('To complete this project setup all dates.');

	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert('sm_special_projects_records', $form_data);
		
		if ($new_id)
		{
			$SwiftUploader->uploadFiles('sm_special_projects_records', $new_id);
			$Core->add_errors($SwiftUploader->getErrors());

			$project_number = date('Y').'-'.$new_id;
			$query = array(
				'UPDATE'	=> 'sm_special_projects_records',
				'SET'		=> 'project_number=\''.$DBLayer->escape($project_number).'\'',
				'WHERE'		=> 'id='.$new_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'Special Project #'.$project_number.' has been created.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_special_projects_active', $new_id), $flash_message);
	}
}

else if (isset($_POST['cancel']))
{
	// Add flash message
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_special_projects_active', 0), $flash_message);
}

$project_managers = $User->getUserAccess('hca_sp', 14, 1);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
}

$Core->set_page_id('hca_sp_new', 'hca_sp');
require SITE_ROOT.'header.php';
?>

<style>
#field_date_action_start{background: #fedced;}
#field_date_bid_start, #field_date_bid_end{background: #ffffbb;}
#field_date_contract_start, #field_date_contract_end{background: #b9eeff;}
#field_date_job_start, #field_date_job_end{background: #b8ffb8;}
</style>

<form method="post" accept-charset="utf-8" action=""  enctype="multipart/form-data" onsubmit="return checkFormSubmit(this)">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">New Project</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_property_id">Property name</label>
					<select id="field_property_id" class="form-select" name="property_id" required onchange="getUnits()">
<?php
echo '<option value="0" selected="selected" disabled>Select a property</option>'."\n";
foreach ($property_info as $cur_info)
{
	if (isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_unit_number">Unit number</label>
					<div id="unit_number">
						<input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : '' ?>" class="form-control" id="field_unit_number">
					</div>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_project_scale">Scale</label>
					<select id="field_project_scale" name="project_scale" class="form-select">
<?php
$project_scale_arr = array(0 => 'Minor Scale', 1 => 'Major Scale');
foreach ($project_scale_arr as $key => $value)
{
	if (isset($_POST['project_scale']) && $_POST['project_scale'] == $key)
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
echo '<option value="0" selected>Select Manager</option>'."\n";
foreach ($project_managers as $user_info)
{
	if (isset($_POST['project_manager_id']) && $_POST['project_manager_id'] == $user_info['id'])
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
echo '<option value="0" selected>Select Manager</option>'."\n";
foreach ($project_managers as $user_info)
{
	if (isset($_POST['second_manager_id']) && $_POST['second_manager_id'] == $user_info['id'])
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
				<textarea id="field_project_desc" name="project_desc" class="form-control" placeholder="Leave project description"><?php echo isset($_POST['project_desc']) ? html_encode($_POST['project_desc']) : '' ?></textarea>
			</div>

		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Project Dates</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_action_start">Action Date</label>
					<input id="field_date_action_start" class="form-control" type="date" name="date_action_start" value="<?php echo isset($_POST['date_action_start']) ? html_encode($_POST['date_action_start']) : '' ?>">
				</div>
			</div>

			<hr>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_bid_start">Bid Start Date</label>
					<input id="field_date_bid_start" class="form-control" type="date" name="date_bid_start" value="<?php echo isset($_POST['date_bid_start']) ? html_encode($_POST['date_bid_start']) : '' ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_date_bid_end">Bid End Date</label>
					<input id="field_date_bid_end" class="form-control" type="date" name="date_bid_end" value="<?php echo isset($_POST['date_bid_end']) ? html_encode($_POST['date_bid_end']) : '' ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_contract_start">Contract Start Date</label>
					<input id="field_date_contract_start" class="form-control" type="date" name="date_contract_start" value="<?php echo isset($_POST['date_contract_start']) ? html_encode($_POST['date_contract_start']) : '' ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_date_contract_end">Contract End Date</label>
					<input id="field_date_contract_end" class="form-control" type="date" name="date_contract_end" value="<?php echo isset($_POST['date_contract_end']) ? html_encode($_POST['date_contract_end']) : '' ?>">
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_date_job_start">Job Start Date</label>
					<input id="field_date_job_start" class="form-control" type="date" name="date_job_start" value="<?php echo isset($_POST['date_job_start']) ? html_encode($_POST['date_job_start']) : '' ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_date_job_end">Job End Date</label>
					<input id="field_date_job_end" class="form-control" type="date" name="date_job_end" value="<?php echo isset($_POST['date_job_end']) ? html_encode($_POST['date_job_end']) : '' ?>">
				</div>
			</div>

			<hr>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_budget">Budget</label>
					<input id="field_budget" class="form-control" type="text" name="budget" value="<?php echo isset($_POST['budget']) ? html_encode($_POST['budget']) : '' ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="field_cost">Total Cost</label>
					<input id="field_cost" class="form-control" type="text" name="cost" value="<?php echo isset($_POST['cost']) ? html_encode($_POST['cost']) : '' ?>">
				</div>
			</div>

			<div class="mb-3">
				<label for="field_remarksc">Remarks</label>
				<textarea id="field_remarks" name="remarks" class="form-control" placeholder="Leave your remarks"><?php echo isset($_POST['remarks']) ? html_encode($_POST['remarks']) : '' ?></textarea>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="field_work_status">Work Status</label>
					<select name="work_status" id="field_work_status" class="form-select">
<?php
foreach ($work_statuses as $key => $value)
{
	if (isset($_POST['work_status']) && $_POST['work_status'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<?php $SwiftUploader->setForm() ?>

		</div>

		<div class="card-body">
			<button type="submit" name="create" class="btn btn-primary">Create Project</button>
			<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
		</div>

	</div>
</form>
	
<script>
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