<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Project does not exist or has been removed.');

$query = [
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, un.unit_number, u1.realname AS project_manager1, u2.realname AS project_manager2, u3.realname AS created_name, u4.realname AS updated_name',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		// Get Project Managers
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.performed_uid2'
		],
		[
			'LEFT JOIN'		=> 'users AS u3',
			'ON'			=> 'u3.id=pj.created_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u4',
			'ON'			=> 'u4.id=pj.updated_by'
		],
	],
	'WHERE'		=> 'pj.id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'a.*, u.realname',
	'FROM'		=> 'hca_mi_actions AS a',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=a.submitted_by'
		],
	],
	'WHERE'		=> 'a.project_id='.$id ,
	'ORDER BY'	=> 'a.time_submitted DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_mi_actions = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_mi_actions[] = $row;
}

$query = array(
	'SELECT'	=> 'e.*, u.realname',
	'FROM'		=> 'sm_calendar_events AS e',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=e.poster_id'
		],
	],
	'WHERE'		=> 'e.project_id='.$id.' AND e.project_name=\'hca_5840\'',
	'ORDER BY'	=> 'e.time DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_calendar_events = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_calendar_events[] = $row;
}

$Core->set_page_id('hca_mi_project_tracking', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<div class="card">
	<div class="card-header d-flex justify-content-between">
		<h6 class="card-title mb-0">Moisture Project Tracking</h6>
		<div>
			<a href="<?=$URL->link('hca_5840_manage_project', $id)?>" class="badge bg-primary text-white">Project</a>
			<a href="<?=$URL->link('hca_5840_manage_files', $id)?>" class="badge bg-primary text-white">Files</a>
			<a href="<?=$URL->link('hca_5840_manage_invoice', $id)?>" class="badge bg-primary text-white">Invoice</a>
			<a href="<?=$URL->link('hca_5840_manage_appendixb', $id)?>" class="badge bg-primary text-white">+ Appendix-B</a>
		</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-md-6">
				<div>
					<span class="">Property:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['pro_name']) ?></span>
				</div>
				<div>
					<span class="">Unit #</span>
					<span class="fw-bold"><?php echo html_encode($main_info['unit_number']) ?></span>
				</div>
				<div>
					<span class="">Location:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['location']) ?></span>
				</div>
				<div>
					<span class="">Project Manager:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['project_manager1']) ?></span>
				</div>
<?php if ($main_info['project_manager2'] != ''): ?>
				<div>
					<span class="">Project Manager 2:</span>
					<span class="fw-bold"><?php echo html_encode($main_info['project_manager2']) ?></span>
				</div>
<?php endif; ?>
			</div>

			<div class="col-sm-6 d-flex justify-content-end">
				<div>
					<div>
						<span class="text-muted">Created by:</span>
						<span class="text-muted fw-bold"><?php echo html_encode($main_info['created_name']) ?></span>
					</div>
					<div>
						<span class="text-muted">Created on</span>
						<span class="text-muted fw-bold"><?php echo format_time($main_info['time_created'], 1) ?></span>
					</div>
					<div>
						<span class="text-muted">Updated by:</span>
						<span class="text-muted fw-bold"><?php echo html_encode($main_info['updated_name']) ?></span>
					</div>
					<div>
						<span class="text-muted">Last updated:</span>
						<span class="text-muted fw-bold"><?php echo format_time($main_info['time_updated'], 0) ?></span>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>





<div class="row">
	<div class="col-md-6">
		<div class="card-header">
			<h6 class="card-title mb-0">Project actions</h6>
		</div>
<?php
if (!empty($hca_mi_actions)) 
{
?>
		<table class="table table-striped table-bordered table-sm">
			<thead>
				<tr>
					<th>Date/Time</th>
					<th>Submitted by</th>
					<th>Message</th>
				</tr>
			</thead>
			<tbody>
<?php

	foreach ($hca_mi_actions as $cur_info)
	{
?>
				<tr>
					<td><p class=""><?php echo format_time($cur_info['time_submitted'], 0) ?></p></td>
					<td><?php echo html_encode($cur_info['realname']) ?></td>
					<td><p><?php echo html_encode($cur_info['message']) ?></p></td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
<?php
} else {
?>
		<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
?>
	</div>

	<div class="col-md-6">
		<div class="card-header">
			<h6 class="card-title mb-0">Project Follow Up Dates</h6>
		</div>
<?php
if (!empty($sm_calendar_events)) 
{
?>
		<table class="table table-striped table-bordered table-sm">
			<thead>
				<tr>
					<th>Date/Time</th>
					<th>Submitted by</th>
					<th>Message</th>
				</tr>
			</thead>
			<tbody>
<?php

	foreach ($sm_calendar_events as $cur_info)
	{
?>
				<tr>
					<td><p class=""><?php echo format_time($cur_info['time'], 0) ?></p></td>
					<td><?php echo html_encode($cur_info['realname']) ?></td>
					<td><p><?php echo html_encode($cur_info['message']) ?></p></td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
<?php
} else {
?>
	<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
?>
	</div>
</div>
<?php
require SITE_ROOT.'footer.php';
