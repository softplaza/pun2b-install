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
		'priority'			=> isset($_POST['priority']) ? intval($_POST['priority']) : 1,
		//'has_animal'		=> isset($_POST['has_animal']) ? intval($_POST['has_animal']) : 0,
		//'enter_permission'	=> isset($_POST['enter_permission']) ? intval($_POST['enter_permission']) : 0,

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

		//$DBLayer->insert_values('hca_wom_tasks', ['work_order_id' => $new_id]);

		// Add flash message
		$flash_message = 'Work Order has been created';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_wom_work_order', $new_id), $flash_message);
	}
}

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
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
}

$Core->set_page_id('hca_wom_work_order_new', 'hca_wom');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">A new Work Order</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_property_id">Available Properties</label>
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
				<div class="col-md-2">
					<label class="form-label" for="fld_unit_number">Unit #</label>
					<div id="unit_list">
						<input type="text" name="unit_id" value="" class="form-control form-control-sm" id="fld_unit_number" disabled>
					</div>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_priority">Priority</label>
					<select name="priority" id="fld_priority" class="form-select form-select-sm" required>
						<option value="" selected disabled>Select one</option>
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
				<div class="col-md-3">
					<label class="form-label">Submit Date</label>
					<h5 class="mb-0"><?php echo date('m/d/Y \a\t H:i') ?></h5>
				</div>
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
</script>

<?php
require SITE_ROOT.'footer.php';
