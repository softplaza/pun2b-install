<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_unit_inspections')) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$key = isset($_POST['key']) ? swift_trim($_POST['key']) : '';
$sec = isset($_POST['sec']) ? intval($_POST['sec']) : 0;
$val = isset($_POST['val']) ? intval($_POST['val']) : 0;

if ($id > 0 && $key != '')
{
	// Kitchen
	if ($sec == 1)
	{
		$query = array(
			'UPDATE'	=> 'hca_unit_inspections_checklist_kitchen',
			'SET'		=> ''.$key.'='.$val,
			'WHERE'		=> 'k_checklist_id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		echo json_encode(array('message' => 'Updated'));
	}
	// Guest Bath
	else if ($sec == 2)
	{
		$query = array(
			'UPDATE'	=> 'hca_unit_inspections_checklist_gbath',
			'SET'		=> ''.$key.'='.$val,
			'WHERE'		=> 'gb_checklist_id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		echo json_encode(array('message' => 'Updated'));
	}
	// Master Bath
	else if ($sec == 3)
	{
		$query = array(
			'UPDATE'	=> 'hca_unit_inspections_checklist_mbath',
			'SET'		=> ''.$key.'='.$val,
			'WHERE'		=> 'mb_checklist_id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		echo json_encode(array('message' => 'Updated'));
	}
	// Half Bath
	else if ($sec == 4)
	{
		$query = array(
			'UPDATE'	=> 'hca_unit_inspections_checklist_hbath',
			'SET'		=> ''.$key.'='.$val,
			'WHERE'		=> 'hb_checklist_id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		echo json_encode(array('message' => 'Updated'));
	}
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
