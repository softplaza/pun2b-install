<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vcr', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

require 'class_auto_assigner.php';	

$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
$statuses = array(0 => 'ACTIVE', 1 => 'COMPLETED', 2 => 'ON HOLD', 5 => 'DELETED');

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.group_id, u.email, u.hca_vcr_access, u.hca_vcr_notify',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.hca_vcr_access > 0',
	'ORDER BY'	=> 'realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vcr_managers_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$vcr_managers_info[] = $row;
}

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);

if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE'] = 'id='.$User->get('sm_pm_property_id');

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

if (isset($_POST['form_sent']))
{
	$form_data = [];
	$form_data['property_id'] = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$form_data['unit_number'] = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';
	$form_data['unit_size'] = isset($_POST['unit_size']) ? swift_trim($_POST['unit_size']) : '';
	$form_data['submited_date'] = time();
	$form_data['submited_by'] = $User->get('realname');

	$check_name = count(explode(' ', $form_data['submited_by']));
	if ($form_data['property_id'] == 0)
		$Core->add_error('Property name cannot be empty. Select a property from dropdown list.');
	if ($check_name < 2)
		$Core->add_error('You must enter your full name. Please enter your First & Last Name.');

	if (empty($Core->errors))
	{
		// Create a New Project
		$db_table_name = 'hca_vcr_projects';
		$form_data['submited_date'] = time();
		$new_id = $DBLayer->insert_values($db_table_name, $form_data);
		
		if ($new_id)
		{
			$query = array(
				'SELECT'	=> 'p.id, p.pro_name',
				'FROM'		=> 'sm_property_db AS p',
				'WHERE'		=> 'p.id='.$form_data['property_id']
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$project_info = $DBLayer->fetch_assoc($result);

			$mail_message = [];
			$mail_message[] = 'A new project was created.'."\n";
			$mail_message[] = 'Property name: '.$project_info['pro_name'];
			$mail_message[] = 'Unit #: '.$form_data['unit_number'];
			$mail_message[] = 'Submitted by: '.$User->get('realname');

			$emails = $User->getNotifyEmails('hca_vcr', 5); // 5 - new project
			if (!empty($emails))
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send(implode(',', $emails), 'VCR: A new project was created', implode("\n", $mail_message));
			}

			// Add flash message
			$flash_message = 'Project #'.$new_id.' has been created.';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_vcr_projects', ['active', $new_id]), $flash_message);
		}
	}
}

$Core->set_page_id('hca_vcr_new_project', 'hca_vcr');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">New project</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_property_id">Property name</label>
					<select name="property_id" id="fld_property_id" class="form-select" required onchange="getUnits()">
<?php
//if ($User->get('sm_pm_property_id') == 0)
echo '<option value="0" selected="selected" disabled>Select property</option>'."\n";
foreach ($property_info as $cur_info) {
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_unit_number">Unit number</label>
					<div id="unit_number">
						<input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : '' ?>" class="form-control" id="fld_unit_number">
					</div>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_unit_size">Unit size</label>
					<input type="text" name="unit_size" value="<?php echo isset($_POST['unit_size']) ? html_encode($_POST['unit_size']) : '' ?>" placeholder="Enter size" list="fld_unit_size" class="form-control">
					<datalist id="fld_unit_size">
<?php
$query = [
	'SELECT'	=> 's.size_title',
	'FROM'		=> 'sm_property_unit_sizes AS s',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$unit_sizes = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	echo "\t\t\t\t\t\t\t".'<option value="'.$row['size_title'].'">'."\n";
}
?>
					</datalist>
				</div>
			</div>
			<button type="submit" name="form_sent" class="btn btn-primary">Create project</button>
		</div>
	</div>
</form>

<script>
function clearDate(id){
	$('.set'+id+' input').val('');
}
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_vcr_ajax_get_units')) ?>";
	var id = $("#fld_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_vcr_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#unit_number").empty().html(re.unit_number);
		},
		error: function(re){
			document.getElementById("#unit_number").innerHTML = re;
		}
	});
}
function enterManually(){
	var v = $("#unit_number select").val();
	if(v == 0){
		$("#unit_number").empty().html('<input type="text" name="unit_number" value="" placeholder="Enter Unit #"/>');
	}
}
window.onload = function(){
	getUnits();
}
</script>
<?php
require SITE_ROOT.'footer.php';