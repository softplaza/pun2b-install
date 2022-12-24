<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$HcaMi = new HcaMi;

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'mailed_time>0 AND submited_time=0',
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
	'WHERE'		=> 'f.mailed_time>0 AND f.submited_time=0',
	'ORDER BY'	=> 'f.mailed_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$forms_info = $projects_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$forms_info[$row['id']] = $row;
	$projects_ids[] = $row['project_id'];
}
$PagesNavigator->num_items($forms_info);

//$Core->set_page_title('Sent forms');
$Core->set_page_id('hca_mi_forms_mailed', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<div class="card-header">
	<h6 class="card-title mb-0">Not confirmed forms of Property Managers</h6>
</div>

<?php
if (!empty($forms_info))
{
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
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
		$leak_type = ($cur_info['leak_type'] > 0 ? '<p class="fw-bold">'.html_encode($HcaMi->leak_types[$cur_info['leak_type']]).'</p>' : '');
?>
			<tr>
				<td>
					<p class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?>, <?php echo html_encode($cur_info['unit_number']) ?></p>
					<p><?php echo html_encode($cur_info['location']) ?></p>
				</td>
				<td class="">
					<?php echo $leak_type ?>
					<p><?php echo $cur_info['mois_source'] ?></p>
				</td>
				<td class=""><p class="fw-bold">
					<?php echo (isset($HcaMi->symptoms[$cur_info['symptom_type']]) ? $HcaMi->symptoms[$cur_info['symptom_type']] : '') ?></p>
					<?php echo html_encode($cur_info['symptoms']) ?>
				</td>
				<td class=""><?php echo $cur_info['action'] ?></td>
				<td class=""><?php echo $cur_info['remarks'] ?></td>
				<td class="">
					<span class="time"><?php echo format_time($cur_info['mailed_time']) ?>:</span>
					<p><?php echo $cur_info['msg_for_manager'] ?></p>
				</td>
				<td class="">
					<span class="time"><?php echo format_time($cur_info['submited_time']) ?>:</span>
					<p><?php echo $cur_info['msg_from_manager'] ?></p>
				</td>
				<td class="">
					<p class="text-danger"><a href="<?php echo $URL->link('hca_5840_form', [$cur_info['id'], $cur_info['link_hash']]) ?>">Waiting for manager confirmation</a></p>
				</td>
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