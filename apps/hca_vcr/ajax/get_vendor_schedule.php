<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$access = (!$User->is_guest()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$time_slots = array(0 => 'ALL DAY', 1 => 'A.M.', 2 => 'P.M.');

if ($id > 0)
{
	$vendors_info = $modal_body = $modal_footer = [];
	$query = array(
		'SELECT'	=> '*',
		'FROM'		=> 'sm_vendors',
		'ORDER BY'	=> 'vendor_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendors_info[] = $row;
	}
	
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_vcr_invoices AS i',
/*		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'hca_vcr_projects AS p',
				'ON'			=> 'p.id=i.project_id'
			),
			array(
				'LEFT JOIN'		=> 'sm_vendors AS v',
				'ON'			=> 'v.id=i.vendor_id'
			),
			array(
				'LEFT JOIN'		=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=p.property_id'
			),
		),*/
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$project_info = $DBLayer->fetch_assoc($result);
	
	if (isset($project_info['date_time']))
	{
		if (!empty($vendors_info))
		{

			$modal_body[] = '<input type="hidden" name="schedule_id" value="'.$id.'">';

			// DO NOT ALLOW CHANGE DATE
			$modal_body[] = '<div class="mb-3">';
			$modal_body[] = '<input type="text" value="'.format_time($project_info['date_time'], 1).'" class="form-control" disabled>';
			$modal_body[] = '</div>';

			$modal_body[] = '<div class="mb-3">';
			$modal_body[] = '<select name="vendor_id" class="form-select">';
			$modal_body[] = '<option value="0">Select Vendor</option>';
			foreach($vendors_info as $cur_info)
			{
				if ($cur_info['id'] == $project_info['vendor_id'])
					$modal_body[] = '<option value="'.$cur_info['id'].'" selected>'.$cur_info['vendor_name'].'</option>';
				else
					$modal_body[] = '<option value="'.$cur_info['id'].'">'.$cur_info['vendor_name'].'</option>';
			}
			$modal_body[] = '</select>';
			$modal_body[] = '</div>';

			$modal_body[] = '<div class="mb-3">';
			$modal_body[] = '<select name="time_shift" class="form-select">';
			foreach($time_slots as $key => $val)
			{
				if ($key == $project_info['shift'])
					$modal_body[] = '<option value="'.$key.'" selected>'.$val.'</option>';
				else
					$modal_body[] = '<option value="'.$key.'">'.$val.'</option>';
			}
			$modal_body[] = '</select>';
			$modal_body[] = '</div>';

			$modal_footer[] = '<button type="submit" name="change_date" class="btn btn-primary">Update</button>';

		}
		
		echo json_encode(array(
			'modal_title'		=> 'Edit vendor',
			'modal_body'		=> implode("\n", $modal_body),
			'modal_footer'		=> implode("\n", $modal_footer),
		));
	}
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
