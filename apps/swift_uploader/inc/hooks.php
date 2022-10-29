<?php

if (!defined('DB_CONFIG')) die();

function swift_uploader_es_essentials()
{
    require SITE_ROOT.'apps/swift_uploader/class/SwiftUploader.php';
}

function swift_uploader_co_modify_url_scheme()
{
    global $URL;

    $urls = [];

    $urls['swift_uploader'] = 'apps/swift_uploader/';
    $urls['swift_uploader_filelist'] = 'apps/swift_uploader/filelist.php';
    $urls['swift_uploader_view'] = 'apps/swift_uploader/view.php?hash=$1';
    $urls['swift_uploader_viewer'] = 'apps/swift_uploader/viewer.php?type=$1';
    $urls['swift_uploader_settings'] = 'apps/swift_uploader/settings.php';

    $urls['swift_uploader_ajax_upload_file'] = 'apps/swift_uploader/ajax/upload_file.php';
    $urls['swift_uploader_ajax_delete_file'] = 'apps/swift_uploader/ajax/delete_file.php';

    $URL->add_urls($urls);
}

function swift_uploader_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->checkAccess('swift_uploader'))
    {
        $SwiftMenu->addItem(['title' => 'Uploader', 'link' => $URL->link('swift_uploader_viewer', 'image'), 'id' => 'swift_uploader', 'icon' => '<i class="fas fa-cloud-upload-alt"></i>']);

        if ($User->checkAccess('swift_uploader', 1))
            $SwiftMenu->addItem(['title' => 'Filelist', 'link' => $URL->link('swift_uploader_filelist'), 'id' => 'swift_uploader_filelist', 'parent_id' => 'swift_uploader']);

        if ($User->checkAccess('swift_uploader', 2))
            $SwiftMenu->addItem(['title' => 'Pictures', 'link' => $URL->link('swift_uploader_viewer', 'image'), 'id' => 'swift_uploader_images', 'parent_id' => 'swift_uploader']);

        if ($User->checkAccess('swift_uploader', 3))
            $SwiftMenu->addItem(['title' => 'Media files', 'link' => $URL->link('swift_uploader_viewer', 'media'), 'id' => 'swift_uploader_media', 'parent_id' => 'swift_uploader']);

        if ($User->checkAccess('swift_uploader', 4))
            $SwiftMenu->addItem(['title' => 'Documents', 'link' => $URL->link('swift_uploader_viewer', 'file'), 'id' => 'swift_uploader_files', 'parent_id' => 'swift_uploader']);

        if ($User->checkAccess('swift_uploader', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('swift_uploader_settings'), 'id' => 'swift_uploader_settings', 'parent_id' => 'swift_uploader']);
    }
}

function swift_uploader_hd_head()
{
    global $Loader;
    
    $swift_uploader_hd_pages = array('hca_5840_new_project', 'hca_fs_new_request', 'hca_vcr_new_project', 'hca_vcr_manage_files', 'hca_vcr_turn_over_new', 'sm_special_projects_manage_files', 'hca_sp_new', 'hca_turn_over_new_inspection');
    if (in_array(PAGE_ID, $swift_uploader_hd_pages))
        $Loader->add_css(BASE_URL.'/apps/swift_uploader/css/stylesheet.css?v='.time(), array('type' => 'url', 'media' => 'screen'));
}

class SwiftUploaderHooks
{
    private static $singleton;

    public static function getInstance(){
        return self::$singleton = new self;
    }

    public static function singletonMethod(){
        return self::getInstance();
    }

    public function ProfileAboutNewAccess()
    {
        global $access_info;

        $access_options = [
            1 => 'FileList',
            2 => 'Pictures',
            3 => 'Media files',
            4 => 'Documents',
        
            11 => 'Manage Files',
        
            20 => 'Settings'
        ];

        if (check_app_access($access_info, 'swift_uploader'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">File management</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'swift_uploader'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['SwiftUploaderHooks', 'ProfileAboutNewAccess']);

