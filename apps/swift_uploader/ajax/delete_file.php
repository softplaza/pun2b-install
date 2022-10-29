<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$ajax = [];
if ($id > 0)
{
	$SwiftUploader = new SwiftUploader;

	// Set Uploader access 
	$SwiftUploader->access_view_files = true;
	$SwiftUploader->access_upload_files = true;
	$SwiftUploader->access_delete_files = true;

	$query = array(
		'SELECT'	=> 'f.id, f.file_name, f.base_name, f.file_ext, f.file_path, f.file_type, f.load_time, f.table_name, f.table_id',
		'FROM'		=> 'sm_uploader AS f',
		'WHERE'		=> 'f.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$file_info = $DBLayer->fetch_assoc($result);

	$files_info = [];

	if ($file_info['table_name'] != '' && $file_info['table_id'] > 0)
	{
		$file_path = $file_info['file_path'] . $file_info['file_name'];

		// Delete file from Database
		$DBLayer->delete('sm_uploader', $id);
		
		// Remove file from server
		if (file_exists(SITE_ROOT . $file_path))
			unlink(SITE_ROOT . $file_path);

		$query = array(
			'SELECT'	=> 'f.*',
			'FROM'		=> 'sm_uploader AS f',
			'WHERE'		=> 'f.table_name=\''.$DBLayer->escape($file_info['table_name']).'\' AND f.table_id='.$file_info['table_id'],
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$files_info[] = $row;
		}
	}

	if (!empty($files_info) && $file_info['table_name'] != '')
	{
		foreach($files_info as $cur_info)
		{
			$file_link = BASE_URL.'/'.$cur_info['file_path'].$cur_info['file_name'];

			$ajax[] = '<div class="col-md-auto me-2 mb-2">';

			$ajax[] = $SwiftUploader->getCurrentFile($cur_info);

			if ($SwiftUploader->access_delete_files)
				$ajax[] = '<p><button type="button" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete it?\')?deleteFile(\''.$file_info['table_name'].'\','.$cur_info['id'].'):\'\';">Delete</button></p>';
			
			$ajax[] = '</div>';
		}
	}
	else
		$ajax[] = '<div class="alert alert-warning py-1"" role="alert">No files uploaded.</div>';

	echo json_encode(
		[
			'result' => '',
			'image_thumbnails' => implode("", $ajax)
		]
	);

}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();

