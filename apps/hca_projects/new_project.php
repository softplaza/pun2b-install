<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_projects', 11)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;
$apt_locations = explode(',', $Config->get('o_hca_5840_locations'));



if (isset($_POST['create']))
{
	$form_data = array(
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_number'		=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',
		'location'			=> isset($_POST['location']) ? swift_trim($_POST['location']) : '',
		'symptoms'			=> isset($_POST['symptoms']) ? swift_trim($_POST['symptoms']) : '',
		'major_repairs'		=> isset($_POST['major_repairs']) ? swift_trim($_POST['major_repairs']) : '',
		'cosmetic_repairs'	=> isset($_POST['cosmetic_repairs']) ? swift_trim($_POST['cosmetic_repairs']) : '',
		'performed_by'		=> isset($_POST['performed_by']) ? intval($_POST['performed_by']) : 0,
		'performed_date'	=> isset($_POST['performed_date']) ? swift_trim($_POST['performed_date']) : '',
		'project_status'	=> 1
	);

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select a property from dropdown list.');
	if ($form_data['performed_by'] == 0)
		$Core->add_error('Select "Performed By" from users list.');
	
	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('hca_projects', $form_data);
		
		if ($new_id)
		{
			$SwiftUploader->uploadFiles('hca_projects', $new_id);
			
			$Core->add_errors($SwiftUploader->getErrors());

			// Add flash message
			$flash_message = 'In-House request has been created';
			$FlashMessenger->add_info($flash_message);
			if (empty($Core->errors))
				redirect($URL->link('hca_projects_list'), $flash_message);
		}
	}
}

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
);
if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE']	= 'id='.$User->get('sm_pm_property_id');
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
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

$apt_locations = explode(',', $Config->get('o_hca_5840_locations'));

$Core->set_page_id('hca_projects_new', 'hca_projects');
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

					<div class="col-md-3">
						<label class="form-label" for="field_location">List of locations</label>
						<select name="location" id="field_location" class="form-select">
<?php
echo '<option value="" selected="selected" disabled>Select a location</option>'."\n";
foreach ($apt_locations as $location) {
	echo "\t\t\t\t\t\t\t".'<option value="'.$location.'">'.html_encode($location).'</option>'."\n";
}
?>
						</select>
					</div>

				</div>

				<div class="mb-3">
					<label class="form-label" for="field_symptoms">Symptoms</label>
					<textarea type="text" name="symptoms" class="form-control" id="field_symptoms" placeholder="Leave your comment"><?php echo (isset($_POST['symptoms']) ? html_encode($_POST['symptoms']) : '') ?></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label" for="field_major_repairs">Major Repairs City</label>
					<textarea type="text" name="major_repairs" class="form-control" id="field_major_repairs" placeholder="Leave your comment"><?php echo (isset($_POST['major_repairs']) ? html_encode($_POST['major_repairs']) : '') ?></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label" for="field_cosmetic_repairs">Cosmetic Repairs In-House</label>
					<textarea type="text" name="cosmetic_repairs" class="form-control" id="field_cosmetic_repairs" placeholder="Leave your comment"><?php echo (isset($_POST['cosmetic_repairs']) ? html_encode($_POST['cosmetic_repairs']) : '') ?></textarea>
				</div>

				<?php $SwiftUploader->setForm() ?>

				<div class="row mb-3">
					<div class="col-md-4">
						<label class="form-label" for="field_performed_by">Performed by</label>
						<select id="field_performed_by" name="performed_by" class="form-control" required>
<?php
echo '<option value="0" selected="selected" disabled>Select an employee</option>'."\n";
foreach ($users_info as $cur_info)
{
	if(isset($_POST['performed_by']) && $_POST['performed_by'] == $cur_info['id'] || $cur_info['id'] == $User->get('id'))
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>'."\n";
}
?>
						</select>
					</div>
					<div class="col-md-2">
						<label class="form-label" for="field_performed_date">Date</label>
						<input type="date" name="performed_date" value="<?php echo (isset($_POST['performed_date']) ? html_encode($_POST['performed_date']) : date('Y-m-d')) ?>" class="form-control" id="field_performed_date">
					</div>
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