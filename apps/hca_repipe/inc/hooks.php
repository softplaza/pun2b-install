<?php

if (!defined('DB_CONFIG')) die();

function hca_repipe_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'hca_repipe';
    $urls = [];
    $urls['hca_repipe_project'] = 'apps/'.$app_id.'/project.php?id=$1';
    $urls['hca_repipe_projects'] = 'apps/'.$app_id.'/projects.php';

    $urls['hca_repipe_ajax_get_buildings'] = 'apps/'.$app_id.'/ajax/get_buildings.php';
    $urls['hca_repipe_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_repipe_ajax_get_followup'] = 'apps/'.$app_id.'/ajax/get_followup.php';

    $urls['hca_repipe_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_repipe_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('hca_repipe'))
    {
        if ($User->checkAccess('hca_repipe', 4))
            $SwiftMenu->addItem(['title' => 'Re-Pipe Projects', 'id' => 'hca_repipe', 'parent_id' => 'hca_mi', 'icon' => '<i class="fas fa-check-double"></i>', 'level' => 19]);

        if ($User->checkAccess('hca_repipe', 1))
            $SwiftMenu->addItem(['title' => '+ New Re-Pipe', 'link' => $URL->link('hca_repipe_project', 0), 'id' => 'hca_repipe_project', 'parent_id' => 'hca_repipe', 'level' => 1]);

        $SwiftMenu->addItem(['title' => 'Re-Pipe Project List', 'link' => $URL->link('hca_repipe_projects'), 'id' => 'hca_repipe_projects', 'parent_id' => 'hca_repipe', 'level' => 2]);

        if ($User->checkAccess('hca_repipe', 20))
            $SwiftMenu->addItem(['title' => 'Re-Pipe Settings', 'link' => $URL->link('hca_repipe_settings'), 'id' => 'hca_repipe_settings', 'parent_id' => 'hca_repipe', 'level' => 25]);
    }
}

function hca_repipe_HcaVendorsDepartmentsTableHead()
{
    global $URL;
    echo '<th>Re-Pipe <a href="'.$URL->link('sm_vendors_edit_project', 'hca_repipe').'"><i class="fas fa-edit"></i></a></th>';
}

function hca_repipe_HcaVendorsDepartmentsTableBody()
{
    global $cur_info;

    if ($cur_info['hca_repipe'] == 1)
        echo '<td><span class="badge bg-success ms-1">ON</span></td>';
    else
        echo '<td><span class="badge bg-secondary ms-1">OFF</span></td>';
}

class HcaRePipe
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
            1 => 'New project',
            2 => 'Edit project',
            3 => 'Delete project',
            4 => 'List of projects',
        ];

        if (check_app_access($access_info, 'hca_repipe'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">Re-Pipe Projects</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_repipe'))
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
        $form_data['hca_repipe'] = isset($_POST['hca_repipe']) ? intval($_POST['hca_repipe']) : '0';
    }

    public function HcaVendorsEditPreSumbit()
    {
        global $edit_info;
?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="hca_repipe" value="1" id="fld_hca_repipe" <?php if ($edit_info['hca_repipe'] == '1') echo 'checked' ?>>
                <label class="form-check-label" for="fld_hca_repipe">Re-Pipe Projects</label>
            </div>
<?php
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaRePipe', 'ProfileAboutNewAccess']);

Hook::addAction('HcaVendorsEditUpdateValidation', ['HcaRePipe', 'HcaVendorsEditUpdateValidation']);
Hook::addAction('HcaVendorsEditPreSumbit', ['HcaRePipe', 'HcaVendorsEditPreSumbit']);
