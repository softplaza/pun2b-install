<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_projects', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;
$apt_locations = explode(',', $Config->get('o_hca_5840_locations'));



if (isset($_POST['create']))
{
	$form_data = array(
		'project_desc'		=> isset($_POST['project_desc']) ? swift_trim($_POST['project_desc']) : '',
		'requested_work'	=> isset($_POST['requested_work']) ? swift_trim($_POST['requested_work']) : '',
		'completed_work'	=> isset($_POST['completed_work']) ? swift_trim($_POST['completed_work']) : '',
		'requested_by'		=> $User->get('id'),
		'requested_date'	=> time(),
		'task_type'			=> isset($_POST['task_type']) ? intval($_POST['task_type']) : 0,
		'urgency'			=> isset($_POST['urgency']) ? intval($_POST['urgency']) : 0,
	);

	if ($form_data['requested_work'] == '')
		$Core->add_error('Write requested work.');

	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('swift_projects', $form_data);
		
		if ($new_id)
		{
			// Add flash message
			$flash_message = 'Task has been created';
			$FlashMessenger->add_info($flash_message);
			if (empty($Core->errors))
				redirect($URL->link('swift_projects_list'), $flash_message);
		}
	}
}

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.email, u.hca_fs_group',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.id > 2',
	'ORDER BY'	=> 'realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$Core->set_page_id('swift_projects_new', 'swift_projects');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Create a new task</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_task_type">Type of task</label>
					<select id="fld_task_type" name="task_type" class="form-select" required>
<?php
$tasks_info = [
	0 => 'Request',
	1 => 'Bug fix',
	2 => 'Improvement',
	3 => 'Testing'
];
foreach ($tasks_info as $key => $value)
{
	if (isset($_POST['task_type']) && $_POST['task_type'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_urgency">Urgency</label>
					<select id="fld_urgency" name="urgency" class="form-select" required>
<?php
$urgency_info = [
	0 => 'Low',
	1 => 'Middle',
	2 => 'High',
];
foreach ($urgency_info as $key => $value)
{
	if (isset($_POST['urgency']) && $_POST['urgency'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="col-md-6 mb-3">
				<label class="form-label" for="fld_project_desc">Project Description</label>
				<input id="fld_project_desc" class="form-control" type="project_desc" name="project_desc" value="<?php echo isset($_POST['project_desc']) ? html_encode($_POST['project_desc']) : '' ?>">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_requested_work">Requested Work</label>
				<textarea type="text" name="requested_work" class="form-control" id="fld_requested_work"><?php echo (isset($_POST['prequested_work']) ? html_encode($_POST['requested_work']) : '') ?></textarea>
			</div>
			<div class="mb-3">
				<button type="submit" name="create" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</div>
</form>
	
<script>
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_units')) ?>";
	var id = $("#field_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#input_unit_number").empty().html(re.unit_number);
		},
		error: function(re){
			document.getElementById("input_unit_number").innerHTML = re;
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';