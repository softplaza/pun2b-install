<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = array(
	'SELECT'	=> 'w.*, p.pro_name, u.realname',
	'FROM'		=> 'hca_ui_water_pressure AS w',
	'JOINS'		=> array(
		array(
			'INNER JOIN'		=> 'sm_property_db as p',
			'ON'				=> 'w.property_id=p.id'
		),
		array(
			'INNER JOIN'		=> 'users as u',
			'ON'				=> 'u.id=w.completed_by'
		),
	),
	'WHERE'		=> 'w.property_id='.$id,
	//'ORDER BY'	=> 'u.unit_number',
	'ORDER BY'	=> 'LENGTH(w.building_number), w.building_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
}

$Core->set_page_id('hca_ui_buildings', 'hca_ui');
require SITE_ROOT.'header.php';
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property</th>
			<th>Building #</th>
			<th>Current pressure</th>
			<th>Adjusted pressure</th>
			<th>Date submitted</th>
			<th>Submitted by</th>
		</tr>
	</thead>
	<tbody>

<?php
if (!empty($main_info)) 
{
	foreach($main_info as $cur_info)
	{
?>
		<tr>
			<td><span class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></span></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['building_number']) ?></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['pressure_current']) ?></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['pressure_adjusted']) ?></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['date_completed']) ?></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['realname']) ?></td>
		</tr>
<?php
	}
}
else
	echo '<tr><td class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</td></tr>';
?>
	</tbody>
</table>

<?php
require SITE_ROOT.'footer.php';