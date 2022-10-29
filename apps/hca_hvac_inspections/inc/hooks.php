<?php

if (!defined('DB_CONFIG')) die();

function hca_hvac_inspections_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'hca_hvac_inspections';
    $urls = [];

    $urls['hca_hvac_inspections_checklist'] = 'apps/'.$app_id.'/checklist.php?id=$1';
    $urls['hca_hvac_inspections_work_order'] = 'apps/'.$app_id.'/work_order.php?id=$1';
    $urls['hca_hvac_inspections_buildings'] = 'apps/'.$app_id.'/buildings.php?id=$1';
    $urls['hca_hvac_inspections_inspections'] = 'apps/'.$app_id.'/inspections.php?id=$1';
    $urls['hca_hvac_inspections_alarms'] = 'apps/'.$app_id.'/alarms.php';

    $urls['hca_hvac_inspections_appendixb'] = 'apps/'.$app_id.'/appendixb.php?id=$1';
    $urls['hca_hvac_inspections_files'] = 'apps/'.$app_id.'/files.php?id=$1';
    $urls['hca_hvac_inspections_summary_report'] = 'apps/'.$app_id.'/summary_report.php?$1';
    $urls['hca_hvac_inspections_print'] = 'apps/'.$app_id.'/print.php';

    $urls['hca_hvac_inspections_water_pressure'] = 'apps/'.$app_id.'/water_pressure.php?id=$1';
    $urls['hca_hvac_inspections_water_pressure_report'] = 'apps/'.$app_id.'/water_pressure_report.php?id=$1';

    $urls['hca_hvac_inspections_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_hvac_inspections_ajax_get_checklist_items'] = 'apps/'.$app_id.'/ajax/get_checklist_items.php';
    $urls['hca_hvac_inspections_ajax_edit_checklist_item'] = 'apps/'.$app_id.'/ajax/edit_checklist_item.php';
    $urls['hca_hvac_inspections_ajax_edit_work_order_item'] = 'apps/'.$app_id.'/ajax/edit_work_order_item.php';
    $urls['hca_hvac_inspections_ajax_get_property_info'] = 'apps/'.$app_id.'/ajax/get_property_info.php';
    //$urls['hca_hvac_inspections_ajax_update_checklist'] = 'apps/'.$app_id.'/ajax/update_checklist.php';
    $urls['hca_hvac_inspections_ajax_reassign_project'] = 'apps/'.$app_id.'/ajax/reassign_project.php';
    $urls['hca_hvac_inspections_ajax_get_water_pressure'] = 'apps/'.$app_id.'/ajax/get_water_pressure.php';

    $urls['hca_hvac_inspections_ajax_get_uploaded_images'] = 'apps/'.$app_id.'/ajax/get_uploaded_images.php';
    $urls['hca_hvac_inspections_ajax_upload_files'] = 'apps/'.$app_id.'/ajax/upload_files.php';

    //Management
    $urls['hca_hvac_inspections_items'] = 'apps/'.$app_id.'/items.php?id=$1';
    $urls['hca_hvac_inspections_filters'] = 'apps/'.$app_id.'/filters.php?id=$1';
    $urls['hca_hvac_inspections_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_hvac_inspections_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('hca_hvac_inspections'))
    {
        // Main item of Menu
        $SwiftMenu->addItem(['title' => 'HVAC Inspections', 'link' => '#', 'id' => 'hca_hvac_inspections', 'parent_id' => 'hca_ui', 'level' => 10]);

        if ($User->checkAccess('hca_hvac_inspections', 1))
            $SwiftMenu->addItem(['title' => '+ New inspection', 'link' => $URL->link('hca_hvac_inspections_checklist', 0), 'id' => 'hca_hvac_inspections_checklist', 'parent_id' => 'hca_hvac_inspections', 'level' => 1]);

        if ($User->checkAccess('hca_hvac_inspections', 4))
            $SwiftMenu->addItem(['title' => 'Inspections', 'link' => $URL->link('hca_hvac_inspections_inspections', ''), 'id' => 'hca_hvac_inspections_inspections', 'parent_id' => 'hca_hvac_inspections', 'level' => 4]);

        if ($User->checkAccess('hca_hvac_inspections', 5))
            $SwiftMenu->addItem(['title' => 'CO Test Log', 'link' => $URL->link('hca_hvac_inspections_alarms', 0), 'id' => 'hca_hvac_inspections_alarms', 'parent_id' => 'hca_hvac_inspections', 'level' => 5]);

         //if ($User->checkAccess('hca_hvac_inspections', 6))
         //   $SwiftMenu->addItem(['title' => 'Summary Report', 'link' => $URL->link('hca_hvac_inspections_summary_report', ''), 'id' => 'hca_hvac_inspections_summary_report', 'parent_id' => 'hca_hvac_inspections', 'level' => 3]);

        if ($User->checkAccess('hca_hvac_inspections', 20))
        {
            $SwiftMenu->addItem(['title' => 'Items', 'link' => $URL->link('hca_hvac_inspections_items', 0), 'id' => 'hca_hvac_inspections_items', 'parent_id' => 'hca_hvac_inspections', 'level' => 22]);

            $SwiftMenu->addItem(['title' => 'Filters', 'link' => $URL->link('hca_hvac_inspections_filters', 0), 'id' => 'hca_hvac_inspections_filters', 'parent_id' => 'hca_hvac_inspections', 'level' => 23]);

            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_hvac_inspections_settings'), 'id' => 'hca_hvac_inspections_settings', 'parent_id' => 'hca_hvac_inspections', 'level' => 25]);
        }
    }
}

class HcaHVACInspectionsHooks
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
            //3 => 'Add Water Pressure',
            4 => 'List of Work Orders',
            //5 => 'Water Pressure Report',
            6 => 'Summary Report',
            7 => 'Edit Water Pressure',
            8 => 'Edit item in Checklist',
            9 => 'Edit item in itemslist',
        
            11 => 'Edit CheckList',
            12 => 'Edit Work Order',
            13 => 'Delete CheckList',
            14 => 'Delete Work Order',
            15 => 'Reassign projects',
            //16 => 'Delete Water Pressure',
            17 => 'View list of actions',
            18 => 'Upload images',
            19 => 'Delete images',
        ];

        if (check_app_access($access_info, 'hca_hvac_inspections'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">Unit Inspections</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_hvac_inspections'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaHVACInspectionsHooks', 'ProfileAboutNewAccess']);

