<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$val = isset($_POST['val']) ? intval($_POST['val']) : 0;

if ($id > 0)
{
	$query = array(
		'UPDATE'	=> 'hca_5840_vendors_filter',
		'SET'		=> 'enabled='.$val,
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	echo json_encode(array(
		'message'		=> '<div class="alert alert-success alert-dismissible fade show" role="alert">Vendor has been updated.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();