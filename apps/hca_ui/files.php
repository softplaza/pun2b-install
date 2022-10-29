<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_ui', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Project does not exist or has been removed.');

$query = array(
	'SELECT'	=> 'ch.*, p.pro_name, p.manager_email, u.unit_number',
	'FROM'		=> 'hca_ui_checklist AS ch',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=ch.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS u',
			'ON'			=> 'u.id=ch.unit_id'
		],
	],
	'WHERE'		=> 'ch.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

if (empty($project_info))
	message('Sorry, this Project does not exist or has been removed.');

$SwiftUploader = new SwiftUploader;
if (isset($_POST['upload_file']))
{
	$SwiftUploader->checkAllowed();
	$Core->add_errors($SwiftUploader->getErrors());

    $SwiftUploader->uploadFiles('hca_ui_checklist', $id);

	$flash_message = 'Files has been uploaded to project #'.$id;
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['delete_files']))
{
	$file_pathes = isset($_POST['file_path']) ? $_POST['file_path'] : [];
	
	$files_ids = [];
	if (!empty($file_pathes))
	{
		foreach($file_pathes as $file_id => $file_path)
		{
			if (file_exists(SITE_ROOT . $file_path))
			{
				unlink(SITE_ROOT . $file_path);
				$files_ids[] = $file_id;
			}
		}
		
		if (!empty($files_ids))
		{
			$query = array(
				'DELETE'	=> 'sm_uploader',
				'WHERE'		=> 'id IN('.implode(',', $files_ids).')'
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$flash_message = 'Selected files of project #'.$id.' have been deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		else
			$Core->add_error('No files have been selected to delete or wrong pathes.');
	}
	else
		$Core->add_error('No files have been selected to delete.');
}

else if (isset($_POST['send_files']))
{
	$file_pathes = isset($_POST['file_path']) ? $_POST['file_path'] : [];
	$emails = isset($_POST['emails']) ? swift_trim($_POST['emails']) : '';
	$mail_message = (isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : '')."\n\n";
	
	if (empty($file_pathes))
		$Core->add_error('No files have been selected to send.');
	if ($emails == '')
		$Core->add_error('Emails field is empty. Insert email of recipient.');
	
	if (empty($Core->errors))
	{
		$pathes = $ids = [];
		foreach($file_pathes as $key => $path) {
			$pathes[] = SITE_ROOT.$path;
			$ids[] = $key;
		}
		
		$url_param = [];
		$url_param[] = 'project=hca_ui_checklist';
		$url_param[] = 'ids='.implode(',', $ids);
		$hash = base64_encode(implode('&', $url_param));

		if (!empty($ids))
		{
			$mail_message .= 'To view shared files follow this link:'."\n";
			$mail_message .= $URL->link('swift_uploader_view', $hash);
		}

		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send($emails, 'Plumbing Inspection', $mail_message);

		$flash_message = 'Files of project #'.$id.' has been sent to '.$emails;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'id, file_name, base_name, file_ext, file_path, file_type, load_time',
	'FROM'		=> 'sm_uploader',
	'WHERE'		=> 'table_name=\'hca_ui_checklist\' AND table_id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$images_info = $files_info = $media_info = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	if ($row['file_type'] == 'image')
		$images_info[] = $row;
	else if ($row['file_type'] == 'file')
		$files_info[] = $row;
	else if ($row['file_type'] == 'media')
		$media_info[] = $row;
}

$Core->set_page_id('hca_ui_files', 'hca_ui');
require SITE_ROOT.'header.php';
?>

<style>
.cur-img, .cur-video {
	vertical-align:top;
	display: inline-block;
	padding: 1.5em;
}
.cur-img img {height: 150px;}
.cur-file {
	width:180px;
	display: inline-block;
	padding: 1.5em;
	vertical-align: top;
}
.cur-file p {word-break: break-all;}
.holder_default {
	width:500px;
	height:150px;
	border: 3px dashed #ccc;
}
</style>

<?php if($access): ?>
	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
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
<?php
	$SwiftUploader->getProjectFiles('hca_ui_checklist', $id);
?>
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Uploaded Images</h6>
			</div>
			<div class="card-body">
<?php
if (!empty($images_info))
{
?>
				<div class="uploaded-images">
<?php
	foreach($images_info as $cur_file)
	{
		$cur_link = BASE_URL.'/'.$cur_file['file_path'].$cur_file['file_name'];
		
		$file_view = [];
		$file_view[] = '<div class="cur-img">';
		$file_view[] = '<a data-fancybox="single" href="'.$cur_link.'" target="_blank"><img src="'.$cur_link.'"/></a>';
		$file_view[] = '<p><span style="margin-right:5px;"><input type="checkbox" name="file_path['.$cur_file['id'].']" value="'.$cur_file['file_path'].$cur_file['file_name'].'" /></span>'.$cur_file['base_name'].'</p>';
		$file_view[] = '</div>';
		
		echo "\n\t".implode("\n\t\t", $file_view);
	}
?>
				</div>
<?php
}
else
{
?>
				<div class="alert alert-warning" role="alert">You don't have any uploaded images associated with this project yet.</div>
<?php
}
?>
			</div>
		</div>
		
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Uploaded Documents</h6>
			</div>
			<div class="card-body">
<?php
if (!empty($files_info))
{
?>
				<div class="uploaded-files">
<?php
	foreach($files_info as $cur_file)
	{
		$cur_link = BASE_URL.'/'.$cur_file['file_path'].'/'.$cur_file['file_name'].'?v='.time();
		$doc_icon = ($cur_file['file_ext'] == 'pdf') ? 'pdf.png' : 'doc.png';
		
		$file_view = [];
		$file_view[] = '<div class="cur-file">';
		$file_view[] = '<a href="'.$cur_link.'" target="_blank"><img src="'.BASE_URL.'/img/'.$doc_icon.'" style="width:80px;height:auto;"/></a>';
		$file_view[] = '<p><span style="margin-right:5px;"><input type="checkbox" name="file_path['.$cur_file['id'].']" value="'.$cur_file['file_path'].$cur_file['file_name'].'" /></span>'.$cur_file['base_name'].'</p>';
		$file_view[] = '</div>';
		
		echo "\n\t".implode("\n\t\t", $file_view);
	}
?>
				</div>
<?php
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

		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Uploaded Media Files</h6>
			</div>
			<div class="card-body">
<?php
if (!empty($media_info))
{
?>
				<div class="uploaded-videos">
<?php
	foreach($media_info as $cur_file)
	{
		$cur_link = BASE_URL.'/'.$cur_file['file_path'].'/'.$cur_file['file_name'];
		
		$file_view = [];
		$file_view[] = '<div class="cur-video">';
		$file_view[] = '<video width="320" height="240" controls><source src="'.$cur_link.'" type="video/mp4">Your browser does not support the video tag.</video>';
		$file_view[] = '<p><span style="margin-right:5px;"><input type="checkbox" name="file_path['.$cur_file['id'].']" value="'.$cur_file['file_path'].$cur_file['file_name'].'" /></span>'.$cur_file['file_name'].' ('.format_time($cur_file['load_time']).')</p>';
		$file_view[] = '</div>';
		
		echo "\n\t".implode("\n\t\t", $file_view);
	}
?>
				</div>
<?php
}
else
{
?>
				<div class="alert alert-warning" role="alert">You don't have any uploaded Media Files associated with this project yet.</div>
<?php
}
?>
			</div>
		</div>
		
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Send files to Email</h6>
			</div>
			<div class="card-body">
<?php
$mail_message = [];
//$mail_message[] = 'Hello,';
$mail_message[] = 'This is the Plumbing Inspection Report.'."\n";
$mail_message[] = 'Property: '.$project_info['pro_name'];
$mail_message[] = 'Unit #: '.$project_info['unit_number']."\n\n";
?>
				<div class="mb-3">
					<label class="form-label" for="emails">Send to:</label>
					<input type="text" name="emails" value="<?php echo (isset($project_info['manager_email']) ? $project_info['manager_email'] : '') ?>" class="form-control" id="emails" placeholder="Insert email addresses separated by commas">
				</div>
				<div class="mb-3">
					<label class="form-label" for="mail_message">Message</label>
					<textarea type="text" name="mail_message" class="form-control" id="mail_message" placeholder="Leave your message"><?php echo implode("\n", $mail_message) ?></textarea>
				</div>
				<div class="mb-3">
					<div class="alert alert-info" role="alert">All selected files will be shared by generated security link.</div>
				</div>
				<button type="submit" name="send_files" class="btn btn-primary">Send files</button>
				<button type="submit" name="delete_files" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selected files?')">Delete files</button>
			</div>
		</div>
	</form>

<?php
require SITE_ROOT.'footer.php';