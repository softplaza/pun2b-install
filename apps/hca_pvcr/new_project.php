<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('hca_pvcr_access') > 0 || $User->get('sm_pm_property_id') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['form_sent']))
{
	$form_data = [];
	$form_data['property_id'] = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$form_data['unit_number'] = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';
	$form_data['unit_size'] = isset($_POST['unit_size']) ? swift_trim($_POST['unit_size']) : '';
	$form_data['submited_by'] = isset($_POST['submited_by']) ? swift_trim($_POST['submited_by']) : '';
	
	$check_name = count(explode(' ', $form_data['submited_by']));
	if ($form_data['property_id'] == 0)
		$Core->add_error('Property name cannot be empty. Select a property from dropdown list.');
	if ($check_name < 2)
		$Core->add_error('You must enter your full name. Please enter your First & Last Name.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'SELECT'	=> 'pt.*',
			'FROM'		=> 'sm_property_db AS pt',
			'WHERE'		=> 'pt.id='.$form_data['property_id']
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$property_info = $DBLayer->fetch_assoc($result);

		// Create a New Project
		$form_data['submited_date'] = time();
		$new_id = $DBLayer->insert_values('hca_pvcr_projects', $form_data);
		
		if ($new_id)
		{
			// Add flash message
			$flash_message = 'Project #'.$new_id.' has been created.';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('hca_pvcr_projects', 'active').'&row='.$new_id, $flash_message);
		}
	}
}

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE'] = 'id='.$User->get('sm_pm_property_id');
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
}

$Core->set_page_title('New project');
$Core->set_page_id('hca_pvcr_new_project', 'hca_pvcr');

require SITE_ROOT.'header.php';

$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;
?>

<div class="main-content main-frm">
	<div class="ct-set warn-set">
		<div class="ct-box warn-box">
			<h6 class="ct-legend hn warn"><span>Information:</span></h6>
			<p>Fill in all fields and click "Submit". Fields highlighted in red are required.</p>
		</div>
	</div>
	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data" onsubmit="return checkFormSubmit(this)">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		</div>
		<fieldset class="frm-group group1">
			<div class="content-head">
				<h2 class="hn"><span>Property Information</span></h2>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Property</strong></span></label><br>
					<span class="fld-input"><select id="property_id" name="property_id" required  onchange="getUnits()">
<?php
//if ($User->get('sm_pm_property_id') == 0)
echo '<option value="0" selected="selected" disabled>Select Property</option>'."\n";

foreach ($property_info as $cur_info)
{
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Unit #</strong></span></label><br>
					<span class="fld-input" id="unit_number"><input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : '' ?>" placeholder="Enter Unit #"/></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Unit Size</strong></span></label><br>
					<span class="fld-input"><select name="unit_size">
<?php
$o_hca_vcr_unit_sizes = explode(',', $Config->get('o_sm_pm_unit_sizes'));
echo '<option value="" selected="selected">Select Size</option>'."\n";
foreach ($o_hca_vcr_unit_sizes as $value) {
	if(isset($_POST['unit_size']) && $_POST['unit_size'] == $value)
		echo "\t\t\t\t\t\t\t".'<option value="'.$value.'" selected="selected">'.html_encode($value).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$value.'">'.html_encode($value).'</option>'."\n";
}
?>
					</select></span>
				</div>
			</div>
<?php 

// Uploader or Hook

?>	
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Submitted by</strong></span></label><br>
					<span class="fld-input"><input type="text" name="submited_by" size="25" maxlength="255" value="<?php echo isset($_POST['submited_by']) ? html_encode($_POST['submited_by']) : (!$User->is_guest() ? html_encode($User->get('realname')) : '') ?>" placeholder="First & Last Name" required></span>
				</div>
			</div>

		</fieldset>
		
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="form_sent" value="Submit" /></span>
		</div>
	</form>
</div>
<script>
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_pvcr_ajax_get_units')) ?>";
	var id = $("#property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_pvcr_ajax_get_units') ?>",
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