<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$modal_body = $message = [];
if ($id > 0)
{
	$query = [
		'SELECT'	=> 'c.*, p.pro_name, p.manager_email, un.unit_number',
		'FROM'		=> 'hca_hvac_inspections_checklist as c',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=c.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=c.unit_id'
			],
		],
		'WHERE'		=> 'c.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$checklist_info = $DBLayer->fetch_assoc($result);
	
	$message[] = ($checklist_info['inspection_completed'] == 2 && $checklist_info['num_problem'] > 0) ? 'This is Work Order,' : 'This is Checklist,'."\n";
	$message[] = 'Property name: '.html_encode($checklist_info['pro_name']);
	$message[] = 'Unit #: '.html_encode($checklist_info['unit_number'])."\n";
	$message[] = ($checklist_info['inspection_completed'] == 2 && $checklist_info['num_problem'] > 0) ? 'To view the Work Order follow this link:' : 'To view the Checklist follow this link:';
	$message[] = ($checklist_info['inspection_completed'] == 2 && $checklist_info['num_problem'] > 0) ? $URL->link('hca_unit_inspections_work_order', $id) : $URL->link('hca_unit_inspections_checklist', $id);

	$modal_body[] = '<input type="hidden" name="checklist_id" value="'.$id.'">';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Recipients</label>';
	$modal_body[] = '<textarea name="emails" class="form-control">talavera@hcares.com, '.html_encode($checklist_info['manager_email']).'</textarea>';
	$modal_body[] = '<label>Insert email addresses separated by commas</label>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Message</label>';
	$modal_body[] = '<textarea name="mail_message" class="form-control" rows="8">'.implode("\n", $message).'</textarea>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Send Email',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="send_email" class="btn btn-primary">Send Email</button>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
