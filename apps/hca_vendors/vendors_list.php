<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vendors', 3)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$access5 = ($User->checkAccess('hca_vendors', 5)) ? true : false;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$search_by_key_words = isset($_GET['key_word']) ? swift_trim($_GET['key_word']) : '';
$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'ORDER BY'	=> 'vendor_name'
);
if ($search_by_key_words != '') {
	$search_by_key_words2 = '%'.$search_by_key_words.'%';
	$query['WHERE'] = '(payee_id LIKE \''.$DBLayer->escape($search_by_key_words2).'\'';
	$query['WHERE'] .= ' OR vendor_name LIKE \''.$DBLayer->escape($search_by_key_words2).'\'';
	$query['WHERE'] .= ' OR service LIKE \''.$DBLayer->escape($search_by_key_words2).'\')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_id('sm_vendors_list', 'sm_vendors');
require SITE_ROOT.'header.php';
?>

<style>
.payee-id input, .phone textarea, .email input{font-weight: bold;}
table input, table textarea{width: 100%;}
</style>

<nav class="navbar navbar-light" style="background-color: #e3f2fd;">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<input type="text" name="key_word" value="<?php echo isset($_GET['key_word']) ? html_encode($_GET['key_word']) : '' ?>" placeholder="Enter keyword" class="form-control"/>
				</div>
				<div class="col">
					<button class="btn btn-sm btn-outline-success" type="submit">Search</button>
				</div>
			</div>
		</div>
	</form>	
</nav>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-striped table-bordered table-sm">
		<thead>
			<tr>
				<th>Payee ID</th>
				<th>Company Name</th>
				<th>Service Provided</th>
				<th>Phone Number</th>
				<th>Email</th>
				<th>Order Limit</th>
			</tr>
		</thead>
		<tbody>
<?php
if (!empty($main_info))
{
	foreach ($main_info as $cur_info)
	{
		$actions = ($access5) ? '<p><a href="'.$URL->link('sm_vendors_edit', $cur_info['id']).'" class="badge bg-secondary text-white">Edit</a></p>' : '';
?>
			<tr>
				<td>
					<?php echo html_encode($cur_info['payee_id']) ?>
					<?php echo $actions ?>
				</td>
				<td class="fw-bold"><?php echo html_encode($cur_info['vendor_name']) ?></td>
				<td><?php echo html_encode($cur_info['service']) ?></td>
				<td class="fw-bold"><?php echo html_encode($cur_info['phone_number']) ?></td>
				<td class="fw-bold"><?php echo html_encode($cur_info['email']) ?></td>
				<td><?php echo $cur_info['orders_limit'] ?></td>
			</tr>
<?php
	}
}
?>
		</tbody>
	</table>
</form>

<?php
require SITE_ROOT.'footer.php';