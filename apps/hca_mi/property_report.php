<?php
//56
define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access20 = ($User->checkAccess('hca_mi', 2)) ? true : false;
if (!$access20)
	message($lang_common['No permission']);

$HcaMiPropertyReport = new HcaMiPropertyReport;

$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_map_id = isset($_GET['map_id']) ? intval($_GET['map_id']) : 0;
$search_by_leak_type = isset($_GET['leak_type']) ? intval($_GET['leak_type']) : 0;

$Loader->add_js(BASE_URL.'/vendor/KBmapMarkers/js/KBmapmarkers.js?'.time(), array('type' => 'url', 'async' => false, 'group' => 100 , 'weight' => 75));
$Loader->add_js(BASE_URL.'/vendor/KBmapMarkers/js/KBmapmarkersCords.js?'.time(), array('type' => 'url', 'async' => false, 'group' => 100 , 'weight' => 75));
$Loader->add_css(BASE_URL.'/vendor/KBmapMarkers/css/KBmapmarkers.css?'.time());

$Core->set_page_id('hca_5840_projects_report', 'hca_5840');
require SITE_ROOT.'header.php';

if ($search_by_property_id == 0)
{
	$sm_property_db = [];
	$query = [
		'SELECT'	=> 'pt.*',
		'FROM'		=> 'sm_property_db AS pt',
		'ORDER BY'	=> 'pt.pro_name',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$sm_property_db[] = $row;
	}

	$sm_property_maps = [];
	$query = [
		'SELECT'	=> 'm.*',
		'FROM'		=> 'sm_property_maps AS m',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=m.property_id'
			],
		],
		'ORDER BY'	=> 'pt.pro_name',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$sm_property_maps[] = $row;
	}
?>

<div class="card-header">
	<h6 class="card-title mb-0">List of Properties</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="max-w-15">Property Name</th>
			<th></th>
			<th>Total units</th>
		</tr>
	</thead>
	<tbody>

<?php

	foreach($sm_property_db as $cur_info)
	{
		if (!empty($sm_property_maps))
		{
			$maps = [];
			foreach($sm_property_maps as $property_map)
			{
				if ($cur_info['id'] == $property_map['property_id'])
				{
					$link = $URL->genLink('hca_mi_property_report', ['property_id' => $cur_info['id'], 'map_id' => $property_map['id']]);
					//$maps[] = '<span class="fw-bold"><a href="'.$link.'">Map #'.$property_map['id'].'</a></span> ('.$property_map['map_description'].')';
					$maps[] = '<a href="'.$link.'" class="badge bg-primary text-light">See map</a>';
				}
			}
		}

		if (!empty($maps))
		{
?>
		<tr>
			<td class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></td>
			<td><?php echo implode(', ', $maps) ?></td>
			<td class="ta-center"><?php echo html_encode($cur_info['total_units']) ?></td>
		</tr>
<?php
		}
	}
?>
	</tbody>
</table>

<?php
	require SITE_ROOT.'footer.php';
}
?>

<nav class="navbar alert-info mb-1">
	<form method="get" accept-charset="utf-8" action="">
		<input type="hidden" name="property_id" value="<?=$search_by_property_id?>">
		<input type="hidden" name="map_id" value="<?=$search_by_map_id?>">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<select name="year" class="form-select form-select-sm">
						<option value="0">All Years</option>
<?php
for ($year = 2019; $year <= date('Y'); $year++)
{
			if ($search_by_year == $year)
				echo '<option value="'.$year.'" selected="selected">'.$year.'</option>';
			else
				echo '<option value="'.$year.'">'.$year.'</option>';
}
?>
					</select>
				</div>
				<div class="col">
					<select name="leak_type" class="form-select form-select-sm">
						<option value="0">All statuses</option>
<?php
$leak_types = [
	4 => 'Copper Line Leak',
	13 => 'Roof Leak',
	15 => 'Slab Leak',
	100 => 'Re-Pipe',
];
foreach ($leak_types as $key => $value)
{
			if ($search_by_leak_type == $key)
				echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
			else
				echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
					</select>
				</div>
				<div class="col">
					<button class="btn btn-sm btn-outline-success" type="submit">Search</button>
					<a href="<?php echo $URL->link('hca_mi_property_report') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Property Map Report</h6>
	</div>
	<div class="card-body">

		<div class="mb-3">
			<span id="leak_type4" class="badge bg-warning fw-bold" style="width:50px;">0</span> - <span class="fw-bold">Copper Line Leak</span>
			<span id="leak_type13" class="badge bg-primary fw-bold ms-3" style="width:50px;">0</span> - <span class="fw-bold">Roof Leak</span>
			<span id="leak_type15" class="badge bg-danger fw-bold ms-3" style="width:50px;">0</span> - <span class="fw-bold">Slab Leak</span>
			<span id="leak_type100" class="badge bg-success fw-bold ms-3" style="width:50px;">0</span> - <span class="fw-bold">Re-Pipe</span>
		</div>

		<section class="KBmap mb-3" id="KBtestmap"></section>
<?php

$i = 0;
$hca_5840_projects_search_query = [];
$hca_5840_projects_search_query[] = 'pj.property_id='.$search_by_property_id;

if ($search_by_leak_type > 0)
	$hca_5840_projects_search_query[] = 'pj.leak_type='.$search_by_leak_type;

if ($search_by_year > 0)
	$hca_5840_projects_search_query[] = 'pj.mois_report_date > '.strtotime($search_by_year.'-01-01').' AND pj.mois_report_date < '.strtotime($search_by_year.'-12-31');

$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u1.realname AS project_manager',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
	],
	'ORDER BY'	=> 'LENGTH(pj.unit_number), pj.unit_number'
);
if (!empty($hca_5840_projects_search_query)) $query['WHERE'] = implode(' AND ', $hca_5840_projects_search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_5840_projects = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_5840_projects[] = $row;
}

$HcaMiPropertyReport->getPropertyUnits();

$hca_repipe_projects_search_query = [];
$hca_repipe_projects_search_query[] = 'pj.property_id='.$search_by_property_id;

if (!in_array($search_by_leak_type, [0, 100]))
	$hca_repipe_projects_search_query[] = 'pj.id=0';


	
// Get RE-PIPE Projects
$query = [
	'SELECT'	=> 'pj.*, p.pro_name, un.unit_number, u1.realname AS created_name, u2.realname AS project_manager, v1.vendor_name AS vendor_name1',
	'FROM'		=> 'hca_repipe_projects as pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.created_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.project_manager_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v1',
			'ON'			=> 'v1.id=pj.vendor_id'
		],
	],
	'ORDER BY'	=> 'LENGTH(un.unit_number), un.unit_number'
];
if (!empty($hca_repipe_projects_search_query)) $query['WHERE'] = implode(' AND ', $hca_repipe_projects_search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_repipe_projects = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_repipe_projects[] = $row;
}


// ____ FOR TES ONLY START _____///
/*
$query = [
	'SELECT'	=> 'un.id, un.unit_number, p.pro_name',
	'FROM'		=> 'sm_property_units AS un',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=un.property_id'
		],
	],
	'ORDER BY'	=> 'LENGTH(un.unit_number), un.unit_number'
];

if ($search_by_property_id > 0)
$query['WHERE'] = 'un.property_id='.$search_by_property_id;

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_repipe_projects = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_repipe_projects[] = [
		'id' => $row['id'],
		'status' => 2,
		'unit_number' => $row['unit_number'],
		'pro_name' => $row['pro_name'],
		'project_manager' => '',
		'date_completed' => '',
		'vendor_name1' => '',
		'date_start' => '',
		'date_end' => '',
	];
}
*/
// ____ FOR TES ONLY END _____///


?>

	</div>
</div>

<?php

$i = $i4 = $i13 = $i15 = $i100 = 0;
$json = [];
$projects = $HcaMiPropertyReport->combineData($hca_5840_projects, $hca_repipe_projects);
if (!empty($projects))
{
?>
<div class="card-header">
	<h6 class="card-title mb-0">List of Projects</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property information</th>
			<th>Project manager / Date</th>
			<th>Performed by Vendor</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($projects as $cur_info)
	{
		if (in_array($cur_info['leak_type'], [4, 13, 15, 100]))
		{
			$status = '';
			$svg = 'fa-map-marker-alt-warning.svg';
			$project_link = $URL->link('hca_5840_manage_project', $cur_info['id']);
			if ($cur_info['leak_type'] == 4)
			{
				$status = '<span class="badge bg-warning">Copper Line Leak</span>';
				$svg = 'fa-map-marker-alt-warning.svg';
				++$i4;
			}
			else if ($cur_info['leak_type'] == 13)
			{
				$status = '<span class="badge bg-primary">Roof Leak</span>';
				$svg = 'fa-map-marker-alt-primary.svg';
				++$i13;
			}
			else if ($cur_info['leak_type'] == 15)
			{
				$status = '<span class="badge bg-danger">Slab Leak</span>';
				$svg = 'fa-map-marker-alt-danger.svg';
				++$i15;
			}
			else if ($cur_info['leak_type'] == 100)
			{
				$project_link = $URL->link('hca_repipe_project', $cur_info['id']);
				$status = '<span class="badge bg-success">Re-Pipe</span>';
				$svg = 'fa-map-marker-alt-success.svg';
				++$i100;
			}

			$start_date = ($cur_info['start_date'] != '') ? 'Start date: '.$cur_info['start_date'] : '';
			$end_date = ($cur_info['end_date'] != '') ? 'End date: '.$cur_info['end_date'] : '';

?>
		<tr>
			<td>
				<p class="fw-bold"><a href="<?=$project_link?>"><?php echo html_encode($cur_info['pro_name']) ?>, <?php echo html_encode($cur_info['unit_number']) ?></a></p>
				<p><?php echo $status ?> - <?php echo $cur_info['project_status'] ?></p>
			</td>
			<td class="ta-center">
				<p class="fw-bold"><?php echo html_encode($cur_info['performed_by']) ?></p>
				<p><?php echo $cur_info['date_performed'] ?></p>
			</td>
			<td class="ta-center">
				<p class="fw-bold"><?php echo html_encode($cur_info['vendor_name']) ?></p>
				<p><?php echo $cur_info['start_date'] ?> - <?php echo $cur_info['end_date'] ?></p>
			</td>
		</tr>
<?php
			$unit_info = $HcaMiPropertyReport->getUnitInfo($cur_info['unit_number']);
			if ($unit_info['pos_x'] != '' && $unit_info['pos_y'] != '' && $svg != '')
			{
				$title = '<a href="'.$project_link.'">Unit # '.html_encode($cur_info['unit_number']).'</a>';
				$content = [];
				$content[] = '<hr class="py-0 my-0">';
				if ($cur_info['performed_by'] != '')
					$content[] = '<p>Project manager: <span class="fw-bold">'.html_encode($cur_info['performed_by']).'</span></p>';
				if ($cur_info['vendor_name'] != '')
					$content[] = '<p>Vendor: <span class="fw-bold">'.html_encode($cur_info['vendor_name']).'</span></p>';
				if ($cur_info['start_date'] != '')
					$content[] = '<p>Vendor Start Date: <span class="fw-bold">'.$cur_info['start_date'].'</span></p>';
				if ($cur_info['end_date'] != '')
					$content[] = '<p>Vendor End Date: <span class="fw-bold">'.$cur_info['end_date'].'</span></p>';

				$content[] = '<p>Project status: <span class="fw-bold">'.$cur_info['project_status'].'</span></p>';

				if (empty($content))
					$content[] = '<p class="fw-bold">No project information.</p>';

				$json[] = '"mapMarker'.$i.'": {
					"cordX": "'.$unit_info['pos_x'].'",
					"cordY": "'.$unit_info['pos_y'].'",
					"icon": "'.BASE_URL.'/apps/swift_property_management/img/'.$svg.'",
					"modal": {
						"title": "'.addslashes($title).'",
						"content": "'.addslashes(implode('', $content)).'"
					}
				},
				';

				++$i;
			}
		}
	}
?>
	</tbody>
</table>
<?php
}
?>

<script>
<?php echo 'var json = {'.implode("\n", $json).'}'."\n"; ?>
document.addEventListener("DOMContentLoaded", () => {
	createKBmap('KBtestmap', '<?php echo BASE_URL ?>/uploads/sm_property_maps/map_<?=$search_by_map_id?>.png');
	KBtestmap.importJSON(json);
	KBtestmap.showAllMapMarkers();

	$('#leak_type4').empty().html(<?=$i4?>);
	$('#leak_type13').empty().html(<?=$i13?>);
	$('#leak_type15').empty().html(<?=$i15?>);
	$('#leak_type100').empty().html(<?=$i100?>);
});
</script>

<?php
require SITE_ROOT.'footer.php';
