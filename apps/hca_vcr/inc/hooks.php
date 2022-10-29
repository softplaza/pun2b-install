<?php

if (!defined('DB_CONFIG')) die();

function hca_vcr_es_essentials()
{
    require SITE_ROOT.'apps/hca_vcr/inc/functions.php';
}

function hca_vcr_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_vcr';
    $urls['hca_vcr_new_project'] = 'apps/'.$app_id.'/new_project.php';
    $urls['hca_vcr_projects'] = 'apps/'.$app_id.'/projects.php?section=$1&id=$2';
    $urls['hca_vcr_report'] = 'apps/'.$app_id.'/report.php';
    $urls['hca_vcr_calendar'] = 'apps/'.$app_id.'/calendar.php';
    $urls['hca_vcr_settings'] = 'apps/'.$app_id.'/settings.php';
    
    $urls['hca_vcr_vendor_schedule'] = 'apps/'.$app_id.'/vendor_schedule.php';
    $urls['hca_vcr_weekly_schedule'] = 'apps/'.$app_id.'/weekly_schedule.php?gid=$1';
    $urls['hca_vcr_property_schedule'] = 'apps/'.$app_id.'/property_schedule.php';
    
    $urls['hca_vcr_manage_project'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['hca_vcr_manage_files'] = 'apps/'.$app_id.'/manage_files.php?id=$1';
    $urls['hca_vcr_manage_invoice'] = 'apps/'.$app_id.'/manage_invoice.php?id=$1';
    
    $urls['hca_vcr_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_vcr_ajax_get_available_technician'] = 'apps/'.$app_id.'/ajax/get_available_technician.php';
    
    $urls['hca_vcr_ajax_get_acive_info'] = 'apps/'.$app_id.'/ajax/get_acive_info.php'; // from projects.php
    $urls['hca_vcr_ajax_get_acive_vendors'] = 'apps/'.$app_id.'/ajax/get_acive_vendors.php'; // from projects.php

    $urls['hca_vcr_ajax_update_default_vendor'] = 'apps/'.$app_id.'/ajax/update_default_vendor.php';

    $urls['hca_vcr_ajax_get_maint_list'] = 'apps/'.$app_id.'/ajax/get_maint_list.php';
    $urls['hca_vcr_ajax_get_paint_list'] = 'apps/'.$app_id.'/ajax/get_paint_list.php';
    $urls['hca_vcr_ajax_get_time_slots'] = 'apps/'.$app_id.'/ajax/get_time_slots.php';
    $urls['hca_vcr_ajax_get_paint_time_slots'] = 'apps/'.$app_id.'/ajax/get_paint_time_slots.php';
    $urls['hca_vcr_ajax_get_final_walk_info'] = 'apps/'.$app_id.'/ajax/get_final_walk_info.php';
    
    $urls['hca_vcr_ajax_get_vendor_schedule'] = 'apps/'.$app_id.'/ajax/get_vendor_schedule.php';
    $urls['hca_vcr_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';
    
    $URL->add_urls($urls);
}

function hca_vcr_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    if ($User->checkAccess('hca_vcr'))
    {
        $SwiftMenu->addItem(['title' => 'VCR Projects', 'link' =>  $URL->link('hca_vcr_projects', 0), 'id' => 'hca_vcr', 'icon' => '<i class="fas fa-calculator"></i>', 'level' => 14]);

        if ($User->checkAccess('hca_vcr', 2))
            $SwiftMenu->addItem(['title' => '+ New project', 'link' => $URL->link('hca_vcr_new_project'), 'id' => 'hca_fs_new_request', 'parent_id' => 'hca_vcr', 'level' => 0]);

        if ($User->checkAccess('hca_vcr', 1))
        {
            $SwiftMenu->addItem(['title' => 'Active Projects', 'link' => $URL->link('hca_vcr_projects', ['active', 0]), 'id' => 'hca_vcr_projects_active', 'parent_id' => 'hca_vcr', 'level' => 1]);

            $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_vcr_projects', ['completed', 0]), 'id' => 'hca_vcr_projects_completed', 'parent_id' => 'hca_vcr', 'level' => 2]);

            $SwiftMenu->addItem(['title' => 'On Hold', 'link' => $URL->link('hca_vcr_projects', ['on_hold', 0]), 'id' => 'hca_vcr_projects_on_hold', 'parent_id' => 'hca_vcr', 'level' => 2]);

            if ($User->is_admin())
                $SwiftMenu->addItem(['title' => 'Recycle', 'link' => $URL->link('hca_vcr_projects', ['recycle', 0]), 'id' => 'hca_vcr_projects_recycle', 'parent_id' => 'hca_vcr', 'level' => 2]);
        }

        if ($User->checkAccess('hca_vcr', 5))
            $SwiftMenu->addItem(['title' => 'Vendor Schedule', 'link' => $URL->link('hca_vcr_vendor_schedule'), 'id' => 'hca_vcr_vendor_schedule', 'parent_id' => 'hca_vcr', 'level' => 3]);

        //if ($User->checkAccess('hca_vcr', 6))
        //   $SwiftMenu->addItem(['title' => 'In-House Schedule', 'link' => $URL->link('hca_vcr_weekly_schedule'), 'id' => 'hca_vcr_weekly_schedule', 'parent_id' => 'hca_vcr', 'level' => 4]);

       // if ($User->checkAccess('hca_vcr', 7))
        //    $SwiftMenu->addItem(['title' => 'Property Schedule', 'link' => $URL->link('hca_vcr_property_schedule'), 'id' => 'hca_vcr_property_schedule', 'parent_id' => 'hca_vcr', 'level' => 5]);

        //$SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('hca_vcr_report'), 'id' => 'hca_vcr_report', 'parent_id' => 'hca_vcr', 'level' => 2]);

        //$SwiftMenu->addItem(['title' => 'Calendar', 'link' => $URL->link('hca_vcr_calendar'), 'id' => 'hca_vcr_calendar', 'parent_id' => 'hca_vcr', 'level' => 3]);


        if ($User->checkAccess('hca_vcr', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_vcr_settings'), 'id' => 'hca_vcr_settings', 'parent_id' => 'hca_vcr', 'level' => 20]);
    }
}

function hca_vcr_HcaVendorsDepartmentsTableHead()
{
    global $URL;
    echo '<th>VCR <a href="'.$URL->link('sm_vendors_edit_project', 'hca_vcr').'"><i class="fas fa-edit"></i></a></th>';
}

function hca_vcr_HcaVendorsDepartmentsTableBody()
{
    global $cur_info;

    if ($cur_info['hca_vcr'] == 1)
        echo '<td><span class="badge bg-success ms-1">ON</span></td>';
    else
        echo '<td><span class="badge bg-secondary ms-1">OFF</span></td>';
}

class HcaVCRHooks
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
            1 => 'Project list',
            2 => 'Create projects',
            3 => 'Edit projects',
            4 => 'Delete projects',
            5 => 'Vendor schedule',
            6 => 'In-House schedule',
            7 => 'Property schedule',

            20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_vcr'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">VCR</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_vcr'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaVCRHooks', 'ProfileAboutNewAccess']);
//Hook::addAction('ProfileAboutNewPermissions', ['HcaFacilityHooks', 'ProfileAboutNewPermissions']);
//Hook::addAction('ProfileAboutNewNotifications', ['HcaFacilityHooks', 'ProfileAboutNewNotifications']);

