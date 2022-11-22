<?php

if (!defined('DB_CONFIG')) die();

function hca_projects_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_projects';

    $urls['hca_projects_new'] = 'apps/'.$app_id.'/new_project.php';
    $urls['hca_projects_management'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['hca_projects_list'] = 'apps/'.$app_id.'/projects.php';
    $urls['hca_projects_report'] = 'apps/'.$app_id.'/projects_report.php?section=view';
    $urls['hca_projects_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_projects_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('hca_projects'))
        $SwiftMenu->addItem(['title' => 'Structure Projects', 'link' => $URL->link('hca_projects_list'), 'id' => 'hca_projects', 'parent_id' => 'hca_sb721', 'level' => 20]);

    if ($User->checkAccess('hca_projects', 11))
        $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('hca_projects_new'), 'id' => 'hca_projects_new', 'parent_id' => 'hca_projects']);

    if ($User->checkAccess('hca_projects', 1))
        $SwiftMenu->addItem(['title' => 'Projects', 'link' => $URL->link('hca_projects_list'), 'id' => 'hca_projects_list', 'parent_id' => 'hca_projects']);

    if ($User->checkAccess('hca_projects', 20))
        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_projects_settings'), 'id' => 'hca_projects_settings', 'parent_id' => 'hca_projects']);
}

class HcaProjects
{
    private static $singleton;

    public static function getInstance(){
        return self::$singleton = new self;
    }

    public static function singletonMethod(){
        return self::getInstance();
    }

    public function ProfileAdminAccess()
    {
        global $access_info;

        $access_options = [
            1 => 'List of Projects',
        
            11 => 'Create new projects',
            12 => 'Edit projects',
            13 => 'Upload Files',
            14 => 'Remove projects',
        
            //20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_projects'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">HCA Projects</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_projects'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaProjects', 'ProfileAdminAccess']);
