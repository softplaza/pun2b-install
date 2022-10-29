<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_uploader', 1)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$search_by_project = isset($_GET['project']) ? swift_trim($_GET['project']) : '';
$search_by_type = isset($_GET['type']) ? swift_trim($_GET['type']) : '';
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;

$search_query = $order_by = [];
if ($search_by_project != '')
	$search_query[] = 'f.table_name=\''.$DBLayer->escape($search_by_project).'\'';
if ($search_by_type != '')
	$search_query[] = 'f.file_type=\''.$DBLayer->escape($search_by_type).'\'';

if ($sort_by == 1)
	$order_by[] = 'f.file_size';
else if ($sort_by == 2)
	$order_by[] = 'f.file_size DESC';

$query = array(
	'SELECT'	=> 'COUNT(f.id)',
	'FROM'		=> 'sm_uploader as f',
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// Show only WORK STARTED - BID - ON HOLD 
$query = [
	'SELECT'	=> 'f.*, u.realname',
	'FROM'		=> 'sm_uploader as f',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=f.user_id'
		],
	],
	'ORDER BY'	=> 'f.load_time DESC',
	'LIMIT'		=> $PagesNavigator->limit()
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
if (!empty($order_by)) $query['ORDER BY'] = implode(', ', $order_by);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $fetch_assoc;
}
$PagesNavigator->num_items($main_info);

/*
$query = [
	'SELECT'	=> 'mi.unit_number, p.pro_name',
	'FROM'		=> 'hca_5840_projects AS mi',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=mi.property_id'
		],
	],
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $fetch_assoc;
}
*/

$projects = [
	'sm_pest_control_records'			=> 'Pest Control Projects',
	'hca_5840_projects' 				=> 'Moisture Inspection',
	'sm_special_projects_records' 		=> 'Projects & Constructions',
	'hca_fs_requests' 					=> 'Property Requests',
	'hca_trees_projects' 				=> 'List of Trees Projects',
	'hca_sb721_projects'				=> 'SB-721 Projects',
	'hca_sb721_documents'				=> 'SB-721 Documents',
	'hca_projects'						=> 'Structure Projects',
	'hca_inventory_equipments'			=> 'Inventory Equipments',
	'hca_vcr_projects'					=> 'VCR Projects',
	'hca_vcr_turn_over_inspections'		=> 'TurnOver Inspections',
];

$Core->set_page_id('swift_uploader_filelist', 'swift_uploader');
require SITE_ROOT.'header.php';
?>

	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<div class="row">
				<div class="col pe-0">
					<select name="project" class="form-select-sm">
						<option value="">All projects</option>
<?php
foreach ($projects as $key => $val)
{
	if ($search_by_project == $key)
		echo '<option value="'.$key.'" selected>'.$val.'</option>';
	else
		echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="type" class="form-select-sm">
						<option value="">All types</option>
<?php
$file_types = [
	'image'		=> 'Images',
	'media'		=> 'Media Files',
	'file'		=> 'Documents',
];
foreach ($file_types as $key => $val)
{
	if ($search_by_type == $key)
		echo '<option value="'.$key.'" selected>'.$val.'</option>';
	else
		echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="sort_by" class="form-select-sm">
						<option value="0">Sort by</option>
<?php
$sort_by_options = [
	1		=> 'File size ASC',
	2		=> 'File size DESC',
];
foreach ($sort_by_options as $key => $val)
{
	if ($sort_by == $key)
		echo '<option value="'.$key.'" selected>'.$val.'</option>';
	else
		echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				</div>
			</div>
		</form>
	</nav>
<?php
if (!empty($main_info))
{
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead class="sticky-under-menu">
				<tr>
					<th>File name</th>
					<th>Project name</th>
					<th>Uploaded by</th>
					<th>Date</th>
					<th>Size</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
		$project_name = isset($projects[$cur_info['table_name']]) ? $projects[$cur_info['table_name']] : '';
		$file_link = BASE_URL.'/'.$cur_info['file_path'].$cur_info['file_name'];
		$base_name = ($cur_info['base_name'] != '') ? $cur_info['base_name'] : $cur_info['file_name'];

		if ($cur_info['file_type'] == 'image')
			$file_name = '<a href="'.$file_link.'" target="_blank"><i class="fas fa-image fa-lg"></i> '.html_encode($base_name).'</a>';
		else if ($cur_info['file_type'] == 'media')
			$file_name = '<a href="'.$file_link.'" target="_blank"><i class="fas fa-video fa-lg"></i> '.html_encode($base_name).'</a>';
		else
			$file_name = '<a href="'.$file_link.'" target="_blank"><i class="fas fa-file-alt fa-lg"></i> '.html_encode($base_name).'</a>';
?>
				<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($cur_info['id'] == $pid) ? 'anchor' : '' ?>">
					<td><?php echo $file_name ?></td>
					<td><?php echo $project_name ?></td>
					<td><?php echo html_encode($cur_info['realname']) ?></td>
					<td><?php echo format_time($cur_info['load_time']) ?></td>
					<td><?php echo gen_number_format($cur_info['file_size']) ?></td>
					<td></td>
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
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}

require SITE_ROOT.'footer.php';
