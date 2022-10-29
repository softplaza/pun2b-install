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
	redirect($URL->link('swift_inventory_management_warehouse', $id), $flash_message);
}

$query = array(
	'SELECT'	=> 'COUNT(i.id)',
	'FROM'		=> 'swift_inventory_management_items AS i',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'i.*, w.wh_quantity',
	'FROM'		=> 'swift_inventory_management_items AS i',

	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'swift_inventory_management_warehouse AS w',
			'ON'			=> 'w.item_id=i.id'
		),
	),

	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $item_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$item_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
}

$updated_item = [];
if ($id > 0)
{
	$item_info = $DBLayer->select('swift_inventory_management_items', 'id='.$id);

	$updated_item[] = '<div class="list-group bg-success">';
	$updated_item[] = '<a href="#" class="list-group-item list-group-item-action" aria-current="true" onclick="selectItem('.$item_info['id'].')">';
	$updated_item[] = '<div class="d-flex w-100 justify-content-between">';
	$updated_item[] = '<h5 class="mb-1 fw-bold">'.html_encode($item_info['item_name']).'</h5>';
	$updated_item[] = '<span class="badge bg-primary rounded-pill">'.html_encode($item_info['quantity_total']).'</span>';
	$updated_item[] = '</div>';
	$updated_item[] = '<p class="mb-1">Decription of item.</p>';
	$updated_item[] = '<small>Last updated on Nov 10 2021.</small>';
	$updated_item[] = '</a>';
	$updated_item[] = '</div>';
}

$PagesNavigator->pages_navi_top = false;

$Core->set_page_title('Invertory Management');
$Core->set_page_id('swift_inventory_management_warehouse', 'swift_inventory_management');
require SITE_ROOT.'header.php';
?>
<style>#search_results{position: absolute;}</style>

	<div class="container-fluid">

		<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-8">
							<div class="mb-3">
							<h5 class="mb-1 fw-bold">Keywords</h5>
								<input type="text" name="keywords" value="" class="form-control" id="input_keywords" onkeyup="getKeyWords()" placeholder="Start typing here">
							</div>

							<div id="search_results"></div>
							
						</div>

						<div id="selected_item"><?php echo implode("\n", $updated_item) ?></div>

					</div>
				</div>
			</div>
		</form>
	</div>

	<div class="container-fluid">
		<table class="table table-striped">
			<thead>
				<tr class="table-primary">
					<th>Image</th>
					<th>Part number/name</th>
					<th>Quantity</th>
				</tr>
			</thead>
			<tbody>
<?php
if (!empty($main_info))
{
	$SwiftUploader->getProjectFiles('swift_inventory_management', $item_ids);

	foreach ($main_info as $cur_info)
	{
		$image = $SwiftUploader->getCurProjectFiles($cur_info['id']);
?>
				<tr id="row<?php echo $cur_info['id'] ?>" onclick="selectItem(<?php echo $cur_info['id'] ?>)">
					<td><p><?php echo $image ?></p></td>
					<td>
						<p class="fw-bold"><?php echo html_encode($cur_info['item_number']) ?></p>
						<p><?php echo html_encode($cur_info['item_name']) ?></p>
					</td>
					<td class="fw-bold"><?php echo html_encode($cur_info['quantity_total']) ?></td>
				</tr>
<?php
	}
}
else
{
	?>
				<tr class="alert alert-warning"><td colspan="3">No items on this page</td></tr>
<?php
}
?>
			</tbody>
		</table>
	</div>



<script>
function getKeyWords(){
	var keywords = $('#input_keywords').val();
	var csrf_token = "<?php echo generate_form_token($URL->link('swift_inventory_management_ajax_get_search_results')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('swift_inventory_management_ajax_get_search_results') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({keywords:keywords,csrf_token:csrf_token}),
		success: function(re){
			$("#search_results").empty().html(re.search_results);
		},
		error: function(re){
			$("#search_results").empty().html(re.search_results);
		}
	});
	$("#selected_item").empty().html('');
}
function selectItem(id){
	var csrf_token = "<?php echo generate_form_token($URL->link('swift_inventory_management_ajax_get_search_results')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('swift_inventory_management_ajax_get_search_results') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#selected_item").empty().html(re.selected_item);
		},
		error: function(re){
			$("#selected_item").empty().html(re.selected_item);
		}
	});
	$("#search_results").empty().html('');
}
</script>

<?php
require SITE_ROOT.'footer.php';