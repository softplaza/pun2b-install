<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access5 = ($User->checkAccess('hca_hvac_inspections', 5)) ? true : false;
if (!$access5)
	message($lang_common['No permission']);

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

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
$sm_property_db = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
}

if ($search_by_property_id > 0)
	$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="'.$URL->genLink('hca_hvac_inspections_print', ['section' => 'alarms', 'property_id' => $search_by_property_id]).'" target="_blank"><i class="fa fa-file-pdf-o fa-1x" aria-hidden="true"></i> Print as PDF</a></li>');

//$Core->add_page_action('<a href="'.$URL->link('hca_ui_print', 'pending_items').'" target="_blank"><i class="fa fa-file-pdf-o fa-2x"></i>Print as PDF</a>');

$Core->set_page_id('hca_hvac_inspections_alarms', 'hca_hvac_inspections');
require SITE_ROOT.'header.php';
?>

<nav class="navbar alert-info mb-1">
	<form method="get" accept-charset="utf-8" action="">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select-sm" id="fld_property_id">
						<option value="0">Select property</option>
<?php
foreach ($sm_property_db as $val)
{
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.html_encode($val['pro_name']).'</option>';
	else
		echo '<option value="'.$val['id'].'">'.html_encode($val['pro_name']).'</option>';
}
?>
					</select>
				</div>

				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="card-header">
	<h6 class="card-title mb-0">SMOKE ALARM AND CARBON MONOXIDE TEST LOG</h6>
</div>
<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th colspan="5">SMOKE ALARMS</th>
			<th colspan="5">CARBON MONOXIDE ALARMS</th>
		</tr>
		<tr>
			<th>Unit #</th>
			<th>Date Tested</th>
			<th>Tested by</th>
			<th>Working</th>
			<th>Date corrected</th>
			<th>Unit #</th>
			<th>Date Tested</th>
			<th>Tested by</th>
			<th>Working</th>
			<th>Date corrected</th>
		</tr>
	</thead>
	<tbody>

<?php
if ($search_by_property_id > 0)
{
	$HcaHVACInspectionsAlarms = new HcaHVACInspectionsAlarms;

	$search_query = $sm_property_units = [];
	if ($search_by_property_id > 0)
		$search_query[] = 'pu.property_id='.$search_by_property_id;

	$query = [
		'SELECT'	=> 'pu.*',
		'FROM'		=> 'sm_property_units AS pu',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS pn',
				'ON'			=> 'pn.id=pu.property_id'
			],
		],
		'ORDER BY'	=> 'LENGTH(pu.unit_number), pu.unit_number',
	];
	$query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$summary_replaced = $summary_repaired = $summary_pending = 0;
	while($row = $DBLayer->fetch_assoc($result))
	{
		$sm_property_units[$row['id']] = $row;
	}

	if (!empty($sm_property_units))
	{
		foreach($sm_property_units as $cur_info)
		{
			$smoke_info = $HcaHVACInspectionsAlarms->getSmokeTest($cur_info['id']);
			$carbon_info = $HcaHVACInspectionsAlarms->getCarbonTest($cur_info['id']);

			$smoke_info['working']
?>
		<tr>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['unit_number']) ?></td>
			<td class="ta-center"><?php echo format_date($smoke_info['date_tested'], 'n/j/y') ?></td>
			<td class="ta-center"><?php echo html_encode($smoke_info['tested_by']) ?></td>
			<td class="ta-center"><?php echo $smoke_info['working'] ?></td>
			<td class="ta-center"><?php echo format_date($smoke_info['date_corrected'], 'n/j/y') ?></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['unit_number']) ?></td>
			<td class="ta-center"><?php echo format_date($carbon_info['date_tested'], 'n/j/y') ?></td>
			<td class="ta-center"><?php echo html_encode($carbon_info['tested_by']) ?></td>
			<td class="ta-center"><?php echo $carbon_info['working'] ?></td>
			<td class="ta-center"><?php echo format_date($carbon_info['date_corrected'], 'n/j/y') ?></td>
		</tr>
<?php
		}
	}
}
?>
	</tbody>
</table>

<?php
require SITE_ROOT.'footer.php';
