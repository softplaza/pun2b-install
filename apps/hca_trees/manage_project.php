<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_trees', 4)) ? true : false;
if(!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Special Project does not exist or has been removed.');

$errors = array();
$work_statuses = array(1 => 'IN PROGRESS', 2 => 'ON HOLD', 3 => 'COMPLETED');

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

if (isset($_POST['form_sent']))
{
	$form_data = array();
	$form_data['property_id'] = isset($_POST['property_id']) ? intval($_POST['property_id']) : '';
	$form_data['location'] = isset($_POST['location']) ? swift_trim($_POST['location']) : '';
	$form_data['project_desc'] = isset($_POST['project_desc']) ? swift_trim($_POST['project_desc']) : '';
	$form_data['noticed_date'] = isset($_POST['noticed_date']) ? strtotime($_POST['noticed_date']) : 0;
	$form_data['vendor_id'] = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : '';
	$form_data['po_number'] = isset($_POST['po_number']) ? swift_trim($_POST['po_number']) : '';
	$form_data['start_date'] = isset($_POST['start_date']) ? strtotime($_POST['start_date']) : 0;
	$form_data['end_date'] = isset($_POST['end_date']) ? strtotime($_POST['end_date']) : 0;
	$form_data['completion_date'] = isset($_POST['completion_date']) ? strtotime($_POST['completion_date']) : 0;
	$form_data['remarks'] = isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : '';
	$form_data['job_status'] = isset($_POST['job_status']) ? intval($_POST['job_status']) : 0;
	$form_data['total_cost'] = is_numeric($_POST['total_cost']) ? $_POST['total_cost'] : 0;

	if (!is_numeric($_POST['total_cost']))
		$Core->add_error('One of the symbols is not a number. Use whole numbers or decimal numbers separated by periods, for example: <strong>456.55</strong> . Do not use other symbols like: $?,/@# and etc.');
	if ($form_data['property_id'] == 0)
		$Core->add_error('Property name cannot be empty.');

	if (empty($Core->errors) &&!empty($form_data))
	{
		$DBLayer->update('hca_trees_projects', $form_data, $id);
		
		$flash_message = 'Project #'.$id.' has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_trees_projects',
	'WHERE'		=> 'id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

if (empty($main_info))
	message('Sorry, this Project does not exist or has been removed.');

$total_cost = is_numeric($main_info['total_cost']) ? number_format($main_info['total_cost'], 2, '.', '') : 0;

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'WHERE'		=> 'hca_trees=1',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

$Core->set_page_title('Project editor');
$Core->set_page_id('hca_trees_projects', 'hca_trees');
require SITE_ROOT.'header.php';

$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;
?>

<style>
.main-subhead .hn span{color: darkblue;font-weight: bold;}
.main-content .warn-set{margin: 1em 0 1em 0;}
.filled input, .filled select, .filled textarea{background: #e2ffe2;}
.unfilled input, .unfilled select, .unfilled textarea{background: #ffe6e2;}
.add-location{cursor:pointer;font-size: 18px;color:green;margin-left:5px;}
#locations_list{margin-bottom:10px;}
.new-location{padding-right: 10px;font-weight:bold;}
.new-location span{color:red;font-size: 14px;cursor: pointer;}
.clear-location{color:red;display:none;cursor: pointer;}
textarea {box-sizing: border-box;resize: none;}
.sf-box img{width: 16px;margin-left: 10px;cursor: pointer;vertical-align: middle;}
</style>

<div class="main-content main-frm">
	<div id="admin-alerts" class="ct-set warn-set">
		<div class="ct-box warn-box">
			<h6 class="ct-legend hn warn"><span>Information:</span></h6>
			<p>Edit fields and click "Submit". Fields highlighted in red are required.</p>
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
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Property Name</strong></span></label><br>
					<span class="fld-input"><select name="property_id" required>
<?php
echo '<option value="" selected="selected" disabled>Select Property</option>'."\n";
foreach ($property_info as $cur_info) {
	if ($main_info['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select></span>
				</div>
			</div>
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Location</span><small>Leave your message</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="location" cols="55" ><?php echo html_encode($main_info['location']) ?></textarea></span></div>
				</div>
			</div>
			
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Project Description</span><small>Leave your message</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="project_desc" cols="55" ><?php echo html_encode($main_info['project_desc']) ?></textarea></span></div>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Date of Notice Posting</strong></span></label><br>
					<span class="fld-input"><input type="date" name="noticed_date" value="<?php echo sm_date_input($main_info['noticed_date']) ?>"><img src="<?php echo BASE_URL ?>/img/clear.png" onclick="clearDate(<?php echo $page_param['item_count'] ?>)"></span>
				</div>
			</div>
			
			<div class="content-head">
				<h2 class="hn"><span>Vendor Information</span></h2>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Vendor</strong></span></label><br>
					<span class="fld-input"><select name="vendor_id">
<?php
echo '<option value="" selected="selected" disabled>Select Vendor</option>'."\n";
foreach ($vendors_info as $cur_info) {
	if ($main_info['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>PO Number</strong></span></label><br>
					<span class="fld-input"><input type="text" name="po_number" size="15" value="<?php echo html_encode($main_info['po_number']) ?>"></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Total Amount</strong></span><small style="color:red">Use whole numbers or decimal numbers separated by periods, for example: <strong>456.55</strong> . Do not use other symbols like: $?,@# and etc.</small></label><br>
					<span class="fld-input"><input type="text" name="total_cost" size="15" value="<?php echo isset($_POST['total_cost']) ? html_encode($_POST['total_cost']) : $total_cost ?>" placeholder="0.00"></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Start Date</strong></span></label><br>
					<span class="fld-input"><input type="date" name="start_date" value="<?php echo sm_date_input($main_info['start_date']) ?>"><img src="<?php echo BASE_URL ?>/img/clear.png" onclick="clearDate(<?php echo $page_param['item_count'] ?>)"></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>End Date</strong></span></label><br>
					<span class="fld-input"><input type="date" name="end_date" value="<?php echo sm_date_input($main_info['end_date']) ?>"><img src="<?php echo BASE_URL ?>/img/clear.png" onclick="clearDate(<?php echo $page_param['item_count'] ?>)"></span>
				</div>
			</div>
			
			<div class="content-head">
				<h2 class="hn"><span>Final Work Information</span></h2>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Job Completion Inspection Date</strong></span></label><br>
					<span class="fld-input"><input type="date" name="completion_date" value="<?php echo sm_date_input($main_info['completion_date']) ?>"><img src="<?php echo BASE_URL ?>/img/clear.png" onclick="clearDate(<?php echo $page_param['item_count'] ?>)"></span>
				</div>
			</div>
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Remarks</span><small>Leave your comment</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="remarks" cols="55" ><?php echo html_encode($main_info['remarks']) ?></textarea></span></div>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Job Status</strong></span></label><br>
					<span class="fld-input filled"><select name="job_status" required>
<?php
foreach ($work_statuses as $key => $status) {
	if ($main_info['job_status'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.html_encode($status).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.html_encode($status).'</option>'."\n";
}
?>
					</select></span>
				</div>
			</div>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="form_sent" value="Update Project"/></span>
		</div>
	</form>
</div>

<script>
function checkFormSubmit(form)
{
	$('form input[name="form_sent"]').css("pointer-events","none");
	$('form input[name="form_sent"]').val("Processing...");
}
function clearDate(id){
	$('.set'+id+' input').val('');
}
</script>

<?php
require SITE_ROOT.'footer.php';