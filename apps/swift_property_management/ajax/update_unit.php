<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;
$bldg_id = isset($_POST['bldg_id']) ? intval($_POST['bldg_id']) : 0;

//$json_units = [];
if ($unit_id > 0)
{
	$DBLayer->update('sm_property_units', [
		'bldg_id' => $bldg_id
	], $unit_id);

	$toast_message = [];
	/*
	$toast_message[] = '<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">';
	$toast_message[] = '<div class="toast-header">';
	$toast_message[] = '<strong class="me-auto">Message</strong>';
	$toast_message[] = '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>';
	$toast_message[] = '</div>';
	$toast_message[] = '<div class="toast-body">Unit #'.$unit_id.' was assigned building #'.$bldg_id.'.</div>';
	$toast_message[] = '</div>';
*/

	//$toast_message[] = '<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">';
	$toast_message[] = '<div class="alert alert-success mb-1 float-end opacity-75" style="width: 350px;">';
	$toast_message[] = 'Unit #'.$unit_id.' was assigned building #'.$bldg_id.'.</div>';
	//$toast_message[] = '</div>';

	echo json_encode(array(
		'toast_message' => implode('', $toast_message),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
