<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vendors', 4)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors_groups',
	'ORDER BY'	=> 'group_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$groups_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$groups_info[] = $row;
}

$Core->set_page_id('sm_vendors_departments', 'sm_vendors');
require SITE_ROOT.'header.php';

if (!empty($main_info))
{
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead>
				<tr>
					<th>Vendor Name</th>
					<th></th>
					<?php $Hooks->get_hook('HcaVendorsDepartmentsTableHead'); ?>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
?>
				<tr>
<?php
		echo '<td><strong>'.html_encode($cur_info['vendor_name']).'</strong></td>';
		echo '<td><a href="'.$URL->link('sm_vendors_edit', $cur_info['id']).'#section_projects" class="badge bg-primary text-white">Edit</a></td>';

		$Hooks->get_hook('HcaVendorsDepartmentsTableBody');
?>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
<?php
}
require SITE_ROOT.'footer.php';