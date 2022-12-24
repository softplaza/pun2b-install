<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 2))
	message($lang_common['No permission']);

$HcaWOM = new HcaWOM;

if (isset($_POST['add']))
{
	$form_data = array(
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_id'			=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
		
		'request_type'		=> isset($_POST['request_type']) ? intval($_POST['request_type']) : 1,
		'template_id'		=> isset($_POST['template_id']) ? intval($_POST['template_id']) : 0,

		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,
		'wo_message'		=> isset($_POST['wo_message']) ? swift_trim($_POST['wo_message']) : '',

		'dt_created'		=> date('Y-m-d\TH:i:s'),
		'requested_by'		=> $User->get('id'),
		'wo_status'			=> 1
	);

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select a property from dropdown list.');

	if (empty($Core->errors))
	{
		// Create a new Work Order
		$new_id = $DBLayer->insert_values('hca_wom_work_orders', $form_data);

		$task_data = [
			'work_order_id' 	=> $new_id,
			'item_id'			=> isset($_POST['item_id'][1]) ? intval($_POST['item_id'][1]) : 0,
			'task_action'		=> isset($_POST['task_action'][1]) ? intval($_POST['task_action'][1]) : 0,
			'assigned_to'		=> isset($_POST['assigned_to'][1]) ? intval($_POST['assigned_to'][1]) : 0,
			'task_message'		=> isset($_POST['task_message'][1]) ? swift_trim($_POST['task_message'][1]) : '',
			'time_created'		=> time(),
			'task_status'		=> 2 // set as already accepted
		];
		$new_tid = $DBLayer->insert_values('hca_wom_tasks', $task_data);

		// Add flash message
		$flash_message = 'Work Order has been created';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_wom_work_order', $new_id), $flash_message);
	}
}

$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'ORDER BY'	=> 'p.display_position',
	//'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] = 'p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = [];
$property_manager = ['id' => 0, 'pro_name' => ''];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
	$property_manager = [
		'id' => $row['id'],
		'pro_name' => $row['pro_name']
	];
}

$Core->set_page_id('hca_wom_work_order_new', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">New Work Order</h6>
		</div>
		<div class="card-body">

			<div class="row">
<?php if ($is_manager): ?>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_property_id">Property</label>
					<input type="hidden" name="property_id" value="<?=$property_manager['id']?>">
					<input type="text" value="<?=html_encode($property_manager['pro_name'])?>" class="form-control form-control-sm" id="fld_property_id" readonly>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_unit_id">Unit #</label>
					<select id="fld_unit_id" name="unit_id" class="form-select form-select-sm">
						<option value="0" selected>Common area</option>
<?php
$query = array(
	'SELECT'	=> 'un.*',
	'FROM'		=> 'sm_property_units AS un',
	'ORDER BY'	=> 'un.unit_number',
	'WHERE'		=> 'un.property_id='.$property_manager['id'],
);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_units = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_units[] = $row;
}
foreach ($sm_property_units as $cur_info)
{
	if(isset($_POST['unit_id']) && $_POST['unit_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['unit_number']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['unit_number']).'</option>'."\n";
}
?>
					</select>
				</div>
<?php else: ?>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_property_id">Properties</label>
					<select id="fld_property_id" name="property_id" class="form-select form-select-sm" required onchange="getUnits()">
<?php
echo '<option value="0" selected disabled>Select one</option>'."\n";
foreach ($sm_property_db as $cur_info)
{
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_unit_number">Unit #</label>
					<div id="unit_list">
						<input type="text" name="unit_id" value="" class="form-control form-control-sm" id="fld_unit_number" disabled>
					</div>
				</div>
<?php endif; ?>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label">Request type</label>

					<div class="form-check">
						<input class="form-check-input" type="radio" name="request_type" value="1" id="fld_request_type1" checked required>
						<label class="form-check-label" for="fld_request_type1">Property Work Order</label>
					</div>

					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="request_type" value="2" id="fld_request_type2">
						<label class="form-check-label" for="fld_request_type2">In-House Request</label>
					</div>
				</div>

<?php
$query = array(
	'SELECT'	=> 'tpl.*',
	'FROM'		=> 'hca_wom_tpl_wo AS tpl',
	'ORDER BY'	=> 'tpl.tpl_name',
	//'WHERE'		=> 'tpl.property_id='.$property_manager['id'],
);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_tpl_wo = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_tpl_wo[] = $row;
}

if (!empty($hca_wom_tpl_wo)):
?>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_template_id">Templates</label>
					<select id="fld_template_id" name="template_id" class="form-select form-select-sm" onchange="getWorkOrderTemplate()">
						<option value="0" selected>Select one</option>
<?php
foreach ($hca_wom_tpl_wo as $cur_info)
{
	if(isset($_POST['unit_id']) && $_POST['unit_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['tpl_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['tpl_name']).'</option>'."\n";
}
?>
					</select>
				</div>
<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="card" id="work_order_template">

		<div class="card-body">

			<div class="col-md-2 mb-2">
				<label class="form-label" for="fld_priority">Priority</label>
				<select name="priority" id="fld_priority" class="form-select form-select-sm">
<?php
	foreach ($HcaWOM->priority as $key => $val)
	{
		if (isset($_POST['priority']) && intval($_POST['priority']) == $key)
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
	}
?>
				</select>
			</div>

			<div class="mb-2">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="has_animal" id="fld_has_animal" value="1" <?php echo (isset($_POST['has_animal']) && $_POST['has_animal'] == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_has_animal">Pets in Unit</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" name="enter_permission" id="fld_enter_permission" value="1" <?php echo (isset($_POST['enter_permission']) && $_POST['enter_permission'] == 1 ? ' checked' : '') ?>>
					<label class="form-check-label" for="fld_enter_permission">Permission to Enter</label>
				</div>
			</div>

			<div class="mb-2">
				<textarea type="text" name="wo_message" class="form-control" placeholder="Enter any special instructions for entry (example: After 2 pm only please)" rows="2"><?php echo (isset($_POST['wo_message']) ? html_encode($_POST['wo_message']) : '') ?></textarea>
			</div>

		</div>

		<div class="card-body badge-secondary">
			<h5 class="card-title mb-0">Task #1</h5>
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_item_id1">Items</label>
					<select id="fld_item_id1" name="item_id[1]" class="form-select form-select-sm">
<?php
$query = [
	'SELECT'	=> 'i.*, tp.type_name',
	'FROM'		=> 'hca_wom_items AS i',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_wom_types AS tp',
			'ON'			=> 'tp.id=i.item_type'
		],
	],
	'ORDER BY'	=> 'i.item_type, i.item_name',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_items = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_items[] = $row;
}

$optgroup = 0;
foreach ($hca_wom_items as $cur_info)
{
	if ($cur_info['item_type'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_info['type_name']).'">';
		$optgroup = $cur_info['item_type'];
	}
	if (isset($_POST['item_id'][1]) && $_POST['item_id'][1] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['item_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>'."\n";
}
?>
					</select>
				</div>

				<div class="col-md-2 mb-3">
					<label class="form-label" for="fld_task_action1">Action/Problem</label>
					<select id="fld_task_action1" name="task_action[1]" class="form-select form-select-sm">
<?php
$query = array(
	'SELECT'	=> 'pr.*',
	'FROM'		=> 'hca_wom_problems AS pr',
	'ORDER BY'	=> 'pr.problem_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_problems = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_problems[] = $row;
}

foreach ($hca_wom_problems as $cur_info)
{
	if (isset($_POST['task_action'][1]) && $_POST['task_action'][1] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['problem_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['problem_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_tassigned_to1">Assigned to</label>
					<select id="fld_assigned_to1" name="assigned_to[1]" class="form-select form-select-sm">
						<option value="0">Select one</option>
<?php
$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id=3',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$optgroup = 0;
foreach ($users_info as $cur_info)
{
	if ($cur_info['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_info['g_title']).'">';
		$optgroup = $cur_info['group_id'];
	}
	if (isset($_POST['assigned_to'][1]) && $_POST['assigned_to'][1] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>

			<div class="mb-2">
				<textarea type="text" name="task_message[1]" class="form-control" placeholder="Enter details here"><?php echo (isset($_POST['task_message'][1]) ? html_encode($_POST['task_message'][1]) : '') ?></textarea>
			</div>

		</div>

		<div class="card-body bm-3">
			<button type="submit" name="add" class="btn btn-primary">Submit</button>
		</div>

	</div>
</form>

<script>
function getUnits()
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_units')) ?>";
	var id = $("#fld_property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#unit_list").empty().html(re.unit_list);
		},
		error: function(re){
			document.getElementById("#unit_list").innerHTML = re;
		}
	});
}
function getWorkOrderTemplate()
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_wo_template')) ?>";
	var id = $("#fld_template_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_wo_template') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#work_order_template").empty().html(re.template_body);
		},
		error: function(re){
			document.getElementById("#unit_list").innerHTML = re;
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';
