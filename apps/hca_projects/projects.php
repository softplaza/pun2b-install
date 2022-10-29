<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$section = isset($_GET['section']) ? $_GET['section'] : 'active';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('hca_projects', 1)) ? true : false;
//if (!$access)
//	message($lang_common['No permission']);

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_manager = isset($_GET['performed_by']) ? swift_trim($_GET['performed_by']) : '';
$search_by_unit = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';


$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'hca_projects',
	'WHERE'		=> 'project_status > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'p.*, pt.pro_name, u.realname',
	'FROM'		=> 'hca_projects AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=p.property_id'
		),
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=p.performed_by'
		),
	),
	'WHERE'		=> 'p.project_status > 0',
	'ORDER BY'	=> 'pt.pro_name',
	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$projects_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'hca_projects\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$uploader_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}
}

$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="mailto:@hcares?subject=HCA Projects&body='.get_current_url().'" target="_blank"><i class="fas fa-share-alt"></i> Share link</a></li>');

$Core->set_page_id('hca_projects_list', 'hca_projects');
require SITE_ROOT.'header.php';

if (!empty($main_info)) 
{
?>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-striped table-bordered">
				<thead>
					<tr class="sticky-under-menu">
						<th class="th1">Property/Unit#</th>
						<th>Inspection Performed</th>
						<th>Symptoms</th>
						<th>Major Repairs City</th>
						<th>Cosmetic Repairs In-House</th>
					</tr>
				</thead>
				<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
		if ($User->checkAccess('hca_projects', 12))
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_projects_management', $cur_info['id']).'"><i class="fas fa-eye"></i> View project</a>');
?>
					<tr id="row<?php echo $cur_info['id'] ?>">
						<td class="td1">
							<?php echo html_encode($cur_info['pro_name']) ?>
							<p>Unit: <?php echo html_encode($cur_info['unit_number']) ?></p>
							<p><?php echo html_encode($cur_info['location']) ?></p>
							<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
						</td>
						<td>
							<?php echo format_date($cur_info['performed_date'], 'n/j/y') ?>
							<p><?php echo html_encode($cur_info['realname']) ?></p>
						</td>
						<td><?php echo html_encode($cur_info['symptoms']) ?></td>
						<td><?php echo html_encode($cur_info['major_repairs']) ?></td>
						<td><?php echo html_encode($cur_info['cosmetic_repairs']) ?></td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</form>
<?php
} else {
?>
	<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
require SITE_ROOT.'footer.php';