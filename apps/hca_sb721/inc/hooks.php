<?php

if (!defined('DB_CONFIG')) die();

// $1 - section
function hca_sb721_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_sb721';

    $urls['hca_sb721_new'] = 'apps/'.$app_id.'/new_project.php';

    $urls['hca_sb721_manage_project'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['hca_sb721_manage_files'] = 'apps/'.$app_id.'/manage_files.php?id=$1';
    $urls['hca_sb721_sb_721_form'] = 'apps/'.$app_id.'/sb_721_form.php?id=$1';
    $urls['hca_sb721_projects'] = 'apps/'.$app_id.'/projects.php?section=$1&pid=$2';
    $urls['hca_sb721_documents'] = 'apps/'.$app_id.'/documents.php';
    $urls['hca_sb721_report'] = 'apps/'.$app_id.'/report.php';
    $urls['hca_sb721_chart'] = 'apps/'.$app_id.'/chart.php?pid=$1';

    $urls['hca_sb721_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';
    $urls['hca_sb721_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_sb721_ajax_get_vendor'] = 'apps/'.$app_id.'/ajax/get_vendor.php';

    $urls['hca_sb721_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_sb721_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($User->checkAccess('hca_sb721'))
        $SwiftMenu->addItem(['title' => 'SB-721', 'link' => '#', 'id' => 'hca_sb721', 'icon' => '<i class="fas fa-house-damage"></i>', 'level' => 12]);

    if ($User->checkAccess('hca_sb721', 11))
        $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('hca_sb721_new'), 'id' => 'hca_sb721_new', 'parent_id' => 'hca_sb721', 'level' => 1]);
    
    if ($User->checkAccess('hca_sb721', 1))
    {
        $SwiftMenu->addItem(['title' => 'Active Projects', 'link' => $URL->link('hca_sb721_projects', ['active', 0]), 'id' => 'hca_sb721_projects_active', 'parent_id' => 'hca_sb721', 'level' => 2]);

        $SwiftMenu->addItem(['title' => 'Completed Projects', 'link' => $URL->link('hca_sb721_projects', ['completed', 0]), 'id' => 'hca_sb721_projects_completed', 'parent_id' => 'hca_sb721', 'level' => 3]);

        if ($User->is_admin())
            $SwiftMenu->addItem(['title' => 'Removed Projects', 'link' => $URL->link('hca_sb721_projects', ['removed', 0]), 'id' => 'hca_sb721_projects_removed', 'parent_id' => 'hca_sb721', 'level' => 3]);
    }

    if ($User->checkAccess('hca_sb721', 2))
        $SwiftMenu->addItem(['title' => 'Guidelines', 'link' => $URL->link('hca_sb721_documents'), 'id' => 'hca_sb721_documents', 'parent_id' => 'hca_sb721', 'level' => 6]);

    //if ($User->checkAccess('hca_sb721', 3))
     //   $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('hca_sb721_report'), 'id' => 'hca_sb721_report', 'parent_id' => 'hca_sb721', 'level' => 3]);

    //if ($User->checkAccess('hca_sb721', 4))
        $SwiftMenu->addItem(['title' => 'Chart', 'link' => $URL->link('hca_sb721_chart', 0), 'id' => 'hca_sb721_chart', 'parent_id' => 'hca_sb721', 'level' => 8]);

    //if ($User->checkAccess('hca_sb721', 3))
    //    $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('hca_sb721_report'), 'id' => 'hca_sb721_report', 'parent_id' => 'hca_sb721', 'level' => 5]);

/*
    if ($id > 0)
    {
        $SwiftMenu->addItem(['title' => 'Project Management', 'link' => '#', 'id' => 'hca_sb721_management', 'parent_id' => 'hca_sb721']);

        if ($User->checkAccess('hca_sb721', 12))
            $SwiftMenu->addItem(['title' => 'Edit Project', 'link' => $URL->link('hca_sb721_manage_project', $id), 'id' => 'hca_sb721_manage_project', 'parent_id' => 'hca_sb721_management']);

        if ($User->checkAccess('hca_sb721', 13))
            $SwiftMenu->addItem(['title' => 'Uploaded Files', 'link' => $URL->link('hca_sb721_manage_files', $id), 'id' => 'hca_sb721_manage_files', 'parent_id' => 'hca_sb721_management']);

        if ($User->checkAccess('hca_sb721', 14))
            $SwiftMenu->addItem(['title' => 'SB-721 Checklist', 'link' => $URL->link('hca_sb721_sb_721_form', $id), 'id' => 'hca_sb721_sb_721_form', 'parent_id' => 'hca_sb721_management']);
    }
*/

    if ($User->checkAccess('hca_sb721', 20))
        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_sb721_settings'), 'id' => 'hca_sb721_settings', 'parent_id' => 'hca_sb721', 'level' => 6]);
}

function hca_sb721_HcaVendorsDepartmentsTableHead()
{
    global $URL;
    echo '<th>SB-721 <a href="'.$URL->link('sm_vendors_edit_project', 'hca_sb721').'"><i class="fas fa-edit"></i></a></th>';
}

function hca_sb721_HcaVendorsDepartmentsTableBody()
{
    global $cur_info;

    if ($cur_info['hca_sb721'] == 1)
        echo '<td><span class="badge bg-success ms-1">ON</span></td>';
    else
        echo '<td><span class="badge bg-secondary ms-1">OFF</span></td>';
}

class HcaSB721Hooks
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
            1 => 'List of Projects',
            2 => 'Guidelines',
            3 => 'Report',
            4 => 'Chart',

            11 => 'Create new projects',
            12 => 'Edit projects',
            13 => 'Upload Files',
            14 => 'SB-721 Checklist',
            15 => 'Remove projects',
            16 => 'Show in the list of project managers',
        
            20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_sb721'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">SB-721 inspection</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_sb721'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }

    public function HcaVendorsEditUpdateValidation()
    {
        global $form_data;
        $form_data['hca_sb721'] = isset($_POST['hca_sb721']) ? intval($_POST['hca_sb721']) : '0';
    }

    public function HcaVendorsEditPreSumbit()
    {
        global $edit_info;
?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="hca_sb721" value="1" id="field_hca_sb721" <?php if ($edit_info['hca_sb721'] == '1') echo 'checked' ?>>
                <label class="form-check-label" for="field_hca_sb721">SB-721</label>
            </div>
<?php
    }
}

//Hook::addAction('HookName', ['HcaSB721Hooks', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaSB721Hooks', 'ProfileAboutNewAccess']);

Hook::addAction('HcaVendorsEditUpdateValidation', ['HcaSB721Hooks', 'HcaVendorsEditUpdateValidation']);
Hook::addAction('HcaVendorsEditPreSumbit', ['HcaSB721Hooks', 'HcaVendorsEditPreSumbit']);