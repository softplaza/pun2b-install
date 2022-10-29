<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('punch_list_management', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['delete_form']))
{
	$item_id = intval(key($_POST['delete_form']));
	$DBLayer->delete('punch_list_management_maint_request_form', $item_id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_completed = isset($_GET['completed']) ? intval($_GET['completed']) : 0;

$search_query = [];

if ($search_by_property_id > 0)
	$search_query[] = 'f.property_id='.$search_by_property_id;

if ($search_by_unit_number != '')
	$search_query[] = 'f.unit_number=\''.$DBLayer->escape($search_by_unit_number).'\'';

if ($search_by_user_id > 0)
	$search_query[] = 'f.technician_id='.$search_by_user_id;

if ($search_by_completed > 0)
	$search_query[] = 'f.completed=1';

$query = [
	'SELECT'	=> 'COUNT(f.id)',
	'FROM'		=> 'punch_list_management_maint_request_form as f',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$forms_info = [];
$query = array(
	'SELECT'	=> 'f.*, u.realname, p.pro_name',
	'FROM'		=> 'punch_list_management_maint_request_form AS f',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=f.technician_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=f.property_id'
		),
	),
	'ORDER BY'		=> 'f.date_submitted DESC'
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result)) {
	$forms_info[] = $row;
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] .= ' AND p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'u.id, u.realname',
	'FROM'		=> 'users AS u',
	//'WHERE'		=> 'u.id > 2',
	'WHERE'		=> 'u.group_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'u.realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$Core->set_page_id('punch_list_management_forms', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select-sm">
						<option value="">All Properties</option>
<?php
foreach ($property_info as $val)
{
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Unit number" class="form-control-sm"/>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="user_id" class="form-select-sm">
						<option value="0">All technicians</option>
<?php
foreach ($users_info as $user_info)
{
	if ($search_by_user_id == $user_info['id'])
		echo '<option value="'.$user_info['id'].'" selected>'.$user_info['realname'].'</option>';
	else
		echo '<option value="'.$user_info['id'].'">'.$user_info['realname'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<div class="mb-0">
						<input name="completed" type="checkbox" value="1" <?php echo ($search_by_completed == 1) ? 'checked' : '' ?> class="form-check-input" id="fld_completed">  
						<label class="form-check-label" for="fld_completed">Completed</label>
					</div>
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('punch_list_management_forms') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Apartment Punch Forms</h6>
		</div>
		<table class="table table-striped my-0">
			<thead>
				<th>Property</th>
				<th>Unit#</th>
				<th>Technician</th>
				<th>Time spent</th>
				<th>Date Requested</th>
				<th>Submitted by Technician on</th>
				<th>Completed</th>
				<th>Remarks</th>
				<th></th>
			</thead>
			<tbody>
<?php

foreach($forms_info as $item)
{
	$form_link = ($item['form_type'] == 2) ? $URL->link('punch_list_management_painter_request', [$item['id'], $item['hash_key']]) : $URL->link('punch_list_management_maintenance_request', [$item['id'], $item['hash_key']]);

	echo '<tr>';

	echo '<td><span class="fw-bold">'.html_encode($item['pro_name']).'</span>';
	echo '<p><a href="'.$form_link.'" class="badge bg-primary text-white">View</a></p>';
	echo '</td>';
	echo '<td class="fw-bold">'.html_encode($item['unit_number']).'</td>';

	echo '<td><span>'.html_encode($item['realname']).'</span>';
	echo '<p class="fst-italic text-muted">'.($item['form_type'] == 2 ? 'Painter' : 'Maintenance').'</p>';
	echo '</td>';

	echo '<td>'.html_encode($item['time_spent']).'</td>';
	echo '<td>'.format_time($item['date_requested'], 1).'</td>';
	echo '<td>'.format_time($item['submitted_by_technician'], 1).'</td>';
	echo '<td class="ta-center">'.($item['completed'] == 1 ? 'YES' : '').'</td>';
	echo '<td>'.html_encode($item['remarks']).'</td>';
	echo '<td>';

	if ($User->checkAccess('punch_list_management', 10))
		echo '<button type="submit" name="delete_form['.$item['id'].']" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button>';

	echo '</td>';

	echo '</tr>';
}
?>
			</tbody>
		</table>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
