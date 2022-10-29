<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sb721', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftUploader = new SwiftUploader;
if (isset($_POST['upload_file']))
{
	$SwiftUploader->checkAllowed();
	//$Core->add_errors($SwiftUploader->getErrors());

    $SwiftUploader->uploadFiles('hca_sb721_documents', 1);

	$flash_message = 'Files has been uploaded';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['delete_file']))
{
	$file_id = intval(key($_POST['delete_file']));
	
	if ($file_id > 0)
	{
		$query = array(
			'SELECT'	=> 'file_name, file_path',
			'FROM'		=> 'sm_uploader',
			'WHERE'		=> 'id='.$file_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$file_info = $DBLayer->fetch_assoc($result);

		if (file_exists(SITE_ROOT.$file_info['file_path'].'/'.$file_info['file_name']))
		{
			unlink(SITE_ROOT.$file_info['file_path'].'/'.$file_info['file_name']);

			$DBLayer->delete('sm_uploader', $file_id);

			$flash_message = 'File has been deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
	}
}

$query = array(
	'SELECT'	=> 'id, file_name, base_name, file_ext, file_path, file_type, load_time',
	'FROM'		=> 'sm_uploader',
	'WHERE'		=> 'table_name=\'hca_sb721_documents\'',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$files_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$files_info[] = $row;
}

$Core->set_page_id('hca_sb721_documents', 'hca_sb721');
require SITE_ROOT.'header.php';
?>

<?php if($access): ?>
	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data" onsubmit="return checkFormSubmit(this)">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">File upload form</h6>
			</div>
			<div class="card-body">

<?php $SwiftUploader->setForm();?>

				<button type="submit" name="upload_file" class="btn btn-primary" id="btn_upload_file">Upload File</button>
			</div>
		</div>
	</form>
<?php endif; ?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Uploaded Documents</h6>
			</div>
			<div class="card-body">
<?php
if (!empty($files_info))
{
	foreach($files_info as $cur_file)
	{
		$cur_link = BASE_URL.'/'.$cur_file['file_path'].'/'.$cur_file['file_name'].'?v='.time();
		$doc_icon = ($cur_file['file_ext'] == 'pdf') ? 'pdf.png' : 'doc.png';
		
		$file_view = [];
		$file_view[] = '<div class="mb-3">';
		$file_view[] = '<div class="mb-3">';
		$file_view[] = '<iframe src="'.$cur_link.'" style="width:auto;height:300px;"></iframe>';
		$file_view[] = '<p><span style="margin-right:5px;"><a href="'.$cur_link.'" target="_blank"><i class="fas fa-file-pdf fa-lg text-danger"></i></a></span><a href="'.$cur_link.'" target="_blank">'.$cur_file['base_name'].'</a></p>';
		$file_view[] = '</div>';
		$file_view[] = '<button type="submit" name="delete_file['.$cur_file['id'].']" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete selected files?\')">Delete</button>';
		$file_view[] = '</div>';
		
		echo "\n\t".implode("\n\t\t", $file_view);
	}
}
else
{
?>
				<div class="alert alert-warning" role="alert">You don't have any uploaded documents associated with this project yet.</div>
<?php
}
?>
			</div>
		</div>
	</form>

<?php
require SITE_ROOT.'footer.php';