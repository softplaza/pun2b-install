<?php

if (!defined('DB_CONFIG')) die();

function hca_trees_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_trees';

    $urls['hca_trees_new_project'] = 'apps/'.$app_id.'/new_project.php';
    $urls['hca_trees_projects'] = 'apps/'.$app_id.'/projects.php?section=$1';
    $urls['hca_trees_manage_project'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['hca_trees_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_trees_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    if ($User->checkAccess('hca_trees'))
        $SwiftMenu->addItem(['title' => 'Trees Projects', 'link' =>  $URL->link('hca_trees_projects', 'active'), 'id' => 'hca_trees', 'icon' => '<i class="fas fa-tree"></i>']);

    if ($User->checkAccess('hca_trees', 2))
        $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('hca_trees_new_project'), 'id' => 'hca_trees_new_project', 'parent_id' => 'hca_trees']);

    if ($User->checkAccess('hca_trees', 3))
        $SwiftMenu->addItem(['title' => 'Projects', 'link' => $URL->link('hca_trees_projects', 'active'), 'id' => 'hca_trees_projects', 'parent_id' => 'hca_trees']);

    $SwiftMenu->addItem(['title' => 'Active', 'link' => $URL->link('hca_trees_projects', 'active'), 'id' => 'hca_trees_projects_active', 'parent_id' => 'hca_trees_projects']);
    $SwiftMenu->addItem(['title' => 'On Hold', 'link' => $URL->link('hca_trees_projects', 'on_hold'), 'id' => 'hca_trees_projects_on_hold', 'parent_id' => 'hca_trees_projects']);
    $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_trees_projects', 'completed'), 'id' => 'hca_trees_projects_completed', 'parent_id' => 'hca_trees_projects']);
    $SwiftMenu->addItem(['title' => 'Recycle', 'link' => $URL->link('hca_trees_projects', 'recycle'), 'id' => 'hca_trees_projects_recycle', 'parent_id' => 'hca_trees_projects']);

    if ($User->checkAccess('hca_trees', 20))
        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_trees_settings'), 'id' => 'sm_pest_control_settings', 'parent_id' => 'hca_trees']);
}

function hca_trees_HcaVendorsDepartmentsTableHead()
{
    global $URL;
    echo '<th>Trees Projects <a href="'.$URL->link('sm_vendors_edit_project', 'hca_trees').'"><i class="fas fa-edit"></i></a></th>';
}

function hca_trees_HcaVendorsDepartmentsTableBody()
{
    global $cur_info;

    if ($cur_info['hca_trees'] == 1)
        echo '<td><span class="badge bg-success ms-1">ON</span></td>';
    else
        echo '<td><span class="badge bg-secondary ms-1">OFF</span></td>';
}

class HcaTreesHooks
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
            //1 => 'Menu',
            2 => 'Create project',
            3 => 'List of Projects',
            4 => 'Edit Projects',
            20 => 'Settings',
        ];

        if (check_app_access($access_info, 'hca_trees'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title">Trees Projects</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_trees'))
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
        $form_data['hca_trees'] = isset($_POST['hca_trees']) ? intval($_POST['hca_trees']) : '0';
    }

    public function HcaVendorsEditPreSumbit()
    {
        global $edit_info;
?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="hca_trees" value="1" id="field_hca_trees" <?php if ($edit_info['hca_trees'] == '1') echo 'checked' ?>>
                <label class="form-check-label" for="field_hca_trees">Trees Projects</label>
            </div>
<?php
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaTreesHooks', 'ProfileAboutNewAccess']);
Hook::addAction('HcaVendorsEditUpdateValidation', ['HcaTreesHooks', 'HcaVendorsEditUpdateValidation']);
Hook::addAction('HcaVendorsEditPreSumbit', ['HcaTreesHooks', 'HcaVendorsEditPreSumbit']);
