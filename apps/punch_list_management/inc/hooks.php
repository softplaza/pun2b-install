<?php

if (!defined('DB_CONFIG')) die();

function punch_list_management_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'punch_list_management';

    // Management
    $urls['punch_list_painter_locations'] = 'apps/'.$app_id.'/painter_locations.php?action=$1&id=$2';
    $urls['punch_list_painter_materials'] = 'apps/'.$app_id.'/painter_materials.php?action=$1&id=$2';

    $urls['punch_list_management_locations'] = 'apps/'.$app_id.'/locations.php?action=$1&id=$2';
    $urls['punch_list_management_moisture'] = 'apps/'.$app_id.'/moisture.php?id=$1';
    $urls['punch_list_management_materials'] = 'apps/'.$app_id.'/materials.php?action=$1&id=$2';
    $urls['punch_list_management_forms'] = 'apps/'.$app_id.'/forms.php';

    // AJAX
    $urls['punch_list_management_ajax_get_location_items'] = 'apps/'.$app_id.'/ajax/get_location_items.php';
    $urls['punch_list_management_ajax_get_equipments'] = 'apps/'.$app_id.'/ajax/get_equipments.php';
    $urls['punch_list_management_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['punch_list_management_ajax_update_moisture_item'] = 'apps/'.$app_id.'/ajax/update_moisture_item.php';
    $urls['punch_list_management_ajax_update_maint_location'] = 'apps/'.$app_id.'/ajax/update_maint_location.php';
    $urls['punch_list_management_ajax_update_permissions'] = 'apps/'.$app_id.'/ajax/update_permissions.php';

    // Technician Form
    $urls['punch_list_management_maintenance_request'] = 'apps/'.$app_id.'/maintenance_request.php?id=$1&hash=$2';
    $urls['punch_list_management_painter_request'] = 'apps/'.$app_id.'/painter_request.php?id=$1&hash=$2';

    $urls['punch_list_management_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function punch_list_management_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->checkAccess('punch_list_management'))
    {
        if ($User->checkAccess('punch_list_management', 9))
            $SwiftMenu->addItem(['title' => 'Punch List', 'link' => '#', 'id' => 'punch_list_management', 'icon' => '<i class="fas fa-tasks"></i>']);

        if ($User->checkAccess('punch_list_management', 2))
            $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('punch_list_management_forms'), 'id' => 'punch_list_management_forms', 'parent_id' => 'punch_list_management']);

        // Locations and Display Positions
        if ($User->checkAccess('punch_list_management', 3))
        {
            $SwiftMenu->addItem(['title' => 'Maintenance Locations', 'link' => $URL->link('punch_list_management_locations', ['list', 0]), 'id' => 'punch_list_management_locations', 'parent_id' => 'punch_list_management']);
            $SwiftMenu->addItem(['title' => 'Maintenance Positions', 'link' => $URL->link('punch_list_management_locations', ['positions', 0]), 'id' => 'punch_list_management_positions', 'parent_id' => 'punch_list_management']);
        }

        // Maintenance Check List
        if ($User->checkAccess('punch_list_management', 4))
            $SwiftMenu->addItem(['title' => 'Maintenance Check List', 'link' => $URL->link('punch_list_management_moisture', 0), 'id' => 'punch_list_management_moisture', 'parent_id' => 'punch_list_management']);
        
        // Maintenance Used Materials
        if ($User->checkAccess('punch_list_management', 5))
            $SwiftMenu->addItem(['title' => 'Maintenance Materials', 'link' => $URL->link('punch_list_management_materials', ['list', 0]), 'id' => 'punch_list_management_moisture', 'parent_id' => 'punch_list_management']);

        // Painter Locations
        if ($User->checkAccess('punch_list_management', 6))
            $SwiftMenu->addItem(['title' => 'Painter Locations', 'link' => $URL->link('punch_list_painter_locations'), 'id' => 'punch_list_painter_locations', 'parent_id' => 'punch_list_management']);

        // Painter Materials
        if ($User->checkAccess('punch_list_management', 7))
            $SwiftMenu->addItem(['title' => 'Painter Materials', 'link' => $URL->link('punch_list_painter_materials'), 'id' => 'punch_list_painter_materials', 'parent_id' => 'punch_list_management']);

        // Settings
        if ($User->checkAccess('punch_list_management', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('punch_list_management_settings'), 'id' => 'punch_list_management_settings', 'parent_id' => 'punch_list_management']);

        //if ($User->checkAccess('punch_list_management', 2))
        //    $SwiftMenu->addItem(['title' => 'Apartment Punch List', 'link' => $URL->link('punch_list_management_forms'), 'id' => 'punch_list_management_forms', 'parent_id' => 'punch_list_management']);

        if ($User->checkAccess('punch_list_management', 8))
        {
            $SwiftMenu->addItem(['title' => 'Apartment Punch List', 'link' => '#', 'id' => 'punch_list_forms', 'parent_id' => 'hca_fs', 'level' => 4]);

            if ($User->checkAccess('punch_list_management', 2))
                $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('punch_list_management_forms'), 'id' => 'punch_list_management_forms', 'parent_id' => 'punch_list_forms']);

            if ($User->checkAccess('hca_fs', 4))
                $SwiftMenu->addItem(['title' => '+ Maintenance Request', 'link' => $URL->link('punch_list_management_maintenance_request', [0, '']), 'id' => 'punch_list_management_maintenance_request', 'parent_id' => 'punch_list_forms']);

            if ($User->checkAccess('hca_fs', 5))
                $SwiftMenu->addItem(['title' => '+ Painter Request', 'link' => $URL->link('punch_list_management_painter_request', [0, '']), 'id' => 'punch_list_management_forms', 'parent_id' => 'punch_list_forms']);
        }
    }
}

class HcaPunchListHooks
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
            //1 => 'Settings',
            2 => 'Report',
            3 => 'Maintenance Locations',
            4 => 'Maintenance Check List',
            5 => 'Maintenance Materials',
            6 => 'Painter Locations',
            7 => 'Painter Materials',
            8 => 'Create Painter Punch List',
            9 => 'Management',
            10 => 'Delete Punch List',

            20 => 'Settings',
        ];

        if (check_app_access($access_info, 'punch_list_management'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title">Punch List</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'punch_list_management'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaPunchListHooks', 'ProfileAboutNewAccess']);