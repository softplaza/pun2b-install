<?php

/**
 * 
 * @package SwiftUploader
 */

class SwiftUploader
{
    private $time_now = 0;
    private $cur_year = 0;
    private $cur_month = 0;

    // Main directory of uploaded files
    private $main_dir = 'uploads/';
    private $cur_path = 'uploads/others';

    // JS directory
    private $js_url = BASE_URL.'/apps/swift_uploader/js/';
    // CSS directory
    private $css_url = BASE_URL.'/apps/swift_uploader/css/';

    private $structure = 0;
    // Types of images allowed
    private $allowed_images = [];
    // Types of media files allowed
    private $allowed_media = [];
    // Types of documents allowed
    private $allowed_files = [];

    // Permissions
    public $access_view_files = false;
    public $access_upload_files = false;
    public $access_delete_files = false;

    // Current project files
    public $cur_project_images = [];
    public $cur_project_files = [];
    public $cur_project_media = [];

    var $uploaded_images = [];

    // Exceptions
    public $errors = [];
    
    function __construct()
    {
        global $Config;

        $this->time_now = time();
        $this->cur_year = date('Y');
        $this->cur_month = date('m');

        $this->structure = $Config->get('o_sm_uploader_structure');
        $this->allowed_images = explode(',', $Config->get('o_sm_uploader_image_types'));
        $this->allowed_media = explode(',', $Config->get('o_sm_uploader_media_types'));
        $this->allowed_files = explode(',', $Config->get('o_sm_uploader_file_types'));
    }

	function displayFiles($table_name, $table_id)
	{
        global $DBLayer, $URL;

        if (!$this->access_view_files)
            return;

        $query = array(
            'SELECT'	=> 'f.id, f.file_path, f.file_name',
            'FROM'		=> 'sm_uploader AS f',
            'WHERE'		=> 'f.table_name=\''.$DBLayer->escape($table_name).'\' AND f.table_id='.$table_id
        );
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        $cur_files = array();
        while ($row = $DBLayer->fetch_assoc($result)) {
            $cur_files[] = $row;
        }

        if (empty($cur_files))
            return;

        if (!empty($cur_files))
        {
            $output = [];
            $output[] = '<div class="row">';
            foreach ($cur_files as $cur_info)
            {
                $image = [];
                $image[] = '<div class="col-md-auto me-2 mb-2">';
                $image[] = '<a data-fancybox="single" href="'.$URL->link($cur_info['file_path'].$cur_info['file_name']).'" target="_blank">';
                $image[] = '<img src="'.$URL->link($cur_info['file_path'].$cur_info['file_name']).'" height="150px" class="mb-2">';
                $image[] = '</a>';
                $image[] = '</div>';

                $output[] = implode("\n\t\t\t", $image);
            }
            $output[] = '</div>';

            return implode("\n\t\t\t", $output);
        }
    }

	function ajaxImages($table_name, $table_id)
	{
        global $DBLayer, $URL;

        if (!$this->access_view_files)
            return;

        $query = array(
            'SELECT'	=> 'f.*',
            'FROM'		=> 'sm_uploader AS f',
            'WHERE'		=> 'f.table_name=\''.$DBLayer->escape($table_name).'\' AND f.table_id='.$table_id
        );
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        $cur_files = array();
        while ($row = $DBLayer->fetch_assoc($result)) {
            $cur_files[] = $row;
        } 
?>
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Uploaded Files</h6>
            </div>
            <div class="card-body">
                <div class="row" id="image_thumbnails_<?=$table_name?>">
<?php
        if (!empty($cur_files))
        {
            foreach ($cur_files as $cur_info)
            {
                $image = [];
                $image[] = '<div class="col-md-auto me-2 mb-2">';

                $image[] = $this->getCurrentFile($cur_info);

                if ($this->access_delete_files)
                    $image[] = '<p><button type="button" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete it?\')?deleteFile(\''.$table_name.'\','.$cur_info['id'].'):\'\';">Delete</button></p>';

                $image[] = '</div>';

                echo implode("\n\t\t\t", $image);
            }
        }
        else
            echo '<div class="alert alert-warning py-1" role="alert">No files uploaded.</div>';
?>
                </div>

                <div id="image_progress_<?=$table_name?>"></div>

<?php if ($this->access_upload_files) : ?>
                <div class="col-md-6 mb-3">
                    <label for="fld_<?=$table_name?>" class="form-label">File upload form</label>
                    <input class="form-control form-control-sm" id="fld_<?=$table_name?>" name="file" type="file" onchange="uploadFile('<?=$table_name?>', <?=$table_id?>)">
                </div>
<?php endif; ?>

            </div>
        </div>
<?php

    }

	function uploadImage($table_name, $table_id)
	{
        if ($this->access_upload_files)
        {
?>
        <div id="image_progress" class="mb-2"></div>

        <div class="input-group mb-3">
            <input type="file" name="image" class="form-control" id="fld_image" onchange="uploadImage('<?=$table_name?>', <?=$table_id?>)">
            <label class="input-group-text" for="fld_image"><i class="fas fa-camera"></i></label>
        </div>
<?php
        }
    }

	function getUploadedImagesLink($table_name, $table_id)
	{
        global $DBLayer, $URL;

        if (!$this->access_view_files)
            return;

        $project_ids = [];
        $query = array(
            'SELECT'	=> 'f.*',
            'FROM'		=> 'sm_uploader AS f',
            'WHERE'		=> 'f.table_name=\''.$DBLayer->escape($table_name).'\' AND f.table_id='.$table_id
        );
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        while ($row = $DBLayer->fetch_assoc($result)) {
            $this->uploaded_images[] = $row;
            $project_ids[] = $row['id'];
        }

        if ($this->access_view_files && !empty($project_ids))
        {
            $hash = base64_encode('project='.$table_name.'&ids='.implode(',', $project_ids));
            return '<a href="'.$URL->link('swift_uploader_view', $hash).'" target="_blank"><strong>'.count($this->uploaded_images).'</strong> <span>uploaded images</span></a>';
        }
        else
            return '<strong id="num_uploaded_images">'.count($this->uploaded_images).'</strong> <span>uploaded images</span>';
    }

	function getCurrentFile($cur_info)
	{
        global $URL;

        $image = [];
        if ($cur_info['file_type'] == 'image')
        {
            $image[] = '<div>';
            $image[] = '<div class="mb-0">';
            $image[] = '<a data-fancybox="single" href="'.$URL->link($cur_info['file_path'].$cur_info['file_name']).'" target="_blank">';
            $image[] = '<img src="'.$URL->link($cur_info['file_path'].$cur_info['file_name']).'" style="height:148px;max-width:300px;">';
            $image[] = '</a>';
            $image[] = '</div>';
            $image[] = '<p style="height:50px;overflow:hidden;width:150px;">'.$cur_info['base_name'].'</p>';
            $image[] = '</div>';
        }
/*
        else if ($cur_info['file_type'] == 'media')
        {
        }
*/
        else
        {
            $image[] = '<div>';
            $image[] = '<div class="mb-1">';
            $image[] = '<a href="'.$URL->link($cur_info['file_path'].$cur_info['file_name']).'" target="_blank" class="mb-2">';
            $image[] = $this->getExtIcon($cur_info['file_ext']);
            $image[] = '</a>';
            $image[] = '</div>';
            $image[] = '<p style="width:150px;height:50px;overflow:hidden;">'.$cur_info['base_name'].'</p>';
            $image[] = '</div>';
        }

        return implode("\n\t\t\t", $image);
    }

	function addJS()
	{
        global $URL;

?>
<script>
function showToastMessage()
{
    const toastLiveExample = document.getElementById('liveToast');
    const toast = new bootstrap.Toast(toastLiveExample);
    toast.show();
}
function uploadImage(table,id)
{
    $('#image_progress').empty().html('<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="width: 25%"></div></div>');

	var fd = new FormData();
	fd.append('csrf_token', "<?php echo generate_form_token($URL->link('swift_uploader_ajax_upload_image')) ?>");
    fd.append('image', $('#fld_image')[0].files[0]);
    fd.append('table', table);
	fd.append('id', id);
	jQuery.ajax({
		url: "<?php echo $URL->link('swift_uploader_ajax_upload_image') ?>",
		type: 'POST',
		dataType: 'json',
		processData: false,
		contentType: false,
		data: fd,
		success: function(re){
            $("#toast_container").empty().html(re.toast_container);
            showToastMessage();
            $('#fld_image').val('');
            $('#image_progress').empty().html('');
            $("#num_uploaded_images").empty().html(re.num_images);
            $("#image_viewer_link").empty().html(re.image_viewer_link);
		},
		error: function(re){
            var msg = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
			msg += '<div class="toast-header toast-danger"><strong class="me-auto">Error</strong></div>';
			msg += '<div class="toast-body toast-danger">Failed to uploade image.</div>';
			msg += '</div>';
			$("#toast_container").empty().html(msg);
            showToastMessage();
            $('#fld_image').val('');
            $('#image_progress_'+table).empty().html('');
		}
	});
}
function uploadFile(table,id)
{
    $('#image_progress_'+table).empty().html('<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div></div>');

	var fd = new FormData();
	fd.append('csrf_token', "<?php echo generate_form_token($URL->link('swift_uploader_ajax_upload_file')) ?>");
    fd.append('file', $('#fld_'+table)[0].files[0]);
    fd.append('table', table);
	fd.append('id', id);
	jQuery.ajax({
		url: "<?php echo $URL->link('swift_uploader_ajax_upload_file') ?>",
		type: 'POST',
		dataType: 'json',
		processData: false,
		contentType: false,
		data: fd,
		success: function(re){
            $('#image_thumbnails_'+table).empty().html(re.image_thumbnails);
            $('#image_progress_'+table).empty().html('');
            $('#fld_'+table).val('');
		},
		error: function(re){
			$('.msg-section').empty().html('<div class="alert alert-danger" role="alert">Error: No data received.</div>');
            $('#image_progress_'+table).empty().html('');
            $('#fld_'+table).val('');
		}
	});
}
function deleteFile(table,id)
{
    $('#image_progress_'+table).empty().html('<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div></div>');

	var csrf_token = "<?php echo generate_form_token($URL->link('swift_uploader_ajax_delete_file')) ?>";
	jQuery.ajax({
		url: "<?php echo $URL->link('swift_uploader_ajax_delete_file') ?>",
		type: 'POST',
		dataType: 'json',
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
            //$('#image_progress_'+table).empty().html('<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div></div>');
			$('#image_thumbnails_'+table).empty().html(re.image_thumbnails);
            $('#image_progress_'+table).empty().html('');
            $('#fld_'+table).val('');
		},
		error: function(re){
			$('.msg-section').empty().html('<div class="alert alert-danger" role="alert">Error: No data received.</div>');
            $('#image_progress_'+table).empty().html('');
            $('#fld_'+table).val('');
		}
	});
}
</script>

<?php
    }

    // Display the multiple form for files uploading
	function setForm($options = [])
	{
        global $Loader;

        $input_param = isset($options['input']) ? swift_trim($options['input']) : 'multiple';

        // Include CSS files
        $Loader->add_css($this->css_url.'stylesheet.css?'.$this->time_now, array('type' => 'url', 'media' => 'screen'));

        // Include JS files
        $Loader->add_js($this->js_url.'javascript.js?'.$this->time_now, array('type' => 'url', 'async' => false, 'group' => 100 , 'weight' => 75));
        $Loader->add_js($this->js_url.'jquery.form.min.js?'.$this->time_now, array('type' => 'url', 'async' => false, 'group' => 100 , 'weight' => 75));
?>
        <div id="preview_files"></div>
        <div class="progress-bar"></div>
        <div class="mb-3">
            <label for="form_files" class="form-label">Multiple files input form</label>
            <input class="form-control" id="form_files" name="file[]" type="file" <?php echo $input_param ?> onclick="clearSelectedFiles()">
        </div>
<?php
    }

    // Uplode files by table name and project/table id
	function uploadFiles($table_name, $table_id)
	{
        global $DBLayer, $Config, $User;

        if ($table_id > 0)
        {
            if ($this->structure == 1)
                $file_path = $this->main_dir . $this->cur_year.'/'.$this->cur_month.'/'.$table_name.'/';
            else
                $file_path = $this->main_dir . $table_name.'/'.$this->cur_year.'/'.$this->cur_month.'/'; // Default

            $this->checkPath($table_name);

            if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name']))
            {
                foreach($_FILES['file']['name'] as $key => $value)
                {
                    $file_ext = str_replace('.', '', strtolower(strrchr($_FILES['file']['name'][$key], '.')));
                    $base_filename = basename($_FILES['file']['name'][$key]);
                    $file_size = isset($_FILES['file']['size'][$key]) ? $_FILES['file']['size'][$key] : 0;
                    $new_filename = date('Ymd_His', $this->time_now).'_'.$table_id.'_'.$key.'.'.$file_ext;
                    
                    $file_type = $this->getFileType($file_ext);

                    if (move_uploaded_file($_FILES['file']['tmp_name'][$key], SITE_ROOT . $file_path . $new_filename))
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
                                '.$this->time_now.',
                                \''.$DBLayer->escape($table_name).'\',
                                '.$table_id.''
                        );
                        $DBLayer->query_build($query) or error(__FILE__, __LINE__);
                    }
                    else
                        $this->errors[] = 'Failed upload file '.$base_filename;
                }
            }
        }
    }

    // Gettig all files of project
	function getProjectFiles($table_name, $table_ids)
	{
        global $DBLayer;

        if (empty($this->cur_project_files))
        {
            $query = array(
                'SELECT'	=> 'f.*',
                'FROM'		=> 'sm_uploader AS f',
            );
    
            if (is_array($table_ids) && !empty($table_ids))
            {
                $query['WHERE'] = 'f.table_name=\''.$table_name.'\' AND f.table_id IN ('.implode(',', $table_ids).')';
                $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
                while ($row = $DBLayer->fetch_assoc($result)) {
                    $this->cur_project_files[] = $row;
                }
                return $this->cur_project_files;
            }
            else if (is_numeric($table_ids))
            {
                $query['WHERE'] = 'f.table_name=\''.$table_name.'\' AND f.table_id='.$table_ids;
                $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
                while ($row = $DBLayer->fetch_assoc($result)) {
                    $this->cur_project_files[] = $row;
                }
                return $this->cur_project_files;
            }
        }
    }

    // Insert this function <div class="row">HERE</div>
	function displayCurProjectImages($table_name, $table_ids)
	{
        global $URL;

        if (empty($this->cur_project_files))
            $this->getProjectFiles($table_name, $table_ids);

        if (!empty($this->cur_project_files))
        {
            $output = [];
            foreach($this->cur_project_files as $cur_info)
            {
                $output[] = '<div class="col-md-auto"><a data-fancybox="single" href="'.$URL->link($cur_info['file_path'].$cur_info['file_name']).'" target="_blank"><img class="img-thumbnail max-h-15" src="'.$URL->link($cur_info['file_path'].$cur_info['file_name']).'"/></a></div>';
            }

            return implode("\n", $output);
        }
        else
            return '<div class="alert alert-warning" role="alert">No images uploaded.</div>';
    }

	function getCurProjectFiles($project_id)
	{
        global $URL;

        $images = [];
        if (!empty($this->cur_project_files))
        {
            foreach($this->cur_project_files as $file_info)
            {
                if ($project_id == $file_info['table_id'])
                {
                    if ($file_info['file_type'] == 'image')
                        $images[] = '<a data-fancybox="single" href="'.$URL->link($file_info['file_path'].$file_info['file_name']).'" target="_blank"><img style="height:80px;margin-right:5px" src="'.$URL->link($file_info['file_path'].$file_info['file_name']).'"/></a>';
                    else
                        $images[] = '<a href="'.$URL->link($file_info['file_path'].$file_info['file_name']).'" target="_blank"><img style="height:50px;margin-right:5px" src="'.BASE_URL.'/img/doc.png"/></a>';
                }
            }
        }
		
		if (!empty($images))
            return implode("\n", $images);
    }

	function getCurProjectLink($project_id)
	{
        global $URL;

        if (!empty($this->cur_project_files))
        {
            foreach($this->cur_project_files as $file_info)
            {
                if ($project_id == $file_info['table_id'])
                    return $URL->link($file_info['file_path'].$file_info['file_name']);
            }
        }
    }

	function getCurProject($project_id)
	{
        $output = [];
        if (!empty($this->cur_project_files))
        {
            foreach($this->cur_project_files as $file_info)
            {
                if ($project_id == $file_info['table_id'])
                    $output[] = $file_info;
            }
        }
        return $output;
    }

    // Gettig all files of project
    // RENAME !!!
	function displayProjectFiles($table_name, $table_id)
	{
        global $DBLayer, $Loader, $URL;

        // Include JS files
        //$Loader->add_js($this->js_url.'javascript.js?'.$this->time_now, array('type' => 'url', 'async' => false, 'group' => 100 , 'weight' => 75));
        //$Loader->add_js($this->js_url.'jquery.form.min.js?'.$this->time_now, array('type' => 'url', 'async' => false, 'group' => 100 , 'weight' => 75));

        // Include CSS files
        //$Loader->add_css($this->css_url.'stylesheet.css?v='.$this->time_now, array('type' => 'url', 'media' => 'screen'));

        $query = array(
            'SELECT'	=> 'file_path, file_name',
            'FROM'		=> 'sm_uploader',
            'WHERE'		=> 'table_name=\''.$table_name.'\' AND table_id='.$table_id
        );
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        $cur_files = array();
        while ($row = $DBLayer->fetch_assoc($result)) {
            $cur_files[] = $row;
        } 

        if (!empty($cur_files))
        {
            foreach ($cur_files as $cur_file)
            {
                $image = [];
                $image[] = '<a data-fancybox="single" href="'.$URL->link($cur_file['file_path'].$cur_file['file_name']).'" target="_blank">';
                $image[] = '<img src="'.$URL->link($cur_file['file_path'].$cur_file['file_name']).'" height="150px" class="me-2 mb-2 border border-2">';
                $image[] = '</a>';

                echo implode("\n\t\t\t", $image);
            }

        }
        else
            echo '<div class="alert alert-warning" role="alert">No images uploaded.</div>';

    }

	function displayProjectImages($table_name, $table_id)
	{
        global $DBLayer, $Loader, $URL;

        if (empty($this->cur_project_images))
        {
            $query = array(
                'SELECT'	=> 'f.*',
                'FROM'		=> 'sm_uploader AS f',
                'WHERE'		=> 'f.table_name=\''.$DBLayer->escape($table_name).'\' AND f.table_id='.$table_id
            );
            $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
            while ($row = $DBLayer->fetch_assoc($result))
            {
                if ($row['file_type'] == 'image')
                    $this->cur_project_images[] = $row;
            } 

?>
<style>
 .cur-img img {height: 150px;}
</style>
        <div class="uploaded-images">
<?php
foreach($this->cur_project_images as $cur_file)
{
    $cur_link = BASE_URL.'/'.$cur_file['file_path'].'/'.$cur_file['file_name'];
    
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
			<div class="ct-box info-box">
				<p>You don't have any uploaded images associated with this project yet.</p>
			</div>
<?php
}
?>
		</div>
<?php
    }

	function getFileType($file_ext)
	{
        if (in_array($file_ext, $this->allowed_images))
            $file_type = 'image';
        else if (in_array($file_ext, $this->allowed_media))
            $file_type = 'media';
        else
            $file_type = 'file';

        return $file_type;
    }

	function checkAllowed($form = '')
	{
        global $Core;

        $ext = [];
        $result = true;
        $form = ($form != '') ? $form : $_FILES['file']['name'];

        if (!empty($form))
        {
            foreach($form as $key => $value)
            {
                $file_ext = str_replace('.', '', strtolower(strrchr($form[$key], '.')));
                
                if (in_array($file_ext, $this->allowed_images))
                    $ext[] = $file_ext;
                else if (in_array($file_ext, $this->allowed_media))
                    $ext[] = $file_ext;
                else if (in_array($file_ext, $this->allowed_files))
                    $ext[] = $file_ext;
                else if ($file_ext != '')
                {
                    $this->errors = 'Not allowed type of file: '.$file_ext;
                    $result = false;
                }
            }
        }

        return $result;
    }
    
	function setOwnPath($path = '')
	{
        $dirs = ($path != '' ? explode('/', $path) : [0 => $this->main_dir, 1 => 'others']);

        if (!empty($dirs))
        {
            $checked_path = SITE_ROOT;
            foreach($dirs as $key => $value)
            {
                if ($value != '')
                {
                    $checked_path .= $value.'/';
                    if (!file_exists($checked_path.'index.html')) 
                        $this->makePath($checked_path);
                }
            }
        }

        return file_exists($checked_path.'index.html') ? true : false;
    }

	function checkPath($project = 'others')
	{
		$path1 = SITE_ROOT . $this->main_dir.'/';

        if ($this->structure == 1)
        {
            $path2 = SITE_ROOT . $this->main_dir . $this->cur_year.'/';
            $path3 = SITE_ROOT . $this->main_dir . $this->cur_year.'/'.$this->cur_month.'/';
            $path4 = SITE_ROOT . $this->main_dir . $this->cur_year.'/'.$this->cur_month.'/'.$project.'/';

            $this->cur_path = $this->main_dir . $this->cur_year.'/'.$this->cur_month.'/'.$project.'/';
        }
        else
        {
            $path2 = SITE_ROOT . $this->main_dir . $project.'/';
            $path3 = SITE_ROOT . $this->main_dir . $project .'/'. $this->cur_year.'/';
            $path4 = SITE_ROOT . $this->main_dir . $project .'/'. $this->cur_year.'/'.$this->cur_month.'/';

            $this->cur_path = $this->main_dir . $project .'/'. $this->cur_year.'/'.$this->cur_month.'/';
        }

        //  /uploads/
		if (!file_exists($path1.'index.html')) 
		{
			$this->makePath($path1);
			$this->makePath($path2);
            $this->makePath($path3);
            $this->makePath($path4);
		}
		else
		{
            // Project_name OR Year
			if (!file_exists($path2.'index.html')) 
			{
                $this->makePath($path2);
                $this->makePath($path3);
                $this->makePath($path4);
			}
			else
			{
                // Year OR Month
				if (!file_exists($path3.'index.html')) 
				{
                    $this->makePath($path3);
                    $this->makePath($path4);
				}
				else
				{
                    // Month OR Project
					if (!file_exists($path4.'index.html'))
                    {
                        $this->makePath($path4);
                    }
				}
			}
		}

        return $this->cur_path;
	}
 
	function makePath($file_path)
	{
        $file = '/index.html';
		$content = '';
		$rights = 0777;

        if (@mkdir($file_path, $rights, true))
            @chmod($file_path, $rights);

        file_put_contents($file_path . $file, $content);   
	}

    function getExtIcon($ext = 'doc')
    {
        $extensions = [
            // Documents
            'txt' => '<i class="fas fa-file-alt fa-9x text-secondary"></i>',
            'pdf' => '<i class="fas fa-file-pdf text-danger fa-9x"></i>',
            'doc' => '<i class="fas fa-file-word fa-9x text-primary"></i>',
            'docx' => '<i class="fas fa-file-word fa-9x text-primary"></i>',
            'xls' => '<i class="fas fa-file-alt fa-9x text-success"></i>',
            'xlsx' => '<i class="fas fa-file-alt fa-9x text-success"></i>',
            // Media files
            'mp4' => '<i class="fas fa-file-video fa-9x text-secondary"></i>',
            'mpg' => '<i class="fas fa-file-video fa-9x text-secondary"></i>',
            'mpeg' => '<i class="fas fa-file-video fa-9x text-secondary"></i>',
            'avi' => '<i class="fas fa-file-video fa-9x text-secondary"></i>',
            'wmv' => '<i class="fas fa-file-video fa-9x text-secondary"></i>',
            '3gp' => '<i class="fas fa-file-video fa-9x text-secondary"></i>',
            // Audio Files
            'mp3' => '<i class="fas fa-file-audio fa-9x text-secondary"></i>',
            'aac' => '<i class="fas fa-file-audio fa-9x text-secondary"></i>',
            'ogg' => '<i class="fas fa-file-audio fa-9x text-secondary"></i>',
            'm4a' => '<i class="fas fa-file-audio fa-9x text-secondary"></i>',
            'wma' => '<i class="fas fa-file-audio fa-9x text-secondary"></i>',
        ];

        return isset($extensions[$ext]) ? $extensions[$ext] : $extensions['doc'];
    }

    function getMime($file)
    {
        $x = explode('.', $file);
        $extension = end($x);
        
        $mimes = array(
            'csv' => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain'),
            'exe' => array('application/octet-stream', 'application/x-msdownload'),
            'psd' => array('application/x-photoshop', 'image/vnd.adobe.photoshop'),
            'pdf' => array('application/pdf', 'application/force-download', 'application/x-download', 'binary/octet-stream'),
            'mif' => 'application/vnd.mif',
            'xls' => array('application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword'),
            'ppt' => array('application/powerpoint', 'application/vnd.ms-powerpoint'),
            'pptx' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/x-zip', 'application/zip'),
            'wbxml' => 'application/wbxml',
            'gz' => 'application/x-gzip',
            'gzip' => 'application/x-gzip',
            'php' => array('application/x-httpd-php', 'application/php', 'application/x-php', 'text/php', 'text/x-php', 'application/x-httpd-php-source'),
            'php4' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'phps' => 'application/x-httpd-php-source',
            'js' => array('application/x-javascript', 'text/plain'),
            'swf' => 'application/x-shockwave-flash',
            'sit' => 'application/x-stuffit',
            'tar' => 'application/x-tar',
            'tgz' => array('application/x-tar', 'application/x-gzip-compressed'),
            'xhtml' => 'application/xhtml+xml',
            'xht' => 'application/xhtml+xml',
            'zip' => array('application/zip', 'application/x-zip', 'application/x-zip-compressed',  'application/octet-stream', 'application/x-compress', 'application/x-compressed', 'multipart/x-zip'),
            'rar' => array('application/rar', 'application/x-rar', 'application/x-rar-compressed', 'application/octet-stream', 'application/x-compress', 'application/x-compressed', 'multipart/x-rar'),
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mpga' => 'audio/mpeg',
            'mp2' => 'audio/mpeg',
            'mp3' => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
            'aif' => array('audio/x-aiff', 'audio/aiff'),
            'aiff' => array('audio/x-aiff', 'audio/aiff'),
            'aifc' => 'audio/x-aiff',
            'ram' => 'audio/x-pn-realaudio',
            'rm' => 'audio/x-pn-realaudio',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'ra' => 'audio/x-realaudio',
            'rv' => 'video/vnd.rn-realvideo',
            'wav' => array('audio/x-wav', 'audio/wave', 'audio/wav'),
            'bmp' => array('image/bmp', 'image/x-windows-bmp'),
            'gif' => 'image/gif',
            'jpeg' => array('image/jpeg', 'image/pjpeg'),
            'jpg' => array('image/jpeg', 'image/pjpeg'),
            'jpe' => array('image/jpeg', 'image/pjpeg'),
            'png' => array('image/png', 'image/x-png'),
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'css' => array('text/css', 'text/plain'),
            'html' => array('text/html', 'text/plain'),
            'htm' => array('text/html', 'text/plain'),
            'shtml' => array('text/html', 'text/plain'),
            'txt' => 'text/plain',
            'text' => 'text/plain',
            'log' => array('text/plain', 'text/x-log'),
            'rtx' => 'text/richtext',
            'rtf' => 'text/rtf',
            'xml' => array('application/xml', 'text/xml', 'text/plain'),
            'xsl' => array('application/xml', 'text/xsl', 'text/xml'),
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'avi' => array('video/x-msvideo', 'video/msvideo', 'video/avi', 'application/x-troff-msvideo'),
            'movie' => 'video/x-sgi-movie',
            'doc' => array('application/msword', 'application/vnd.ms-office'),
            'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'application/x-zip'),
            'dot' => array('application/msword', 'application/vnd.ms-office'),
            'dotx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword'),
            'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/vnd.ms-excel', 'application/msword', 'application/x-zip'),
            'word' => array('application/msword', 'application/octet-stream'),
            'xl' => 'application/excel',
            'json' => array('application/json', 'text/json'),
            '3g2' => 'video/3gpp2',
            '3gp' => 'video/3gp',
            'mp4' => 'video/mp4',
            'm4a' => 'audio/x-m4a',
            'f4v' => 'video/mp4',
            'webm' => 'video/webm',
            'aac' => 'audio/x-acc',
            'm4u' => 'application/vnd.mpegurl',
            'm3u' => 'text/plain',
            'xspf' => 'application/xspf+xml',
            'vlc' => 'application/videolan',
            'wmv' => array('video/x-ms-wmv', 'video/x-ms-asf'),
            'au' => 'audio/x-au',
            'ac3' => 'audio/ac3',
            'flac' => 'audio/x-flac',
            'ogg' => 'audio/ogg',
            'kmz' => array('application/vnd.google-earth.kmz', 'application/zip', 'application/x-zip'),
            'kml' => array('application/vnd.google-earth.kml+xml', 'application/xml', 'text/xml'),
            'ics' => 'text/calendar',
            'zsh' => 'text/x-scriptzsh',
            '7zip' => array('application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'),
            'cdr' => array('application/cdr', 'application/coreldraw', 'application/x-cdr', 'application/x-coreldraw', 'image/cdr', 'image/x-cdr', 'zz-application/zz-winassoc-cdr'),
            'wma' => array('audio/x-ms-wma', 'video/x-ms-asf'),
            'jar' => array('application/java-archive', 'application/x-java-application', 'application/x-jar', 'application/x-compressed'),
            'ico' => array('image/x-ico', 'image/x-icon'),
        );
        
        if ( ! isset($mimes[$extension])) {
            $mime = 'application/octet-stream';
        } else {
            $mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
        }
        
        return $mime;
    }

    function getErrors()
    {
        return $this->errors;
    }
}
