<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$keywords = isset($_POST['keywords']) ? swift_trim($_POST['keywords']) : '';

$SwiftUploader = new SwiftUploader;

if ($keywords != '')
{
	$search_query = [];
	$search_query[] = 'item_number LIKE \''.$DBLayer->escape('%'.$keywords.'%').'\'';
	$search_query[] = 'item_name LIKE \''.$DBLayer->escape('%'.$keywords.'%').'\'';
	
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'swift_inventory_management_items as i',
		'WHERE'		=> implode(' OR ', $search_query),
		'ORDER BY'	=> 'i.item_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$db_info = $item_ids = [];
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$db_info[] = $row;
		$item_ids[] = $row['id'];
	}

	$json_array = [];
	if (!empty($db_info))
	{
		$SwiftUploader->getProjectFiles('swift_inventory_management', $item_ids);

		$json_array[] = '<div class="list-group">';
		foreach($db_info as $cur_info)
		{
			$image_link = $SwiftUploader->getCurProjectLink($cur_info['id']);

			$json_array[] = '<a href="#" class="list-group-item list-group-item-action" aria-current="true" onclick="selectItem('.$cur_info['id'].')">';
			$json_array[] = '<div class="d-flex w-100 justify-content-between">';
			$json_array[] = '<img src="'.$image_link.'" style="height:80px"">';
			$json_array[] = '<h5 class="mb-1 fw-bold">'.html_encode($cur_info['item_name']).'</h5>';
			//$json_array[] = '<span class="badge bg-primary rounded-pill">'.html_encode($cur_info['quantity_total']).'</span>';
			$json_array[] = '</div>';
			//$json_array[] = '<p class="mb-1">'.html_encode($cur_info['item_description']).'</p>';
			$json_array[] = '<p class="mb-1">#'.html_encode($cur_info['item_number']).'</p>';
			$json_array[] = '</a>';

/*
			$json_array[] = '<div class="d-flex w-100 justify-content-between">';
			$json_array[] = '<div class="row">';
			$json_array[] = '<div class="col-6 col-md-4"><img src="'.$image_link.'" class="rounded float-start" style="height:80px""></div>';
			$json_array[] = '<div class="col-md-8">';
			$json_array[] = '<h5 class="mb-1 fw-bold">'.html_encode($cur_info['item_name']).'</h5>';
			$json_array[] = '</div>';
			$json_array[] = '</div>';
			$json_array[] = '</div>';


			$json_array[] = '<div class="d-flex position-relative">';
			$json_array[] = '<img src="'.$image_link.'" class="flex-shrink-0 me-3">';
			$json_array[] = '<div>';
			$json_array[] = '<h5 class="mt-0">'.html_encode($cur_info['item_name']).'</h5>';
			$json_array[] = '<p>#'.html_encode($cur_info['item_number']).'</p>';
			$json_array[] = '</div>';
			$json_array[] = '</div>';
*/
		}
		$json_array[] = '</div>';
	}
	
	echo json_encode(array('search_results' => implode("\n", $json_array)));
}
else if ($id > 0)
{
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'swift_inventory_management_items as i',
		'WHERE'		=> 'i.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$db_info = $DBLayer->fetch_assoc($result);

	$json_array = [];
	$json_array[] = '<div class="list-group">';
	$json_array[] = '<a href="#" class="list-group-item list-group-item-action" aria-current="true">';
	$json_array[] = '<div class="d-flex w-100 justify-content-between">';
	$json_array[] = '<h5 class="mb-1 fw-bold">'.html_encode($db_info['item_name']).'</h5>';
	$json_array[] = '<span class="badge bg-primary rounded-pill">'.html_encode($db_info['quantity_total']).'</span>';
	$json_array[] = '</div>';
	//$json_array[] = '<p class="mb-1">Some placeholder content in a paragraph.</p>';
	$json_array[] = '<small>And some small print.</small>';
	$json_array[] = '</a>';
	$json_array[] = '</div>';
	$json_array[] = '<div class="mb-3">';
	$json_array[] = '<label for="input_item_name" class="form-label"></label>';
	$json_array[] = '<h5 class="mb-1 fw-bold">Quantity</h5>';
	$json_array[] = '<input type="hidden" name="id" value="'.$db_info['id'].'">';
	$json_array[] = '<input type="number" name="quantity" value="0" class="form-control" id="input_quantity" min="0" required>';
	$json_array[] = '</div>';
	$json_array[] = '<div class="col-md-8">';
	$json_array[] = '<button type="submit" name="stock_out" class="btn btn-danger">- Remove Item</button>';
	$json_array[] = '<button type="submit" name="stock_in" class="btn btn-success">+ Restock Item</button>';
	$json_array[] = '</div>';
	
	echo json_encode(array('selected_item' => implode("\n", $json_array)));	
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
