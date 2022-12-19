<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi', 24))
	message($lang_common['No permission']);

$Moisture = new Moisture;
$HcaMi = new HcaMi;

// OPTIONS START
// Set project ID
if (isset($_POST['add_vendor']))
{
	$form_data = [
		'vendor_name' => isset($_POST['vendor_name']) ? swift_trim($_POST['vendor_name']) : '',
		'hca_5840' => 1
	];

	if ($form_data['vendor_name'] == '')
		$Core->add_error('Vendor name can not be empty. Please fill out Vendor Name and send form again.');
	else
	{
		$num_rows = $DBLayer->getNumRows('sm_vendors', 'vendor_name=\''.$DBLayer->escape($form_data['vendor_name']).'\'');

		if ($num_rows > 0)
			$Core->add_error('The vendor "'.html_encode($form_data['vendor_name']).'" already in the List of Available Vendors. Please setup existing Vendor.');
	}

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert_values('sm_vendors', $form_data);
		
		foreach($HcaMi->default_services as $key => $title)
		{
			$data = [
				'vendor_id'		=> $new_id,
				'group_id'		=> $key,
				'enabled'		=> isset($_POST['service_'.$key]) ? intval($_POST['service_'.$key]) : 0
			];
			$DBLayer->insert('hca_5840_vendors_filter', $data);
		}

		$flash_message = 'Vendor '.$form_data['vendor_name'].' has been added.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['create_filter']))
{
	$vendor_id = intval(key($_POST['create_filter']));

	if ($vendor_id > 0)
	{
		foreach($HcaMi->default_services as $key => $title)
		{
			$data = [
				'vendor_id'		=> $vendor_id,
				'group_id'		=> $key,
				'enabled'		=> 0
			];
			$DBLayer->insert('hca_5840_vendors_filter', $data);
		}

		// Add flash message
		$flash_message = 'Vendor filter created';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_filter']))
{
	$vendor_id = intval(key($_POST['delete_filter']));

	if ($vendor_id > 0)
	{
		$DBLayer->delete('hca_5840_vendors_filter', 'vendor_id='.$vendor_id);

		//$DBLayer->update('sm_vendors', ['hca_5840' => 0], $vendor_id);

		// Add flash message
		$flash_message = 'Filter has been deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$Core->set_page_id('hca_5840_admin_vendors', 'hca_5840');
require SITE_ROOT.'header.php';

// VENDORS SETTINGS
$query = array(
	'SELECT'	=> 'v.*',
	'FROM'		=> 'sm_vendors AS v',
	'WHERE'		=> 'v.hca_5840=1',
	'ORDER BY'	=> 'v.vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

$query = array(
	'SELECT'	=> 'vf.*',
	'FROM'		=> 'hca_5840_vendors_filter AS vf',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$default_vendors = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$default_vendors[] = $row;
}
?>	


<div class="accordion-item mb-3" id="accordionExample">
	<h2 class="accordion-header" id="heading0">
		<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse0" aria-expanded="true" aria-controls="collapse0">Suggest a Vendor</button>
	</h2>
	<div id="collapse0" class="accordion-collapse collapse" aria-labelledby="heading0" data-bs-parent="#accordionExample">
		<div class="accordion-body card-body">

			<form method="post" accept-charset="utf-8" action="" class="was-validated">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="row">
					<div class="col-md-3">
						<div class="mb-3">
							<label class="form-label" for="fld_vendor_name">Vendor name</label>
							<input type="text" name="vendor_name" value="<?php echo isset($_POST['vendor_name']) ? html_encode($_POST['vendor_name']) : '' ?>" class="form-control" id="fld_vendor_name" placeholder="Enter Company Name" required>
						</div>
					</div>
				</div>

				<h5 class="mb-0">Project settings</h5>
				<hr class="my-2">
<?php
foreach($HcaMi->default_services as $key => $title)
{
?>
				<div class="mb-2">
					<div class="form-check form-check-inline">
						<input type="hidden" name="service_<?=$key?>" value="0">
						<input class="form-check-input" id="fld_service_<?=$key?>" type="checkbox" name="service_<?=$key?>" value="1">
						<label class="form-check-label" for="fld_service_<?=$key?>"><?=$title?></label>
					</div>
				</div>
<?php
}
?>
				<button type="submit" name="add_vendor" class="btn btn-primary">Submit</button>

			</form>
		</div>
	</div>
</div>

<div class="callout callout-info mb-3">To display a Vendor in your project, create a filter and set up group permissions. If you don't see a vendor listed below, suggest a new Vendor.</div>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card-header">
		<h6 class="card-title mb-0">Available vendors</h6>
	</div>
	<table class="table table-sm table-bordered table-striped">
		<thead>
			<tr>
				<th rowspan="2" class="align-middle">Vendor name</th>
				<th colspan="<?=count($HcaMi->default_services)+1?>"><h6>Services</h6></th>
			</tr>
			<tr>
<?php
	foreach($HcaMi->default_services as $key => $title)
		echo '<th>'.$title.'</th>';
?>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php

if (!empty($vendors_info))
{
	foreach($vendors_info as $cur_info)
	{
		echo '<tr id="vid'.$cur_info['id'].'">';
		echo '<td><span class="fw-bold">'.html_encode($cur_info['vendor_name']).'</span></td>';

		foreach($HcaMi->default_services as $key => $title)
		{
			$default_vendor = $HcaMi->check_vendor($default_vendors, $cur_info['id'], $key);
			if (!empty($default_vendor))
			{
				if ($default_vendor['group_id'] == $key)
				{
					$checked = ($default_vendor['enabled'] == 1) ? 'checked' : '';
					echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateVendor('.$default_vendor['id'].')" id="vendor_'.$default_vendor['id'].'" '.$checked.'></div></td>';
				}
			}
			else
			{
				echo '<td colspan="'.count($HcaMi->default_services).'"><button type="submit" name="create_filter['.$cur_info['id'].']" class="badge bg-primary ms-1 float-end">Create filter</button></td>';
				break;
			}
		}

		echo '<td><button type="submit" name="delete_filter['.$cur_info['id'].']" class="badge bg-danger ms-1" onclick="return confirm(\'Are you sure you want to reset this vendor filter?\')">Reset</button></td>';
		echo '</tr>';
	}
}
?>
		</tbody>
	</table>
</form>

<script>
function showToastMessage()
{
	const toastLiveExample = document.getElementById('liveToast');
	const toast = new bootstrap.Toast(toastLiveExample);
	toast.show();
}
function updateVendor(id){
	var val = 0;
	if($('#vendor_'+id).prop("checked") == true){val = 1;}
	else {val = 0;}
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_5840_ajax_update_default_vendor')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_5840_ajax_update_default_vendor') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,val:val,csrf_token:csrf_token}),
		success: function(re){
			var msg = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
			msg += '<div class="toast-body toast-success">Settings updated.</div>';
			msg += '</div>';
			$("#toast_container").empty().html(msg);
			showToastMessage();
		},
		error: function(re){
			var msg = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
			msg += '<div class="toast-body toast-danger">Failed to update the settings.</div>';
			msg += '</div>';
			$("#toast_container").empty().html(msg);
			showToastMessage();
		}
	});	
}
</script>

<?php
require SITE_ROOT.'footer.php';
