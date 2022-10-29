<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_property_management', 1)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

if (isset($_POST['update_hash']))
{

	if (!empty($property_info))
	{
		foreach($property_info as $property)
		{
			$hash = random_key(10, true, true);
			$query = array(
				'UPDATE'	=> 'sm_property_db',
				'SET'		=> 'hash=\''.$DBLayer->escape($hash).'\'',
				'WHERE'		=> 'id='.$property['id'],
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'Properties hash updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$Core->set_page_id('sm_property_management_properties_list', 'sm_property_management');
require SITE_ROOT.'header.php';
?>

	<form method="post"  accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-striped my-0">
			<thead>
				<tr>
					<th>Property name</th>
					<th>Map</th>
					<th>Address</th>
					<th>Phone #/Fax</th>
					<th>Total units</th>
				</tr>
			</thead>
			<tbody>
<?php
$zone_array = array(1 => 'Zone 1', 2 => 'Zone 2', 3 => 'Zone 3');

foreach ($property_info as $cur_info)
{
	$map_link = ($cur_info['map_link'] != '') ? '<a href="'.BASE_URL.'/'.$cur_info['map_link'].'?v='.time().'"><i class="fas fa-map-marked fa-lg"></i></a>' : '';
	$zone = isset($zone_array[$cur_info['zone']]) ? $zone_array[$cur_info['zone']] : '';

	if ($User->checkAccess('swift_property_management', 12))
		$Core->add_dropdown_item('<a href="'.$URL->link('sm_property_management_edit_property', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit property</a>');

	if ($User->checkAccess('swift_property_management', 12))
		$Core->add_dropdown_item('<a href="'.$URL->link('sm_property_management_buildings', $cur_info['id']).'"><i class="fas fa-building"></i> Buildings</a>');

	if ($User->checkAccess('swift_property_management', 12))
		$Core->add_dropdown_item('<a href="'.$URL->link('sm_property_management_units_list', $cur_info['id']).'"><i class="fas fa-bed"></i> Units</a>');
		
	if ($User->checkAccess('swift_property_management', 12))
		$Core->add_dropdown_item('<a href="'.$URL->link('sm_property_management_maps', $cur_info['id']).'"><i class="fas fa-map"></i></i> Maps</a>');
	
	if ($User->checkAccess('swift_property_management', 12))
		$Core->add_dropdown_item('<a href="'.$URL->link('sm_property_management_unit_keys', $cur_info['id']).'"><i class="fas fa-key"></i></i> Keys</a>');

	$css = ($cur_info['enabled'] == '0') ? 'table-danger' : '';
?>
				<tr class="<?php echo $css; ?>">
					<td>
						<strong><?php echo html_encode($cur_info['pro_name']) ?></strong>
						<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
					</td>
					<td class="ta-center"><?php echo $map_link ?></td>
					<td><a href='https://maps.google.com/?q=<?php echo html_encode($cur_info['office_address']) ?>'><a href='https://maps.apple.com/maps?q=<?php echo html_encode($cur_info['office_address']) ?>'><?php echo html_encode($cur_info['office_address']) ?></a></a></td>
					<td>
						<p>Phone: <a href="tel:<?php echo html_encode($cur_info['office_phone']) ?>"><?php echo html_encode($cur_info['office_phone']) ?></a></p>
						<p>Fax: <?php echo html_encode($cur_info['office_fax']) ?></p>
					</td>
					<td class="ta-center fw-bold"><?php echo html_encode($cur_info['total_units']) ?></td>
				</tr>
<?php
}
?>
			</tbody>
		</table>

		<div class="my-3">
<?php if ($User->is_admin()) : ?>
			<button type="submit" name="update_hash" class="btn btn-warning">Update Hash</button>
<?php endif; ?>
		</div>
	</form>

<?php
require SITE_ROOT.'footer.php';