<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;

// Add a new task
if ($pid > 0 && $uid > 0)
{
	$query = [
		'SELECT'	=> 'w.*',
		'FROM'		=> 'hca_wom_work_orders AS w',
		/*
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.item_id'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_types AS tp',
				'ON'			=> 'tp.id=i.item_type'
			],
			[
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=t.assigned_to'
			]
		],
		*/
		'WHERE'		=> 'w.wo_status=1 AND w.property_id='.$pid.' AND w.unit_id='.$uid,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$cur_info = $DBLayer->fetch_assoc($result);

	$content = [];
	if (!empty($cur_info))
	{
		$content[] = '<div class="alert alert-warning" role="alert">';
		$content[] = '<p class="fw-bold">Duplicate found!</p>';
		$content[] = '<p class="mb-2">This Work Order already exists and has an open status. You can go to an existing Work Order and add the needed tasks in there.</p>';
		$content[] = '<a href="'.$URL->link('hca_wom_work_order', $cur_info['id']).'" class="btn btn-success text-white me-2">Go to exists Work Order</a>';
		$content[] = '<button type="button" class="btn btn-secondary" onclick="clearDupeInfo()">Create a duplicate</button>';
		$content[] = '</div>';
	}

	echo json_encode(array(
		'content' => implode('', $content),
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
