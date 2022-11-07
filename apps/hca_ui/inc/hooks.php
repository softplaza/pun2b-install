<?php

if (!defined('DB_CONFIG')) die();

function hca_ui_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'hca_ui';
    $urls = [];

    $urls['hca_ui_checklist'] = 'apps/'.$app_id.'/checklist.php?id=$1';
    $urls['hca_ui_work_order'] = 'apps/'.$app_id.'/work_order.php?id=$1';
    $urls['hca_ui_buildings'] = 'apps/'.$app_id.'/buildings.php?id=$1';
    $urls['hca_ui_inspections'] = 'apps/'.$app_id.'/inspections.php?id=$1';
    
    $urls['hca_ui_appendixb'] = 'apps/'.$app_id.'/appendixb.php?id=$1';
    $urls['hca_ui_files'] = 'apps/'.$app_id.'/files.php?id=$1';
    $urls['hca_ui_summary_report'] = 'apps/'.$app_id.'/summary_report.php';
    $urls['hca_ui_print'] = 'apps/'.$app_id.'/print.php';

    $urls['hca_ui_water_pressure'] = 'apps/'.$app_id.'/water_pressure.php?id=$1';
    $urls['hca_ui_water_pressure_report'] = 'apps/'.$app_id.'/water_pressure_report.php?id=$1';

    $urls['hca_ui_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_ui_ajax_get_checklist_items'] = 'apps/'.$app_id.'/ajax/get_checklist_items.php';
    $urls['hca_ui_ajax_edit_checklist_item'] = 'apps/'.$app_id.'/ajax/edit_checklist_item.php';
    $urls['hca_ui_ajax_edit_work_order_item'] = 'apps/'.$app_id.'/ajax/edit_work_order_item.php';
    $urls['hca_ui_ajax_get_property_info'] = 'apps/'.$app_id.'/ajax/get_property_info.php';
    //$urls['hca_ui_ajax_update_checklist'] = 'apps/'.$app_id.'/ajax/update_checklist.php';
    $urls['hca_ui_ajax_reassign_project'] = 'apps/'.$app_id.'/ajax/reassign_project.php';
    $urls['hca_ui_ajax_get_water_pressure'] = 'apps/'.$app_id.'/ajax/get_water_pressure.php';

    $urls['hca_ui_ajax_get_uploaded_images'] = 'apps/'.$app_id.'/ajax/get_uploaded_images.php';
    $urls['hca_ui_ajax_upload_files'] = 'apps/'.$app_id.'/ajax/upload_files.php';

    //Management
    $urls['hca_ui_items'] = 'apps/'.$app_id.'/items.php?id=$1';
    $urls['hca_ui_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_ui_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('hca_ui') || $User->checkAccess('hca_unit_inspections') || $User->checkAccess('hca_hvac_inspections'))
        $SwiftMenu->addItem(['title' => 'Property Inspections', 'link' => '#', 'id' => 'hca_ui', 'icon' => '<i class="fas fa-check-double"></i>', 'level' => 20]);

    if ($User->checkAccess('hca_ui'))
    {
        $SwiftMenu->addItem(['title' => 'Plumbing Inspections', 'link' => '#', 'id' => 'hca_pi', 'parent_id' => 'hca_ui', 'level' => 5]);

        if ($User->checkAccess('hca_ui', 1))
            $SwiftMenu->addItem(['title' => '+ New inspection', 'link' => $URL->link('hca_ui_checklist', 0), 'id' => 'hca_ui_checklist', 'parent_id' => 'hca_pi', 'level' => 1]);

        if ($User->checkAccess('hca_ui', 4))
            $SwiftMenu->addItem(['title' => 'Work Orders', 'link' => $URL->link('hca_ui_inspections', 0), 'id' => 'hca_ui_inspections', 'parent_id' => 'hca_pi', 'level' => 3]);

        if ($User->checkAccess('hca_ui', 6))
            $SwiftMenu->addItem(['title' => 'Summary Report', 'link' => $URL->link('hca_ui_summary_report'), 'id' => 'hca_ui_summary_report', 'parent_id' => 'hca_pi', 'level' => 7]);

        // MANAGEMENT
        //$SwiftMenu->addItem(['title' => 'Management', 'link' => '#', 'id' => 'hca_ui_management', 'parent_id' => 'hca_pi', 'level' => 20]);
        if ($User->checkAccess('hca_ui', 20))
            $SwiftMenu->addItem(['title' => 'Items', 'link' => $URL->link('hca_ui_items', 0), 'id' => 'hca_ui_items', 'parent_id' => 'hca_pi', 'level' => 22]);
        if ($User->checkAccess('hca_ui', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_ui_settings'), 'id' => 'hca_ui_settings', 'parent_id' => 'hca_pi', 'level' => 25]);
    }

    // WATER PRESSURE
    if ($User->checkAccess('hca_ui'))
    {
        $SwiftMenu->addItem(['title' => 'Building Water Pressure', 'link' => '#', 'id' => 'hca_ui_bwp', 'parent_id' => 'hca_ui', 'level' => 20]);

        if ($User->checkAccess('hca_ui', 3))
            $SwiftMenu->addItem(['title' => '+ New Water Pressure', 'link' => $URL->link('hca_ui_water_pressure', 0), 'id' => 'hca_ui_water_pressure', 'parent_id' => 'hca_ui_bwp', 'level' => 1]);

        if ($User->checkAccess('hca_ui', 5))
            $SwiftMenu->addItem(['title' => 'Water Pressure Report', 'link' => $URL->link('hca_ui_water_pressure_report', 0), 'id' => 'hca_ui_water_pressure_report', 'parent_id' => 'hca_ui_bwp', 'level' => 5]);
    }
}

class HcaUIHooks
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
            1 => 'New CheckList',
            2 => 'Work Order',
            3 => 'Add Water Pressure',
            4 => 'List of Work Orders',
            5 => 'Water Pressure Report',
            6 => 'Summary Report',
            7 => 'Edit Water Pressure',
            8 => 'Edit item in Checklist',
            9 => 'Edit item in itemslist',
        
            11 => 'Edit CheckList',
            12 => 'Edit Work Order',
            13 => 'Delete CheckList',
            14 => 'Delete Work Order',
            15 => 'Reassign projects',
            16 => 'Delete Water Pressure',
            17 => 'View list of actions',
            18 => 'Upload images',
            19 => 'Delete images',
        ];

        if (check_app_access($access_info, 'hca_ui'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">Unit Inspections</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_ui'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaUIHooks', 'ProfileAboutNewAccess']);

