<?php

if (!defined('DB_CONFIG')) die();

function hca_vendors_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'hca_vendors';

    $urls = [];

    $urls['sm_vendors_new'] = 'apps/'.$app_id.'/new.php';
    $urls['sm_vendors_edit'] = 'apps/'.$app_id.'/edit.php?id=$1';
    $urls['sm_vendors_edit_project'] = 'apps/'.$app_id.'/edit_project.php?pid=$1';

    $urls['sm_vendors_list'] = 'apps/'.$app_id.'/vendors_list.php';
    $urls['sm_vendors_departments'] = 'apps/'.$app_id.'/departments.php';
    $urls['sm_vendors_groups'] = 'apps/'.$app_id.'/groups.php';
    $urls['sm_vendors_schedule'] = 'apps/'.$app_id.'/schedule.php';
    $urls['sm_vendors_calendar'] = 'apps/'.$app_id.'/calendar.php';
    $urls['sm_vendors_settings'] = 'apps/'.$app_id.'/settings.php';

    $urls['sm_vendors_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';

    $URL->add_urls($urls);
}

function hca_vendors_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->checkAccess('hca_vendors'))
    {
        //$SwiftMenu->addItem(['title' => 'Vendors', 'link' => $URL->link('sm_vendors_list'), 'id' => 'sm_vendors', 'icon' => '<i class="fas fa-hard-hat"></i>', 'level' => 15]);
        $SwiftMenu->addItem(['title' => 'Vendors', 'link' => $URL->link('sm_vendors_list'), 'id' => 'sm_vendors', 'icon' => '<i class="icofont-labour" style="font-size: 34px;"></i>', 'level' => 15]);

        if ($User->checkAccess('hca_vendors', 2))
            $SwiftMenu->addItem(['title' => '+ Add Vendor', 'link' =>  $URL->link('sm_vendors_new'), 'id' => 'sm_vendors_new', 'parent_id' => 'sm_vendors']); 

        if ($User->checkAccess('hca_vendors', 3))
            $SwiftMenu->addItem(['title' => 'Vendor List', 'link' =>  $URL->link('sm_vendors_list'), 'id' => 'sm_vendors_list', 'parent_id' => 'sm_vendors']);

        if ($User->checkAccess('hca_vendors', 4))
            $SwiftMenu->addItem(['title' => 'Visability', 'link' =>  $URL->link('sm_vendors_departments'), 'id' => 'sm_vendors_departments', 'parent_id' => 'sm_vendors']);

        if ($User->checkAccess('hca_vendors', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' =>  $URL->link('sm_vendors_settings'), 'id' => 'sm_vendors_settings', 'parent_id' => 'sm_vendors']);

        if ($User->is_admin())
        {
            $SwiftMenu->addItem(['title' => 'Schedule', 'link' =>  $URL->link('sm_vendors_schedule'), 'id' => 'sm_vendors_schedule', 'parent_id' => 'sm_vendors']);
            $SwiftMenu->addItem(['title' => 'Groups', 'link' =>  $URL->link('sm_vendors_groups'), 'id' => 'sm_vendors_groups', 'parent_id' => 'sm_vendors']);
        }
    }
}

class HcaVendorsHooks
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
            2 => 'Add vendor',
            3 => 'Vendor List',
            4 => 'Visability',
            5 => 'Edit vendors',
            6 => 'Remove vendors',

            20 => 'Settings',
        ];

        if (check_app_access($access_info, 'hca_vendors'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title">Vendor Management</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_vendors'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaVendorsHooks', 'ProfileAboutNewAccess']);
