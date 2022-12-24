<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$HcaMi = new HcaMi;
$SwiftUploader = new SwiftUploader;

$work_statuses = array(1 => 'IN PROGRESS', 2 => 'ON HOLD', 3 => 'COMPLETED');
$apt_locations = explode(',', $Config->get('o_hca_5840_locations'));

if (isset($_POST['form_sent']))
{
	$form_data = [
		'property_id' 			=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_id' 				=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
		'mois_report_date' 		=> isset($_POST['mois_report_date']) ? strtotime($_POST['mois_report_date']) : 0,
		'mois_inspection_date' 	=> isset($_POST['mois_inspection_date']) ? strtotime($_POST['mois_inspection_date']) : 0,
		'performed_uid' 		=> isset($_POST['performed_uid']) ? intval($_POST['performed_uid']) : 0,
		'leak_type' 			=> isset($_POST['leak_type']) ? intval($_POST['leak_type']) : 0,
		'symptom_type' 			=> isset($_POST['symptom_type']) ? intval($_POST['symptom_type']) : 0,
		'symptoms' 				=> isset($_POST['symptoms']) ? swift_trim($_POST['symptoms']) : '',
		'action' 				=> isset($_POST['action']) ? swift_trim($_POST['action']) : '',
		'job_status' 			=> 1,
		'time_created'			=> time(),
		'created_by'			=> $User->get('id'),
		'time_updated'			=> time(),
		'updated_by'			=> $User->get('id'), // last updated
	];

	$location = $locations = [];
	foreach ($HcaMi->locations as $key => $value)
	{
		if (isset($_POST['location'][$key]) && $_POST['location'][$key] == '1')
		{
			$location[] = $value;
			$locations[] = $key;
		}
	}

	$form_data['location'] = implode(',', $location);
	$form_data['locations'] = implode(',', $locations);

	if ($form_data['property_id'] == 0)
		$Core->add_error('Property name cannot be empty.');
	if ($form_data['performed_uid'] == 0)
		$Core->add_error('No Project Manager has been selected.');
	if (empty($location))
		$Core->add_error('No "Locations" have been selected.');

	$SwiftUploader->checkAllowed();

	if (empty($Core->errors))
	{
		$new_pid = $DBLayer->insert_values('hca_5840_projects', $form_data);
		
		if ($new_pid)
			$SwiftUploader->uploadFiles('hca_5840_projects', $new_pid);

		$flash_message = 'New project #'.$new_pid.' has been created';
		$HcaMi->addAction($new_pid, $flash_message);
		
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_5840_manage_project', $new_pid), $flash_message);
	}
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

$User->getUserAccess('hca_mi');
$project_user_ids = $User->getUserAccessIDS();
$project_user_ids[] = $User->get('id');
$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.id > 2',
	'ORDER BY'	=> 'u.realname',
);
if (!empty($project_user_ids))
	$query['WHERE'] = 'u.id IN ('.implode(',', $project_user_ids).')';

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$Core->set_page_title('New Project');
$Core->set_page_id('hca_mi_new_project', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data" class="was-validated">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Property information</h6>
		</div>
		<div class="card-body">
			<div class="col-md-4 mb-3">
				<label class="form-label" for="property_id">Property name</label>
				<select id="property_id" class="form-select form-select-sm" name="property_id" required onchange="getUnits()">
<?php
echo '<option value="" selected disabled>Select Property</option>'."\n";
foreach ($property_info as $cur_info)
{
	if (isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_unit_id">Unit number</label>
				<div id="unit_number">
					<input type="text" class="form-control form-control-sm" disabled>
				</div>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_location">Locations</label>
				<select class="form-select form-select-sm" id="fld_location" onchange="addLocation()" required>
<?php
echo '<option value="" selected>Select one</option>'."\n";
foreach ($HcaMi->locations as $key => $value)
{
	echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
				</select>
			</div>

			<div class="mb-3">
				<div id="locations"></div>
			</div>

			<?php $SwiftUploader->setForm() ?>

		</div>
	</div>

	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Moisture Inspection</h6>
		</div>
		<div class="card-body">

			<div class="col-md-3 mb-3">
				<label class="form-label" for="fld_mois_report_date">Date Reported</label>
				<input type="date" name="mois_report_date" id="fld_mois_report_date" class="form-control form-control-sm" value="" onclick="this.showPicker()">
				<label class="text-danger" onclick="document.getElementById('fld_mois_report_date').value=''">Click to clear date</label>
			</div>
			<div class="col-md-3 mb-3">
				<label class="form-label" for="fld_mois_inspection_date">Date of Inspection</label>
				<input type="date" name="mois_inspection_date" id="fld_mois_inspection_date" class="form-control form-control-sm" value="" onclick="this.showPicker()">
				<label class="text-danger" onclick="document.getElementById('fld_mois_inspection_date').value=''">Click to clear date</label>
			</div>
			<div class="col-md-3 mb-3">
				<label class="form-label" for="fld_performed_uid">Performed by</label>
				<select name="performed_uid" required class="form-select form-select-sm" id="fld_performed_uid">
<?php
echo '<option value="0" selected disabled>Select one</option>'."\n";
foreach ($users_info as $cur_info)
{
	if (isset($_POST['performed_uid']) && $_POST['performed_uid'] == $cur_info['id'] || $User->get('id') == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>'."\n";
}
?>
				</select>
			</div>

			<div class="col-md-3 mb-3">
				<label class="form-label" for="fld_leak_type">Source of Moisture</label>
				<select name="leak_type" required class="form-select form-select-sm" id="fld_leak_type" required>
					<option value="" selected>Select one</option>
<?php
foreach ($HcaMi->leak_types as $key => $value)
{
	if (isset($_POST['leak_type']) && $_POST['leak_type'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="mb-3 col-md-3">
				<label class="form-label" for="fld_symptom_type">Symptoms</label>
				<select name="symptom_type" class="form-select form-select-sm" id="fld_symptom_type">
					<option value="" selected>Select one</option>
<?php
foreach ($HcaMi->symptoms as $key => $value)
{
	if (isset($_POST['symptom_type']) && $_POST['symptom_type'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="mb-3">
				<label class="form-label" for="symptoms">Comments</label>
				<textarea id="symptoms" class="form-control" name="symptoms" placeholder="Leave your comment"><?php echo isset($_POST['symptoms']) ? html_encode($_POST['symptoms']) : '' ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label" for="action">Action</label>
				<textarea id="action" class="form-control" name="action" placeholder="Leave your comment"><?php echo isset($_POST['action']) ? html_encode($_POST['action']) : '' ?></textarea>
			</div>

			<button type="submit" name="form_sent" class="btn btn-primary">Submit</button>

		</div>
	</div>

</form>

<script>
function clearDate(id){
	$('.set'+id+' input').val('');
}
function addLocation(){
	//get all added

	var val = $("#fld_location").val();
	var text = $("#fld_location").find("option:selected").text();
	
	var html = '<div class="form-check-inline" id="location_'+val+'"><div class="mb-1 d-flex btn" style="border-color: #6c757d;">';
	html += '<input type="hidden" name="location['+val+']" value="1">';
	html += '<div class="toast-body fw-bold py-0 pe-1">'+text+'</div>';
	html += '<button type="button" class="btn-close" aria-label="Close" onclick="return confirm(\'Are you sure you want to delete it?\')?clearLocation('+val+'):\'\';"></button>';
	html += '</div></div>';

	if (val > 0)
		$( "#locations" ).after(html);
}
function clearLocation(id){
	$("div").remove("#location_"+id);
}
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_5840_ajax_get_units')) ?>";
	var id = $("#property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_5840_ajax_get_units') ?>",
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
