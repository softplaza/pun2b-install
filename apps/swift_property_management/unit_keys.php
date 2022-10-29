<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	//'WHERE'		=> 'u.id='.$id,
	'ORDER BY'	=> 'p.pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$sm_property_db[] = $row;
}

$query = array(
	'SELECT'	=> 'u.*',
	'FROM'		=> 'sm_property_units AS u',
	'WHERE'		=> 'u.property_id='.$pid,
	'ORDER BY'	=> 'LENGTH(u.unit_number), u.unit_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_units = [];
while ($data = $DBLayer->fetch_assoc($result))
{
	$sm_property_units[] = $data;
}

$Core->set_page_id('sm_property_management_unit_keys', 'sm_property_management');
require SITE_ROOT.'header.php';
?>

<form method="get" accept-charset="utf-8" action="">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Update unit information</h6>
		</div>
		<div class="card-body">

			<div class="mb-3 col-md-4">
				<label class="form-label" for="fld_property_id">Properties</label>
				<select id="fld_property_id" class="form-select" name="pid">
					<option value="0">Select a property</option>
<?php
if (!empty($sm_property_db))
{
	foreach($sm_property_db as $cur_info)
	{
		if ($pid == $cur_info['id'])
			echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	}
}
?>
				</select>
			</div>

			<div class="mb-3">
				<button type="submit" class="btn btn-primary">Search</button>
			</div>

		</div>
	</div>
</form>

<?php
if (!empty($sm_property_units))
{
?>
<div class="card-header">
	<h6 class="card-title mb-0">List of units</h6>
</div>
<table class="table table-sm table-striped table-bordered">
	<thead>
		<tr>
			<th>Unit Number</th>
			<th>Key Number</th>
		</tr>
	</thead>
	<tbody>

<?php
	foreach ($sm_property_units as $cur_info)
	{
?>
		<tr>
			<td class="fw-bold"><?php echo html_encode($cur_info['unit_number']) ?></td>
			<td><?php echo html_encode($cur_info['key_number']) ?></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>

<?php
}
require SITE_ROOT.'footer.php';