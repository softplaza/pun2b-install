<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sb721', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;
$apt_locations = explode(',', $Config->get('o_hca_5840_locations'));

if (isset($_POST['create']))
{
	$form_data = array(
		'property_id'			=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_number'			=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',
		'locations'				=> isset($_POST['locations']) ? swift_trim($_POST['locations']) : '',
		'project_description'	=> isset($_POST['project_description']) ? swift_trim($_POST['project_description']) : '',
		'symptoms'				=> isset($_POST['symptoms']) ? swift_trim($_POST['symptoms']) : '',
		'action'				=> isset($_POST['action']) ? swift_trim($_POST['action']) : '',
		'project_status'		=> 1,
		'created'				=> time()
	);

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select a property from dropdown list.');
	
	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('hca_sb721_projects', $form_data);
		
		if ($new_id)
		{
			$SwiftUploader->uploadFiles('hca_sb721_projects', $new_id);
			$Core->add_errors($SwiftUploader->getErrors());

			$project_number = date('Y').'-'.$new_id;
			$DBLayer->update('hca_sb721_projects', ['project_number' => $project_number], $new_id);

			// Add flash message
			$flash_message = 'New project has been created';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_sb721_projects', ['active', $new_id]), $flash_message);
		}
	}
}

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
}

$Core->set_page_id('hca_sb721_new', 'hca_sb721');
require SITE_ROOT.'header.php';
?>


	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data" onsubmit="return checkFormSubmit(this)">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Create a new project</h6>
			</div>
			<div class="card-body">
				<div class="row mb-3">
					<div class="col-md-4">
						<label class="form-label" for="field_property_id">Property</label>
						<select id="field_property_id" name="property_id" class="form-control" required onchange="getUnits()">
<?php
echo '<option value="0" selected="selected" disabled>Select a property</option>'."\n";
foreach ($property_info as $cur_info) {
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
						</select>
					</div>

					<div class="col-md-2">
						<label class="form-label" for="field_unit_number">Unit number</label>
						<div id="input_unit_number">
							<input type="text" name="unit_number" value="<?php echo (isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : '') ?>" class="form-control" id="field_unit_number" placeholder="Enter unit #">
						</div>
					</div>
				</div>

				<div class="row mb-3">
					<div class="col-md-3">
						<label class="form-label" for="field_location">List of locations</label>
						<select name="location" id="field_location" class="form-select">
<?php
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

echo '<option value="" selected="selected" disabled>Select a location</option>'."\n";
foreach ($location_info as $location)
{
	echo "\t\t\t\t\t\t\t".'<option value="'.html_encode($location['location_name']).'">'.html_encode($location['location_name']).'</option>'."\n";
}
?>
						</select>
					</div>
					<div class="col-md-1" style="display:flex;flex-direction: column;justify-content: center;">
						<button type="button" class="btn btn-sm btn-success" onclick="addLocationSelect()">+</button>
					</div>
					<div class="col-md-8" id="cur_locations">
						<label class="form-label" for="field_locations">Locations</label>
						<textarea id="field_locations" name="locations" class="form-control"></textarea>
					</div>
				</div>

				<div class="mb-3">
					<label class="form-label" for="field_project_description">Project Description</label>
					<textarea type="text" name="project_description" class="form-control" id="field_project_description"><?php echo (isset($_POST['project_description']) ? html_encode($_POST['project_description']) : '') ?></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label" for="field_symptoms">Symptoms</label>
					<textarea type="text" name="symptoms" class="form-control" id="field_symptoms"><?php echo (isset($_POST['symptoms']) ? html_encode($_POST['symptoms']) : '') ?></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label" for="field_action">Action</label>
					<textarea type="text" name="action" class="form-control" id="field_action"><?php echo (isset($_POST['action']) ? html_encode($_POST['action']) : '') ?></textarea>
				</div>

				<?php $SwiftUploader->setForm() ?>

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

function addLocationSelect()
{
	var et = $("#field_locations").val();
	var es = $("#field_location").val();
	var coma = (et !== '') ? ', ' : '';
	if(es != 0 && es !== '' && es !== null){
		var em = et + coma + es;
		$("#field_locations").val(em);
	}
}
</script>

<?php
require SITE_ROOT.'footer.php';