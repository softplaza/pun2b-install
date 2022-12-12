<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 24)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$Moisture = new Moisture;
$HcaMi = new HcaMi;

// OPTIONS START
// Set project ID

if (isset($_POST['create_filter']))
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
			$(".msg-section").empty().html(re.message);
		},
		error: function(re){
			$(".msg-section").empty().html('Error: Please refresh this page and try again.');
		}
	});	
}
</script>

<?php
require SITE_ROOT.'footer.php';
