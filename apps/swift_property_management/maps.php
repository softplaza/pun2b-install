<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
if ($id < 1 && $property_id < 1)
	message($lang_common['Bad request']);

$SwiftUploader = new SwiftUploader;

if (isset($_POST['upload_file']))
{
	if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name']))
	{
		$file_path = 'uploads/sm_property_maps/';
		$path_result = $SwiftUploader->setOwnPath($file_path);
		
		$new_id = $DBLayer->insert_values('sm_property_maps', [
			'property_id' => $property_id,
			'map_description' => swift_trim($_POST['map_description'])
		]);

		foreach($_FILES['file']['name'] as $key => $value)
		{
			$base_filename = basename($_FILES['file']['name'][$key]);
			$file_ext = str_replace('.', '', strtolower(strrchr($_FILES['file']['name'][$key], '.')));
			$map_path = $file_path . 'map_'.$new_id.'.'.$file_ext;

			if (!$path_result || !move_uploaded_file($_FILES['file']['tmp_name'][$key], SITE_ROOT . $map_path))
			{
				$Core->add_error('Could not upload file.');
			}

			$DBLayer->update('sm_property_maps', ['map_name' => 'map_'.$new_id.'.'.$file_ext], $new_id);

			break;
		}

		if (empty($Core->errors))
		{
			$flash_message = 'Files has been uploaded to the property #'.$property_id;
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
	}
}

else if (isset($_POST['delete_map']))
{
	$map_id = intval(key($_POST['delete_map']));

	$query = [
		'SELECT'	=> 'm.*',
		'FROM'		=> 'sm_property_maps AS m',
		'WHERE'		=> 'm.property_id='.$map_id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$map_info = $DBLayer->fetch_assoc($result);

	$query = array(
		'DELETE'	=> 'sm_property_maps',
		'WHERE'		=> 'id='.$map_id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$file = SITE_ROOT.'uploads/sm_property_maps/'.$map_info['map_name'];
	if (file_exists($file))
		unlink($file);

	// Add flash message
	$flash_message = 'Map has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

// Grab the maps
$sm_property_maps = [];
$query = [
	'SELECT'	=> 'm.*, m.property_id, pt.pro_name',
	'FROM'		=> 'sm_property_maps AS m',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=m.property_id'
		],
	],
	'WHERE'		=> 'm.property_id='.$property_id,
	'ORDER BY'	=> 'pt.pro_name',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_maps[] = $row;
}

$Core->set_page_id('sm_property_management_maps', 'sm_property_management');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">

		<div class="card-header">
			<h6 class="card-title mb-0">Uploaded Property Maps</h6>
		</div>
		<div class="card-body">
<?php
if (!empty($sm_property_maps))
{
	$output = [];
	$output[] = '<div class="row row-cols-1 row-cols-md-2 g-4">';
	foreach($sm_property_maps as $cur_info)
	{
		$output[] = '<div class="col">';
		$output[] = '<div class="card">';
		$output[] = '<a href="'.BASE_URL.'/uploads/sm_property_maps/'.$cur_info['map_name'].'"><img src="'.BASE_URL.'/uploads/sm_property_maps/'.$cur_info['map_name'].'" height="300"></a>';
		$output[] = '<div class="card-body">';
        $output[] = '<h5 class="card-title">'.html_encode($cur_info['map_name']).'</h5>';
		$output[] = '<p class="card-text">'.html_encode($cur_info['map_description']).'</p>';
		$output[] = '</div>';
		$output[] = '<div class="card-footer">';
        $output[] = '<button type="submit" name="delete_map['.$cur_info['id'].']" class="badge bg-danger">Delete</button>';
		$output[] = '</div>';
		$output[] = '</div>';
		$output[] = '</div>';
	}

	echo implode("\n", $output);
	echo '</div>';
}
else
	echo '<div class="alert alert-warning" role="alert">You have no items on this page.</div>';
?>
		</div>
<?php
if ($id > 0)
{
?>
	<div class="card-header">
		<h6 class="card-title mb-0">Upload Map</h6>
	</div>
	<div class="card-body">

		<?php $SwiftUploader->setForm(['input' => '']);?>

		<div class="col-md-6 mb-3">
			<label class="form-label" for="fld_map_title">Map title</label>
			<input type="text" name="map_title" value="" id="fld_map_title" class="form-control" placeholder="Example: Map #1">
		</div>
		<div class="mb-3">
			<label class="form-label" for="fld_map_description">Map description</label>
			<textarea class="form-control" name="map_description" id="fld_map_description" placeholder="Example: Units 1 - 115"></textarea>
		</div>

		<button type="submit" name="upload_file" class="btn btn-primary" id="btn_upload_file">Upload file</button>
		
	</div>
<?php
}
else
{
?>
		<div class="card-header">
			<h6 class="card-title mb-0">Upload Map</h6>
		</div>
		<div class="card-body">
			<?php $SwiftUploader->setForm(['input' => '']);?>

			<div class="col-md-6 mb-3">
				<label class="form-label" for="fld_map_title">Map title</label>
				<input type="text" name="map_title" value="" id="fld_map_title" class="form-control" placeholder="Example: Map #1">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_map_description">Map description</label>
				<textarea class="form-control" name="map_description" id="fld_map_description" placeholder="Example: Units 1 - 115"></textarea>
			</div>

			<button type="submit" name="upload_file" class="btn btn-primary" id="btn_upload_file">Upload file</button>
		</div>
<?php
}
?>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
