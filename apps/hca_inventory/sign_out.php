<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('hca_inventory', 15)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;

if (isset($_POST['sign_out']))
{
	$form_data = array(
		'sign_out_to'		=> isset($_POST['sign_out_to']) ? intval($_POST['sign_out_to']) : 0,
		'equipment_id'		=> $id,
		'sign_out_date'		=> date('Y-m-d'),
		'sign_out_time'		=> date('H:i:s'),
		'comments'			=> isset($_POST['comments']) ? swift_trim($_POST['comments']) : '',
	);
	
	if ($form_data['sign_out_to'] == 0)
		$Core->add_error('Sellect assigned employee from dropdown list.');
	if ($form_data['comments'] == '')
		$Core->add_error('Reason field cannot be empty. Please leave your comment.');
	
	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('hca_inventory_records', $form_data);
		
		$query = [
			'UPDATE'	=> 'hca_inventory_equipments',
			'SET'		=> 'uid='.$form_data['sign_out_to'].', last_record_id='.$new_id,
			'WHERE'		=> 'id='.$id
		];
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'Item has been added';
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

$main_info = $DBLayer->select('hca_inventory_equipments', 'id='.$id);

$query = array(
	'SELECT'	=> 'e.*',
	'FROM'		=> 'hca_inventory_equipments AS e',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$equipments_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$equipments_info[] = $row;
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

$Core->set_page_title('Inventory Management');
$Core->set_page_id('hca_inventory_sign_out', 'hca_inventory');
require SITE_ROOT.'header.php';
?>

	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Sign-out equipment form</h6>
			</div>
			<div class="card-body">
				<h6 class="h6 card-title"><?php echo html_encode($main_info['item_name']) ?></h6>
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
						<label class="form-label fw-bold" for="input_item_number">Sign-out to</label>
						<select name="sign_out_to" class="form-select form-select-sm">
<?php
	$optgroup = 0;
	if (!in_array($User->get('group_id'), [3,9]))
		echo "\t\t\t\t\t\t".'<option value="" selected="selected" disabled>Select an user</option>'."\n";
	foreach ($users_info as $cur_user)
	{
		if ($cur_user['group_id'] != $optgroup) {
			if ($optgroup) {
				echo '</optgroup>';
			}
			echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
			$optgroup = $cur_user['group_id'];
		}
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
	}
?>
						</select>
					</div>
				</div>
				<div class="mb-3">
					<label class="form-label fw-bold" for="input_item_number">Reason</label>
					<textarea rows="2" name="comments" class="form-control" placeholder="Fill in the reason for the equipment sign-out" required><?php echo (isset($_POST['comments']) ? html_encode($_POST['comments']) : '') ?></textarea>
				</div>
				<button type="submit" name="sign_out" class="btn btn-primary">Sign-Out</button>
				<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
			</div>
		</div>
	</div>
</form>
<?php
require SITE_ROOT.'footer.php';