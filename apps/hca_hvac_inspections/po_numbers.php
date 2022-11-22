<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access8 = ($User->checkAccess('hca_hvac_inspections', 8)) ? true : false;

if (!$access8)
	message($lang_common['No permission']);

$HcaHVACInspections = new HcaHVACInspections;

if (isset($_POST['update']))
{
	//$form_data = [
	//	'id' => isset($_GET['id']) ? intval($_GET['id']) : 0,
	//	'po_number' => isset($_POST['po_number']) ? swift_trim($_POST['po_number']) : '',
	//];

	//if ($form_data['po_number'] == '' || $form_data['element_id'] == 0)
	//	$Core->add_error('Filter size field cannot be empty. Select a property from dropdown.');

	if (isset($_POST['po_number']) && !empty($_POST['po_number']))
	{
		foreach($_POST['po_number'] as $id => $value)
		{
			$DBLayer->update('hca_hvac_inspections_items', ['po_number' => swift_trim($value)], $id);
		}
		
		// Add flash message
		$flash_message = 'PO numbers have been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$Core->set_page_title('Filter size management');
$Core->set_page_id('hca_hvac_inspections_po_numbers', 'hca_hvac_inspections');
require SITE_ROOT.'header.php';

$query = array(
	'SELECT'	=> 'i.*',
	'FROM'		=> 'hca_hvac_inspections_items AS i',
	'WHERE'		=> 'i.summary_report=1'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}
?>

<div class="card-header">
	<h6 class="card-title mb-0">List of P.O. Numbers</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<table class="table table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th>Item name</th>
				<th>P.O. Number</th>
			</tr>
		</thead>
		<tbody>

<?php
if (!empty($main_info))
{
	foreach($main_info as $cur_info)
	{
		$actions = ($access8) ? '<a href="'.$URL->link('hca_hvac_inspections_items', $cur_info['id']).'" class="badge bg-primary text-white">Edit</a>' : '';
		$element_name = isset($HcaHVACInspections->equipments[$cur_info['equipment_id']]) ? $HcaHVACInspections->equipments[$cur_info['equipment_id']] : '';
?>
		<tr class="">
			<td class="fw-bold"><?php echo $element_name ?></td>
			<td class="ta-center"><input type="text" name="po_number[<?php echo html_encode($cur_info['id']) ?>]" value="<?php echo html_encode($cur_info['po_number']) ?>"></td>
		</tr>
<?php
	}
}
?>
			</tbody>
		</table>
		<div class="card-body">
			<button type="submit" name="update" class="btn btn-primary">Update</button>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
