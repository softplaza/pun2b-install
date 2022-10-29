<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$modal_body = $message = [];
if ($id > 0)
{
	$query = array(
		'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'group_id > 2',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $fetch_assoc;
	}

	$modal_body[] = '<input name="checklist_id" type="hidden" value="'.$id.'">';
	$modal_body[] = '<div class="mb-3">';
	$modal_body[] = '<label class="form-label">Userlist</label>';
	$modal_body[] = '<select name="owned_by" class="form-select form-select-sm">';
	$modal_body[] = '<option value="0" selected>Select an user</option>'."\n";
	$optgroup = 0;
	foreach ($users_info as $cur_user)
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				$modal_body[] = '</optgroup>';
			}
			$modal_body[] = '<optgroup label="'.html_encode($cur_user['g_title']).'">';
			$optgroup = $cur_user['group_id'];
		}
		
		$modal_body[] = "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
	$modal_body[] = '</select>';
	$modal_body[] = '</div>';

	echo json_encode(array(
		'modal_title' => 'Reassign Work Order',
		'modal_body' => implode("\n", $modal_body),
		'modal_footer' => '<button type="submit" name="reassign" class="btn btn-primary">Reassign</button>',
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
