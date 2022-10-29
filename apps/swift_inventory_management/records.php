<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$SwiftUploader = new SwiftUploader;

if (isset($_POST['stock_in']) || isset($_POST['stock_out']))
{
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

	if (isset($_POST['stock_in']))
		$query = 'quantity_total=quantity_total+'.$quantity;
	else if (isset($_POST['stock_out']))
		$query = 'quantity_total=quantity_total-'.$quantity;

	$DBLayer->update('swift_inventory_management_items', $query, $id);

	$flash_message = 'Item updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'COUNT(i.id)',
	'FROM'		=> 'swift_inventory_management_items AS i',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'i.*',
	'FROM'		=> 'swift_inventory_management_items AS i',
/*
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'i.property_id=pt.id'
		),
	),
*/
	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $item_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$item_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

$Core->set_page_id('swift_inventory_management_records', 'swift_inventory_management');
require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm" id="swift_inventory_management_records">
	<div class="ct-group">

<?php
if (!empty($main_info))
{
	$SwiftUploader->getProjectFiles('swift_inventory_management', $item_ids);
?>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-striped">
				<thead class="sticky-under-menu">
					<tr class="table-primary">
						<th>Image</th>
						<th>Part name / Part number</th>
						<th>Quantity</th>
						<th>Min</th>
						<th>Max</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
//		$action = '<p class="float-end"><button type="button" class="btn btn-outline-secondary" onclick="updateItem('.$cur_info['id'].')"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"></path><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"></path></svg><span class="visually-hidden">Button</span></button></p>';

		$image = $SwiftUploader->getCurProjectFiles($cur_info['id']);
?>
					<tr id="row<?php echo $cur_info['id'] ?>">
						<td><p><?php echo $image ?></p></td>
						<td>
							<p class="fw-bold"><?php echo html_encode($cur_info['item_number']) ?></p>
							<p><?php echo html_encode($cur_info['item_name']) ?></p>
						</td>
						<td class="text-center fw-bold"><?php echo html_encode($cur_info['quantity_total']) ?></td>
						<td class="text-center"><?php echo html_encode($cur_info['limit_min']) ?></td>
						<td class="text-center"><?php echo html_encode($cur_info['limit_max']) ?></td>
						<td><?php echo html_encode($cur_info['item_description']) ?></td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</form>
<?php
} else {
?>
		<div class="alert alert-warning" role="alert">You have no items on this page.</div>
<?php
}
?>
	</div>
</div>

<div class="modal" tabindex="-1">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?=generate_form_token()?>" />
		<input type="hidden" name="id" value="" />
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Quantity</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeModal()"></button>
				</div>
				<div class="modal-body">
					<div class="row g-3 d-flex justify-content-evenly">
						<div class="col-auto">
							<input type="number" name="quantity" value="0" class="form-control" id="input_quantity_total" min="0">
						</div>
					</div>
				</div>
				<div class="modal-footer justify-content-evenly">
					<button type="submit" name="stock_out" class="btn btn-danger">Remove Item</button>
					<button type="submit" name="stock_in" class="btn btn-success">Restock Item</button>
				</div>
			</div>
		</div>
	</form>
</div>

<script>
function updateItem(id){
	$('form input[name="id"]').val(id);
	$('form input[name="quantity"]').val('0');
	$(".modal").fadeIn();
}
function closeModal(){
	$(".modal").fadeOut();
	$('form input[name="id"]').val('0');
}
</script>

<?php
require SITE_ROOT.'footer.php';