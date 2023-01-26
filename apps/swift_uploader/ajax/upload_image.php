<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$table_name = isset($_POST['table']) ? swift_trim($_POST['table']) : '';

if (isset($_FILES['image']['name']) && $id > 0 && $table_name != '')
{
	$toast_container = [];

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

	$file_ext = str_replace('.', '', strtolower(strrchr($_FILES['image']['name'], '.')));
	$base_filename = basename($_FILES['image']['name']);
	$file_size = isset($_FILES['image']['size']) ? $_FILES['image']['size'] : 0;
	$new_filename = $User->get('id').'_'.$id.'_'.date('ymd_His').'.'.$file_ext;
	$file_type = $SwiftUploader->getFileType($file_ext);

	// Warning: POST Content-Length of 2 785 018 417 bytes exceeds the limit of 41 943 040 bytes in Unknown on line 0
	if (move_uploaded_file($_FILES['image']['tmp_name'], SITE_ROOT . $file_path . $new_filename))
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

		$toast_container[] = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
		$toast_container[] = '<div class="toast-header toast-success"><strong class="me-auto">Message</strong></div>';
		$toast_container[] = '<div class="toast-body toast-success">Image uploaded successfully.</div>';
		$toast_container[] = '</div>';
	}
	else
	{
		$toast_container[] = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
		$toast_container[] = '<div class="toast-header toast-danger"><strong class="me-auto">Error</strong></div>';
		$toast_container[] = '<div class="toast-body toast-danger">Failed to uploade image.</div>';
		$toast_container[] = '</div>';
	}

	$num_rows = $DBLayer->getNumRows('sm_uploader', 'table_name=\''.$DBLayer->escape($table_name).'\' AND table_id='.$id);


	$uploaded_images = $ids = [];
	$query = array(
		'SELECT'	=> 'f.*',
		'FROM'		=> 'sm_uploader AS f',
		'WHERE'		=> 'f.table_name=\''.$DBLayer->escape($table_name).'\' AND f.table_id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploaded_images[] = $row;
		$ids[] = $row['id'];
	}

	if (!empty($ids))
	{
		$hash = base64_encode('project='.$table_name.'&ids='.implode(',', $ids));
		$image_viewer_link = '<a href="'.$URL->link('swift_uploader_view', $hash).'" target="_blank"><strong id="num_uploaded_images">'.count($uploaded_images).'</strong> <span>uploaded images</span></a>';
	}
	else
		$image_viewer_link = '<span class="fw-bold">No uploaded images</span>';

	echo json_encode(
		[
			'toast_container'	=> implode('', $toast_container),
			'num_images'		=> $num_rows,
			'image_viewer_link'	=> $image_viewer_link,
			'list_images'		=> ''
		]
	);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();

