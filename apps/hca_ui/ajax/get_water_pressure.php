<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$modal_body = $message = [];
if ($id > 0)
{
	$query = [
		'SELECT'	=> 'wp.*, p.pro_name, p.manager_email, u.realname',
		'FROM'		=> 'hca_ui_water_pressure AS wp',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=wp.property_id'
			],
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=wp.completed_by'
			],
		],
		'WHERE'	=> 'wp.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$cur_info = $DBLayer->fetch_assoc($result);

	$message[] = 'This is Water Pressure Report.'."\n";
	$message[] = 'Property: '.html_encode($cur_info['pro_name']);
	$message[] = 'BLDG #: '.html_encode($cur_info['building_number']);
	$message[] = 'Current value: '.html_encode($cur_info['pressure_current']);
	if ($cur_info['pressure_adjusted'] > 0)
		$message[] = 'Adjusted value: '.html_encode($cur_info['pressure_adjusted']);
	$message[] = 'Submitted by: '.html_encode($cur_info['realname']);
	$message[] = 'Date submitted: '.format_date($cur_info['date_completed'], 'n/j/Y');
	if ($cur_info['comment'] != '')
		$message[] = 'Comment: '.html_encode($cur_info['comment'])."\n";
	$message[] = 'To view the Report follow this link:';
	$message[] = $URL->link('hca_ui_water_pressure', $cur_info['id']);

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Recipients</label>';
	$modal_body[] = '<textarea name="emails" class="form-control">'.html_encode($cur_info['manager_email']).'</textarea>';
	$modal_body[] = '<label>Insert email addresses separated by commas</label>';
	$modal_body[] = '</div>';

	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Message</label>';
	$modal_body[] = '<textarea name="mail_message" class="form-control" rows="14">'.implode("\n", $message).'</textarea>';
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
