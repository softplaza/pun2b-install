<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('hca_inventory', 4)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$query = [
	'SELECT'	=> 'e.*',
	'FROM'		=> 'hca_inventory_equipments AS e',
	'WHERE'		=> 'e.id='.$id
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

if (isset($_POST['reassign']))
{
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$comments = isset($_POST['comments']) ? swift_trim($_POST['comments']) : '';

	if ($user_id == 0)
		$Core->add_error('Select an employee from dropdown list.');
	if ($comments == '')
		$Core->add_error('Reason field cannot be empty. Please leave your comment.');
	if ($main_info['uid'] == $user_id)
		$Core->add_error('Already signed-out for this user.');

	if (empty($Core->errors))
	{
		$update_data = array(
			'sign_back_in_date'		=> date('Y-m-d'),
			'sign_back_in_time'		=> date('H:i:s'),
			'returned'				=> 2 // Reassigned
		);
		if ( $main_info['last_record_id'] > 0)
			$DBLayer->update('hca_inventory_records', $update_data, $main_info['last_record_id']);

		$new_data = array(
			'sign_out_to'		=> $user_id,
			'equipment_id'		=> $id,
			'sign_out_date'		=> date('Y-m-d'),
			'sign_out_time'		=> date('H:i:s'),
			'comments'			=> $comments,
		);
		$new_id = $DBLayer->insert_values('hca_inventory_records', $new_data);

		$query = [
			'UPDATE'	=> 'hca_inventory_equipments',
			'SET'		=> 'uid='.$user_id.', last_record_id='.$new_id,
			'WHERE'		=> 'id='.$id
		];
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'Equipment has been reassigned';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_inventory_warehouse', $id), $flash_message);
	}
}

else if (isset($_POST['cancel']))
{
	$flash_message = 'Action canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_inventory_warehouse', $id), $flash_message);
}

$query = array(
	'SELECT'	=> 'id, file_path, file_name',
	'FROM'		=> 'sm_uploader',
	'WHERE'		=> 'table_name=\'hca_inventory_equipments\' AND table_id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$files_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$files_info[] = $row;
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.group_id=3 OR u.group_id=9',
	'ORDER BY'	=> 'g.g_id, u.realname',
);

if (in_array($User->get('group_id'), [3,9]))
	$query['WHERE'] .= ' AND u.id='.$User->get('id');

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$Core->set_page_id('hca_inventory_sign_out', 'hca_inventory');
require SITE_ROOT.'header.php';
?>

	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Reassign equipment form</h6>
			</div>
			<div class="card-body">
				<h6 class="h6 card-title"><?php echo html_encode($main_info['item_name']) ?></h6>
				<p>Serial # <?php echo html_encode($main_info['item_number']) ?></p>
				<div class="row">
					<div class="col mb-3">
						<picture>
<?php
if (!empty($files_info))
{
	foreach($files_info as $cur_file)
		echo '<img src="'.BASE_URL.'/'.$cur_file['file_path'].$cur_file['file_name'].'" class="img-fluid img-thumbnail" style="height:200px">';
}
?>
						</picture>
					</div>
				</div>
				<div class="row">
					<div class="col mb-3">
						<label class="form-label fw-bold" for="input_item_number">Reassign to</label>
						<select name="user_id" class="form-select form-select-sm">
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select an employee</option>'."\n";
	foreach ($users_info as $cur_user)
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				echo '</optgroup>';
			}
			echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
			$optgroup = $cur_user['group_id'];
		}

		if ($main_info['uid'] == $cur_user['id'])
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected style="color:red">'.html_encode($cur_user['realname']).' (Signed-out)</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
?>
						</select>
					</div>
				</div>
				<div class="mb-3">
					<label class="form-label fw-bold" for="input_item_number">Reason</label>
					<textarea rows="2" name="comments" class="form-control" placeholder="Leave your comment" required><?php echo (isset($_POST['comments']) ? html_encode($_POST['comments']) : '') ?></textarea>
				</div>
				<button type="submit" name="reassign" class="btn btn-primary">Reassign</button>
				<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
			</div>
		</div>
	</form>
<?php
require SITE_ROOT.'footer.php';