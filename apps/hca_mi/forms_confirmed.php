<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'completed_time>0',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

//GET NEW UNCOMPLETED FORM FROM MANAGER
$query = array(
	'SELECT'	=> 'f.*, pj.unit_number, pj.location, pj.leak_type, pj.symptom_type, pj.symptoms, pj.mois_source, pj.action, pj.remarks, p.pro_name',
	'FROM'		=> 'hca_5840_forms AS f',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_5840_projects AS pj',
			'ON'			=> 'pj.id=f.project_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
	],
	'WHERE'		=> 'f.completed_time>0',
	'ORDER BY'	=> 'f.mailed_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$forms_info = $projects_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$forms_info[] = $row;
}
$PagesNavigator->num_items($forms_info);

$Core->set_page_id('hca_mi_forms_confirmed', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<div class="card-header">
	<h6 class="card-title mb-0">Confirmed forms of Property Managers</h6>
</div>

<?php
if (!empty($forms_info))
{
?>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property Information</th>
			<th>Source of moisture</th>
			<th>Symptoms</th>
			<th>Action</th>
			<th>Remarks</th>
			<th>Notice for Manager</th>
			<th>Manager Comment</th>
			<th>Confirm</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($forms_info as $cur_info) 
	{
?>
		<tr>
			<td>
				<p><a href="<?php echo $URL->link('hca_5840_projects', '#pid'.$cur_info['project_id']) ?>"><?php echo $cur_info['pro_name'] ?></a></p>
				<p><?php echo html_encode($cur_info['unit_number']) ?></p>
				<p><?php echo html_encode($cur_info['location']) ?></p>
			</td>
			
			<td><?php echo html_encode($cur_info['mois_source']) ?></td>
			<td><?php echo html_encode($cur_info['symptoms']) ?></td>
			<td><?php echo html_encode($cur_info['action']) ?></td>
			<td><?php echo html_encode($cur_info['remarks']) ?></td>
			<td>
				<span class="time"><?php echo format_time($cur_info['mailed_time']) ?>:</span>
				<p><?php echo $cur_info['msg_for_manager'] ?></p>
			</td>
			<td>
				<span class="time"><?php echo format_time($cur_info['submited_time']) ?>:</span>
				<p><?php echo $cur_info['msg_from_manager'] ?></p>
			</td>
			
			<td>
				<p style="color:green">Completed: <?php echo format_time($cur_info['completed_time']) ?></p>
			</td>
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

require SITE_ROOT.'footer.php';
