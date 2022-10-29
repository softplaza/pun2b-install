<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;

$modal_body = $modal_footer = [];
if ($pid > 0)
{
	require SITE_ROOT.'apps/hca_mi/classes/Moisture.php';
	$Moisture = new Moisture;

	$query = [
		'SELECT'	=> 'pj.*, p.manager_email, p.pro_name',
		'FROM'		=> 'hca_5840_projects AS pj',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=pj.property_id'
			]
		],
		'WHERE'		=> 'pj.id='.$pid
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$cur_info = $DBLayer->fetch_assoc($result);

	$hca_5840_mailing_fields_details = $Moisture->get_email_details();
	$o_hca_5840_mailing_fields = explode(',', $Config->get('o_hca_5840_mailing_fields'));

	$hca_5840_mailing_fields = array();
	foreach($hca_5840_mailing_fields_details as $key => $value)
	{
		if (in_array($key, $o_hca_5840_mailing_fields))
			$hca_5840_mailing_fields[] = '<p><input type="checkbox" value="1" checked="checked" name="hca_5840_mailing_fields['.$key.']"> '.$value.'</p>';
		else
			$hca_5840_mailing_fields[] = '<p><input type="checkbox" value="0" name="hca_5840_mailing_fields['.$key.']"> '.$value.'</p>';
	}

	//$cur_info = $DBLayer->select('hca_5840_projects', 'id='.$pid);
	
	$mailing_list = $Config->get('o_hca_5840_mailing_list') . ($cur_info['manager_email'] != '' ? ','.$cur_info['manager_email'] : '');

	$modal_body[] = '<input type="hidden" name="project_id" value="'.$pid.'">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Recipients</label>';
	$modal_body[] = '<textarea name="email_list" rows="2" placeholder="Enter emails separated by commas" class="form-control">'.$mailing_list.'</textarea>';
	$modal_body[] = '<label class="text-muted">Enter emails separated by commas</label>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Subject</label>';
	$modal_body[] = '<input type="text" name="subject" value="HCA: Moisture Inspection" class="form-control">';
	$modal_body[] = '</div>';
	
	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Message</label>';
	$modal_body[] = '<textarea name="message" class="form-control" rows="5">This is Moisture Inspection information.</textarea>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3" style="columns: 2;">';
	$modal_body[] = '<label class="form-label">Additional information</label>';
	$modal_body[] = implode(' ', $hca_5840_mailing_fields);
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Send project information by email',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="send_email" class="btn btn-primary">Send email</button>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();