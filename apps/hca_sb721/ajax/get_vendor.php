<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message('No permission');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'WHERE'		=> 'hca_sb721=1',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

if ($id > 0)
{
	$query = array(
		'SELECT'	=> 'v2.*, v1.vendor_name',
		'FROM'		=> 'hca_sb721_vendors AS v2',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_vendors AS v1',
				'ON'			=> 'v1.id=v2.vendor_id'
			),
		),
		'ORDER BY'	=> 'v1.vendor_name',
		'WHERE'		=> 'v2.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$project_vendor = $DBLayer->fetch_assoc($result);

	$modal_hidden = $modal_body = $modal_footer = [];

	$modal_hidden[] = '<input type="hidden" name="vid" value="'.$id.'" />';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label" for="fld_vendor_id">Vendor</label>';
	$modal_body[] = '<select name="vendor_id" class="form-select">';
	$modal_body[] = '<option value="0" selected="selected">Select a vendor</option>';
	foreach ($vendors_info as $vendor)
	{
		if ($project_vendor['vendor_id'] == $vendor['id'])
			$modal_body[] = "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'" selected>'.html_encode($vendor['vendor_name']).'</option>';
		else
			$modal_body[] = "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'">'.html_encode($vendor['vendor_name']).'</option>';
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_date_bid" class="col-form-label">Bid date</label>';
	$modal_body[] = '<input type="date" name="date_bid" value="'.format_date($project_vendor['date_bid']).'" class="form-control" id="fld_date_bid">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_date_start_job" class="col-form-label">Job start date</label>';
	$modal_body[] = '<input type="date" name="date_start_job" value="'.format_date($project_vendor['date_start_job']).'" class="form-control" id="fld_date_start_job">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_date_end_job" class="col-form-label">Job end date</label>';
	$modal_body[] = '<input type="date" name="date_end_job" value="'.format_date($project_vendor['date_end_job']).'" class="form-control" id="fld_date_end_job">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_cost" class="col-form-label">Cost</label>';
	$modal_body[] = '<input type="text" name="cost" value="'.$project_vendor['cost'].'" class="form-control" id="fld_cost">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_comment" class="col-form-label">Comment</label>';
	$modal_body[] = '<textarea name="comment" class="form-control" id="fld_comment" rows="5">'.$project_vendor['comment'].'</textarea>';
	$modal_body[] = '</div>';

	$modal_footer[] = '<button type="submit" name="update_vendor" class="btn btn-primary">Update</button>';
	$modal_footer[] = '<button type="submit" name="delete_vendor" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this action?\')">Delete</button>';

	echo json_encode(array(
		'modal_hidden'	=> implode("\n", $modal_hidden),
		'modal_body'	=> implode("\n", $modal_body),
		'modal_footer'	=> implode("\n", $modal_footer),
	));
}
else
{
	$modal_body = $modal_footer = [];
	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label" for="fld_vendor_id">Vendor</label>';
	$modal_body[] = '<select name="vendor_id" class="form-select">';
	$modal_body[] = '<option value="0" selected="selected">Select a vendor</option>';
	foreach ($vendors_info as $vendor)
	{
		$modal_body[] = "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'">'.html_encode($vendor['vendor_name']).'</option>';
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_date_bid" class="col-form-label">Bid date</label>';
	$modal_body[] = '<input type="date" name="date_bid" class="form-control" id="fld_date_bid">';
	//$modal_body[] = '<label class="text-danger" onclick="document.getElementById(\'field_date_city_inspection_end\').value=\'\'">Click to clear date</label>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_date_start_job" class="col-form-label">Job start date</label>';
	$modal_body[] = '<input type="date" name="date_start_job" class="form-control" id="fld_date_start_job">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_date_end_job" class="col-form-label">Job end date</label>';
	$modal_body[] = '<input type="date" name="date_end_job" class="form-control" id="fld_date_end_job">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_cost" class="col-form-label">Cost</label>';
	$modal_body[] = '<input type="text" name="cost" class="form-control" id="fld_cost">';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label for="fld_comment" class="col-form-label">Comment</label>';
	$modal_body[] = '<textarea name="comment" class="form-control" id="fld_comment" rows="5"></textarea>';
	$modal_body[] = '</div>';

	$modal_footer[] = '<button type="submit" name="add_vendor" class="btn btn-primary">Add vendor</button>';

	echo json_encode(array(
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => implode("\n", $modal_footer),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
