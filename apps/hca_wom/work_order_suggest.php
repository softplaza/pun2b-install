<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 4))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaWOM = new HcaWOM;

if (isset($_POST['add']))
{
	$wo_data = array(
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_id'			=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		//'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		//'enter_permission'=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,

		'dt_created'		=> date('Y-m-d\TH:i:s'),
		'requested_by'		=> $User->get('id'),
		//'wo_status'			=> 1
	);

	$task_data = array(
		'item_id'			=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'task_action'		=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'task_message'		=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'time_created'		=> time(),
	);

	if ($wo_data['property_id'] == 0)
		$Core->add_error('Select a property from dropdown list.');

	if (empty($Core->errors))
	{
		// Create a new Work Order
		$new_id = $DBLayer->insert_values('hca_wom_work_orders', $wo_data);

		if ($new_id)
		{
			$task_data['work_order_id'] = $new_id;
			$new_tid = $DBLayer->insert_values('hca_wom_tasks', $task_data);

			if ($new_tid)
			{
				$query = array(
					'UPDATE'	=> 'hca_wom_work_orders',
					'SET'		=> 'num_tasks=num_tasks+1, last_task_id='.$new_tid,
					'WHERE'		=> 'id='.$new_id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);


				// Send Email to Manager if needed



			}
		}

		// Add flash message
		$flash_message = 'Work Order has been created.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_wom_work_orders_suggested'), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'ORDER BY'	=> 'p.display_position',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] = 'p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
}

$query = array(
	'SELECT'	=> 'i.*, tp.type_name',
	'FROM'		=> 'hca_wom_items AS i',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'hca_wom_types AS tp',
			'ON'			=> 'tp.id=i.item_type'
		],
	],
	'ORDER BY'	=> 'tp.type_name, i.item_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_items = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_items[] = $row;
}

$Core->set_page_id('hca_wom_work_order_suggest', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">A new Work Order</h6>
		</div>
		<div class="card-body">

			<div class="row">
				<div class="col-md-3 mb-3 was-validated">
					<label class="form-label" for="fld_property_id">Available Properties</label>
					<select id="fld_property_id" name="property_id" class="form-select form-select-sm" required onchange="getUnits()">
<?php
echo '<option value="" selected disabled>Select one</option>'."\n";
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
				<div class="col-md-2 mb-3">
					<label class="form-label" for="fld_unit_number">Unit #</label>
					<div id="unit_list">
						<input type="text" name="unit_id" value="" class="form-control form-control-sm" id="fld_unit_number" disabled>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3 mb-3">
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
			</div>

			<div class="row">
				<div class="col-md-3 mb-3 was-validated">
					<label class="form-label" for="fld_item_id">Item</label>
					<select name="item_id" class="form-select form-select-sm" id="fld_item_id" required onchange="getActions()">
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="" selected disabled >Select an item</option>'."\n";
	foreach ($hca_wom_items as $cur_info)
	{
		if ($cur_info['item_type'] != $optgroup) {
			if ($optgroup) {
				echo '</optgroup>';
			}
			echo '<optgroup label="'.html_encode($cur_info['type_name']).'">';
			$optgroup = $cur_info['item_type'];
		}
		
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['item_name']).'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_task_action">Action/Problem</label>
					<select name="task_action" class="form-select form-select-sm" id="fld_task_action">

					</select>
				</div>
			</div>

			<div class="mb-3 was-validated">
				<label class="form-label" for="fld_task_message">Comments</label>
				<textarea type="text" name="task_message" class="form-control" id="fld_task_message" placeholder="Enter details here" required><?php echo isset($_POST['task_message']) ? html_encode($_POST['task_message']) : '' ?></textarea>
			</div>

			<div class="mb-3">
				<button type="submit" name="add" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</div>
</form>

<script>
function getUnits(){
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
function getActions(){
	var item_id = $("#fld_item_id").val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,csrf_token:csrf_token}),
		success: function(re){
			$("#fld_task_action").empty().html(re.item_actions);

		},
		error: function(re){
			document.getElementById("#fld_task_action").innerHTML = re;
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';
