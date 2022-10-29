<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$table_name = isset($_POST['table']) ? swift_trim($_POST['table']) : '';

$ajax = [];
if (isset($_FILES['file']['name']) && $id > 0 && $table_name != '')
{
	if ($Config->get('o_sm_uploader_structure') == 1)
		$file_path = 'uploads/'.date('Y').'/'.date('m').'/'.$table_name.'/';
	else
		$file_path = 'uploads/'.$table_name.'/'.date('Y').'/'.date('m').'/'; // Default

	$SwiftUploader = new SwiftUploader;

	// Set Uploader access 
	$SwiftUploader->access_view_files = true;
	$SwiftUploader->access_upload_files = true;
	$SwiftUploader->access_delete_files = true;

	$SwiftUploader->checkPath($table_name);

	$file_ext = str_replace('.', '', strtolower(strrchr($_FILES['file']['name'], '.')));
	$base_filename = basename($_FILES['file']['name']);
	$file_size = isset($_FILES['file']['size']) ? $_FILES['file']['size'] : 0;
	$new_filename = $User->get('id').'_'.$id.'_'.date('ymd_His').'.'.$file_ext;
	$file_type = $SwiftUploader->getFileType($file_ext);

	// Warning: POST Content-Length of 2 785 018 417 bytes exceeds the limit of 41 943 040 bytes in Unknown on line 0
	if (move_uploaded_file($_FILES['file']['tmp_name'], SITE_ROOT . $file_path . $new_filename))
	{
		$query = array(
			'INSERT'	=> 'user_id, user_name, base_name, file_name, file_type, file_ext, file_size, file_path, load_time, table_name, table_id',
			'INTO'		=> 'sm_uploader',
			'VALUES'	=> '\''.$DBLayer->escape($User->get('id')).'\',
				\''.$DBLayer->escape($User->get('realname')).'\',
				\''.$DBLayer->escape($base_filename).'\',
				\''.$DBLayer->escape($new_filename).'\',
				\''.$DBLayer->escape($file_type).'\',
				\''.$DBLayer->escape($file_ext).'\',
				\''.$DBLayer->escape($file_size).'\',
				\''.$DBLayer->escape($file_path).'\',
				'.time().',
				\''.$DBLayer->escape($table_name).'\',
				'.$id.''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$result = '';
	}
	else
		$result = '<div class="alert alert-danger py-1"" role="alert">Failed to upload file.</div>';

	$query = array(
		'SELECT'	=> 'f.*',
		'FROM'		=> 'sm_uploader AS f',
		'WHERE'		=> 'f.table_name=\''.$DBLayer->escape($table_name).'\' AND f.table_id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$files_info = [];
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$files_info[] = $row;
	}

	if (!empty($files_info))
	{
		foreach($files_info as $cur_info)
		{
			$file_link = BASE_URL.'/'.$cur_info['file_path'].$cur_info['file_name'];


			$ajax[] = '<div class="col-md-auto me-2 mb-2">';

			$ajax[] = $SwiftUploader->getCurrentFile($cur_info);

			if ($SwiftUploader->access_delete_files)
				$ajax[] = '<p><button type="button" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete it?\')?deleteFile(\''.$table_name.'\','.$cur_info['id'].'):\'\';">Delete</button></p>';

			$ajax[] = '</div>';
		}
	}
	else
		$ajax[] = '<div class="alert alert-warning py-1"" role="alert">No files uploaded.</div>';

	echo json_encode(
		[
			'result' => $result,
			'image_thumbnails' => implode("", $ajax)
		]
	);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();

