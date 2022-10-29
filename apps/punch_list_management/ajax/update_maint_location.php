<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('punch_list_management')) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$key = isset($_POST['key']) ? intval($_POST['key']) : 0;
$val = isset($_POST['val']) ? intval($_POST['val']) : 0;

function removeArrayValue($array, $value)
{
	$output = [];
	if (!empty($array))
	{
		foreach($array as $key => $val)
		{
			if ($value != $val)
				$output[] = $val;
		}
	}
	return $output;
}

if ($id > 0)
{
	$query = array(
		'SELECT'	=> 'property_exceptions',
		'FROM'		=> 'punch_list_management_maint_items',
		'WHERE'		=> 'id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$row = $DBLayer->fetch_assoc($result);

	$property_exceptions = !empty($row['property_exceptions']) ? explode(',', $row['property_exceptions']) : [];

	if ($val == '1')
		array_push($property_exceptions, $key);
	else
		$property_exceptions = removeArrayValue($property_exceptions, $key);
	
	$exceptions = !empty($property_exceptions) ? implode(',', $property_exceptions) : '';
	$DBLayer->update('punch_list_management_maint_items', ['property_exceptions' => $exceptions], $id);

	echo json_encode(array(
		'message'		=> '<div class="alert alert-success alert-dismissible fade show" role="alert">Location has been updated. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
	));	
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
