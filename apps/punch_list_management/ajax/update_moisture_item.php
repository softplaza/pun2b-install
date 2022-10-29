<?php

if (!defined('SITE_ROOT') )
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
		'SELECT'	=> 'status_exceptions',
		'FROM'		=> 'punch_list_management_maint_moisture',
		'WHERE'		=> 'id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$row = $DBLayer->fetch_assoc($result);

	$status_exceptions = !empty($row['status_exceptions']) ? explode(',', $row['status_exceptions']) : [];

	if ($val == '1')
		array_push($status_exceptions, $key);
	else
		$status_exceptions = removeArrayValue($status_exceptions, $key);
	
	$statuses = !empty($status_exceptions) ? implode(',', $status_exceptions) : '';
	$DBLayer->update('punch_list_management_maint_moisture', ['status_exceptions' => $statuses], $id);

	echo json_encode(array(
		'message'		=> 'Updated'.$val,
	));	
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
