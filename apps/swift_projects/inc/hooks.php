<?php

if (!defined('DB_CONFIG')) die();

function swift_projects_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'swift_projects';

    $urls['swift_projects_new'] = 'apps/'.$app_id.'/new_project.php';
    $urls['swift_projects_management'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['swift_projects_list'] = 'apps/'.$app_id.'/projects.php';
    $urls['swift_projects_report'] = 'apps/'.$app_id.'/projects_report.php?section=view';

    $urls['swift_projects_ajax_get_project_fields'] = 'apps/'.$app_id.'/ajax/get_project_fields.php';

    $urls['swift_projects_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function swift_projects_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('swift_projects'))
        $SwiftMenu->addItem(['title' => 'Development', 'link' => $URL->link('swift_projects_list'), 'id' => 'swift_projects', 'icon' => '<i class="fas fa-laptop-code"></i>', 'level' => 200]);

    if ($User->checkAccess('swift_projects', 11))
        $SwiftMenu->addItem(['title' => '+ New task', 'link' => $URL->link('swift_projects_new'), 'id' => 'swift_projects_new', 'parent_id' => 'swift_projects']);

    if ($User->checkAccess('swift_projects', 1))
        $SwiftMenu->addItem(['title' => 'Task List', 'link' => $URL->link('swift_projects_list'), 'id' => 'swift_projects_list', 'parent_id' => 'swift_projects']);

    if ($User->checkAccess('swift_projects', 20))
        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('swift_projects_settings'), 'id' => 'swift_projects_settings', 'parent_id' => 'swift_projects']);
}
