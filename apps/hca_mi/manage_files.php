<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$permission4 = ($User->checkPermissions('hca_mi', 4)) ? true : false; // upload files
$permission6 = ($User->checkPermissions('hca_mi', 6)) ? true : false; // upload files

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Project does not exist or has been removed.');

$HcaMi = new HcaMi;
$SwiftUploader = new SwiftUploader;

if (isset($_POST['upload_file']))
{
	$SwiftUploader->checkAllowed();
	$Core->add_errors($SwiftUploader->getErrors());

    $SwiftUploader->uploadFiles('hca_5840_projects', $id);

	$flash_message = 'Files has been uploaded to project #'.$id;
	$HcaMi->addAction($id, $flash_message);

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
			$HcaMi->addAction($id, $flash_message);

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
		$url_param[] = 'project=hca_5840_projects';
		$url_param[] = 'ids='.implode(',', $ids);
		$hash = base64_encode(implode('&', $url_param));

		if (!empty($ids))
		{
			$mail_message .= 'To view shared files follow this link:'."\n";
			$mail_message .= $URL->link('swift_uploader_view', $hash);
		}

		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send($emails, 'Moisture Project', $mail_message);

		$flash_message = 'Files of project #'.$id.' has been sent to '.$emails;
		$HcaMi->addAction($id, $flash_message);

		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

//Get project info
$query = [
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, pt.manager_email, un.unit_number, u1.realname AS project_manager1, u2.realname AS project_manager2',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.performed_uid2'
		],
		//add users proj mng 1 and 2
	],
	'WHERE'		=> 'pj.id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);
$project_info['unit_number'] = ($project_info['unit_number'] == 0 && $project_info['unit'] == '') ? 'Common area' : $project_info['unit_number'];
$project_info['unit_number'] = ($project_info['unit_number'] == 0 && $project_info['unit'] != '') ? $project_info['unit'] : $project_info['unit_number'];

if (empty($project_info))
	message('Sorry, this Project does not exist or has been removed.');

$query = array(
	'SELECT'	=> 'id, file_name, base_name, file_ext, file_path, file_type, load_time',
	'FROM'		=> 'sm_uploader',
	'WHERE'		=> 'table_name=\'hca_5840_projects\' AND table_id='.$id,
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

$Core->set_page_id('hca_mi_manage_files', 'hca_mi');
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
	width:150px;
	display: inline-block;
	padding: 1.5em;
	vertical-align: top;
}
.cur-file p {word-break: break-all;}
</style>

<?php if($permission4): ?>
<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header d-flex justify-content-between">
			<h6 class="card-title mb-0">File upload form</h6>
			<div>
				<a href="<?=$URL->link('hca_5840_manage_project', $id)?>" class="badge bg-primary text-white">Project</a>
				<a href="<?=$URL->link('hca_5840_manage_invoice', $id)?>" class="badge bg-primary text-white">Invoice</a>
				<a href="<?=$URL->link('hca_5840_manage_appendixb', $id)?>" class="badge bg-primary text-white">new Appendix-B</a>
			</div>
		</div>
		<div class="card-body">

			<?php $SwiftUploader->setForm(); ?>

			<button type="submit" name="upload_file" class="btn btn-primary" id="btn_upload_file">Upload File</button>

		</div>
	</div>
</form>
<?php endif; ?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<?php $SwiftUploader->getProjectFiles('hca_5840_projects', $id); ?>

<?php
if (!empty($images_info))
{
?>
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Uploaded Images</h6>
		</div>
		<div class="card-body">
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
		</div>
	</div>
<?php
}


if (!empty($files_info))
{
?>
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Uploaded Documents</h6>
		</div>
		<div class="card-body">
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
		</div>
	</div>
<?php
}


if (!empty($media_info))
{
?>
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Uploaded Media Files</h6>
		</div>
		<div class="card-body">
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
		</div>
	</div>
<?php
}
?>

<?php if ($permission6): ?>
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Send files to Email</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-warning py-1" role="alert">
				<p class="fw-bold">Important!</p>
				<p>Before sending an e-mail, please make sure that you have selected the files to be sent by e-mail.</p>
			</div>
<?php
$mail_message = [];
$mail_message[] = 'This is the Moisture Inspection Report.'."\n";
$mail_message[] = 'Property: '.$project_info['pro_name'];
if ($project_info['unit_number'] != '')
	$mail_message[] = 'Unit #: '.$project_info['unit_number']."\n\n";
?>
			<div class="mb-3">
				<label class="form-label" for="fld_emails">Send to:</label>
				<input type="text" name="emails" value="<?php echo (isset($project_info['manager_email']) ? $project_info['manager_email'] : '') ?>" class="form-control" id="fld_emails" placeholder="Insert email addresses separated by commas" required>
				<label class="text-muted" for="fld_emails">Insert email addresses separated by commas</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_mail_message">Message</label>
				<textarea type="text" name="mail_message" class="form-control" id="fld_mail_message" required><?php echo implode("\n", $mail_message) ?></textarea>
				<label class="text-muted" for="fld_mail_message">Write your text message. A link to view the files will be added below your message.</label>
			</div>
			<button type="submit" name="send_files" class="btn btn-primary">Send files</button>
			<button type="submit" name="delete_files" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selected files?')">Delete files</button>
		</div>
	</div>
<?php endif; ?>

</form>

<?php
require SITE_ROOT.'footer.php';
