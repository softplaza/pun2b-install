<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_cc')) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_frequency = isset($_GET['frequency']) ? intval($_GET['frequency']) : 0;

$search_query = [];
if ($search_by_frequency > 0)
	$search_query[] = 'i.frequency='.$search_by_frequency;

$HcaCC = new HcaCC;

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
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
	'WHERE'		=> 'group_id > 2',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$query = [
	'SELECT'	=> 'i.*, pj.date_completed, pj.notes, pt.pro_name, u.realname',
	'FROM'		=> 'hca_cc_items AS i',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'hca_cc_items_tracking AS pj',
			'ON'			=> 'pj.id=i.last_tracking_id'
		],	
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=i.action_owner'
		],	
		[
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=i.property_id'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $main_ids = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
	$main_ids[] = $row['id'];
}

$hca_cc_owners = $hca_cc_properties = $hca_cc_due_months = [];

if (!empty($main_ids))
{
	$query = [
		'SELECT'	=> 'o.*, u.realname',
		'FROM'		=> 'hca_cc_owners AS o',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=o.user_id'
			],
		],
		'WHERE'		=> 'o.item_id IN ('.implode(',', $main_ids).')'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$hca_cc_owners[] = $fetch_assoc;
	}

	$query = [
		'SELECT'	=> 'p.*, p2.pro_name',
		'FROM'		=> 'hca_cc_properties AS p',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p2',
				'ON'			=> 'p2.id=p.property_id'
			]
		],
		'WHERE'		=> 'p.item_id IN ('.implode(',', $main_ids).')'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$hca_cc_properties[] = $fetch_assoc;
	}

	$query = [
		'SELECT'	=> 'm.*',
		'FROM'		=> 'hca_cc_due_months AS m',
		'WHERE'		=> 'm.item_id IN ('.implode(',', $main_ids).')'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$hca_cc_due_months[] = $fetch_assoc;
	}
}

$Core->set_page_title('List of items');
$Core->set_page_id('hca_cc_items', 'hca_cc');
require SITE_ROOT.'header.php';

if (!empty($main_info)) 
{
?>

<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="frequency" class="form-select-sm">
						<option value="0">Frequency</option>
<?php
foreach($HcaCC->frequency as $key => $value)
{
	if ($search_by_frequency == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>

				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_cc_items', 0) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="card-header">
	<h6 class="card-title mb-0">List of items</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Item</th>
			<th>Description</th>
			<th>Frequency</th>
			<th>Department</th>
			<th>Action Owners</th>
			<th>Properties</th>
			<th>Months Due</th>
			<th>Year Due</th>
		</tr>
	</thead>
	<tbody>

<?php

	foreach($main_info as $cur_info)
	{
		$frequency = $HcaCC->frequency[$cur_info['frequency']];
		$department = $HcaCC->departments[$cur_info['department']];
		$required_by = $HcaCC->required_by[$cur_info['required_by']];

		$owners = $HcaCC->getOwners($cur_info['id'], $hca_cc_owners);
		$properties = $HcaCC->getProperties($cur_info['id'], $hca_cc_properties);
		$months = $HcaCC->getMonths($cur_info['id'], $hca_cc_due_months);
?>
		<tr>
			<td class="ta-center">
				<?php echo html_encode($cur_info['item_name']) ?>
				<a href="<?php echo $URL->link('hca_cc_item', $cur_info['id']) ?>" class="badge bg-primary text-white float-end">Edit</a>
			</td>
			<td><?php echo html_encode($cur_info['item_desc']) ?></td>
			<td><?php echo html_encode($frequency) ?></td>
			<td class="ta-center"><?php echo html_encode($department) ?></td>
			<td class="ta-center"><?php echo $owners ?></td>
			<td class="ta-center"><?php echo $properties ?></td>
			<td class="ta-center"><?php echo $months ?></td>
			<td class="ta-center"></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>

<?php
}
else
	echo '<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>';

require SITE_ROOT.'footer.php';
